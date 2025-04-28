<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['etudiant_id'])) {
    header("Location: login.php");
    exit;
}

$id_etudiant = $_SESSION['etudiant_id'];
$examen_id = $_GET['id'];

$stmt = $pdo->prepare("
    SELECT q.id_q, q.texte AS question, q.type, q.note_max,
           re.reponse, re.note,
           ro.texte AS bonne_reponse
    FROM etudiant_examen ee
    JOIN examens3 ex ON ex.id_ex = ee.id_examen
    JOIN questions3 q ON q.exam_id = ex.id_ex
    LEFT JOIN reponses_etudiants2 re ON re.question_id = q.id_q AND re.etudiant_id = ee.id_etudiant
    LEFT JOIN options3 ro ON ro.question_id = q.id_q AND ro.correct = 1
    WHERE ee.id = ? AND ee.id_etudiant = ?
");
$stmt->execute([$examen_id, $id_etudiant]);
$questions = $stmt->fetchAll();
?>

<h2>Résultats de l'examen</h2>
<?php foreach ($questions as $q): ?>
    <div style="margin-bottom: 15px;">
        <b>Question :</b> <?= htmlspecialchars($q['question']) ?><br>
        <b>Votre réponse :</b> <?= htmlspecialchars($q['reponse']) ?><br>
        <b>Bonne réponse :</b> <?= htmlspecialchars($q['bonne_reponse']) ?><br>
        <b>Note :</b> <?= $q['note'] ?>/<?= $q['note_max'] ?>
    </div>
<?php endforeach; ?>
<a href="dashboard.php">⬅ Retour</a>
