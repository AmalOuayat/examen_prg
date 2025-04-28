<?php
// config/Database.php
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        $host = "localhost";
        $username = "root";
        $password = "DD202";
        $dbname = "examens_db";
        
        try {
            $this->conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// models/ExamManager.php
class ExamManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getFilieres() {
        return $this->db->query("SELECT id_f, nom_filiere FROM filieres")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getBranches($filiereId) {
        $stmt = $this->db->prepare("SELECT id_b, nom_branche FROM branches WHERE filiere_id = ?");
        $stmt->execute([$filiereId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getGroupes($brancheId) {
        $stmt = $this->db->prepare("SELECT id_g, nom FROM groupes WHERE id_branche = ?");
        $stmt->execute([$brancheId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getExamens($groupeId, $formateurId) {
        $stmt = $this->db->prepare("
            SELECT id_ex, titre 
            FROM examens3 
            WHERE groupe_id = ? AND formateur_id = ?
        ");
        $stmt->execute([$groupeId, $formateurId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getEtudiantReponses($examenId, $etudiantId = null) {
        $sql = "SELECT 
                re.id_re, 
                u.id_u AS etudiant_id, 
                u.nom AS etudiant_nom, 
                q3.texte, 
                q3.note_max,
                re.reponse, 
                re.note, 
                ex3.titre AS examen_nom,
                ex3.id_ex as examen_id
            FROM reponses_etudiants2 re
            JOIN questions3 q3 ON re.question_id = q3.id_q
            JOIN etudiants e ON re.etudiant_id = e.id_etudiant
            JOIN utilisateurs u ON e.utilisateur_id = u.id_u
            JOIN examens3 ex3 ON re.examen_id = ex3.id_ex
            WHERE ex3.formateur_id = :formateur_id
            AND re.examen_id = :examen_id";

        $params = [
            'formateur_id' => $_SESSION['user']['id'],
            'examen_id' => $examenId
        ];

        if ($etudiantId > 0) {
            $sql .= " AND u.id_u = :etudiant_id";
            $params['etudiant_id'] = $etudiantId;
        }

        $sql .= " ORDER BY q3.id_q";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organiser les résultats par étudiant
        $etudiants = [];
        foreach ($results as $row) {
            $etudiantId = $row['etudiant_id'];
            if (!isset($etudiants[$etudiantId])) {
                $etudiants[$etudiantId] = [
                    'nom' => $row['etudiant_nom'],
                    'reponses' => []
                ];
            }
            $etudiants[$etudiantId]['reponses'][] = $row;
        }
        return $etudiants;
    }
    
    public function updateNotes($notes) {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("UPDATE reponses_etudiants2 SET note = ? WHERE id_re = ?");
            
            foreach ($notes as $id_re => $note) {
                $stmt->execute([floatval($note), $id_re]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

// ExamCorrection.php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'formateur') {
    header('Location: login.php');
    exit();
}

$examManager = new ExamManager();
$message = '';

// Si l'examen_id est fourni mais pas l'etudiant_id, rediriger vers la liste des étudiants
if (isset($_GET['examen_id']) && !isset($_GET['etudiant_id'])) {
    header("Location: liste_etudiants_examen.php?examen_id=" . $_GET['examen_id']);
    exit();
}

try {
    // Récupération des données de base
    $filieres = $examManager->getFilieres();
    
    // Gestion des sélections
    $selected = [
        'filiere' => $_GET['filiere'] ?? null,
        'branche' => $_GET['branche'] ?? null,
        'groupe' => $_GET['groupe'] ?? null,
        'examen' => $_GET['examen_id'] ?? null
    ];
    
    // Chargement des données dépendantes
    $branches = $selected['filiere'] ? $examManager->getBranches($selected['filiere']) : [];
    $groupes = $selected['branche'] ? $examManager->getGroupes($selected['branche']) : [];
    $examens = $selected['groupe'] ? $examManager->getExamens($selected['groupe'], $_SESSION['user']['id']) : [];
    
    // Si l'étudiant_id est fourni, récupérer uniquement ses réponses
    if (isset($_GET['etudiant_id'])) {
        $etudiants = $examManager->getEtudiantReponses($selected['examen'], $_GET['etudiant_id']);
    }
    
    // Traitement de la soumission des notes
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notes'])) {
        if ($examManager->updateNotes($_POST['notes'])) {
            $message = '<div class="success">Les notes ont été mises à jour avec succès.</div>';
            // Recharger les données
            if (isset($_GET['etudiant_id'])) {
                $etudiants = $examManager->getEtudiantReponses($selected['examen'], $_GET['etudiant_id']);
            }
        }
    }
} catch (Exception $e) {
    $message = '<div class="error">Une erreur est survenue : ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction des Examens</title>
    <style>
       :root {
    /* Nouvelle palette de couleurs */
    --primary-color: #0f9ef7;
    --primary-dark: #0d8de0;
    --secondary-color: #6c757d;
    --dark-color: #121212;
    --light-color: #f8f9fa;
    --border-radius: 8px;
    --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

body {
    font-family: Arial, sans-serif;
    background-color: var(--light-color);
    color: var(--dark-color);
    margin: 0;
    padding: 20px;
    transition: background-color 0.3s, color 0.3s;
}

.container {
    max-width: 900px;
    margin: 20px auto;
    padding: 30px;
    background-color: #fff;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    transition: box-shadow 0.3s;
}

h1 {
    color: var(--primary-color);
    text-align: center;
    margin-bottom: 30px;
}

.filters {
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.filters label {
    font-weight: bold;
    color: var(--secondary-color);
}

.filters select {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: var(--border-radius);
    background-color: var(--light-color);
    color: var(--dark-color);
    appearance: none;
    cursor: pointer;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.filters select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 5px rgba(var(--primary-color-rgb), 0.5);
}

.student-section {
    margin-bottom: 20px;
    padding: 20px;
    background-color: var(--light-color);
    border-radius: var(--border-radius);
}

.student-section h2 {
    color: var(--primary-dark);
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.question-item {
    margin-bottom: 15px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: var(--border-radius);
    background-color: #f9f9f9;
}

.question-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 10px;
    align-items: start;
}

.question-text {
    font-weight: bold;
    margin-bottom: 5px;
}

.reponse-text {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: var(--border-radius);
    background-color: #fff;
    margin-top: 5px;
}

.note-max {
    color: var(--secondary-color);
    font-size: 0.9em;
}

.note-input {
    width: 60px;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: var(--border-radius);
    background-color: #fff;
    color: var(--dark-color);
    transition: border-color 0.3s, box-shadow 0.3s;
}

.note-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 5px rgba(var(--primary-color-rgb), 0.5);
}

.submit-btn {
    padding: 10px 20px;
    background-color: var(--primary-color);
    color: #fff;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: background-color 0.3s, transform 0.3s;
}

.submit-btn:hover {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
}

.back-btn {
    display: inline-block;
    padding: 10px 15px;
    background-color: var(--secondary-color);
    color: #fff;
    text-decoration: none;
    border-radius: var(--border-radius);
    transition: background-color 0.3s, transform 0.3s;
}

.back-btn:hover {
    background-color: #555;
    transform: translateY(-2px);
}

.success,
.error {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: var(--border-radius);
}

.success {
    background-color: #d4edda;
    color: #28a745;
    border: 1px solid #c3e6cb;
}

.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

    </style>
</head>
<body>
    <div class="container">
        <h1>Correction des Examens</h1>
        
        <?= $message ?>

        <?php if (!isset($_GET['etudiant_id'])): ?>
            <div class="filters">
                <div>
                    <select name="filiere" id="filiere">
                        <option value="">Sélectionner une filière</option>
                        <?php foreach ($filieres as $filiere): ?>
                            <option value="<?= $filiere['id_f'] ?>" <?= $selected['filiere'] == $filiere['id_f'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($filiere['nom_filiere']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <select name="branche" id="branche" <?= empty($branches) ? 'disabled' : '' ?>>
                        <option value="">Sélectionner une branche</option>
                        <?php foreach ($branches as $branche): ?>
                            <option value="<?= $branche['id_b'] ?>" <?= $selected['branche'] == $branche['id_b'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($branche['nom_branche']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <select name="groupe" id="groupe" <?= empty($groupes) ? 'disabled' : '' ?>>
                        <option value="">Sélectionner un groupe</option>
                        <?php foreach ($groupes as $groupe): ?>
                            <option value="<?= $groupe['id_g'] ?>" <?= $selected['groupe'] == $groupe['id_g'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($groupe['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <select name="examen" id="examen" <?= empty($examens) ? 'disabled' : '' ?>>
                        <option value="">Sélectionner un examen</option>
                        <?php foreach ($examens as $examen): ?>
                            <option value="<?= $examen['id_ex'] ?>" <?= $selected['examen'] == $examen['id_ex'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($examen['titre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php else: ?>
            <a href="liste_etudiants_examen.php?examen_id=<?= $selected['examen'] ?>" class="back-btn">
                Retour à la liste des étudiants
            </a>
        <?php endif; ?>

        <?php if (isset($_GET['etudiant_id']) && !empty($etudiants)): ?>
            <?php foreach ($etudiants as $etudiantId => $etudiant): ?>
                <div class="student-section">
                    <h2>Étudiant : <?= htmlspecialchars($etudiant['nom']) ?></h2>
                    <form method="POST">
                        <?php foreach ($etudiant['reponses'] as $reponse): ?>
                            <div class="question-item">
                                <div class="question-grid">
                                    <div>
                                        <strong>Question :</strong>
                                        <div><?= htmlspecialchars($reponse['texte']) ?></div>
                                        <div class="reponse-text">
                                            <strong>Réponse :</strong>
                                            <div><?= nl2br(htmlspecialchars($reponse['reponse'])) ?></div>
                                        </div>
                                    </div>
                                    <div>
                                        <strong>Note maximale :</strong>
                                        <div class="note-max"><?= htmlspecialchars($reponse['note_max']) ?></div>
                                    </div>
                                    <div>
                                        <strong>Note attribuée :</strong>
                                        <div>
                                            <input type="number" 
                                                   name="notes[<?= $reponse['id_re'] ?>]" 
                                                   value="<?= htmlspecialchars($reponse['note']) ?>" 
                                                   step="0.25" 
                                                   min="0" 
                                                   max="<?= $reponse['note_max'] ?>" 
                                                   class="note-input">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" class="submit-btn">Enregistrer les notes</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filiere = document.getElementById('filiere');
            const branche = document.getElementById('branche');
            const groupe = document.getElementById('groupe');
            const examen = document.getElementById('examen');

            filiere?.addEventListener('change', function() {
                window.location.href = `?filiere=${this.value}`;
            });

            branche?.addEventListener('change', function() {
                window.location.href = `?filiere=${filiere.value}&branche=${this.value}`;
            });

            groupe?.addEventListener('change', function() {
                window.location.href = `?filiere=${filiere.value}&branche=${branche.value}&groupe=${this.value}`;
            });

            examen?.addEventListener('change', function() {
                window.location.href = `liste_etudiants_examen.php?examen_id=${this.value}`;
            });
        });
    </script>
</body>
</html>