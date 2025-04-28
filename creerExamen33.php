<?php
session_start();
$host = "localhost";
$dbname = "examens_db"; 
$username = "root";
$password = "DD202";

// Activer le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Fonction pour journaliser les données
function log_debug($message) {
    file_put_contents('debug_exam.log', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

log_debug("Début du script");

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    log_debug("Connexion à la base de données réussie");
    
    if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'formateur') {
        log_debug("Accès refusé - Utilisateur non authentifié ou non formateur");
        die("Accès refusé. Vous devez être connecté en tant que formateur.");
    }
    
    // Récupération des données de base de l'examen
    $titre = $_POST['titre'] ?? '';
    $heure_debut = $_POST['heure_debut'] ?? '';
    $heure_fin = $_POST['heure_fin'] ?? '';
    $duree = $_POST['duree'] ?? 0;
    $groupe_id = $_POST['groupe_id'] ?? 0;
    $module_id = $_POST['module_id'] ?? 0;
    $nombre_variantes = $_POST['nombre_variantes'] ?? 1;
    
    // Récupération des données des variantes et questions
    $nom_variantes = $_POST['nom_variante'] ?? [];
    $questions = $_POST['questions'] ?? [];
    $type_question = $_POST['type_question'] ?? [];
    $note_max = $_POST['note_max'] ?? [];
    $options = $_POST['options'] ?? [];
    $correct = $_POST['correct'] ?? [];

    // Journaliser les données reçues
    log_debug("Données du formulaire: " . json_encode($_POST));
    
    // Vérifier si les données essentielles sont présentes
    if (empty($titre) || empty($heure_debut) || empty($heure_fin) || empty($duree) || empty($groupe_id)) {
        log_debug("Données manquantes dans le formulaire");
        die("Veuillez remplir tous les champs obligatoires.");
    }

    

    // Insérer l'examen dans examens3
    $stmtExam = $conn->prepare("
        INSERT INTO examens3 (titre, heure_debut, heure_fin, duree, groupe_id, formateur_id, module_id, statut, has_variantes, nombre_variantes)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'indisponible', ?, ?)
    ");
    $stmtExam->execute([
        $titre, 
        $heure_debut, 
        $heure_fin, 
        $duree, 
        $groupe_id, 
        $_SESSION['user']['id'], 
        $module_id, 
        ($nombre_variantes > 1) ? 1 : 0, 
        $nombre_variantes
    ]);
    $exam_id = $conn->lastInsertId();
    log_debug("Examen créé avec ID: " . $exam_id);

    // Tableau pour stocker les IDs de variantes
    $variantes_ids = [];

    // Parcourir les variantes
    foreach ($nom_variantes as $varianteIndex => $nom_variante) {
        log_debug("Traitement de la variante $varianteIndex: $nom_variante");
        
        // Insérer la variante
        $stmtVariante = $conn->prepare("INSERT INTO variantes (exam_id, nom_variante) VALUES (?, ?)");
        $stmtVariante->execute([$exam_id, $nom_variante]);
        $variante_id = $conn->lastInsertId();
        $variantes_ids[] = $variante_id;
        log_debug("Variante insérée avec ID: " . $variante_id);

        // Pour chaque question de cette variante
        if (isset($questions[$varianteIndex]) && is_array($questions[$varianteIndex])) {
            log_debug("Nombre de questions pour variante $varianteIndex: " . count($questions[$varianteIndex]));
            
            foreach ($questions[$varianteIndex] as $questionIndex => $question) {
                $type = $type_question[$varianteIndex][$questionIndex] ?? 'text';
                $max = $note_max[$varianteIndex][$questionIndex] ?? 1;
                
                log_debug("Question $questionIndex: $question (type: $type, note_max: $max)");
                
                // Insérer la question
                $stmtQuestion = $conn->prepare("
                    INSERT INTO questions3 (exam_id, variante_id, texte, type, note_max)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtQuestion->execute([$exam_id, $variante_id, $question, $type, $max]);
                $question_id = $conn->lastInsertId();
                log_debug("Question insérée avec ID: " . $question_id);

                // Pour les questions QCM et Vrai/Faux, insérer les options
                if ($type === "qcm") {
                    if (isset($options[$varianteIndex][$questionIndex]) && is_array($options[$varianteIndex][$questionIndex])) {
                        log_debug("Traitement des options QCM: " . count($options[$varianteIndex][$questionIndex]));
                        
                        foreach ($options[$varianteIndex][$questionIndex] as $optionIndex => $optionText) {
                            $isCorrect = isset($correct[$varianteIndex][$questionIndex][$optionIndex]) ? 1 : 0;
                            log_debug("Option $optionIndex: $optionText (correct: $isCorrect)");
                            
                            $stmtOption = $conn->prepare("
                                INSERT INTO options3 (question_id, texte, correct)
                                VALUES (?, ?, ?)
                            ");
                            $stmtOption->execute([$question_id, $optionText, $isCorrect]);
                            log_debug("Option insérée avec ID: " . $conn->lastInsertId());
                        }
                    }
                } elseif ($type === "true_false") {
                    log_debug("Traitement des options Vrai/Faux");
                    
                    $correctAnswer = $correct[$varianteIndex][$questionIndex] ?? null;
                    log_debug("Réponse correcte: " . ($correctAnswer ?? "non définie"));
                    
                    $stmtOption = $conn->prepare("
                        INSERT INTO options3 (question_id, texte, correct)
                        VALUES (?, ?, ?)
                    ");
                    // Insérer Vrai
                    $vraiCorrect = ($correctAnswer === "Vrai") ? 1 : 0;
                    $stmtOption->execute([$question_id, "Vrai", $vraiCorrect]);
                    log_debug("Option 'Vrai' insérée (correct: $vraiCorrect)");
                    
                    // Insérer Faux
                    $fauxCorrect = ($correctAnswer === "Faux") ? 1 : 0;
                    $stmtOption->execute([$question_id, "Faux", $fauxCorrect]);
                    log_debug("Option 'Faux' insérée (correct: $fauxCorrect)");
                }
            }
        } else {
            log_debug("Pas de questions pour la variante $varianteIndex");
        }
    }
    
    // Récupération des étudiants du groupe
    $stmtEtudiants = $conn->prepare("
        SELECT e.id_etudiant 
        FROM etudiants e
        JOIN etudiants_groupes eg ON e.id_etudiant = eg.etudiant_id
        WHERE eg.id_groupe = ?
    ");
    $stmtEtudiants->execute([$groupe_id]);
    $etudiants = $stmtEtudiants->fetchAll(PDO::FETCH_ASSOC);
    log_debug("Nombre d'étudiants trouvés: " . count($etudiants));
    
    // Attribution des variantes aux étudiants
    if (count($etudiants) > 0 && count($variantes_ids) > 0) {
        log_debug("Attribution des variantes aux étudiants...");
        
        $stmtAttribution = $conn->prepare("
            INSERT INTO etudiant_examen (id_etudiant, id_examen, id_variante, statut)
            VALUES (?, ?, ?, 'en_cours')
        ");
        
        foreach ($etudiants as $index => $etudiant) {
            $varianteIndex = $index % count($variantes_ids);
            $variante_id = $variantes_ids[$varianteIndex];
            
            log_debug("Attribution à étudiant ID " . $etudiant['id_etudiant'] . " de la variante ID " . $variante_id);
            
            $stmtAttribution->execute([
                $etudiant['id_etudiant'],
                $exam_id,
                $variante_id
            ]);
        }
        log_debug("Attribution des variantes terminée");
    } else {
        log_debug("Pas d'étudiants ou pas de variantes à attribuer");
    }

    echo "<div style='padding: 20px; background-color: #d4edda; border-radius: 5px; margin: 20px;'>
            <h2>Examen créé avec succès!</h2>
            <p>L'examen <strong>\"$titre\"</strong> a été créé avec $nombre_variantes variante(s).</p>
            <p>Vous pouvez consulter le fichier <code>debug_exam.log</code> pour plus de détails.</p>
            <a href='dashboard_formateur.php' class='btn btn-primary'>Retour au tableau de bord</a>
          </div>";
    log_debug("Script terminé avec succès");
    
} catch (PDOException $e) {
    $errorMessage = "Erreur : " . $e->getMessage();
    log_debug("ERREUR CRITIQUE: " . $errorMessage);
    echo "<div style='padding: 20px; background-color: #f8d7da; border-radius: 5px; margin: 20px;'>
            <h2>Erreur lors de la création de l'examen</h2>
            <p>$errorMessage</p>
            <a href='javascript:history.back()' class='btn btn-secondary'>Retour au formulaire</a>
          </div>";
}
?>