<?php
session_start();

$nom = isset($_SESSION['user']['nom']) ? $_SESSION['user']['nom'] : "Invité";
$role = isset($_SESSION['user']['roleu']) ? $_SESSION['user']['roleu'] : "Inconnu";
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Tableau de bord étudiant</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f8ff;
            /* bleu très clair */
            color: #333;
            margin: 0;
            padding: 0;
        }

        .dashboard {
            max-width: 800px;
            margin: 60px auto;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.2);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
        }

        h1 {
            color: #007bff;
            /* bleu clair */
        }

        .role {
            font-size: 1.2em;
            margin-top: 10px;
            color: #555;
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <h1>Bonjour <?php echo htmlspecialchars($role); ?> <?php echo htmlspecialchars($nom); ?> !</h1>
        <p class="role">Bienvenue sur votre tableau de bord Formateur.</p>
    </div>
</body>

</html>