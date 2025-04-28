<?php
header('Content-Type: application/json');
require_once 'db.php'; // Utilisation de votre fichier existant

// Vérifier l'action demandée
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'getGroups':
            // Récupérer tous les groupes
            $stmt = $conn->query("SELECT id_g as id, nom FROM groupes ORDER BY nom");
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($groups);
            break;

        case 'getExams':
            // Récupérer les examens d'un groupe
            $groupId = $_GET['group_id'] ?? null;
            if (!$groupId) {
                echo json_encode([]);
                break;
            }

            $stmt = $conn->prepare("
                SELECT e.id_ex as id, e.titre, g.nom as groupe_nom, 
                       DATE_FORMAT(e.heure_debut, '%d/%m/%Y') as date_examen
                FROM examens3 e
                JOIN groupes g ON e.groupe_id = g.id_g
                WHERE e.groupe_id = :group_id
                ORDER BY e.heure_debut DESC
            ");
            $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'getResults':
            // Récupérer les résultats selon les filtres
            $groupId = $_GET['group_id'] ?? null;
            $examId = $_GET['exam_id'] ?? null;

            $where = [];
            $params = [];

            if ($examId) {
                $where[] = "e.id_ex = :exam_id";
                $params[':exam_id'] = $examId;
            } elseif ($groupId) {
                $where[] = "e.groupe_id = :group_id";
                $params[':group_id'] = $groupId;
            }

            $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

            $query = "
                SELECT 
                    u.id_u as id_etudiant, u.nom, u.prenom,
                    e.id_ex as examen_id, e.titre as exam_titre, 
                    g.nom as groupe_nom, DATE_FORMAT(e.heure_debut, '%d/%m/%Y') as exam_date,
                    SUM(re.note) as note_obtenue, SUM(q.note_max) as total_points,
                    ROUND((SUM(re.note) / SUM(q.note_max)) * 100, 1) as score
                FROM reponses_etudiants2 re
                JOIN questions3 q ON re.question_id = q.id_q
                JOIN utilisateurs u ON re.etudiant_id = u.id_u
                JOIN examens3 e ON re.examen_id = e.id_ex
                JOIN groupes g ON e.groupe_id = g.id_g
                $whereClause
                GROUP BY u.id_u, u.nom, u.prenom, e.id_ex, e.titre, g.nom, e.heure_debut
                ORDER BY u.nom, u.prenom
            ";

            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            // Formater les résultats pour le frontend
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ajouter des classes de score pour le CSS
            foreach ($results as &$result) {
                $result['score_class'] = 'score-';
                if ($result['score'] >= 70) {
                    $result['score_class'] .= 'high';
                } elseif ($result['score'] >= 50) {
                    $result['score_class'] .= 'medium';
                } else {
                    $result['score_class'] .= 'low';
                }
            }
            
            echo json_encode($results);
            break;

        case 'exportCSV':
            // Export CSV
            $examId = $_GET['exam_id'] ?? null;
            if (!$examId) {
                die(json_encode(['error' => 'Aucun examen spécifié']));
            }

            // Récupérer les infos de l'examen
            $stmt = $conn->prepare("
                SELECT e.titre, g.nom as groupe_nom, e.heure_debut
                FROM examens3 e
                JOIN groupes g ON e.groupe_id = g.id_g
                WHERE e.id_ex = :exam_id
            ");
            $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
            $stmt->execute();
            $exam_info = $stmt->fetch(PDO::FETCH_ASSOC);

            // Récupérer les résultats
            $stmt = $conn->prepare("
                SELECT 
                    u.nom, u.prenom, 
                    SUM(re.note) as note, 
                    SUM(q.note_max) as note_max,
                    ROUND((SUM(re.note) / SUM(q.note_max)) * 100, 1) as score
                FROM reponses_etudiants2 re
                JOIN questions3 q ON re.question_id = q.id_q
                JOIN utilisateurs u ON re.etudiant_id = u.id_u
                WHERE re.examen_id = :exam_id
                GROUP BY u.id_u, u.nom, u.prenom
                ORDER BY u.nom, u.prenom
            ");
            $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
            $stmt->execute();
            $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Générer le CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=resultats_' . preg_replace('/[^a-z0-9]/i', '_', $exam_info['titre']) . '.csv');

            $output = fopen('php://output', 'w');

            // En-tête
            fputcsv($output, ['Examen:', $exam_info['titre']]);
            fputcsv($output, ['Groupe:', $exam_info['groupe_nom']]);
            fputcsv($output, ['Date:', date('d/m/Y', strtotime($exam_info['heure_debut']))]);
            fputcsv($output, []); // Ligne vide
            fputcsv($output, ['Nom', 'Prénom', 'Note', 'Note maximale', 'Score (%)']);

            // Données
            foreach ($resultats as $resultat) {
                fputcsv($output, [
                    $resultat['nom'],
                    $resultat['prenom'],
                    $resultat['note'],
                    $resultat['note_max'],
                    $resultat['score']
                ]);
            }

            fclose($output);
            exit();
            break;

        default:
            echo json_encode(['error' => 'Action non reconnue']);
            break;
    }
} catch(PDOException $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur de base de données']);
}