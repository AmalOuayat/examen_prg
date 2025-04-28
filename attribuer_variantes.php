<?php
session_start();
$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connexion échouée : " . $e->getMessage());
}

// Vérifier si l'utilisateur est connecté et est un formateur
if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'formateur') {
    die("Accès refusé: Vous devez être connecté en tant que formateur.");
}

$formateur_id = $_SESSION['user']['id']; // ID du formateur connecté

function getGroupsForFormateur($pdo, $formateur_id) {
    // Récupérer les groupes liés aux modules du formateur connecté
    // en utilisant la structure spécifique de la base de données
    $stmt = $pdo->prepare("
        SELECT DISTINCT g.id_g, g.nom 
        FROM groupes g
        JOIN modules m ON g.id_branche = m.id_branche
        WHERE m.formateur_id = ?
        ORDER BY g.nom ASC
    ");
    $stmt->execute([$formateur_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getExamsForFormateur($pdo, $formateur_id) {
    // Récupérer uniquement les examens créés par ce formateur
    $stmt = $pdo->prepare("
        SELECT id_ex, titre 
        FROM examens3 
        WHERE formateur_id = ?
        ORDER BY titre ASC
    ");
    $stmt->execute([$formateur_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$message = "";
$affectations = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['groupe'], $_POST['examen'])) {
    $group_id = $_POST['groupe'];
    $exam_id = $_POST['examen'];

    // Vérifier que l'examen appartient bien au formateur connecté
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM examens3 WHERE id_ex = ? AND formateur_id = ?");
    $stmt->execute([$exam_id, $formateur_id]);
    if ($stmt->fetchColumn() == 0) {
        $message = "<p class='error'>Vous n'avez pas accès à cet examen.</p>";
    } else {
        // Récupérer les étudiants du groupe
        $stmt = $pdo->prepare("SELECT e.id_etudiant, u.nom, u.prenom
                               FROM etudiants e
                               JOIN utilisateurs u ON e.utilisateur_id = u.id_u
                               JOIN etudiants_groupes eg ON e.id_etudiant = eg.etudiant_id
                               WHERE eg.id_groupe = ?
                               ORDER BY u.nom ASC");
        $stmt->execute([$group_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les variantes de l'examen
        $stmt = $pdo->prepare("SELECT * FROM variantes WHERE exam_id = ? ORDER BY id_variante ASC");
        $stmt->execute([$exam_id]);
        $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $nbVariants = count($variants);

        // Créer un tableau associatif id_variante => nom_variante
        $variantNames = [];
        foreach ($variants as $var) {
            $variantNames[$var['id_variante']] = $var['nom_variante']; // change 'nom_variante' si ton champ a un autre nom
        }

        if ($nbVariants == 0) {
            $message = "<p class='error'>Cet examen n'a aucune variante.</p>";
        } elseif (empty($students)) {
            $message = "<p class='error'>Aucun étudiant trouvé dans ce groupe.</p>";
        } else {
            $index = 0;
            foreach ($students as $student) {
                $variant_id = ($nbVariants == 1) ? $variants[0]['id_variante'] : $variants[$index % $nbVariants]['id_variante'];

                // Insérer ou mettre à jour l'affectation
                $stmtInsert = $pdo->prepare("INSERT INTO etudiant_examen (id_etudiant, id_examen, id_variante)
                                             VALUES (?, ?, ?)
                                             ON DUPLICATE KEY UPDATE id_variante = VALUES(id_variante)");
                $stmtInsert->execute([$student['id_etudiant'], $exam_id, $variant_id]);

                // Ajouter à la liste des affectations pour affichage
                $affectations[] = [
                    'nom' => $student['nom'],
                    'prenom' => $student['prenom'],
                    'variante' => $variantNames[$variant_id] ?? 'Variante inconnue'
                ];

                $index++;
            }

            $message = "<p class='success'>Variantes attribuées avec succès.</p>";
        }
    }
}

// Appel des fonctions avec la variable $pdo
$groups = getGroupsForFormateur($pdo, $formateur_id);
$exams = getExamsForFormateur($pdo, $formateur_id);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Affectation des Variantes</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        form { max-width: 500px; margin: auto; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        select, button { width: 100%; padding: 10px; margin: 10px 0; }
        table { width: 80%; margin: 20px auto; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #aaa; text-align: center; }
        th { background-color: #f2f2f2; }
        .success { color: green; text-align: center; }
        .error { color: red; text-align: center; }
    </style>
</head>
<body>
    <h1 style="text-align:center;">Affecter les Variantes</h1>

    <?= $message ?>

    <form method="POST">
        <label for="groupe">Groupe :</label>
        <select name="groupe" required>
            <option value="">-- Sélectionner un groupe --</option>
            <?php foreach ($groups as $group): ?>
                <option value="<?= $group['id_g'] ?>"><?= htmlspecialchars($group['nom']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="examen">Examen :</label>
        <select name="examen" required>
            <option value="">-- Sélectionner un examen --</option>
            <?php foreach ($exams as $exam): ?>
                <option value="<?= $exam['id_ex'] ?>"><?= htmlspecialchars($exam['titre']) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Affecter les Variantes</button>
    </form>

    <?php if (!empty($affectations)) : ?>
        <h2 style="text-align:center;">Liste des étudiants avec leurs variantes</h2>
        <table>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Variante</th>
            </tr>
            <?php foreach ($affectations as $affectation) : ?>
                <tr>
                    <td><?= htmlspecialchars($affectation['nom']) ?></td>
                    <td><?= htmlspecialchars($affectation['prenom']) ?></td>
                    <td><?= htmlspecialchars($affectation['variante']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>