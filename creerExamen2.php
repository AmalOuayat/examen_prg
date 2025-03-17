<?php
session_start();

$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les groupes depuis la base de données
    $stmtGroups = $conn->prepare("SELECT id_g, nom FROM groupes");
    $stmtGroups->execute();
    $groupes = $stmtGroups->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        try {
            // Récupérer les données POST
            $titre = trim($_POST['titre']);
            $heure_debut = trim($_POST['heure_debut']);
            $heure_fin = trim($_POST['heure_fin']);
            $questions = $_POST['questions'];
            $type_questions = $_POST['type_question'];
            $group_id = intval($_POST['group_id']);

            // Validation des données
            if (empty($titre) || empty($heure_debut) || empty($heure_fin) || empty($questions)) {
                throw new Exception("Tous les champs sont obligatoires.");
            }

            if (strtotime($heure_debut) >= strtotime($heure_fin)) {
                throw new Exception("L'heure de début doit être avant l'heure de fin.");
            }

            // Calcul de la durée en minutes
            $duree = (strtotime($heure_fin) - strtotime($heure_debut)) / 60;

            // Début de la transaction
            $conn->beginTransaction();

            // Insérer l'examen
            $stmtExamen = $conn->prepare("
                INSERT INTO examens2 (titre, heure_debut, heure_fin, duree, formateur_id, group_id) 
                VALUES (:titre, :heure_debut, :heure_fin, :duree, :formateur_id, :group_id)
            ");
            $stmtExamen->execute([
                ':titre' => $titre,
                ':heure_debut' => $heure_debut,
                ':heure_fin' => $heure_fin,
                ':duree' => $duree,
                ':formateur_id' => $_SESSION['user']['id'],
                ':group_id' => $group_id
            ]);

            $examen_id = $conn->lastInsertId();

            // Insérer les questions et leurs options (si QCM)
            foreach ($questions as $index => $question) {
                $stmtQuestion = $conn->prepare("
                    INSERT INTO questions2 (examen_id, texte_question, type_question) 
                    VALUES (:examen_id, :texte_question, :type_question)
                ");
                $stmtQuestion->execute([
                    ':examen_id' => $examen_id,
                    ':texte_question' => trim($question),
                    ':type_question' => $type_questions[$index]
                ]);

                $question_id = $conn->lastInsertId();

                if ($type_questions[$index] === 'qcm' && isset($_POST['options'][$index])) {
                    foreach ($_POST['options'][$index] as $option_index => $option_text) {
                        $is_correct = isset($_POST['correct'][$index][$option_index]) ? 1 : 0;

                        $stmtOption = $conn->prepare("
                            INSERT INTO reponses2 (question_id, texte_reponse, is_correct) 
                            VALUES (:question_id, :texte_reponse, :is_correct)
                        ");
                        $stmtOption->execute([
                            ':question_id' => $question_id,
                            ':texte_reponse' => trim($option_text),
                            ':is_correct' => $is_correct
                        ]);
                    }
                }
            }

            // Valider la transaction
            $conn->commit();
            echo "Examen créé avec succès.";
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $conn->rollBack();
            die("Erreur : " . $e->getMessage());
        }
    }
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
