<?php
session_start();

$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si l'utilisateur est connecté et est un formateur
    if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'formateur') {
        die("Accès refusé. Vous devez être connecté en tant que formateur.");
    }

    // Récupérer les données du formulaire
    $titre = $_POST['titre'];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin = $_POST['heure_fin'];
    $duree = $_POST['duree'];
    $groupe_id = $_POST['groupe_id'];
    $module_id = $_POST['module_id'];
    $nom_variantes = $_POST['nom_variante'];
    $questions = $_POST['questions'] ?? [];
    $type_question = $_POST['type_question'] ?? [];
    $note_max = $_POST['note_max'] ?? [];
    $options = $_POST['options'] ?? [];
    $correct = $_POST['correct'] ?? [];

    // Insérer l'examen dans la table examens3
    $stmt = $conn->prepare("
        INSERT INTO examens3 (titre, heure_debut, heure_fin, duree, groupe_id, formateur_id, module_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$titre, $heure_debut, $heure_fin, $duree, $groupe_id, $_SESSION['user']['id'], $module_id]);
    $exam_id = $conn->lastInsertId();

    // Insérer les variantes et leurs questions
    foreach ($nom_variantes as $varianteIndex => $nom_variante) {
        $stmtVariante = $conn->prepare("
            INSERT INTO variantes (exam_id, nom_variante) 
            VALUES (?, ?)
        ");
        $stmtVariante->execute([$exam_id, $nom_variante]);
        $variante_id = $conn->lastInsertId();

        // Vérifier que $questions[$varianteIndex] est un tableau
        if (!isset($questions[$varianteIndex]) || !is_array($questions[$varianteIndex])) {
            // S'il n'est pas défini ou n'est pas un tableau, passer à la variante suivante
            continue;
        }

        // Insérer les questions pour cette variante
        foreach ($questions[$varianteIndex] as $questionIndex => $question) {
            $type = $type_question[$varianteIndex][$questionIndex];
            $note = $note_max[$varianteIndex][$questionIndex];

            $stmtQuestion = $conn->prepare("
                INSERT INTO questions3 (exam_id, variante_id, texte, type, note_max) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtQuestion->execute([$exam_id, $variante_id, $question, $type, $note]);
            $question_id = $conn->lastInsertId();

            // Insérer les options pour les questions QCM ou Vrai/Faux
            if ($type === "qcm" && isset($options[$varianteIndex][$questionIndex])) {
                foreach ($options[$varianteIndex][$questionIndex] as $optionIndex => $optionText) {
                    $isCorrect = in_array($optionIndex + 1, $correct[$varianteIndex][$questionIndex] ?? []) ? 1 : 0;
                    $stmtOption = $conn->prepare("
                        INSERT INTO options3 (question_id, texte, correct) 
                        VALUES (?, ?, ?)
                    ");
                    $stmtOption->execute([$question_id, $optionText, $isCorrect]);
                }
            } elseif ($type === "true_false" && isset($correct[$varianteIndex][$questionIndex])) {
                $correctAnswer = $correct[$varianteIndex][$questionIndex][0];
                $stmtOption = $conn->prepare("
                    INSERT INTO options3 (question_id, texte, correct) 
                    VALUES (?, ?, ?)
                ");
                $stmtOption->execute([$question_id, $correctAnswer, 1]);
            }
        }
    }

    echo "Examen créé avec succès !";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>