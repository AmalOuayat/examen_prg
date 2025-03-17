<?php
session_start();

$response = ['status' => 'disconnected'];

if (isset($_SESSION['user']) && $_SESSION['user']['roleu'] === 'etudiant') {
    $response['status'] = 'connected';
}

echo json_encode($response);
?>