<?php
class CorrectionAutomatique {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    private function log($message, $data = null) {
        $logFile = __DIR__ . '/correction.log';
        $logMessage = date('Y-m-d H:i:s') . " - " . $message . "\n";
        if ($data !== null) {
            $logMessage .= print_r($data, true) . "\n";
        }
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    private function normalizeText($text) {
        // Supprimer les espaces en début et fin
        $text = trim($text);
        // Convertir en minuscules
        $text = strtolower($text);
        // Supprimer les accents
        $text = str_replace(
            array('é', 'è', 'ê', 'ë', 'à', 'â', 'î', 'ï', 'ô', 'û', 'ü', 'ù'),
            array('e', 'e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'u', 'u', 'u'),
            $text
        );
        return $text;
    }
    
    private function isTrue($text) {
        $normalized = $this->normalizeText($text);
        $this->log("Vérification isTrue pour '$text' (normalisé: '$normalized')");
        $result = in_array($normalized, ['vrai', 'true', '1', 'v', 't', 'oui', 'yes']);
        $this->log("Résultat isTrue: " . ($result ? 'VRAI' : 'FAUX'));
        return $result;
    }
    
    public function corrigerExamen($etudiant_id, $examen_id) {
        $this->log("\n\n=== DÉBUT DE LA CORRECTION ===");
        $this->log("Étudiant ID: $etudiant_id, Examen ID: $examen_id");
        
        // Récupérer toutes les questions de l'examen avec leurs détails
        $stmt = $this->conn->prepare("
            SELECT 
                q.*, 
                re.id_re, 
                re.reponse as reponse_etudiant,
                q.type as type_question,
                q.note_max as points_max
            FROM questions3 q
            LEFT JOIN reponses_etudiants2 re ON q.id_q = re.question_id 
                AND re.etudiant_id = ? AND re.examen_id = ?
            WHERE q.exam_id = ?
        ");
        $stmt->execute([$etudiant_id, $examen_id, $examen_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->log("Questions trouvées:", $questions);
        
        $total_points = 0;
        $points_max_total = 0;
        
        foreach ($questions as $question) {
            $note = 0;
            $this->log("\n=== Traitement de la question ID: {$question['id_q']} ===");
            $this->log("Type: {$question['type']}, Points max: {$question['note_max']}");
            
            if (isset($question['reponse_etudiant'])) {
                $this->log("Réponse étudiant brute: '{$question['reponse_etudiant']}'");
                $this->log("Type de la réponse: " . gettype($question['reponse_etudiant']));
                $this->log("Longueur de la réponse: " . strlen($question['reponse_etudiant']));
                $this->log("Valeur ASCII de chaque caractère:");
                for ($i = 0; $i < strlen($question['reponse_etudiant']); $i++) {
                    $this->log("- Position $i: " . ord($question['reponse_etudiant'][$i]));
                }
            } else {
                $this->log("Réponse étudiant: Non répondu");
            }
            
            // Ne traiter que les questions QCM et true/false
            if (!in_array($question['type'], ['qcm', 'true_false'])) {
                $this->log("Question de type {$question['type']} ignorée (pas de correction automatique)");
                continue;
            }
            
            if ($question['type'] === 'true_false') {
                if (isset($question['reponse_etudiant'])) {
                    // Pour les questions vrai/faux, on cherche l'option correcte
                    $stmt = $this->conn->prepare("
                        SELECT texte, correct 
                        FROM options3 
                        WHERE question_id = ? AND correct = 1
                        LIMIT 1
                    ");
                    $stmt->execute([$question['id_q']]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result) {
                        // Normaliser la réponse de l'étudiant et la réponse correcte
                        $reponse_etudiant = $this->isTrue($question['reponse_etudiant']);
                        $reponse_correcte = $this->isTrue($result['texte']);
                        
                        $this->log("Comparaison Vrai/Faux détaillée:");
                        $this->log("- Réponse étudiant originale: '{$question['reponse_etudiant']}'");
                        $this->log("- Réponse correcte dans options3: '{$result['texte']}'");
                        $this->log("- Réponse étudiant convertie: " . ($reponse_etudiant ? 'VRAI' : 'FAUX'));
                        $this->log("- Réponse correcte convertie: " . ($reponse_correcte ? 'VRAI' : 'FAUX'));
                        $this->log("- Comparaison: " . ($reponse_etudiant === $reponse_correcte ? 'ÉGAL' : 'DIFFÉRENT'));
                        
                        if ($reponse_etudiant === $reponse_correcte) {
                            $note = $question['note_max'];
                            $this->log("✓ Réponse correcte! Note attribuée: $note");
                        } else {
                            $this->log("✗ Réponse incorrecte. Note: 0");
                        }
                    } else {
                        $this->log("⚠ Pas d'option correcte trouvée pour cette question vrai/faux");
                    }
                } else {
                    $this->log("⚠ Pas de réponse fournie pour cette question");
                }
            } 
            else if ($question['type'] === 'qcm') {
                if (!isset($question['reponse_etudiant'])) {
                    $this->log("⚠ Pas de réponse fournie pour cette question QCM");
                    continue;
                }
                
                // Récupérer les options correctes avec leur texte
                $stmt = $this->conn->prepare("
                    SELECT texte, correct
                    FROM options3
                    WHERE question_id = ?
                ");
                $stmt->execute([$question['id_q']]);
                $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $this->log("Options disponibles:", $options);
                
                // Séparer les réponses de l'étudiant
                $student_answers = array_map('trim', explode(',', $question['reponse_etudiant']));
                $student_answers = array_map([$this, 'normalizeText'], $student_answers);
                
                // Compter les bonnes réponses
                $correct_count = 0;
                $wrong_answers = [];
                $total_correct = 0;
                
                foreach ($options as $option) {
                    if ($option['correct']) {
                        $total_correct++;
                        $normalized_option = $this->normalizeText($option['texte']);
                        if (in_array($normalized_option, $student_answers)) {
                            $correct_count++;
                        }
                    }
                }
                
                // Vérifier les réponses incorrectes
                foreach ($student_answers as $answer) {
                    $found = false;
                    foreach ($options as $option) {
                        if ($this->normalizeText($option['texte']) === $answer && $option['correct']) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $wrong_answers[] = $answer;
                    }
                }
                
                $this->log("Analyse des réponses:");
                $this->log("- Nombre de bonnes réponses: $correct_count / $total_correct");
                $this->log("- Réponses incorrectes:", $wrong_answers);
                
                // Attribution des points
                if ($correct_count === $total_correct && empty($wrong_answers)) {
                    $note = $question['note_max'];
                    $this->log("✓ Toutes les réponses sont correctes! Note complète: $note");
                }
                else if (empty($wrong_answers) && $correct_count > 0) {
                    $note = ($correct_count / $total_correct) * $question['note_max'];
                    $this->log("~ Note partielle attribuée: $note ($correct_count/$total_correct correct)");
                } else {
                    $this->log("✗ Réponses incorrectes présentes. Note: 0");
                }
            }
            
            $points_max_total += $question['note_max'];
            $total_points += $note;
            
            // Mettre à jour la note
            if ($question['id_re']) {
                $stmt = $this->conn->prepare("
                    UPDATE reponses_etudiants2 
                    SET note = ?
                    WHERE id_re = ?
                ");
                $stmt->execute([$note, $question['id_re']]);
                $this->log("Note mise à jour pour id_re={$question['id_re']}: $note");
            } else {
                $this->log("⚠ Pas d'ID de réponse trouvé, impossible de mettre à jour la note");
            }
        }
        
        $this->log("\n=== RÉSUMÉ DE LA CORRECTION ===");
        $this->log("Total des points: $total_points / $points_max_total");
        $this->log("Fin de la correction\n");
        
        return true;
    }
}
?>
