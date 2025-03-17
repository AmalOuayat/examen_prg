<?php
session_start();
header('Content-Type: application/json');

$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    if (!isset($_GET['id_filiere']) || !isset($_GET['type'])) {
        throw new Exception('Paramètres manquants');
    }

    $id_filiere = filter_var($_GET['id_filiere'], FILTER_VALIDATE_INT);
    $type = $_GET['type'];

    // Debug - Afficher les valeurs reçues
    error_log("ID Filière reçu: " . $id_filiere);
    error_log("Type reçu: " . $type);

    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Requête pour vérifier si la filière existe
    $checkFiliere = $conn->prepare("SELECT id_f FROM filieres WHERE id_f = ?");
    $checkFiliere->execute([$id_filiere]);
    if (!$checkFiliere->fetch()) {
        throw new Exception("Filière non trouvée");
    }

    // Adapter le type pour correspondre à la base de données
    $typeMapping = [
        'tronc_commun' => 'Tronc_commun',
        'branche' => 'Specialise'
    ];
    
    $typeValue = $typeMapping[$type] ?? $type;

    $query = "SELECT id_b, nom_branche 
              FROM branches 
              WHERE filiere_id = :id_filiere 
              AND type_b = :type_b";

    error_log("Requête SQL: " . $query);

    $stmt = $conn->prepare($query);
    $params = [
        ':id_filiere' => $id_filiere,
        ':type_b' => $typeValue
    ];
    
    error_log("Paramètres: " . json_encode($params));
    
    $stmt->execute($params);
    $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Résultat: " . json_encode($branches));

    if (empty($branches)) {
        echo json_encode([
            'error' => 'Aucune branche trouvée',
            'debug' => [
                'filiere' => $id_filiere,
                'type' => $typeValue,
                'sql' => $query
            ]
        ]);
    } else {
        echo json_encode($branches);
    }

} catch (Exception $e) {
    error_log("Erreur: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTrace()
    ]);
}