<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connexion
$conn = new mysqli('localhost', 'root', 'DD202', 'examens_db');
if ($conn->connect_error) die("Erreur de connexion : " . $conn->connect_error);

// Vérification formateur connecté
if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'formateur') {
    header("Location: login.php");
    exit();
}

$formateur_id = $_SESSION['user']['id'];

$where = "";
if (isset($_GET['groupe']) && is_numeric($_GET['groupe'])) {
    $id_groupe_filtre = intval($_GET['groupe']);
    $where = "AND ex.groupe_id = $id_groupe_filtre";
}

$sql = "SELECT ex.id_ex, ex.titre, g.id_g, g.nom AS nom_groupe
        FROM examens3 ex
        JOIN groupes g ON ex.groupe_id = g.id_g
        WHERE ex.formateur_id = $formateur_id $where
        ORDER BY g.nom";

$res = $conn->query($sql);
if (!$res) die("Erreur SQL examens : " . $conn->error);

// On organise les examens par groupe
$groupes = [];
while ($row = $res->fetch_assoc()) {
    $groupes[$row['nom_groupe']]['examens'][] = [
        'id_ex' => $row['id_ex'],
        'titre' => $row['titre']
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats - Formateur</title>
    <style>
        body { font-family: Arial; background: #f4f6f9; margin: 30px; }
        .groupe-card {
            background: white; padding: 20px; margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 10px;
        }
        h2 { color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: center; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        a.button {
            background: #2ecc71; color: white; padding: 5px 10px;
            text-decoration: none; border-radius: 5px;
        }
    </style>
</head>
<body>
<form method="GET" style="margin-bottom: 30px;">
    <label for="groupe">Filtrer par groupe :</label>
    <select name="groupe" id="groupe" onchange="this.form.submit()">
        <option value="">-- Tous les groupes --</option>
        <?php
        // Liste des groupes du formateur
        $groupes_req = $conn->query("
            SELECT DISTINCT g.nom, g.id_g
            FROM groupes g
            JOIN examens3 e ON e.groupe_id = g.id_g
            WHERE e.formateur_id = $formateur_id
        ");
        while ($g = $groupes_req->fetch_assoc()):
            $selected = (isset($_GET['groupe']) && $_GET['groupe'] == $g['id_g']) ? 'selected' : '';
            echo "<option value='{$g['id_g']}' $selected>{$g['nom']}</option>";
        endwhile;
        ?>
    </select>
</form>

<h1>Résultats des étudiants par groupe</h1>

<?php foreach ($groupes as $nom_groupe => $infos): ?>
    <div class="groupe-card">
        <h2><?= htmlspecialchars($nom_groupe) ?></h2>
        <?php foreach ($infos['examens'] as $exam): ?>
            <h3>📘 <?= htmlspecialchars($exam['titre']) ?></h3>
            <table>
                <tr>
                    <th>Étudiant</th>
                    <th>Note</th>
                    <th>Action</th>
                </tr>
                <?php
                $exam_id = $exam['id_ex'];
                $sql_notes = "
    SELECT ut.nom, ut.prenom, ee.id_etudiant, SUM(re.note) AS total
    FROM etudiant_examen ee
    JOIN etudiants e ON e.id_etudiant = ee.id_etudiant
    JOIN utilisateurs ut ON ut.id_u = e.utilisateur_id
    LEFT JOIN reponses_etudiants2 re ON re.etudiant_id = ee.id_etudiant AND re.examen_id = ee.id_examen
    WHERE ee.id_examen = $exam_id AND ee.statut = 'termine'
    GROUP BY ee.id_etudiant
";

                $res_notes = $conn->query($sql_notes);
                if ($res_notes && $res_notes->num_rows > 0):
                    while ($row_note = $res_notes->fetch_assoc()):
                        $note = is_numeric($row_note['total']) ? round(min($row_note['total'], 20), 2) : "Non noté";
                        ?>
                        <tr>
                            <td><?= $row_note['prenom'] . " " . $row_note['nom'] ?></td>
                            <td><?= $note ?> / 20</td>
                            <td><a class="button" href="details_etudiant.php?etudiant_id=<?= $row_note['id_etudiant'] ?>&examen_id=<?= $exam_id ?>">Voir détail</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3">Aucune donnée disponible</td></tr>
                <?php endif; ?>
            </table>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

</body>
</html>
