<?php
session_start();
$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier si l'utilisateur est connecté en tant que formateur
    if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'formateur') {
        die("Accès refusé. Vous devez être connecté en tant que formateur.");
    }
    
    $formateur_id = $_SESSION['user']['id'];
    
    // Traitement de la mise à jour du statut si un formulaire est soumis
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['examen_id']) && isset($_POST['statut'])) {
        $examen_id = $_POST['examen_id'];
        $statut = $_POST['statut'];
        
        $stmtUpdate = $conn->prepare("UPDATE examens3 SET statut = :statut WHERE id_ex = :examen_id AND formateur_id = :formateur_id");
        $stmtUpdate->execute([
            'statut' => $statut,
            'examen_id' => $examen_id,
            'formateur_id' => $formateur_id
        ]);
        
        // Message de confirmation
        $message = "Le statut de l'examen a été mis à jour avec succès.";
    }
    
    // Récupérer tous les examens créés par le formateur
    $stmt = $conn->prepare("
        SELECT e.id_ex, e.titre, e.heure_debut, e.heure_fin, e.duree, e.statut, 
               m.nom AS module_nom, g.nom AS groupe_nom
        FROM examens3 e
        JOIN modules m ON e.module_id = m.id_m
        JOIN groupes g ON e.groupe_id = g.id_g
        WHERE e.formateur_id = :formateur_id
        ORDER BY e.heure_debut DESC
    ");
    $stmt->execute(['formateur_id' => $formateur_id]);
    $examens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Examens</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: #f9f9f9; 
            padding: 20px; 
        }
        h1 { 
            color: #d32f2f; 
            margin-bottom: 30px; 
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .badge-disponible {
            background-color: #28a745;
        }
        .badge-indisponible {
            background-color: #dc3545;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Mes Examens</h1>
            <a href="creerExamen22.php" class="btn btn-primary">Créer un nouvel examen</a>
        </div>
        
        <?php if(isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if(empty($examens)): ?>
            <div class="alert alert-info">
                Vous n'avez pas encore créé d'examens.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach($examens as $examen): ?>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <?= htmlspecialchars($examen['titre']) ?>
                                <span class="badge <?= $examen['statut'] === 'disponible' ? 'badge-disponible' : 'badge-indisponible' ?> float-end">
                                    <?= ucfirst(htmlspecialchars($examen['statut'])) ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <p><strong>Module:</strong> <?= htmlspecialchars($examen['module_nom']) ?></p>
                                <p><strong>Groupe:</strong> <?= htmlspecialchars($examen['groupe_nom']) ?></p>
                                <p><strong>Date:</strong> <?= date('d/m/Y', strtotime($examen['heure_debut'])) ?></p>
                                <p><strong>Horaire:</strong> <?= date('H:i', strtotime($examen['heure_debut'])) ?> à <?= date('H:i', strtotime($examen['heure_fin'])) ?></p>
                                <p><strong>Durée:</strong> <?= htmlspecialchars($examen['duree']) ?> minutes</p>
                                
                                <div class="actions">
                                    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                                        <input type="hidden" name="examen_id" value="<?= $examen['id_ex'] ?>">
                                        <input type="hidden" name="statut" value="<?= $examen['statut'] === 'disponible' ? 'indisponible' : 'disponible' ?>">
                                        <button type="submit" class="btn btn-sm <?= $examen['statut'] === 'disponible' ? 'btn-danger' : 'btn-success' ?>">
                                            <?= $examen['statut'] === 'disponible' ? 'Rendre indisponible' : 'Rendre disponible' ?>
                                        </button>
                                    </form>
                                    <!-- <a href="voir_examen.php?id=<?= $examen['id_ex'] ?>" class="btn btn-sm btn-info">Détails</a>
                                    <a href="modifier_examen.php?id=<?= $examen['id_ex'] ?>" class="btn btn-sm btn-warning">Modifier</a> -->
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>