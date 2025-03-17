<?php
session_start();
include 'db.php';

// Vérifier si l'utilisateur est connecté et est un étudiant
if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'etudiant') {
    die("Accès refusé. Vous devez être connecté en tant qu'étudiant.");
}

// Récupérer l'ID de l'utilisateur connecté
$utilisateur_id = $_SESSION['user']['id'];

// Récupérer l'ID de l'étudiant à partir de la table `etudiants`
$stmtEtudiant = $conn->prepare("SELECT id_etudiant FROM etudiants WHERE utilisateur_id = :utilisateur_id");
$stmtEtudiant->execute([':utilisateur_id' => $utilisateur_id]);
$etudiant = $stmtEtudiant->fetch(PDO::FETCH_ASSOC);

if (!$etudiant) {
    die("Étudiant non trouvé.");
}

$etudiant_id = $etudiant['id_etudiant']; // ID de l'étudiant

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Récupérer les données du formulaire
    $examen_id = $_POST['examen_id'];
    $reponses = $_POST['reponses'];

    try {
        // Commencer une transaction
        $conn->beginTransaction();

        // Insérer ou mettre à jour les réponses de l'étudiant
        foreach ($reponses as $question_id => $reponse) {
            if (is_array($reponse)) {
                $reponse = implode(", ", $reponse); // Convertir les réponses QCM en chaîne
            }

            // Vérifier si la réponse existe déjà
            $stmtCheck = $conn->prepare("SELECT * FROM reponses_etudiants2 WHERE examen_id = :examen_id AND etudiant_id = :etudiant_id AND question_id = :question_id");
            $stmtCheck->execute([
                ':examen_id' => $examen_id,
                ':etudiant_id' => $etudiant_id, // Utiliser l'ID de l'étudiant
                ':question_id' => $question_id
            ]);

            if ($stmtCheck->rowCount() > 0) {
                // Mettre à jour la réponse existante
                $stmtUpdate = $conn->prepare("UPDATE reponses_etudiants2 SET reponse = :reponse WHERE examen_id = :examen_id AND etudiant_id = :etudiant_id AND question_id = :question_id");
                $stmtUpdate->execute([
                    ':reponse' => $reponse,
                    ':examen_id' => $examen_id,
                    ':etudiant_id' => $etudiant_id, // Utiliser l'ID de l'étudiant
                    ':question_id' => $question_id
                ]);
            } else {
                // Insérer une nouvelle réponse
                $stmtInsert = $conn->prepare("INSERT INTO reponses_etudiants2 (examen_id, etudiant_id, question_id, reponse) VALUES (:examen_id, :etudiant_id, :question_id, :reponse)");
                $stmtInsert->execute([
                    ':examen_id' => $examen_id,
                    ':etudiant_id' => $etudiant_id, // Utiliser l'ID de l'étudiant
                    ':question_id' => $question_id,
                    ':reponse' => $reponse
                ]);
            }
        }

        // Valider la transaction
        $conn->commit();
        echo "Réponses sauvegardées avec succès.";
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $conn->rollBack();
        die("Erreur lors de la sauvegarde : " . $e->getMessage());
    }
}
?>