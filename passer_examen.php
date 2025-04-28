<?php
// passer_examen.php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Protection contre CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Vérification de la session utilisateur
if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'etudiant') {
    header('Location: login.php');
    exit('Accès réservé aux étudiants');
}
$userId = $_SESSION['user']['id'];

// Connexion PDO sécurisée
try {
    $pdo = new PDO("mysql:host=localhost;dbname=examens_db;charset=utf8mb4", 'root', 'DD202', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    error_log("Erreur de connexion à la base de données: " . $e->getMessage());
    exit('Erreur de connexion à la base de données. Contactez l\'administrateur.');
}

// Récupérer id_etudiant
$stmt = $pdo->prepare("SELECT id_etudiant FROM etudiants WHERE utilisateur_id = ?");
$stmt->execute([$userId]);
$etu = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$etu) exit('Étudiant introuvable');
$idEtudiant = $etu['id_etudiant'];

// Récupérer examen en cours
$stmt = $pdo->prepare(
    "SELECT e.id_ex, e.titre, e.duree, ee.id_variante, ee.id AS ee_id, e.heure_debut
     FROM examens3 e
     JOIN etudiant_examen ee ON e.id_ex = ee.id_examen
     WHERE ee.id_etudiant = ? AND ee.statut='en_cours' AND e.statut='disponible' LIMIT 1"
);
$stmt->execute([$idEtudiant]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$exam) exit('Aucun examen disponible');
$examId     = $exam['id_ex'];
$varianteId = $exam['id_variante'];
$eeId       = $exam['ee_id'];

// Calcul du temps restant
$dateDebut = new DateTime($exam['heure_debut']);
$maintenant = new DateTime();
$dureeExam = intval($exam['duree']); // durée en minutes
$dateFinExam = clone $dateDebut;
$dateFinExam->add(new DateInterval("PT{$dureeExam}M"));
$tempsRestantSeconds = $dateFinExam->getTimestamp() - $maintenant->getTimestamp();
$tempsRestantSeconds = max(0, $tempsRestantSeconds);

// Charger questions et options
$stmt = $pdo->prepare(
    "SELECT DISTINCT q.id_q, q.texte, q.type, q.note_max, o.id_op, o.texte AS option_texte
     FROM questions3 q
     LEFT JOIN options3 o ON q.id_q = o.question_id
     WHERE q.exam_id = ? AND (q.variante_id = ? OR q.variante_id IS NULL)
     ORDER BY q.id_q, o.id_op"
);
$stmt->execute([$examId, $varianteId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$questions = [];
foreach ($rows as $row) {
    $id = $row['id_q'];
    if (!isset($questions[$id])) {
        $questions[$id] = [
            'id_q'     => $id,
            'texte'    => $row['texte'],
            'type'     => $row['type'],
            'note_max' => $row['note_max'],
            'options'  => []
        ];
    }
    if ($row['option_texte']) {
        $questions[$id]['options'][] = [
            'id_op' => $row['id_op'],
            'texte' => $row['option_texte']
        ];
    }
}
$questions = array_values($questions);

// Récupérer réponses existantes
$stmt = $pdo->prepare("SELECT question_id, reponse FROM reponses_etudiants2 WHERE etudiant_id = ? AND examen_id = ?");
$stmt->execute([$idEtudiant, $examId]);
$existing = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'reponse', 'question_id');

// Fonction pour enregistrer les tentatives de triche
function logCheatingAttempt($pdo, $etudiantId, $examenId, $type, $details = '') {
    $stmt = $pdo->prepare(
        "INSERT INTO tentatives_triche (etudiant_id, examen_id, type, details, date_tentative) 
         VALUES (?, ?, ?, ?, NOW())"
    );
    $stmt->execute([$etudiantId, $examenId, $type, $details]);
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        exit('Erreur de sécurité: formulaire invalide');
    }
    
    $answers   = $_POST['answers'] ?? [];
    $languages = $_POST['language'] ?? [];
    
    // Enregistrement des réponses
    foreach ($answers as $qId => $resp) {
        $qId = filter_var($qId, FILTER_VALIDATE_INT);
        if ($qId === false) continue;
        
        $reponse = is_array($resp) ? json_encode($resp) : trim($resp);
        
        // Calcul note auto
        $s = $pdo->prepare("SELECT note_max, type FROM questions3 WHERE id_q = ?");
        $s->execute([$qId]); 
        $q = $s->fetch(PDO::FETCH_ASSOC);
        
        if (!$q) continue;
        
        $note = 0;
        if (in_array($q['type'], ['qcm','true_false'])) {
            if ($q['type'] === 'qcm') {
                foreach ((array)$resp as $optId) {
                    $optId = filter_var($optId, FILTER_VALIDATE_INT);
                    if ($optId === false) continue;
                    
                    $oQ = $pdo->prepare("SELECT correct FROM options3 WHERE question_id = ? AND id_op = ?");
                    $oQ->execute([$qId, $optId]); 
                    $o = $oQ->fetch();
                    if ($o && $o['correct']) { 
                        $note = $q['note_max']; 
                        break; 
                    }
                }
            } else {
                $oQ = $pdo->prepare("SELECT correct FROM options3 WHERE question_id = ? AND texte = ?");
                $oQ->execute([$qId, $resp]); 
                $o = $oQ->fetch();
                if ($o && $o['correct']) { 
                    $note = $q['note_max']; 
                }
            }
        }
        
        // Enregistrer
        $up = $pdo->prepare(
            "REPLACE INTO reponses_etudiants2 (examen_id, etudiant_id, question_id, reponse, note)
             VALUES (?, ?, ?, ?, ?)"
        );
        $up->execute([$examId, $idEtudiant, $qId, $reponse, $note]);
    }
    
    // Soumission finale
    $u = $pdo->prepare("UPDATE etudiant_examen SET statut='termine', date_soumission=NOW() WHERE id = ?");
    $u->execute([$eeId]);
    
    $log = $pdo->prepare("INSERT INTO security_logs (user_id, exam_id, event_type, timestamp) VALUES (?, ?, 'soumission', NOW())");
    $log->execute([$idEtudiant, $examId]);
    
    echo "<p>Examen soumis et corrigé automatiquement.</p>";
    exit;
}

$log = $pdo->prepare("INSERT INTO security_logs (user_id, exam_id, event_type, timestamp) VALUES (?, ?, 'accès', NOW())");
$log->execute([$idEtudiant, $examId]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=htmlspecialchars($exam['titre'])?></title>
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script>hljs.highlightAll();</script>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 40px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .question { display: none; }
        .question.active { display: block; }
        .question p { font-size: 1.1em; }
        textarea.code { width: 100%; height: 120px; font-family: monospace; background: #272822; color: #f8f8f2; padding: 10px; border-radius: 4px; border: none; resize: vertical; }
        pre.code-preview { background: #272822; color: #f8f8f2; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .options { margin: 10px 0; }
        .nav-buttons { display: flex; justify-content: space-between; margin-top: 20px; }
        .nav-buttons button { background: #3498db; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .nav-buttons button:disabled { background: #ccc; cursor: not-allowed; }
        .progress { text-align: center; margin-bottom: 20px; }
        .lang-select { margin-bottom: 10px; }
        
        #timer {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            font-size: 18px;
            font-weight: bold;
            z-index: 1000;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            text-align: center;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            color: #d9534f;
            font-size: 18px;
            font-weight: bold;
        }
        #warningCounter {
            font-size: 24px;
            color: #d9534f;
            font-weight: bold;
        }
        .fullscreen-notice {
            text-align: center;
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        #examContent { display: none; }
        
        /* Style pour les avertissements */
        .security-warning {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(220, 53, 69, 0.95);
            color: white;
            padding: 20px;
            border-radius: 8px;
            z-index: 10000;
            text-align: center;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.6);
            display: none;
            font-size: 18px;
            font-weight: bold;
            max-width: 80%;
        }
        
        /* Bloquer la sélection de texte */
        body {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        /* Exception pour les zones de texte */
        textarea {
            -webkit-user-select: auto;
            -moz-user-select: auto;
            -ms-user-select: auto;
            user-select: auto;
        }
        
        #fullscreenBtn {
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            cursor: pointer;
            font-weight: bold;
        }
        
        #fullscreenBtn:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div id="warningModal" class="modal">
        <div class="modal-content">
            <p>ATTENTION: Tentative de navigation détectée!</p>
            <p>Cette action est interdite pendant l'examen.</p>
            <p>Avertissement <span id="warningCounter">1</span>/3</p>
            <p>Au bout de 3 avertissements, votre examen sera automatiquement soumis.</p>
            <button id="returnBtn" style="padding: 10px 20px; background: #d9534f; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 15px;">
                Retourner à l'examen
            </button>
        </div>
    </div>
    
    <div id="securityWarning" class="security-warning">
        <p id="securityWarningText">Action non autorisée détectée!</p>
        <p>Cette violation sera enregistrée.</p>
    </div>
    
    <div id="timer">Temps restant: <span id="countdown">--:--</span></div>
    
    <div class="container">
        <h1><?=htmlspecialchars($exam['titre'])?></h1>
        
        <div id="fullscreenNotice" class="fullscreen-notice">
            Veuillez activer le mode plein écran pour commencer l'examen
            <button id="fullscreenBtn">Activer le plein écran</button>
        </div>
        
        <div id="examContent">
            <div class="progress">Question <span id="current">1</span> / <?=count($questions)?></div>
            
            <form method="post" id="examForm">
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>">
                
                <?php foreach ($questions as $idx => $q):
                    $val = $existing[$q['id_q']] ?? '';
                    if ($q['type'] === 'qcm') $val = json_decode($val, true) ?: [];
                ?>
                    <div class="question<?= $idx === 0 ? ' active' : '' ?>" data-index="<?= $idx ?>">
                        <p><?=nl2br(htmlspecialchars($q['texte']))?></p>
                        <?php if ($q['type'] === 'text'): ?>
                            <div class="lang-select">
                                <label>Langage:
                                    <select name="language[<?=$q['id_q']?>]" class="language-dropdown">
                                        <option value="plain">Texte normal</option>
                                        <option value="php">PHP</option>
                                        <option value="html">HTML</option>
                                        <option value="js">JavaScript</option>
                                    </select>
                                </label>
                            </div>
                            <textarea id="ta-<?=$q['id_q']?>" name="answers[<?=$q['id_q']?>]" class="code language-plain"><?=htmlspecialchars($val)?></textarea>
                            <pre id="preview-<?=$q['id_q']?>" class="code-preview language-plain"><?=htmlspecialchars($val)?></pre>
                        <?php elseif ($q['type'] === 'true_false'): ?>
                            <div class="options">
                                <label><input type="radio" name="answers[<?=$q['id_q']?>]" value="Vrai" <?=($val === 'Vrai' ? 'checked' : '')?>> Vrai</label>
                                <label><input type="radio" name="answers[<?=$q['id_q']?>]" value="Faux" <?=($val === 'Faux' ? 'checked' : '')?>> Faux</label>
                            </div>
                        <?php else: ?>
                            <div class="options">
                                <?php foreach ($q['options'] as $opt): ?>
                                    <label><input type="checkbox" name="answers[<?=$q['id_q']?>][]" value="<?=$opt['id_op']?>" <?=in_array($opt['id_op'], (array)$val) ? 'checked' : ''?>> <?=htmlspecialchars($opt['texte'])?></label><br>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="nav-buttons">
                    <button type="button" id="prevBtn">Précédent</button>
                    <button type="button" id="nextBtn">Suivant</button>
                    <button type="submit" id="submitBtn" style="display:none;">Soumettre</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Configuration initiale
        const examId = <?= $examId ?>;
        const etudiantId = <?= $idEtudiant ?>;
        const tempsRestant = <?= $tempsRestantSeconds ?>;
        let isFullscreen = false;
        let warningCount = 0;
        let lastHeartbeat = Date.now();
        let examLocked = true; // Commencer verrouillé
        let mouseOutCount = 0;
        let lastFocusTime = Date.now();
        let inactivityTimer = null;
        let copyPasteCount = 0;
        let windowBlurCount = 0;
        
        // Éléments DOM
        const questions = document.querySelectorAll('.question');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');
        const currentLabel = document.getElementById('current');
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        const fullscreenNotice = document.getElementById('fullscreenNotice');
        const warningModal = document.getElementById('warningModal');
        const warningCounter = document.getElementById('warningCounter');
        const returnBtn = document.getElementById('returnBtn');
        const countdownEl = document.getElementById('countdown');
        const examForm = document.getElementById('examForm');
        const examContent = document.getElementById('examContent');
        const securityWarning = document.getElementById('securityWarning');
        const securityWarningText = document.getElementById('securityWarningText');
        
        // Fonction pour afficher les avertissements temporaires
        function showTemporaryWarning(message, duration = 3000) {
            securityWarningText.textContent = message;
            securityWarning.style.display = 'block';
            
            setTimeout(() => {
                securityWarning.style.display = 'none';
            }, duration);
        }
        
        // Fonction pour activer/désactiver le plein écran
        function enterFullscreen() {
            const elem = document.documentElement;
            
            // Vérifier si déjà en plein écran
            if (document.fullscreenElement || document.webkitFullscreenElement || 
                document.mozFullScreenElement || document.msFullscreenElement) {
                return;
            }
            
            // Demander le plein écran
            if (elem.requestFullscreen) {
                elem.requestFullscreen().then(() => {
                    isFullscreen = true;
                    enableExam();
                    fullscreenNotice.style.display = 'none';
                }).catch(err => {
                    console.error("Erreur plein écran:", err);
                    // Si le navigateur bloque le plein écran, permettre quand même l'examen
                    enableExam();
                    fullscreenNotice.style.display = 'none';
                    showTemporaryWarning("Le mode plein écran n'a pas pu être activé. Veuillez ne pas quitter cette page.");
                });
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            } else if (elem.mozRequestFullScreen) {
                elem.mozRequestFullScreen();
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            } else {
                // Si le navigateur ne supporte pas le plein écran, permettre quand même
                enableExam();
                fullscreenNotice.style.display = 'none';
                showTemporaryWarning("Votre navigateur ne supporte pas le mode plein écran. Veuillez ne pas quitter cette page.");
            }
        }
        
        // Vérifier l'état du plein écran
        function checkFullscreenState() {
            isFullscreen = !!document.fullscreenElement || 
                          !!document.webkitFullscreenElement || 
                          !!document.mozFullScreenElement ||
                          !!document.msFullscreenElement;
            
            if (isFullscreen) {
                enableExam();
                fullscreenNotice.style.display = 'none';
            } else {
                // Ne pas désactiver l'examen immédiatement, seulement afficher l'avertissement
                fullscreenNotice.style.display = 'block';
                
                // Si le plein écran est quitté pendant l'examen
                if (!examLocked) {
                    showWarning();
                    logCheating('fullscreen_exit');
                    showTemporaryWarning('Sortie du mode plein écran détectée!');
                    // Réessayer d'entrer en plein écran
                    setTimeout(enterFullscreen, 1000);
                }
            }
        }
        
        // Désactiver/activer les contrôles de l'examen
        function disableExam() {
            examLocked = true;
            examContent.style.display = 'none';
            document.querySelectorAll('input, textarea, select, button:not(#fullscreenBtn):not(#returnBtn)').forEach(el => {
                el.disabled = true;
            });
        }
        
        function enableExam() {
            examLocked = false;
            examContent.style.display = 'block';
            document.querySelectorAll('input, textarea, select, button').forEach(el => {
                el.disabled = false;
            });
            prevBtn.disabled = current === 0;
            updateNextSubmitButtons();
        }
        
        // Navigation entre les questions
        let current = 0;
        
        function showQuestion(idx) {
            if (examLocked) return;
            
            questions[current].classList.remove('active');
            questions[idx].classList.add('active');
            current = idx;
            currentLabel.textContent = current + 1;
            prevBtn.disabled = current === 0;
            updateNextSubmitButtons();
        }
        
        function updateNextSubmitButtons() {
            nextBtn.style.display = current === questions.length - 1 ? 'none' : '';
            submitBtn.style.display = current === questions.length - 1 ? '' : 'none';
        }
        
        // Événements de navigation
        prevBtn.addEventListener('click', () => showQuestion(current - 1));
        nextBtn.addEventListener('click', () => showQuestion(current + 1));
        
        // Mise à jour live du preview et du highlight
        document.querySelectorAll('textarea.code').forEach(ta => {
            const qid = ta.id.replace('ta-', '');
            const pre = document.getElementById(`preview-${qid}`);
            const select = document.querySelector(`select[name="language[${qid}]"]`);
            
            ta.addEventListener('input', () => {
                pre.textContent = ta.value;
                hljs.highlightElement(pre);
            });
            
            select?.addEventListener('change', e => {
                const lang = e.target.value;
                ta.className = `code language-${lang}`;
                pre.className = `code-preview language-${lang}`;
                hljs.highlightElement(pre);
            });
        });
        
        // Événements de plein écran
        fullscreenBtn.addEventListener('click', function(e) {
            e.preventDefault();
            enterFullscreen();
        });
        
        document.addEventListener('fullscreenchange', checkFullscreenState);
        document.addEventListener('webkitfullscreenchange', checkFullscreenState);
        document.addEventListener('mozfullscreenchange', checkFullscreenState);
        document.addEventListener('MSFullscreenChange', checkFullscreenState);
        
        // Au chargement
        document.addEventListener('DOMContentLoaded', function() {
            checkFullscreenState();
            
            // Initialiser détecteur d'inactivité
            startInactivityMonitor();
            
            // Sauvegarde automatique des réponses toutes les 30 secondes
            setInterval(autoSaveAnswers, 30000);
            
            // Heartbeat pour s'assurer que l'étudiant est présent
            setInterval(sendHeartbeat, 5000);
            
            // Démarrer le compte à rebours
            startCountdown(tempsRestant);
            
            // S'assurer que les boutons sont disponibles
            fullscreenBtn.disabled = false;
            returnBtn.disabled = false;
        });
        
        // Détection lorsque l'utilisateur quitte l'onglet ou la fenêtre
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState !== 'visible' && !examLocked) {
                showWarning();
                logCheating('tab_switch');
                showTemporaryWarning('Changement d\'onglet détecté!');
            }
        });
        
        // Détection lorsque la fenêtre perd le focus
        window.addEventListener('blur', function() {
            if (!examLocked) {
                windowBlurCount++;
                logCheating('window_blur', 'Compte: ' + windowBlurCount);
                showTemporaryWarning('Changement d\'application détecté!');
                
                if (windowBlurCount >= 3) {
                    showWarning();
                }
            }
        });
        
        // Détection si la souris quitte la fenêtre
        document.addEventListener('mouseout', function(e) {
            if (!examLocked && e.relatedTarget === null && e.toElement === null && e.target === document.documentElement) {
                mouseOutCount++;
                logCheating('mouse_out', 'Compte: ' + mouseOutCount);
                
                if (mouseOutCount >= 5) {
                    showWarning();
                    mouseOutCount = 0;
                }
            }
        });
        
        // Désactiver le clic droit
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            logCheating('right_click');
            showTemporaryWarning('Clic droit non autorisé');
            return false;
        });
        
        // Désactiver les raccourcis clavier
        document.addEventListener('keydown', function(e) {
            // Bloquer les touches de navigation et développeur
            if (
                (e.altKey && e.key === 'Tab') || 
                (e.ctrlKey && (e.key === 't' || e.key === 'n' || e.key === 'w' || e.key === 'r')) ||
                (e.key === 'F5') ||
                (e.key === 'F12') ||
                (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                (e.altKey && e.key === 'F4') ||
                (e.key === 'PrintScreen') ||
                (e.metaKey && e.key === 'Tab') ||
                (e.metaKey && e.key === 'r') ||
                (e.metaKey && e.key === 'l')
            ) {
                e.preventDefault();
                showWarning();
                logCheating('keyboard_shortcut', e.key);
                showTemporaryWarning('Raccourci clavier non autorisé: ' + e.key);
                return false;
            }
            
            // Bloquer Alt+Tab et Alt+F4
            if (e.altKey || e.key === 'Alt') {
                e.preventDefault();
                logCheating('alt_key');
                showTemporaryWarning('Utilisation de la touche Alt non autorisée');
                return false;
            }
            
            // Bloquer Windows key
            if (e.key === 'Meta') {
                e.preventDefault();
                logCheating('windows_key');
                showTemporaryWarning('Utilisation de la touche Windows non autorisée');
                return false;
            }
            
            // Mise à jour de l'activité
            lastFocusTime = Date.now();
        });
        
        // Bloquer les copier-coller (sauf dans les zones de texte)
        document.addEventListener('copy', function(e) {
            const target = e.target;
            if (target.tagName !== 'TEXTAREA' && target.tagName !== 'INPUT') {
                e.preventDefault();
                copyPasteCount++;
                logCheating('copy_attempt');
                showTemporaryWarning('Copier de texte non autorisé');
                
                if (copyPasteCount >= 3) {
                    showWarning();
                }
                return false;
            }
        });
        
        document.addEventListener('paste', function(e) {
            const target = e.target;
            if (target.tagName !== 'TEXTAREA' && target.tagName !== 'INPUT') {
                e.preventDefault();
                copyPasteCount++;
                logCheating('paste_attempt');
                showTemporaryWarning('Coller de texte non autorisé');
                
                if (copyPasteCount >= 3) {
                    showWarning();
                }
                return false;
            }
        });
        
        // Détecter les impressions d'écran
        window.addEventListener('keyup', function(e) {
            if (e.key === 'PrintScreen') {
                logCheating('print_screen');
                showTemporaryWarning('Capture d\'écran détectée');
                showWarning();
            }
        });
        
        // Vérifier l'inactivité de l'utilisateur
        function startInactivityMonitor() {
            lastFocusTime = Date.now();
            
            // Réinitialiser le minuteur à chaque activité
            ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart'].forEach(function(evt) {
                document.addEventListener(evt, function() {
                    lastFocusTime = Date.now();
                });
            });
            
            // Vérifier l'inactivité toutes les 10 secondes
            inactivityTimer = setInterval(function() {
                const inactiveTime = (Date.now() - lastFocusTime) / 1000;
                
                // Si inactif plus de 120 secondes (2 minutes)
                if (inactiveTime > 120 && !examLocked) {
                    logCheating('inactivity', Math.floor(inactiveTime) + ' secondes');
                    showTemporaryWarning('Inactivité détectée. Continuez votre examen.');
                }
                
                // Si inactif plus de 5 minutes, générer un avertissement
                if (inactiveTime > 300 && !examLocked) {
                    showWarning();
                    lastFocusTime = Date.now(); // Réinitialiser après avertissement
                }
            }, 10000);
        }
        
        // Gérer l'affichage des avertissements
        function showWarning() {
            if (examLocked) return;
            
            warningCount++;
            warningCounter.textContent = warningCount;
            warningModal.style.display = 'block';
            
            if (warningCount >= 3) {
                setTimeout(() => {
                    alert("L'examen sera soumis automatiquement en raison de violations répétées des règles.");
                    logCheating('auto_submit', 'Après ' + warningCount + ' avertissements');
                    examForm.submit();
                }, 3000);
            }
        }
        
        // Fermer la modal d'avertissement
        returnBtn.addEventListener('click', function() {
            warningModal.style.display = 'none';
            enterFullscreen();
        });
        
        // Envoyer un heartbeat pour confirmer présence
        function sendHeartbeat() {
            lastHeartbeat = Date.now();
            
            fetch('heartbeat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `etudiant_id=${etudiantId}&examen_id=${examId}`
            }).catch(err => console.error('Erreur heartbeat:', err));
        }
        
        // Enregistrer les tentatives de triche
        function logCheating(type, details = '') {
            fetch('log_cheating.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `etudiant_id=${etudiantId}&examen_id=${examId}&type=${type}&details=${details}`
            }).catch(err => console.error('Erreur log:', err));
        }
        
        // Sauvegarde automatique des réponses
        function autoSaveAnswers() {
            if (examLocked) return;
            
            const formData = new FormData(examForm);
            formData.append('auto_save', '1');
            
            fetch('save_answers.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
              .then(data => {
                  console.log('Réponses sauvegardées', data);
              })
              .catch(err => console.error('Erreur sauvegarde:', err));
        }
        
        // Compte à rebours
        function startCountdown(seconds) {
            let remainingTime = seconds;
            
            function updateTimer() {
                const minutes = Math.floor(remainingTime / 60);
                const secs = remainingTime % 60;
                countdownEl.textContent = `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                
                if (remainingTime <= 0) {
                    clearInterval(timerInterval);
                    alert("Le temps est écoulé. Votre examen va être soumis automatiquement.");
                    examForm.submit();
                }
                
                remainingTime--;
            }
            
            updateTimer();
            const timerInterval = setInterval(updateTimer, 1000);
        }
        
        // Bloquer le drag and drop des fichiers
        window.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });

        window.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            logCheating('file_drop');
            showTemporaryWarning('Tentative de glisser-déposer détectée');
        });
        
        // Détection si l'utilisateur tente d'ouvrir les outils de développeur
        window.addEventListener('devtoolschange', function(e) {
            if (e.detail.open) {
                logCheating('devtools_open');
                showWarning();
                showTemporaryWarning('Outils de développement détectés!');
            }
        });
        
        // Confirmer avant de quitter la page
        window.addEventListener('beforeunload', function(e) {
            if (!examLocked) {
                // Enregistrer la tentative de quitter
                logCheating('page_leave_attempt');
                
                const message = "Attention: Quitter cette page soumettra automatiquement votre examen. Êtes-vous sûr?";
                e.returnValue = message;
                return message;
            }
        });
        
                // Vérification de l'intégrité du DOM
                function checkDOMIntegrity() {
            const criticalElements = ['timer', 'examForm', 'warningModal'];
            criticalElements.forEach(id => {
                if (!document.getElementById(id)) {
                    logCheating('dom_tampering', `Element ${id} manquant`);
                    forceExamSubmit();
                }
            });
        }

        // Soumission forcée de l'examen
        function forceExamSubmit() {
            alert("Altération de l'interface détectée! Soumission immédiate.");
            examForm.submit();
        }

        // Détection de l'émulation mobile
        function detectMobileEmulation() {
            if (window.matchMedia('(pointer:fine)').matches && 
                navigator.userAgent.match(/Mobile/i)) {
                logCheating('mobile_emulation');
                showTemporaryWarning('Émulation mobile détectée');
            }
        }

        // Vérifier périodiquement les dimensions de l'écran
        let initialWidth = window.innerWidth;
        let initialHeight = window.innerHeight;
        setInterval(() => {
            if (window.innerWidth !== initialWidth || 
                window.innerHeight !== initialHeight) {
                logCheating('window_resize', 
                    `Nouvelles dimensions: ${window.innerWidth}x${window.innerHeight}`);
                initialWidth = window.innerWidth;
                initialHeight = window.innerHeight;
            }
        }, 5000);

        // Détection des extensions de navigateur
        function detectBrowserExtensions() {
            const performanceEntries = performance.getEntriesByType('resource');
            const extensionResources = performanceEntries.filter(entry => 
                entry.name.startsWith('chrome-extension://') || 
                entry.name.startsWith('moz-extension://'));
            
            if (extensionResources.length > 0) {
                logCheating('browser_extensions', 
                    `Extensions détectées: ${extensionResources.length}`);
                showWarning();
            }
        }

        // Démarrer toutes les vérifications de sécurité
        function startSecurityChecks() {
            detectMobileEmulation();
            detectBrowserExtensions();
            checkVirtualEnvironment();
            detectIncognito();
            setInterval(detectBrowserExtensions, 30000);
        }

        // Initialisation finale
        document.addEventListener('DOMContentLoaded', () => {
            startSecurityChecks();
            enterFullscreen();
        });
    </script>
</body>
</html>