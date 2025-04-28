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

            /* Variables additionnelles dérivées de la nouvelle palette */
            --primary-light: #61c1ff;
            --secondary-light: #adb5bd;
            --gray-dark: #343a40;
            --gray: #6c757d;
            --gray-light: #dee2e6;
            --danger: #dc3545;
            --warning: #ffc107;
            --success: #28a745;

            /* Variables de mise en page */
            --sidebar-width: 280px;
            --header-height: 70px;
            --card-shadow: var(--box-shadow);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-color);
            color: var(--dark-color);
            margin: 0;
            padding: 20px;
        }

        form {
            max-width: 700px;
            margin: 20px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--gray-dark);
            font-weight: 500;
        }

        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            box-sizing: border-box;
            font-size: 16px;
            color: var(--dark-color);
            background-color: var(--light-color);
        }

        select {
            appearance: none;
            background-image: url('data:image/svg+xml;charset=UTF-8,<svg viewBox="0 0 24 24" fill="%236c757d" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px top 50%;
            background-size: 20px;
        }

        textarea {
            resize: vertical;
            height: 100px;
        }

        button,
        input[type="submit"] {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        button:hover,
        input[type="submit"]:hover {
            background-color: var(--primary-dark);
        }

        .checkbox-container {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid var(--gray-light);
            padding: 10px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            background-color: #fff;
        }

        .checkbox-group {
            margin-bottom: 10px;
        }

        .checkbox-group-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--gray-dark);
        }

        /* Improved Checkbox Styling */
        input[type="checkbox"] {
            appearance: none;
            /* Hide default checkbox */
            -webkit-appearance: none;
            -moz-appearance: none;
            width: 18px;
            height: 18px;
            border: 1px solid var(--gray);
            border-radius: 3px;
            outline: none;
            cursor: pointer;
            position: relative;
            background-color: var(--light-color);
            transition: background-color 0.2s;
        }

        input[type="checkbox"]:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        input[type="checkbox"]:checked::before {
            content: '\f00c';
            /* FontAwesome checkmark */
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 14px;
            color: white;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        label[for^="filiere-"],
        label[for^="branche-"] {
            margin-left: 5px;
            color: var(--dark-color);
            cursor: pointer;
        }

        /* Validation Styles */
        input:invalid {
            border-color: var(--primary-color);
            /* Change to primary color */
        }

        input:invalid:focus {
            box-shadow: 0 0 5px var(--primary-color);
            /* Change to primary color */
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            form {
                padding: 20px;
            }

            input[type="text"],
            input[type="number"],
            select,
            textarea {
                font-size: 14px;
            }
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