<?php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'etudiant') {
    header('Location: login.php');
    exit();
}

$host = "localhost";
$username = "root";
$password = "DD202";
$dbname = "examens_db";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $etudiant_id = $_SESSION['user']['id'];
    
    // Récupérer l'ID de l'examen depuis l'URL
    $examen_id = isset($_GET['examen_id']) ? intval($_GET['examen_id']) : 0;
    
    if ($examen_id === 0) {
        die("ID de l'examen non spécifié.");
    }

    // Vérifier si l'étudiant appartient au bon groupe et si l'examen est disponible
    $stmt = $conn->prepare("
        SELECT e.*, g.nom as groupe_nom 
        FROM examens3 e 
        JOIN groupes g ON e.groupe_id = g.id_g
        JOIN etudiants_groupes eg ON g.id_g = eg.id_groupe
        JOIN etudiants et ON eg.etudiant_id = et.id_etudiant
        WHERE e.id_ex = ? 
        AND et.utilisateur_id = ?
        AND NOW() BETWEEN e.heure_debut AND e.heure_fin
    ");
    $stmt->execute([$examen_id, $etudiant_id]);
    $examen = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$examen) {
        die("Vous n'avez pas accès à cet examen ou il n'est pas encore disponible.");
    }

    // Vérifier si l'étudiant a déjà commencé l'examen
    $stmt = $conn->prepare("
        SELECT date_debut, date_fin 
        FROM examens3 
        WHERE id_ex = ? 
        AND date_debut IS NOT NULL
    ");
    $stmt->execute([$examen_id]);
    $temps_examen = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$temps_examen) {
        // Initialiser le temps de début si pas encore commencé
        $stmt = $conn->prepare("
            UPDATE examens3 
            SET date_debut = NOW(), 
                date_fin = DATE_ADD(NOW(), INTERVAL duree MINUTE) 
            WHERE id_ex = ?
        ");
        $stmt->execute([$examen_id]);
        
        // Récupérer les temps mis à jour
        $stmt = $conn->prepare("SELECT date_debut, date_fin FROM examens3 WHERE id_ex = ?");
        $stmt->execute([$examen_id]);
        $temps_examen = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer les questions de l'examen avec leurs options pour les QCM
    $stmt = $conn->prepare("
        SELECT 
            q.id_q, 
            q.texte, 
            q.type,
            q.note_max,
            COALESCE(re.reponse, '') as reponse,
            CASE 
                WHEN q.type = 'qcm' THEN (
                    SELECT JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'id', id_option,
                            'texte', texte_option
                        )
                    )
                    FROM options_qcm
                    WHERE question_id = q.id_q
                )
                ELSE NULL
            END as options_qcm
        FROM questions3 q
        LEFT JOIN reponses_etudiants2 re ON q.id_q = re.question_id 
            AND re.etudiant_id = ? 
            AND re.examen_id = ?
        WHERE q.exam_id = ?
        ORDER BY q.id_q
    ");
    $stmt->execute([$etudiant_id, $examen_id, $examen_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Examen : <?php echo htmlspecialchars($examen['titre']); ?></title>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --error-color: #c0392b;
            --background-color: #f5f6fa;
            --border-color: #dcdde1;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background-color: var(--background-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }

        .timer {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 1.2em;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .question-container {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .question-text {
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .answer-input {
            width: 100%;
            min-height: 100px;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            resize: vertical;
            font-family: inherit;
        }

        .qcm-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }

        .qcm-option {
            display: flex;
            align-items: center;
            padding: 10px;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .qcm-option input[type="checkbox"],
        .qcm-option input[type="radio"] {
            margin-right: 10px;
        }

        .vrai-faux-options {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .vrai-faux-option {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .points {
            color: var(--secondary-color);
            font-size: 0.9em;
            margin-top: 10px;
        }

        .save-status {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 10px 20px;
            border-radius: 4px;
            display: none;
            z-index: 1000;
        }

        .save-status.success {
            background: var(--success-color);
            color: white;
        }

        .save-status.error {
            background: var(--error-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="timer" id="timer"></div>
        
        <h1><?php echo htmlspecialchars($examen['titre']); ?></h1>
        
        <form id="examForm" method="POST">
            <input type="hidden" name="examen_id" value="<?php echo $examen_id; ?>">
            
            <?php foreach ($questions as $question): ?>
                <div class="question-container">
                    <div class="question-text">
                        <?php echo htmlspecialchars($question['texte']); ?>
                    </div>
                    
                    <?php if ($question['type'] === 'text'): ?>
                        <textarea 
                            name="reponses[<?php echo $question['id_q']; ?>]" 
                            class="answer-input"
                            onpaste="return false;" 
                            oncut="return false;" 
                            oncopy="return false;"
                        ><?php echo htmlspecialchars($question['reponse']); ?></textarea>
                    
                    <?php elseif ($question['type'] === 'qcm'): ?>
                        <div class="qcm-options">
                            <?php 
                            $options = json_decode($question['options_qcm'], true);
                            $selected_options = explode(',', $question['reponse']);
                            foreach ($options as $option): 
                            ?>
                                <label class="qcm-option">
                                    <input 
                                        type="checkbox" 
                                        name="reponses[<?php echo $question['id_q']; ?>][]" 
                                        value="<?php echo $option['id']; ?>"
                                        <?php echo in_array($option['id'], $selected_options) ? 'checked' : ''; ?>
                                    >
                                    <?php echo htmlspecialchars($option['texte']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    
                    <?php elseif ($question['type'] === 'vrai_faux'): ?>
                        <div class="vrai-faux-options">
                            <label class="vrai-faux-option">
                                <input 
                                    type="radio" 
                                    name="reponses[<?php echo $question['id_q']; ?>]" 
                                    value="vrai"
                                    <?php echo strtolower($question['reponse']) === 'vrai' ? 'checked' : ''; ?>
                                >
                                Vrai
                            </label>
                            <label class="vrai-faux-option">
                                <input 
                                    type="radio" 
                                    name="reponses[<?php echo $question['id_q']; ?>]" 
                                    value="faux"
                                    <?php echo strtolower($question['reponse']) === 'faux' ? 'checked' : ''; ?>
                                >
                                Faux
                            </label>
                        </div>
                    <?php endif; ?>
                    
                    <div class="points">
                        Points : <?php echo $question['note_max']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </form>
        
        <div id="saveStatus" class="save-status"></div>
    </div>

    <script>
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
</body>
</html>
