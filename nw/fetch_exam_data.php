<?php
include 'db_connection.php';

// Check if examen_id and etudiant_id are provided
if (!isset($_GET['examen_id']) || !isset($_GET['etudiant_id'])) {
    die("examen ID and etudiant ID are required.");
}

$examen_id = $_GET['examen_id'];
$etudiant_id = $_GET['etudiant_id'];

// Fetch examen details
$sql = "SELECT * FROM examens3 WHERE id_ex = $examen_id";
$result = $conn->query($sql);

if (!$result) {
    die("Error fetching examen details: " . $conn->error);
}

$examen = $result->fetch_assoc();

if (!$examen) {
    die("No examen found with ID: $examen_id");
}

// Fetch questions for the examen
$sql = "SELECT * FROM questions3 WHERE examen_id = $examen_id";
$questions_result = $conn->query($sql);

if (!$questions_result) {
    die("Error fetching questions: " . $conn->error);
}

$questions = [];
while ($row = $questions_result->fetch_assoc()) {
    $questions[] = $row;
}

// Fetch etudiant responses
$sql = "SELECT * FROM reponses_etudiants2 WHERE examen_id = $examen_id AND etudiant_id = $etudiant_id";
$responses_result = $conn->query($sql);

if (!$responses_result) {
    die("Error fetching etudiant responses: " . $conn->error);
}

$responses = [];
while ($row = $responses_result->fetch_assoc()) {
    $responses[$row['question_id']] = $row;
}

$conn->close();
?>