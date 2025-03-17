<?php
session_start();
$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Vérifier si un étudiant est connecté
if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'etudiant') {
    die("Accès refusé.");
}

$etudiant_id = $_SESSION['user']['id'];

try {
    // Récupérer les examens disponibles pour cet étudiant
    $stmt = $conn->prepare("SELECT e.id AS examen_id, e.titre, q.id AS question_id, q.texte_question 
        FROM examens2 e 
        JOIN questions2 q ON e.id = q.examen_id");
    $stmt->execute([':etudiant_id' => $etudiant_id]);
    $examens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$examens) {
        die("Aucun examen disponible pour vous.");
    }
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examens disponibles</title>
</head>
<body>
    <h1>Examens disponibles</h1>
    <ul>
        <?php foreach ($examens as $examen): ?>
            <li><a href="passer_examen.php?examen_id=<?= $examen['id'] ?>"><?= htmlspecialchars($examen['titre']) ?></a></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
