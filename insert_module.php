<?php
// Database connection
$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection error: " . $e->getMessage());
}

// Function to get branches for selected filières
function getBranchesByFilieres($pdo, $filiereIds) {
    if (empty($filiereIds)) return [];
    
    $placeholders = implode(',', array_fill(0, count($filiereIds), '?'));
    $query = "SELECT b.*, f.nom_filiere 
              FROM branches b 
              JOIN filieres f ON b.filiere_id = f.id_f 
              WHERE f.id_f IN ($placeholders)
              ORDER BY f.nom_filiere, b.nom_branche";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($filiereIds);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// AJAX handler for dynamic branch loading
if (isset($_GET['action']) && $_GET['action'] == 'get_branches') {
    $selectedFilieres = $_GET['filieres'] ?? [];
    $branches = getBranchesByFilieres($pdo, $selectedFilieres);
    
    echo json_encode($branches);
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $coefficient = $_POST['coefficient'];
    $type = $_POST['type'];
    $description = $_POST['description'] ?? null;
    
    $filieres = $_POST['filieres'] ?? [];
    $branches = $_POST['branches'] ?? [];
    $formateur_id = $_POST['formateur_id'];

    try {
        $pdo->beginTransaction();

        // Verify trainer exists
        $stmtFormateur = $pdo->prepare("SELECT id_u FROM utilisateurs WHERE id_u = ? AND roleu = 'formateur'");
        $stmtFormateur->execute([$formateur_id]);

        if ($stmtFormateur->rowCount() === 0) {
            throw new Exception("Invalid trainer selected.");
        }

        // Insert module
        $stmt = $pdo->prepare("
            INSERT INTO modules 
            (nom, coefficient, type, description, formateur_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nom, $coefficient, $type, $description, $formateur_id]);
        $module_id = $pdo->lastInsertId();

        // Insert module-filière-branche relations
        $stmtRelation = $pdo->prepare("
            INSERT INTO module_branches_formateurs 
            (module_id,  id_branche, formateur_id,) 
            VALUES (?, ?, ?)
        ");

        foreach ($filieres as $filiere_id) {
            foreach ($branches as $branche_id) {
                $stmtRelation->execute([$module_id, $filiere_id, $branche_id]);
            }
        }

        $pdo->commit();
        echo "Module added successfully!";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}

// Fetch filières and formateurs
$queryFiliere = $pdo->query("SELECT * FROM filieres");
$queryFormateur = $pdo->query("SELECT * FROM utilisateurs WHERE roleu = 'formateur'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Module Insertion</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .checkbox-container {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
        }
        .checkbox-group {
            margin-bottom: 10px;
        }
        .checkbox-group-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
<form id="moduleForm" action="insert_module.php" method="POST">
    <div>
        <label for="nom">Module Name:</label>
        <input type="text" id="nom" name="nom" required>
    </div>
    <div>
        <label for="coefficient">Coefficient:</label>
        <input type="number" id="coefficient" name="coefficient" required>
    </div>
    <div>
        <label for="description">Description (optional):</label>
        <textarea id="description" name="description" rows="3"></textarea>
    </div>
    <div>
        <label for="type">Type:</label>
        <select id="type" name="type" required>
            <option value="regional">Regional</option>
            <option value="national">National</option>
        </select>
    </div>
    <div>
        <label>Filières:</label>
        <div id="filieres-container" class="checkbox-container">
            <?php 
            $queryFiliere->execute();
            while ($filiere = $queryFiliere->fetch(PDO::FETCH_ASSOC)): ?>
                <div>
                    <input type="checkbox" id="filiere-<?= $filiere['id_f'] ?>" 
                           name="filieres[]" 
                           value="<?= $filiere['id_f'] ?>"
                           class="filiere-checkbox">
                    <label for="filiere-<?= $filiere['id_f'] ?>"><?= $filiere['nom_filiere'] ?></label>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <div>
        <label>Branches:</label>
        <div id="branches-container" class="checkbox-container">
            <!-- Branches will be dynamically populated -->
        </div>
    </div>
    <div>
        <label for="formateur_id">Trainer:</label>
        <select id="formateur_id" name="formateur_id" required>
            <option value="">Select a Trainer</option>
            <?php 
            $queryFormateur->execute();
            while ($formateur = $queryFormateur->fetch(PDO::FETCH_ASSOC)): ?>
                <option value="<?= $formateur['id_u'] ?>"><?= $formateur['nom'] ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <input type="submit" value="Add Module">
    </div>
</form>

<script>
$(document).ready(function() {
    // Track selected filières and dynamically load branches
    $('.filiere-checkbox').on('change', function() {
        var selectedFilieres = $('.filiere-checkbox:checked').map(function() {
            return this.value;
        }).get();
        
        $.ajax({
            url: 'insert_module.php',
            method: 'GET',
            data: {
                action: 'get_branches',
                filieres: selectedFilieres
            },
            dataType: 'json',
            success: function(branches) {
                // Clear existing branches
                $('#branches-container').empty();
                
                // Group branches by filière
                var branchesByFiliere = {};
                branches.forEach(function(branch) {
                    if (!branchesByFiliere[branch.filiere_id]) {
                        branchesByFiliere[branch.filiere_id] = [];
                    }
                    branchesByFiliere[branch.filiere_id].push(branch);
                });

                // Populate branches with groups
                Object.keys(branchesByFiliere).forEach(function(filiereId) {
                    var filiereGroup = $('<div>').addClass('checkbox-group');
                    var filiereTitle = $('<div>').addClass('checkbox-group-title')
                        .text('Filière: ' + branchesByFiliere[filiereId][0].nom_filiere);
                    filiereGroup.append(filiereTitle);
                    
                    branchesByFiliere[filiereId].forEach(function(branch) {
                        var branchCheckbox = $('<input>')
                            .attr({
                                type: 'checkbox',
                                id: 'branche-' + branch.id_b,
                                name: 'branches[]',
                                value: branch.id_b
                            });
                        
                        var branchLabel = $('<label>')
                            .attr('for', 'branche-' + branch.id_b)
                            .text(`${branch.nom_branche} - ${branch.type_b}`);
                        
                        filiereGroup.append(branchCheckbox, branchLabel, '<br>');
                    });
                    
                    $('#branches-container').append(filiereGroup);
                });
            }
        });
    });
});
</script>
</body>
</html>