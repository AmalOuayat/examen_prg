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

} catch(PDOException $e) {
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
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --success-color: #27ae60;
            --error-color: #c0392b;
            --background-color: #f5f6fa;
            --border-color: #dcdde1;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background-color: var(--background-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }

        .info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-section p {
            margin: 5px 0;
            color: var(--secondary-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #f1f2f6;
        }

        .status {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 500;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .passed {
            background-color: #d4edda;
            color: var(--success-color);
        }

        .not-passed {
            background-color: #f8d7da;
            color: var(--error-color);
        }

        .btn-corriger {
            display: inline-block;
            padding: 6px 12px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .btn-corriger:hover {
            background-color: var(--secondary-color);
        }

        .back-btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--secondary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background-color: var(--primary-color);
        }

        .no-students {
            text-align: center;
            padding: 20px;
            color: var(--secondary-color);
            background: #f8f9fa;
            border-radius: 8px;
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
                                    <a href="corriger_examen.php?examen_id=<?php echo $examen_id; ?>&etudiant_id=<?php echo $etudiant['id_u']; ?>" class="btn-corriger">
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
