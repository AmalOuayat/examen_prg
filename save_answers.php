<?php
session_start();
require 'config.php';
header('Content-Type: application/json');

try {
    // Validation
    if (!isset($_SESSION['user']['id'])) {
        throw new Exception("Accès non autorisé");
    }

    $required = ['exam_id', 'student_id', 'answers'];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            throw new Exception("Champ manquant : $field");
        }
    }

    // Vérification de l'étudiant
    if ($_SESSION['user']['id'] != $_POST['student_id']) {
        throw new Exception("ID étudiant invalide");
    }

    $examId = (int)$_POST['exam_id'];
    $studentId = (int)$_POST['student_id'];
    $answers = json_decode($_POST['answers'], true);

    // Transaction
    $conn->beginTransaction();

    // Suppression anciennes réponses
    $stmt = $conn->prepare("DELETE FROM reponses_etudiants2 
                          WHERE examen_id = ? AND etudiant_id = ?");
    $stmt->execute([$examId, $studentId]);

    // Insertion nouvelles réponses
    foreach ($answers as $questionId => $reponse) {
        $questionId = (int)$questionId;
        $value = is_array($reponse) ? json_encode($reponse) : $reponse;

        $stmt = $conn->prepare("INSERT INTO reponses_etudiants2 
                              (examen_id, etudiant_id, question_id, reponse)
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([$examId, $studentId, $questionId, $value]);
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}