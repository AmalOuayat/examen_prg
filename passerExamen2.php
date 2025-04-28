<?php
session_start();

// Configuration de la base de données
$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202"; // À adapter selon votre configuration

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupération de l'utilisateur connecté depuis la session
if (!isset($_SESSION['user']['id'])) {
    die("Utilisateur non connecté");
}

$userId = $_SESSION['user']['id'];

// Récupérer l'ID de l'étudiant
$stmt = $conn->prepare("SELECT id_etudiant FROM etudiants WHERE utilisateur_id = ?");
$stmt->execute([$userId]);
$student = $stmt->fetch();

if (!$student) {
    die("Étudiant non trouvé");
}

$studentId = $student['id_etudiant'];

// Récupérer l'examen actif avec statut "en_cours" pour cet étudiant
$stmt = $conn->prepare("SELECT e.*, ee.id_variante, ee.statut as exam_statut, f.nom as formateur_nom, f.prenom as formateur_prenom
                       FROM examens3 e
                       JOIN etudiant_examen ee ON e.id_ex = ee.id_examen
                       LEFT JOIN utilisateurs f ON e.formateur_id = f.id_u
                       WHERE ee.id_etudiant = ? 
                       AND ee.statut = 'en_cours'
                       AND e.statut = 'disponible'
                       LIMIT 1");
$stmt->execute([$studentId]);
$exam = $stmt->fetch();
// Récupérer la variante de l'examen
$stmt = $conn->prepare("SELECT v.*, ee.id_variante, ee.statut as exam_statut
                       FROM variantes v
                       JOIN etudiant_examen ee ON v.id_variante = ee.id_variante
                       WHERE ee.id_etudiant = ? 
                       AND ee.statut = 'en_cours'
                       LIMIT 1");
$stmt->execute([$studentId]); // 1 paramètre pour 1 placeholder
$variante = $stmt->fetch();
// Récupérer les informations complètes de l'étudiant
$stmt = $conn->prepare("SELECT u.nom, u.prenom, e.promotion 
                       FROM etudiants e
                       JOIN utilisateurs u ON e.utilisateur_id = u.id_u
                       WHERE e.id_etudiant = ?");
$stmt->execute([$studentId]);
$studentInfo = $stmt->fetch();

// Vérifier si l'étudiant a d'autres examens disponibles
$stmt = $conn->prepare("SELECT COUNT(*) as total_exams FROM etudiant_examen 
                        WHERE id_etudiant = ? AND statut = 'en_cours'");
$stmt->execute([$studentId]);
$examCount = $stmt->fetch();
$hasExams = ($examCount['total_exams'] > 0);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examens disponibles</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            flex: 1;
        }
        header {
            background-color: #3498db;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .page-title {
            margin-bottom: 30px;
            color: #2c3e50;
            font-size: 2rem;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            display: inline-block;
        }
        .message-box {
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .message-title {
            color: #3498db;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .exam-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 30px;
            max-width: 700px;
        }
        .exam-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .exam-header {
            background-color: #3498db;
            color: white;
            padding: 20px;
            position: relative;
        }
        .exam-title {
            margin: 0;
            font-size: 1.5rem;
        }
        .exam-body {
            padding: 25px;
        }
        .exam-info {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 15px;
            align-items: center;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #7f8c8d;
        }
        .info-value {
            flex: 1;
        }
        .exam-description {
            margin-bottom: 20px;
            line-height: 1.6;
            color: #555;
        }
        .timer-card {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .timer-icon {
            margin-right: 15px;
            color: #e74c3c;
            font-size: 24px;
        }
        .timer-text {
            font-weight: bold;
        }
        .start-btn {
            display: inline-block;
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 1rem;
            border-radius: 30px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(46, 204, 113, 0.2);
        }
        .start-btn:hover {
            background-color: #27ae60;
        }
        .no-exams {
            text-align: center;
            padding: 50px 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .no-exams-icon {
            font-size: 50px;
            color: #95a5a6;
            margin-bottom: 20px;
        }
        .no-exams-title {
            font-size: 1.5rem;
            color: #34495e;
            margin-bottom: 15px;
        }
        .no-exams-text {
            color: #7f8c8d;
            max-width: 500px;
            margin: 0 auto;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            margin-top: 40px;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <header>
        <h1>Portail d'examens</h1>
        <div class="user-info">
            <?php if($studentInfo): ?>
                <?= htmlspecialchars($studentInfo['prenom'] . ' ' . $studentInfo['nom']) ?> | <?= htmlspecialchars($studentInfo['promotion']) ?>
            <?php endif; ?>
        </div>
    </header>
    
    <div class="container">
        <h2 class="page-title">Examens disponibles</h2>
        
        <div class="message-box">
            <h3 class="message-title">Bonjour <?= htmlspecialchars($studentInfo['prenom']) ?> !</h3>
            <p>Voici les examens que vous pouvez passer. Assurez-vous d'avoir suffisamment de temps avant de commencer un examen.</p>
        </div>
        
        <?php if($hasExams && $exam): ?>
            <div class="exam-card">
                <div class="exam-header">
                    <h3 class="exam-title"><?= htmlspecialchars($exam['titre']) ?></h3>
                </div>
                <div class="exam-body">
                    <div class="exam-info">
                        <div class="info-row">
                            <div class="info-label">Formateur:</div>
                            <div class="info-value">
                                <?= !empty($exam['formateur_prenom']) && !empty($exam['formateur_nom']) 
                                    ? htmlspecialchars($exam['formateur_prenom'] . ' ' . $exam['formateur_nom']) 
                                    : 'Non spécifié' ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Durée:</div>
                            <div class="info-value"><?= !empty($exam['duree']) ? htmlspecialchars($exam['duree']) . ' minutes' : 'Non spécifiée' ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Variante:</div>
                            <div class="info-value"><?= !empty($variante['nom_variante']) ? htmlspecialchars($variante['nom_variante']) : 'Standard' ?></div>
                        </div>
                    </div>
                    
                    <?php if(!empty($exam['description'])): ?>
                        <div class="exam-description">
                            <?= htmlspecialchars($exam['description']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="timer-card">
                        <div class="timer-icon">⏱️</div>
                        <div class="timer-text">
                            Durée de l'examen: <?= !empty($exam['duree']) ? htmlspecialchars($exam['duree']) . ' minutes' : 'Non spécifiée' ?>
                        </div>
                    </div>
                    
                    <a href="passer_examen.php?id=<?= $exam['id_ex'] ?>" class="start-btn">Commencer l'examen</a>
                </div>
            </div>
        <?php else: ?>
            <div class="no-exams">
                <div class="no-exams-icon">📝</div>
                <h3 class="no-exams-title">Aucun examen disponible</h3>
                <p class="no-exams-text">
                    Vous avez terminé tous les examens disponibles pour le moment. 
                    Veuillez vérifier ultérieurement ou contacter votre formateur pour plus d'informations.
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <footer class="footer">
        &copy; <?= date('Y') ?> Système d'examen en ligne - Tous droits réservés
    </footer>
</body>
</html>