<?php
include 'db_connection.php';

// Get form data
$examen_id = $_POST['examen_id'];
$etudiant_id = $_POST['etudiant_id'];
$notes = $_POST['note'];

// Update the etudiant's responses with the corrected scores
foreach ($notes as $question_id => $note) {
    $sql = "UPDATE reponses_etudiants2 SET note = $note WHERE examen_id = $examen_id AND etudiant_id = $etudiant_id AND question_id = $question_id";
    if (!$conn->query($sql)) {
        die("Error updating record: " . $conn->error);
    }
}

echo "Corrections saved successfully!";
$conn->close();
?>