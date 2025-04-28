<?php
session_start();
require 'config.php';
header('Content-Type: application/json');

try {
    // Validation
    if (!isset($_SESSION['user']['id'])) {
        throw new Exception("Non authentifié");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $required = ['exam_id', 'student_id'];
    
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Champ manquant : $field");
        }
    }

    $examId = (int)$input['exam_id'];
    $studentId = (int)$input['student_id'];

    // Vérifier l'accès à l'examen
    $stmt = $conn->prepare("SELECT * FROM etudiant_examen 
                          WHERE id_examen = ? AND id_etudiant = ?
                          AND statut = 'en_cours'");
    $stmt->execute([$examId, $studentId]);
    if (!$stmt->fetch()) {
        throw new Exception("Examen non disponible");
    }

    // Calcul du score
    $score = 0;
    $stmt = $conn->prepare("SELECT q.id_q, q.note_max, q.bonne_reponse, re.reponse
                          FROM reponses_etudiants2 re
                          JOIN questions3 q ON re.question_id = q.id_q
                          WHERE re.examen_id = ? AND re.etudiant_id = ?");
    $stmt->execute([$examId, $studentId]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['type'] === 'qcm') {
            $reponse = json_decode($row['reponse'], true);
            $bonneReponse = json_decode($row['bonne_reponse'], true);
            sort($reponse);
            sort($bonneReponse);
            if ($reponse === $bonneReponse) $score += $row['note_max'];
        } else {
            if (trim(strtolower($row['reponse'])) === trim(strtolower($row['bonne_reponse']))) {
                $score += $row['note_max'];
            }
        }
    }

    // Mise à jour de la base
    $conn->beginTransaction();

    // Marquer l'examen comme terminé
    $stmt = $conn->prepare("UPDATE etudiant_examen 
                          SET statut = 'termine', date_soumission = NOW()
                          WHERE id_examen = ? AND id_etudiant = ?");
    $stmt->execute([$examId, $studentId]);

    // Enregistrer le résultat
    $stmt = $conn->prepare("INSERT INTO resultats_examens 
                          (etudiant_id, examen_id, score, date_soumission)
                          VALUES (?, ?, ?, NOW())");
    $stmt->execute([$studentId, $examId, $score]);

    $conn->commit();
    echo json_encode(['success' => true, 'score' => $score]);

} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}