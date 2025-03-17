<?php
session_start();

$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si l'utilisateur est connecté et est un étudiant
    if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'etudiant') {
        die("Accès refusé. Vous devez être connecté en tant qu'étudiant.");
    }

    // Récupérer la liste des examens disponibles
    $stmt = $conn->prepare("SELECT id_ex, titre FROM examens3");
    $stmt->execute();
    $examens = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sélectionner un examen</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Liste des examens</h1>
        <?php foreach ($examens as $examen): ?>
            <a href="passerExamen.php?id=<?= htmlspecialchars($examen['id_ex']) ?>" class="btn btn-link">
                <?= htmlspecialchars($examen['titre']) ?>
            </a><br>
        <?php endforeach; ?>
    </div>
</body>
</html>