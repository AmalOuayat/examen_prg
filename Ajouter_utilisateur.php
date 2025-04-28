
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter utilisateur</title>
    <style>
        form {
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-top: 10px;
        }

        input,
        select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        button {
            margin-top: 15px;
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .success-message {
            color: green;
            margin: 10px 0;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 4px;
        }

        .error-message {
            color: red;
            margin: 10px 0;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <!-- Ajouter un utilisateur -->
    <form method="POST" action="admin_action.php" target="_self">
        <h3>Ajouter un utilisateur</h3>

        <?php
        session_start();
        // Afficher les messages de succès ou d'erreur
        if (isset($_SESSION['success'])) {
            echo '<div class="success-message">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        ?>

        <label for="nom">Nom :</label>
        <input type="text" id="nom" name="nom" required>

        <label for="prenom">Prenom :</label>
        <input type="text" id="prenom" name="prenom" required>

        <label for="email">Email :</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Mot de passe :</label>
        <input type="password" id="password" name="password" required>

        <label for="roleu">Rôle :</label>
        <select id="roleu" name="roleu" required>
            <option value="formateur">Formateur</option>
            <option value="etudiant">Étudiant</option>
        </select>

        <input type="hidden" name="action" value="add_user">
        <button type="submit">Ajouter</button>
    </form>
</body>

</html>