<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connexion à la base de données
$host = 'localhost';
$user = 'root';
$pass = 'DD202';
$dbname = 'examens_db';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'etudiant') {
    header('Location: login.php');
    exit('Accès réservé aux étudiants');
}

$id_etudiant = $_SESSION['user']['id'];

// Requête principale avec filtre sur la note non nulle
$sql = "SELECT ee.*, ex.titre, ex.duree
        FROM etudiant_examen ee
        JOIN examens3 ex ON ee.id_examen = ex.id_ex
        WHERE ee.id_etudiant = $id_etudiant
        AND ee.statut = 'termine'
        AND EXISTS (
            SELECT 1 FROM reponses_etudiants2 
            WHERE reponses_etudiants2.etudiant_id = ee.id_etudiant 
            AND reponses_etudiants2.examen_id = ee.id_examen 
            AND reponses_etudiants2.note IS NOT NULL
        )";

$result = $conn->query($sql);
if (!$result) {
    die("Erreur SQL : " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Résultats</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 40px 20px;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }

        .card-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .exam-card {
            background-color: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 300px;
            padding: 20px;
            transition: transform 0.3s;
        }

        .exam-card:hover {
            transform: translateY(-5px);
        }

        .exam-title {
            font-size: 18px;
            font-weight: bold;
            color: #34495e;
            margin-bottom: 10px;
        }

        .exam-info {
            margin-bottom: 8px;
            color: #7f8c8d;
        }

        .note {
            font-size: 20px;
            font-weight: bold;
            color: #27ae60;
        }

        .no-exam {
            text-align: center;
            font-size: 18px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>

    <h1>Mes Résultats d'Examens</h1>

    <?php if ($result->num_rows > 0): ?>
        <div class="card-container">
            <?php while($row = $result->fetch_assoc()): ?>
                <?php
                    $id_exam = $row['id_examen'];
                    $sql_note = "SELECT SUM(note) AS total_note FROM reponses_etudiants2
                                 WHERE etudiant_id = $id_etudiant AND examen_id = $id_exam AND note IS NOT NULL";
                    $res_note = $conn->query($sql_note);
                    $note_data = $res_note->fetch_assoc();
                    $note_total = $note_data['total_note'] ?? null;

                    // Normalisation
                    $note_affichee = is_numeric($note_total) ? ($note_total > 20 ? 20 : $note_total) : null;
                    $note_affichee = $note_affichee ? round($note_affichee, 2) : null;
                ?>
                <div class="exam-card">
                    <div class="exam-title"><?= htmlspecialchars($row['titre']) ?></div>
                    <div class="exam-info">Durée : <?= intval($row['duree']) ?> min</div>
                    <div class="exam-info">Statut : <?= htmlspecialchars($row['statut']) ?></div>
                    <div class="note"><?= $note_affichee ?> / 20</div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="no-exam">Aucun examen noté trouvé.</p>
    <?php endif; ?>

</body>
</html>
