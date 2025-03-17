<?php
session_start();
require_once 'db.php';  // Utiliser db.php au lieu de config.php

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Debug: écrire les données de session et les variables globales
$debug_info = [
    'SESSION' => $_SESSION,
    'POST' => $_POST,
    'GET' => $_GET,
    'SERVER' => [
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
        'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? 'Non défini',
        'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? 'Non défini'
    ]
];
file_put_contents('security_debug.log', date('Y-m-d H:i:s') . " - Début de la requête\n" . print_r($debug_info, true) . "\n", FILE_APPEND);

// Vérifier si l'étudiant est connecté - CORRIGÉ
$user_id = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
if (!$user_id) {
    $error = "Erreur: user_id non défini dans la session";
    file_put_contents('security_debug.log', date('Y-m-d H:i:s') . " - $error\n", FILE_APPEND);
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => $error]));
}

// Récupérer les données POST
$rawData = file_get_contents('php://input');
file_put_contents('security_debug.log', date('Y-m-d H:i:s') . " - Données brutes reçues: " . $rawData . "\n", FILE_APPEND);

$data = json_decode($rawData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $error = "Erreur de décodage JSON: " . json_last_error_msg();
    file_put_contents('security_debug.log', date('Y-m-d H:i:s') . " - $error\n", FILE_APPEND);
}

if (!$data || !isset($data['event_type']) || !isset($data['timestamp'])) {
    $error = "Données invalides ou manquantes";
    file_put_contents('security_debug.log', date('Y-m-d H:i:s') . " - $error\n", FILE_APPEND);
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => $error]));
}

// Vérifier si l'examen est en cours
$exam_id = isset($_SESSION['exam_id']) ? $_SESSION['exam_id'] : (isset($_GET['exam_id']) ? $_GET['exam_id'] : 0);

// Ajouter des événements de sécurité supplémentaires
$security_events = [
    'fullscreen_exit' => 'L\'étudiant a quitté le mode plein écran',
    'tab_switch' => 'L\'étudiant a changé de tab/fenêtre',
    'copy_paste_detected' => 'Tentative de copier-coller détectée',
    'suspicious_browser_resize' => 'Redimensionnement suspect de la fenêtre du navigateur',
    'multiple_browser_tabs' => 'Plusieurs onglets/fenêtres détectés pendant l\'examen'
];

// Fonction pour enregistrer les événements de sécurité
function logSecurityEvent($conn, $user_id, $exam_id, $event_type, $event_data = null) {
    try {
        $sql = "INSERT INTO exam_security_logs 
                (user_id, exam_id, event_type, timestamp, event_data, screen_width, screen_height, is_fullscreen, ip_address, user_agent) 
                VALUES 
                (:user_id, :exam_id, :event_type, :timestamp, :event_data, :screen_width, :screen_height, :is_fullscreen, :ip_address, :user_agent)";
        
        $stmt = $conn->prepare($sql);
        
        $params = [
            ':user_id' => $user_id,
            ':exam_id' => $exam_id,
            ':event_type' => $event_type,
            ':timestamp' => date('Y-m-d H:i:s'),
            ':event_data' => json_encode($event_data, JSON_UNESCAPED_UNICODE),
            ':screen_width' => $_POST['screen_width'] ?? null,
            ':screen_height' => $_POST['screen_height'] ?? null,
            ':is_fullscreen' => $_POST['is_fullscreen'] ?? null,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Non défini',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Non défini'
        ];
        
        $stmt->execute($params);
        
        return true;
    } catch(PDOException $e) {
        file_put_contents('security_debug.log', date('Y-m-d H:i:s') . " - Erreur d'enregistrement de l'événement: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

try {
    // La connexion est déjà établie dans db.php, utiliser $conn directement
    file_put_contents('security_debug.log', date('Y-m-d H:i:s') . " - Tentative de création/vérification de la table\n", FILE_APPEND);

    // Vérifier si la table existe
    $stmt = $conn->query("SHOW TABLES LIKE 'exam_security_logs'");
    if ($stmt->rowCount() == 0) {
        file_put_contents('security_debug.log', date('Y-m-d H:i:s') . " - Création de la table exam_security_logs\n", FILE_APPEND);

        // Créer la table - MISE À JOUR
        $sql = "CREATE TABLE exam_security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            exam_id INT NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            timestamp DATETIME NOT NULL,
            event_data TEXT,
            screen_width INT,
            screen_height INT,
            is_fullscreen BOOLEAN,
            ip_address VARCHAR(100),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sql);
        file_put_contents('security_debug.log', date('Y-m-d H:i:s') . " - Table créée avec succès\n", FILE_APPEND);
    }

    try {
        // Préparer et exécuter la requête - MISE À JOUR
        $sql = "INSERT INTO exam_security_logs 
                (user_id, exam_id, event_type, timestamp, event_data, screen_width, screen_height, is_fullscreen, ip_address, user_agent) 
                VALUES 
                (:user_id, :exam_id, :event_type, :timestamp, :event_data, :screen_width, :screen_height, :is_fullscreen, :ip_address, :user_agent)";
        $stmt = $conn->prepare($sql);
        
        // Préparer les paramètres
        $params = [
            ':user_id' => $user_id,
            ':exam_id' => $exam_id,
            ':event_type' => $data['event_type'],
            ':timestamp' => date('Y-m-d H:i:s', strtotime($data['timestamp'])),
            ':event_data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ':screen_width' => $data['screen_width'] ?? null,
            ':screen_height' => $data['screen_height'] ?? null,
            ':is_fullscreen' => $data['is_fullscreen'] ?? null,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Non défini',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Non défini'
        ];

        file_put_contents('security_debug.log', date('Y-m-d H:i:s') . " - Tentative d'insertion avec les paramètres:\n" . print_r($params, true) . "\n", FILE_APPEND);
        
        $stmt->execute($params);
        $lastId = $conn->lastInsertId();
        
        file_put_contents('security_debug.log', date('Y-m-d H:i:s') . " - Événement enregistré avec succès, ID: $lastId\n", FILE_APPEND);

        // Enregistrer les événements de sécurité supplémentaires
        if (isset($security_events[$data['event_type']])) {
            logSecurityEvent($conn, $user_id, $exam_id, $data['event_type'], $data);
        }

        echo json_encode(['success' => true, 'id' => $lastId]);

    } catch(PDOException $e) {
        $error = "Erreur SQL: " . $e->getMessage();
        file_put_contents('security_debug.log', date('Y-m-d H:i:s') . " - $error\n", FILE_APPEND);
        error_log($error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error]);
    }

} catch(PDOException $e) {
    $error = "Erreur SQL: " . $e->getMessage();
    file_put_contents('security_debug.log', date('Y-m-d H:i:s') . " - $error\n", FILE_APPEND);
    error_log($error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $error]);
}
?>
