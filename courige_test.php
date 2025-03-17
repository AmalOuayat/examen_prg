<?php
session_start();

// Connexion à la base de données
$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $reponses = [];
    $etudiant_id = null;
    $examen_id = null;

    // Récupérer les réponses des étudiants pour un examen sélectionné
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST['etudiant_id'])) {
            $etudiant_id = intval($_POST['etudiant_id']);
            $examen_id = intval($_POST['examen_id']); // Assurez-vous d'ajouter cet ID dans le formulaire de sélection de l'examen

            $stmtReponses = $conn->prepare("
                SELECT r.id_re AS reponse_id, u.nom AS etudiant_nom, q.texte, r.reponse 
                FROM reponses_etudiants2 r
                JOIN questions3 q ON r.question_id = q.id_q
                JOIN utilisateurs u ON r.etudiant_id = u.id_u
                WHERE r.examen_id = :examen_id AND r.etudiant_id = :etudiant_id
            ");
            $stmtReponses->bindParam(':examen_id', $examen_id, PDO::PARAM_INT);
            $stmtReponses->bindParam(':etudiant_id', $etudiant_id, PDO::PARAM_INT);
            $stmtReponses->execute();
            $reponses = $stmtReponses->fetchAll(PDO::FETCH_ASSOC);
        }
    }

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récupérer les Réponses de l'Étudiant</title>
</head>
<body>
    <h1>Récupérer les Réponses de l'Étudiant</h1>

    <!-- Formulaire de saisie de l'ID de l'étudiant et de l'examen -->
    <form method="POST">
        <label for="etudiant_id">ID de l'Étudiant :</label>
        <input type="number" name="etudiant_id" id="etudiant_id" required>
        <br><br>

        <label for="examen_id">ID de l'Examen :</label>
        <input type="number" name="examen_id" id="examen_id" required>
        <br><br>

        <button type="submit">Afficher les Réponses</button>
    </form>

    <?php if (!empty($reponses)): ?>
        <h2>Réponses de l'Étudiant</h2>
        <form method="POST">
            <?php foreach ($reponses as $reponse): ?>
                <p><strong>Étudiant :</strong> <?= htmlspecialchars($reponse['etudiant_nom']) ?></p>
                <p><strong>Question :</strong> <?= htmlspecialchars($reponse['texte']) ?></p>
                <p><strong>Réponse :</strong> <?= htmlspecialchars($reponse['reponse']) ?></p>
                <label for="note_<?= $reponse['reponse_id'] ?>">Note :</label>
                <input type="number" name="notes[<?= $reponse['reponse_id'] ?>]" id="note_<?= $reponse['reponse_id'] ?>" step="0.01" min="0" max="20"><br><br>
            <?php endforeach; ?>
            <button type="submit" name="enregistrer_notes">Enregistrer les Notes</button>
        </form>
    <?php elseif (isset($etudiant_id) && isset($examen_id)): ?>
        <p>Aucune réponse trouvée pour cet étudiant et cet examen.</p>
    <?php endif; ?>

</body>
</html>
