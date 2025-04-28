<?php
session_start();

// Database connection
$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des étudiants et groupes
    $etudiants = $conn->query("
        SELECT u.id_u, u.nom, u.prenom
        FROM utilisateurs u
        INNER JOIN etudiants e ON u.id_u = e.utilisateur_id
        WHERE u.roleu = 'etudiant' AND u.statut = 'actif'
    ")->fetchAll(PDO::FETCH_ASSOC);

    $groupes = $conn->query("SELECT id_g, nom FROM groupes")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de connexion : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigner étudiant à un groupe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

            /* Variables additionnelles dérivées de la nouvelle palette */
            --primary-light: #61c1ff;
            --secondary-light: #adb5bd;
            --gray-dark: #343a40;
            --gray: #6c757d;
            --gray-light: #dee2e6;
            --danger: #dc3545;
            --warning: #ffc107;
            --success: #28a745;

            /* Variables de mise en page */
            --sidebar-width: 280px;
            --header-height: 70px;
            --card-shadow: var(--box-shadow);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: var(--dark-color);
            overflow-x: hidden;
            line-height: 1.6;
            padding: 20px;
        }

        form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        h2 {
            color: var(--dark-color);
            margin-bottom: 20px;
            text-align: center;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
            color: var(--gray-dark);
        }

        select,
        button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            box-sizing: border-box;
        }

        select {
            color: var(--dark-color);
            background-color: var(--light-color);
        }

        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: var(--primary-dark);
        }

        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: var(--border-radius);
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Style pour les options disabled */
        select option:disabled {
            color: var(--gray);
        }
    </style>
</head>

<body>
    <?php
    // Afficher les messages de session
    if (isset($_SESSION['success'])) {
        echo '<div class="message success">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="message error">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    ?>

    <form method="POST" action="admin_action.php">
        <h2>Assigner un étudiant à un groupe</h2>

        <label for="etudiant_id">Étudiant :</label>
        <select id="etudiant_id" name="etudiant_id" required>
            <option value="" disabled selected>Sélectionner un étudiant</option>
            <?php if (empty($etudiants)): ?>
                <option value="" disabled>Aucun étudiant disponible</option>
            <?php else: ?>
                <?php foreach ($etudiants as $etudiant): ?>
                    <option value="<?= $etudiant['id_u'] ?>">
                        <?= htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>

        <label for="groupe_id">Groupe :</label>
        <select id="groupe_id" name="groupe_id" required>
            <option value="" disabled selected>Sélectionner un groupe</option>
            <?php if (empty($groupes)): ?>
                <option value="" disabled>Aucun groupe disponible</option>
            <?php else: ?>
                <?php foreach ($groupes as $groupe): ?>
                    <option value="<?= $groupe['id_g'] ?>">
                        <?= htmlspecialchars($groupe['nom']) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>

        <button type="submit" name="action" value="assign_group">Assigner</button>
    </form>
</body>

</html>