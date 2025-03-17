<?php
session_start();
include 'db.php';
require_once 'correction_automatique.php';

// Vérifier si l'utilisateur est connecté et est un étudiant
if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'etudiant') {
    die(json_encode(['success' => false, 'message' => "Accès refusé. Vous devez être connecté en tant qu'étudiant."]));
}

// Récupérer l'ID de l'utilisateur connecté
$utilisateur_id = $_SESSION['user']['id'];

// Récupérer l'ID de l'étudiant à partir de la table `etudiants`
$stmtEtudiant = $conn->prepare("SELECT id_etudiant FROM etudiants WHERE utilisateur_id = :utilisateur_id");
$stmtEtudiant->execute([':utilisateur_id' => $utilisateur_id]);
$etudiant = $stmtEtudiant->fetch(PDO::FETCH_ASSOC);

if (!$etudiant) {
    die(json_encode(['success' => false, 'message' => "Étudiant non trouvé."]));
}

$etudiant_id = $etudiant['id_etudiant'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['examen_id']) && isset($_POST['reponses'])) {
    // Récupérer les données du formulaire
    $examen_id = $_POST['examen_id'];
    $reponses = $_POST['reponses'];

    try {
        // Commencer une transaction
        $conn->beginTransaction();

        // Insérer ou mettre à jour les réponses de l'étudiant
        foreach ($reponses as $question_id => $reponse) {
            if (is_array($reponse)) {
                $reponse = implode(",", $reponse); // Convertir les réponses QCM en chaîne
            }

            $stmtCheck = $conn->prepare("
                SELECT * FROM reponses_etudiants2 
                WHERE examen_id = :examen_id 
                AND etudiant_id = :etudiant_id 
                AND question_id = :question_id
            ");
            $stmtCheck->execute([
                ':examen_id' => $examen_id,
                ':etudiant_id' => $etudiant_id,
                ':question_id' => $question_id
            ]);

            if ($stmtCheck->rowCount() > 0) {
                $stmtUpdate = $conn->prepare("
                    UPDATE reponses_etudiants2 
                    SET reponse = :reponse 
                    WHERE examen_id = :examen_id 
                    AND etudiant_id = :etudiant_id 
                    AND question_id = :question_id
                ");
                $stmtUpdate->execute([
                    ':reponse' => $reponse,
                    ':examen_id' => $examen_id,
                    ':etudiant_id' => $etudiant_id,
                    ':question_id' => $question_id
                ]);
            } else {
                $stmtInsert = $conn->prepare("
                    INSERT INTO reponses_etudiants2 
                    (examen_id, etudiant_id, question_id, reponse) 
                    VALUES (:examen_id, :etudiant_id, :question_id, :reponse)
                ");
                $stmtInsert->execute([
                    ':examen_id' => $examen_id,
                    ':etudiant_id' => $etudiant_id,
                    ':question_id' => $question_id,
                    ':reponse' => $reponse
                ]);
            }
        }

        // Effectuer la correction automatique
        $correction = new CorrectionAutomatique($conn);
        $correction->corrigerExamen($etudiant_id, $examen_id);

        // Valider la transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => "Examen soumis et corrigé avec succès."
        ]);
        
    } catch (Exception $e) {
        // Vérifier si une transaction est active avant de faire un rollback
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo json_encode([
            'success' => false, 
            'message' => "Erreur lors de la soumission : " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => "Données de formulaire manquantes ou méthode non autorisée"
    ]);
}
?>