<?php
session_start();

// Informations pour la base de données
$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    // Connexion à la base de données
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        
        // Récupérer les informations de l'utilisateur depuis la base
        $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $password === $user['mot_de_passe']) {
            if ($user['statut'] === 'bloque') {
                echo "Votre compte est bloqué. Veuillez contacter l'administrateur.";
            } else {
            // Stocker les informations de l'utilisateur en session
            $_SESSION['user'] = [
                'id' => $user['id_u'],
                'nom' => $user['nom'],
                'email' => $user['email'],
                'roleu' => $user['roleu']
            ];

            // Redirection selon le rôle
            switch ($user['roleu']) {
                case 'admin':
                    header("Location: admin.php");
                    exit();
                case 'formateur':
                    header("Location: formateur.html");
                    exit();
                case 'etudiant':
                    header("Location: etudiant.html");
                    exit();
                default:
                    echo "Rôle non reconnu.";
                    exit();
                }}
        } else {
            echo "Email ou mot de passe incorrect.";
        }
    }
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
