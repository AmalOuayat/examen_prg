<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Calculer le score
    $score = 0;
    foreach ($data['answers'] as $questionId => $answer) {
        $stmt = $conn->prepare("SELECT bonne_reponse, note_max FROM questions3 WHERE id_q = ?");
        $stmt->execute([$questionId]);
        $question = $stmt->fetch();
        
        if ($question['type'] === 'qcm') {
            $correct = json_decode($question['bonne_reponse'], true);
            $studentAnswer = json_decode($answer, true);
            sort($correct);
            sort($studentAnswer);
            if ($correct === $studentAnswer) {
                $score += $question['note_max'];
            }
        } else {
            if (trim(strtolower($answer)) === trim(strtolower($question['bonne_reponse']))) {
                $score += $question['note_max'];
            }
        }
    }
    
    // Enregistrer le résultat
    $stmt = $conn->prepare("INSERT INTO resultats_examens 
                          (etudiant_id, examen_id, score, date_soumission)
                          VALUES (?, ?, ?, NOW())");
    $stmt->execute([$data['student_id'], $data['exam_id'], $score]);
    
    echo json_encode(['success' => true, 'score' => $score]);
}