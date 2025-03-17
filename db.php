<?php
$host = "localhost";
$username = "root";
$password = "DD202";  // Mot de passe correct pour votre configuration
$dbname = "examens_db";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8mb4");
} catch(PDOException $e) {
    error_log("Erreur de connexion à la base de données: " . $e->getMessage());
    die("Erreur de connexion : " . $e->getMessage());
}
?>
