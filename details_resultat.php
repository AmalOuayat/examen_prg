<?php
session_start();
require_once 'config.php';

// Vérifier si l'utilisateur est connecté et est un formateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'formateur') {
    header('Location: login.php');
    exit();
}

// Vérifier les paramètres
if (!isset($_GET['examen_id']) || !isset($_GET['etudiant_id'])) {
    header('Location: voir_resultats_formateur.php');
    exit();
}

$examen_id = $_GET['examen_id'];
$etudiant_id = $_GET['etudiant_id'];

// Récupérer les informations de l'étudiant
$query = "SELECT nom, prenom FROM etudiants WHERE id = :etudiant_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['etudiant_id' => $etudiant_id]);
$etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les informations de l'examen
$query = "SELECT titre FROM examens WHERE id = :examen_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['examen_id' => $examen_id]);
$examen = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les réponses détaillées de l'étudiant
$query = "SELECT q.question, re.reponse, re.score_question, q.reponse_correcte
          FROM reponses_etudiants2 re
          JOIN questions q ON re.question_id = q.id
          WHERE re.examen_id = :examen_id AND re.etudiant_id = :etudiant_id
          ORDER BY q.id";
$stmt = $pdo->prepare($query);
$stmt->execute([
    'examen_id' => $examen_id,
    'etudiant_id' => $etudiant_id
]);
$reponses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer le score total
$query = "SELECT score FROM reponses_etudiants2 
          WHERE examen_id = :examen_id AND etudiant_id = :etudiant_id
          LIMIT 1";
$stmt = $pdo->prepare($query);
$stmt->execute([
    'examen_id' => $examen_id,
    'etudiant_id' => $etudiant_id
]);
$score_total = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails des résultats</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Détails des résultats</h1>
        
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Informations</h5>
                <p><strong>Étudiant :</strong> <?= htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']) ?></p>
                <p><strong>Examen :</strong> <?= htmlspecialchars($examen['titre']) ?></p>
                <p><strong>Score total :</strong> <?= $score_total ?>%</p>
            </div>
        </div>

        <h2>Réponses détaillées</h2>
        <?php foreach ($reponses as $index => $reponse): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Question <?= $index + 1 ?></h5>
                    <p class="card-text"><strong>Question :</strong> <?= htmlspecialchars($reponse['question']) ?></p>
                    <p class="card-text"><strong>Réponse de l'étudiant :</strong> <?= htmlspecialchars($reponse['reponse']) ?></p>
                    <p class="card-text"><strong>Réponse correcte :</strong> <?= htmlspecialchars($reponse['reponse_correcte']) ?></p>
                    <p class="card-text"><strong>Score pour cette question :</strong> <?= $reponse['score_question'] ?>%</p>
                </div>
            </div>
        <?php endforeach; ?>

        <a href="voir_resultats_formateur.php?examen_id=<?= $examen_id ?>" class="btn btn-secondary">
            Retour aux résultats
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 