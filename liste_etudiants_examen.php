<?php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'formateur') {
    header('Location: login.php');
    exit();
}

$host = "localhost";
$username = "root";
$password = "DD202";
$dbname = "examens_db";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $formateur_id = $_SESSION['user']['id'];

    // Récupérer l'ID de l'examen depuis l'URL
    $examen_id = isset($_GET['examen_id']) ? intval($_GET['examen_id']) : 0;

    if ($examen_id === 0) {
        die("ID de l'examen non spécifié.");
    }

    // Récupérer les informations de l'examen
    $stmt = $conn->prepare("
        SELECT e.*, g.nom as groupe_nom 
        FROM examens3 e 
        JOIN groupes g ON e.groupe_id = g.id_g 
        WHERE e.id_ex = ? AND e.formateur_id = ?
    ");
    $stmt->execute([$examen_id, $formateur_id]);
    $examen = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$examen) {
        die("Examen non trouvé ou vous n'avez pas les droits d'accès.");
    }

    // Récupérer la liste des étudiants du groupe avec leur statut de participation
    $sql = "
        SELECT DISTINCT
            u.id_u,
            u.nom as etudiant_nom,
            CASE 
                WHEN (
                    SELECT COUNT(*) 
                    FROM reponses_etudiants2 re2 
                    WHERE re2.etudiant_id = e.id_etudiant 
                    AND re2.examen_id = ?
                ) > 0 THEN 'Passé'
                ELSE 'Non passé'
            END as statut
        FROM utilisateurs u
        JOIN etudiants e ON u.id_u = e.utilisateur_id
        JOIN etudiants_groupes eg ON e.id_etudiant = eg.etudiant_id
        WHERE eg.id_groupe = ?
        AND u.roleu = 'etudiant'
        ORDER BY u.nom
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$examen_id, $examen['groupe_id']]);
    $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Liste des Étudiants - <?php echo htmlspecialchars($examen['titre']); ?></title>
    <style>
        :root {
            /* Nouvelle palette de couleurs */
            --primary-color: #0f9ef7;
            --primary-dark: #0d8de0;
            --secondary-color: #6c757d;
            --dark-color: #121212;
            --light-color: #f8f9fa;
            --border-radius: 8px;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-color);
            color: var(--dark-color);
            margin: 0;
            padding: 20px;
            transition: background-color 0.3s, color 0.3s;
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
            background: white;
            padding: 25px 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: box-shadow var(--transition);
        }

        .container:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 12px;
            border-bottom: 3px solid var(--primary-color);
            font-weight: 700;
            font-size: 2rem;
        }

        .info-section {
            background-color: #e9f2ff;
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            color: var(--secondary-color);
            font-weight: 600;
            box-shadow: inset 0 0 8px rgba(15, 158, 247, 0.15);
        }

        .info-section p {
            margin: 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1rem;
            color: var(--dark-color);
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        tbody tr:nth-child(even) {
            background-color: #f4f9ff;
        }

        tbody tr:hover {
            background-color: #d6eaff;
            cursor: default;
            transition: background-color var(--transition);
        }

        .status {
            display: inline-block;
            min-width: 80px;
            padding: 6px 12px;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-align: center;
            transition: background-color var(--transition), color var(--transition);
        }

        .passed {
            background-color: #d0ebff;
            color: var(--primary-dark);
            border: 1px solid var(--primary-color);
        }

        .not-passed {
            background-color: #fbeaea;
            color: #b00020;
            border: 1px solid #e74c3c;
        }

        .btn-corriger,
        .back-btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 600;
            transition: background-color var(--transition), box-shadow var(--transition);
            box-shadow: 0 4px 8px rgba(15, 158, 247, 0.3);
            user-select: none;
        }

        .btn-corriger {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-corriger:hover {
            background-color: var(--primary-dark);
            box-shadow: 0 6px 12px rgba(13, 141, 224, 0.5);
        }

        .back-btn {
            background-color: var(--secondary-color);
            color: white;
            margin-bottom: 25px;
            display: inline-block;
        }

        .back-btn:hover {
            background-color: var(--primary-color);
            box-shadow: 0 6px 12px rgba(15, 158, 247, 0.5);
        }

        .no-students {
            text-align: center;
            padding: 25px;
            background-color: #e9f2ff;
            border-radius: var(--border-radius);
            color: var(--secondary-color);
            font-weight: 600;
            box-shadow: inset 0 0 10px rgba(15, 158, 247, 0.1);
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="corriger_examen.php" class="back-btn">← Retour à la sélection des examens</a>

        <h1>Liste des Étudiants</h1>

        <div class="info-section">
            <p><strong>Examen :</strong> <?php echo htmlspecialchars($examen['titre']); ?></p>
            <p><strong>Groupe :</strong> <?php echo htmlspecialchars($examen['groupe_nom']); ?></p>
        </div>

        <?php if (empty($etudiants)): ?>
            <div class="no-students">
                <p>Aucun étudiant n'est inscrit dans ce groupe.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nom de l'étudiant</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($etudiants as $etudiant): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($etudiant['etudiant_nom']); ?></td>
                            <td>
                                <span class="status <?php echo $etudiant['statut'] === 'Passé' ? 'passed' : 'not-passed'; ?>">
                                    <?php echo htmlspecialchars($etudiant['statut']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($etudiant['statut'] === 'Passé'): ?>
                                    <a href="corriger_examen.php?examen_id=<?php echo $examen_id; ?>&etudiant_id=<?php echo $etudiant['id_u']; ?>"
                                        class="btn-corriger">
                                        Corriger
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>

</html>