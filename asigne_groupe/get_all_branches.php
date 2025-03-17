<?php
header('Content-Type: application/json');

    session_start();

    $host = "localhost";
    $dbname = "examens_db";
    $username = "root";
    $password = "DD202";

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Erreur : " . $e->getMessage());
    }

try {
    if (!isset($_GET['filiere_id'])) {
        throw new Exception('ID de filière requis');
    }

    $filiere_id = filter_var($_GET['filiere_id'], FILTER_VALIDATE_INT);
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("
        SELECT id_b, nom_branche, type_b
        FROM branches 
        WHERE filiere_id = :filiere_id
        ORDER BY type_b, nom_branche
    ");
    
    $stmt->execute([':filiere_id' => $filiere_id]);
    $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($branches);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}