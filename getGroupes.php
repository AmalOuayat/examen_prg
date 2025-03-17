<?php
header('Content-Type: application/json');
$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $conn->prepare("SELECT id, nom FROM groupes");
    $stmt->execute();
    $groupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($groupes);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
