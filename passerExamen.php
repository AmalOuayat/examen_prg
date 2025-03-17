<?php
// Configurer les paramètres de session AVANT de démarrer la session
ini_set('session.gc_maxlifetime', 3600); // Durée de vie de la session à 1 heure
ini_set('session.cookie_lifetime', 3600); // Durée de vie du cookie de session à 1 heure

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un étudiant
if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'etudiant') {
    die("Accès refusé. Vous devez être connecté en tant qu'étudiant.");
}

// Connexion à la base de données
$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si un examen est sélectionné
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $id = intval($_GET['id']);

        // Récupérer les détails de l'examen
        $stmt = $conn->prepare("SELECT * FROM examens3 WHERE id_ex = :id");
        $stmt->execute([':id' => $id]);
        $examen = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$examen) {
            die("Examen introuvable.");
        }

        // Vérifier si l'examen a commencé
        $currentTime = time();
        $examStartTime = strtotime($examen['heure_debut']);

        if ($currentTime < $examStartTime) {
            die("L'examen n'a pas encore commencé.");
        }

        // Vérifier si l'examen est terminé
        $examEndTime = strtotime($examen['heure_fin']);
        if ($currentTime > $examEndTime) {
            die("L'examen est terminé.");
        }

        // Récupérer les questions de l'examen
        $stmtQuestions = $conn->prepare("SELECT * FROM questions3 WHERE exam_id = :examen_id");
        $stmtQuestions->execute([':examen_id' => $id]);
        $questions = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);
    } else {
        die("Aucun examen sélectionné.");
    }
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passer l'examen</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        #timer {
            font-size: 24px;
            font-weight: bold;
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Examen : <?= htmlspecialchars($examen['titre']) ?></h1>
        <div id="timer">Temps restant : <span id="time">00:00</span></div>
        <form id="examForm" action="soumettre_examen.php" method="POST">
            <input type="hidden" name="examen_id" value="<?= htmlspecialchars($id) ?>">
            <?php foreach ($questions as $index => $question): ?>
                <div class="mb-4">
                    <h4><?= ($index + 1) . ". " . htmlspecialchars($question['texte']) ?></h4>
                    <?php if ($question['type'] === 'text'): ?>
                        <textarea name="reponses[<?= htmlspecialchars($question['id_q']) ?>]" class="form-control" rows="3" required></textarea>
                    <?php elseif ($question['type'] === 'qcm'): ?>
                        <?php
                        // Récupérer les options pour la question
                        $stmtOptions = $conn->prepare("SELECT * FROM options3 WHERE question_id = :question_id");
                        $stmtOptions->execute([':question_id' => $question['id_q']]);
                        $options = $stmtOptions->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php foreach ($options as $option): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="reponses[<?= htmlspecialchars($question['id_q']) ?>][]" 
                                       value="<?= htmlspecialchars($option['texte']) ?>">
                                <label class="form-check-label"><?= htmlspecialchars($option['texte']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($question['type'] === 'true_false'): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="reponses[<?= htmlspecialchars($question['id_q']) ?>]" value="vrai" required>
                            <label class="form-check-label">Vrai</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="reponses[<?= htmlspecialchars($question['id_q']) ?>]" value="faux" required>
                            <label class="form-check-label">Faux</label>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary">Soumettre</button>
        </form>

        <script>
            const dureeExamen = <?= $examen['duree'] * 60 ?>; // Convertir la durée en secondes
            let tempsRestant = dureeExamen;

            function updateTimer() {
                const minutes = Math.floor(tempsRestant / 60);
                const seconds = tempsRestant % 60;
                document.getElementById('time').textContent = 
                    `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

                if (tempsRestant <= 0) {
                    clearInterval(timerInterval);
                    alert("Temps écoulé ! L'examen sera soumis.");
                    submitExam(); // Soumettre automatiquement l'examen
                }
                tempsRestant--;
            }

            const timerInterval = setInterval(updateTimer, 1000);

            // Fonction pour soumettre automatiquement l'examen
            function submitExam() {
                document.getElementById('examForm').submit();
            }
        </script>
         <script>
        // Envoyer les dimensions de l'écran au serveur
        document.addEventListener('DOMContentLoaded', function() {
            const data = {
                screen_width: window.screen.width,
                screen_height: window.screen.height,
                is_fullscreen: document.fullscreenElement !== null
            };
            
            fetch('log_security_event.php?exam_id=<?= $_SESSION['exam_id'] ?? 0 ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ...data,
                    event_type: 'page_load',
                    timestamp: new Date().toISOString()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Erreur:', data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        });
    </script>
    <script>
        // Fonction pour enregistrer les événements de sécurité
        function logSecurityEvent(eventType) {
            fetch('log_security_event.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    event_type: eventType,
                    timestamp: new Date().toISOString()
                })
            });
        }

        // Désactiver le clic droit
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            alert("Le clic droit est désactivé pendant l'examen.");
            logSecurityEvent('right_click_attempt');
        });

        // Désactiver les raccourcis clavier
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.altKey || e.metaKey) {
                if (e.key === 'c' || e.key === 'v' || e.key === 'x' || e.key === 'a' || 
                    e.key === 'Tab' || e.key === 'r' || e.key === 'p') {
                    e.preventDefault();
                    alert("Les raccourcis clavier sont désactivés pendant l'examen.");
                    logSecurityEvent('keyboard_shortcut_attempt');
                }
            }
        });

        // Détecter le changement de fenêtre
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                alert("ATTENTION : Vous avez quitté la page d'examen !");
                logSecurityEvent('window_change');
            }
        });

        // Empêcher de quitter la page
        window.onbeforeunload = function(e) {
            logSecurityEvent('page_leave_attempt');
            return "Attention : Quitter cette page mettra fin à votre examen. Êtes-vous sûr ?";
        };

        // Désactiver la sélection de texte
        document.addEventListener('selectstart', (e) => {
            e.preventDefault();
            logSecurityEvent('text_selection_attempt');
        });

        // Désactiver le copier-coller
        document.addEventListener('copy', (e) => {
            e.preventDefault();
            alert("La copie est désactivée pendant l'examen.");
            logSecurityEvent('copy_attempt');
        });

        document.addEventListener('paste', (e) => {
            e.preventDefault();
            alert("Le collage est désactivé pendant l'examen.");
            logSecurityEvent('paste_attempt');
        });

        document.addEventListener('cut', (e) => {
            e.preventDefault();
            alert("Le couper est désactivé pendant l'examen.");
            logSecurityEvent('cut_attempt');
        });

        // Forcer le mode plein écran
        function requestFullscreen() {
            const elem = document.documentElement;
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            }
        }

        // Détecter la sortie du mode plein écran
        document.addEventListener('fullscreenchange', () => {
            if (!document.fullscreenElement) {
                alert("Veuillez rester en mode plein écran pendant l'examen.");
                logSecurityEvent('fullscreen_exit');
                requestFullscreen();
            }
        });

        // Timer pour l'examen
        const examDuration = <?= $examen['duree'] * 60 ?>; // Convertir en secondes
        let timeLeft = examDuration;

        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timer').textContent = 
                `Temps restant : ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

            if (timeLeft <= 0) {
                logSecurityEvent('exam_timeout');
                document.getElementById('examForm').submit();
            } else {
                timeLeft--;
                setTimeout(updateTimer, 1000);
            }
        }

        // Initialiser au chargement de la page
        document.addEventListener('DOMContentLoaded', () => {
            requestFullscreen();
            updateTimer();
        });
    </script><script>
        // Fonction pour formater le temps restant
        function formatTime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        }

        // Configuration du timer
        const endTime = new Date('<?php echo $temps_examen['date_fin']; ?>').getTime();
        const timerElement = document.getElementById('timer');

        function updateTimer() {
            const now = new Date().getTime();
            const timeLeft = Math.max(0, Math.floor((endTime - now) / 1000));

            if (timeLeft === 0) {
                submitForm(true);
            } else {
                timerElement.textContent = formatTime(timeLeft);
            }
        }

        // Mettre à jour le timer chaque seconde
        setInterval(updateTimer, 1000);
        updateTimer();

        // Fonction pour sauvegarder automatiquement
        function autoSave() {
            const form = document.getElementById('examForm');
            const formData = new FormData(form);

            fetch('save_answers.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const saveStatus = document.getElementById('saveStatus');
                saveStatus.style.display = 'block';
                
                if (data.success) {
                    saveStatus.textContent = 'Réponses sauvegardées';
                    saveStatus.className = 'save-status success';
                } else {
                    saveStatus.textContent = 'Erreur lors de la sauvegarde';
                    saveStatus.className = 'save-status error';
                }

                setTimeout(() => {
                    saveStatus.style.display = 'none';
                }, 3000);
            });
        }

        // Sauvegarder toutes les 30 secondes
        setInterval(autoSave, 30000);

        // Sauvegarder avant de quitter la page
        window.onbeforeunload = function() {
            autoSave();
            return null;
        };

        // Fonction pour soumettre le formulaire
        function submitForm(isAutoSubmit = false) {
            const form = document.getElementById('examForm');
            const formData = new FormData(form);

            fetch('soumettre_examen.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (isAutoSubmit) {
                        alert("Le temps est écoulé. Vos réponses ont été soumises automatiquement.");
                    }
                    window.location.href = 'examens_list.php';
                } else {
                    alert("Erreur lors de la soumission : " + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert("Une erreur est survenue lors de la soumission de l'examen.");
            });
        }

        // Désactiver le clic droit
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });

        // Désactiver les raccourcis clavier courants
        document.addEventListener('keydown', function(e) {
            if (
                (e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 'x')) || // Copier, Coller, Couper
                (e.altKey && e.key === 'Tab') || // Alt+Tab
                (e.key === 'PrintScreen') || // Capture d'écran
                (e.key === 'F12') // Outils de développement
            ) {
                e.preventDefault();
            }
        });
    </script>
    
    <script>
        // Configuration initiale de la sécurité
        let initialFullscreenState = document.fullscreenElement !== null;
        let tabSwitchCount = 0;
        let lastVisibilityState = document.visibilityState;

        // Fonction pour envoyer un événement de sécurité
        function sendSecurityEvent(eventType, additionalData = {}) {
            const eventData = {
                event_type: eventType,
                timestamp: new Date().toISOString(),
                screen_width: window.screen.width,
                screen_height: window.screen.height,
                is_fullscreen: document.fullscreenElement !== null,
                ...additionalData
            };

            fetch('log_security_event.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(eventData)
            });
        }

        // Événement de changement de plein écran
        document.addEventListener('fullscreenchange', function() {
            const currentFullscreenState = document.fullscreenElement !== null;
            if (currentFullscreenState !== initialFullscreenState) {
                sendSecurityEvent('fullscreen_exit', {
                    previous_state: initialFullscreenState,
                    current_state: currentFullscreenState
                });
                initialFullscreenState = currentFullscreenState;
            }
        });

        // Événement de changement de visibilité de la page
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState !== lastVisibilityState) {
                tabSwitchCount++;
                sendSecurityEvent('tab_switch', {
                    previous_state: lastVisibilityState,
                    current_state: document.visibilityState,
                    switch_count: tabSwitchCount
                });
                lastVisibilityState = document.visibilityState;
            }
        });

        // Événement de copier-coller
        document.addEventListener('copy', function(e) {
            sendSecurityEvent('copy_paste_detected', {
                action: 'copy'
            });
        });

        document.addEventListener('paste', function(e) {
            sendSecurityEvent('copy_paste_detected', {
                action: 'paste'
            });
        });

        // Événement de redimensionnement de la fenêtre
        let lastWindowSize = { width: window.innerWidth, height: window.innerHeight };
        window.addEventListener('resize', function() {
            const currentSize = { width: window.innerWidth, height: window.innerHeight };
            const sizeDiff = Math.abs(currentSize.width - lastWindowSize.width) + 
                             Math.abs(currentSize.height - lastWindowSize.height);
            
            if (sizeDiff > 200) {  // Seuil de redimensionnement significatif
                sendSecurityEvent('suspicious_browser_resize', {
                    previous_size: lastWindowSize,
                    current_size: currentSize,
                    size_difference: sizeDiff
                });
            }
            lastWindowSize = currentSize;
        });

        // Vérification des onglets multiples
        let openTabCount = 1;
        window.addEventListener('focus', function() {
            openTabCount++;
            if (openTabCount > 2) {  // Plus de 2 onglets/fenêtres
                sendSecurityEvent('multiple_browser_tabs', {
                    tab_count: openTabCount
                });
            }
        });
    </script>
    </div>
</body>
</html>