<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connexion à la BDD
$conn = new mysqli('localhost', 'root', 'DD202', 'examens_db');
if ($conn->connect_error) die("Erreur de connexion : " . $conn->connect_error);

// Vérifier les paramètres GET
if (!isset($_GET['etudiant_id']) || !isset($_GET['examen_id'])) {
    die("Paramètres manquants");
}

$etudiant_id = intval($_GET['etudiant_id']);
$examen_id = intval($_GET['examen_id']);

// Info étudiant
$req_user = $conn->query("
    SELECT u.nom, u.prenom
    FROM etudiants e
    JOIN utilisateurs u ON e.utilisateur_id = u.id_u
    WHERE e.id_etudiant = $etudiant_id
");

$etudiant = $req_user->fetch_assoc();

// Info examen
$req_exam = $conn->query("SELECT titre FROM examens3 WHERE id_ex = $examen_id");
$examen = $req_exam->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails étudiant</title>
    <style>
        body { font-family: Arial; background: #f0f3f5; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #3498db; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h2>🧑‍🎓 <?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></h2>
    <h3>📘 Examen : <?= htmlspecialchars($examen['titre']) ?></h3>

    <table>
        <tr>
            <th>Question</th>
            <th>Réponse</th>
            <th>Note</th>
        </tr>
        <?php
        $sql = "
            SELECT q.texte AS question, re.reponse, re.note
            FROM reponses_etudiants2 re
            JOIN questions3 q ON q.id_q = re.question_id
            WHERE re.etudiant_id = $etudiant_id AND re.examen_id = $examen_id
        ";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0):
            while ($row = $res->fetch_assoc()):
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['question']) ?></td>
                    <td><?php
$reponse = $row['reponse'];
$affichage = '';

if (str_starts_with($reponse, '[')) {
    // Réponse sous forme JSON => probablement un QCM
    $ids = json_decode($reponse, true);
    if (is_array($ids) && count($ids) > 0) {
        // Sécurité : on évite les injections
        $ids_int = array_map('intval', $ids);
        $ids_str = implode(',', $ids_int);

        // 🔁 Change le nom de la table et de la colonne selon ta base
        $sql = "SELECT texte FROM options3 WHERE id_op IN ($ids_str)";
        $opt_req = $conn->query($sql);

        if (!$opt_req) {
            // Afficher l'erreur SQL pour debug
            echo "<span style='color:red;'>Erreur SQL: " . $conn->error . "</span>";
        } else {
            $texts = [];
            while ($opt = $opt_req->fetch_assoc()) {
                $texts[] = $opt['texte'];
            }
            $affichage = implode(', ', $texts);
        }
    } else {
        $affichage = 'Aucune option sélectionnée';
    }
} else {
    $affichage = htmlspecialchars($reponse);
}

echo $affichage;
?>
</td>
                    <td><?= is_numeric($row['note']) ? $row['note'] : 'Non noté' ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="3">Aucune réponse trouvée.</td></tr>
        <?php endif; ?>
    </table>
</div>
</body>
</html>
