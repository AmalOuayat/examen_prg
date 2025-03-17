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
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --success-color: #27ae60;
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
            max-width: 1200px;
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

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        select {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: white;
        }

        .student-section {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .student-section h2 {
            color: var(--secondary-color);
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .question-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }

        .question-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .reponse-text {
            background: white;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin: 10px 0;
        }

        .note-input {
            width: 80px;
            padding: 5px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .note-max {
            color: var(--secondary-color);
            font-weight: bold;
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 15px;
        }

        .submit-btn:hover {
            background-color: var(--secondary-color);
        }

        .success {
            background-color: #d4edda;
            color: var(--success-color);
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .error {
            background-color: #f8d7da;
            color: var(--error-color);
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--secondary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background-color: var(--primary-color);
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