<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Admin</title>
</head>
<body>
    <h1>Bienvenue, Administrateur</h1>
    <p>Vous êtes connecté en tant qu'administrateur.</p>

    <?php
    session_start();

    $host = "localhost";
    $dbname = "examens_db";
    $username = "root";
    $password = "DD202";

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Erreur : " . $e->getMessage());
    }

    // Récupération des données nécessaires
    $formateurs = $conn->query("SELECT id_u, nom , prenom FROM utilisateurs WHERE roleu = 'formateur'")->fetchAll(PDO::FETCH_ASSOC);
    $etudiants = $conn->query("SELECT id_u, nom ,prenom FROM utilisateurs WHERE roleu = 'etudiant'")->fetchAll(PDO::FETCH_ASSOC);
    $groupes = $conn->query("SELECT id_g, nom FROM groupes")->fetchAll(PDO::FETCH_ASSOC);
    $branches = $conn->query("SELECT id_b, nom_branche FROM branches")->fetchAll(PDO::FETCH_ASSOC);
    $modules = $conn->query("SELECT id_m, nom FROM modules")->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <!-- Ajouter un utilisateur -->
    <form method="POST" action="admin_action.php">
        <h3>Ajouter un utilisateur</h3>
        <label for="nom">Nom :</label>
        <input type="text" id="nom" name="nom" required>

        <label for="prenom">Prenom :</label>
        <input type="text" id="prenom" name="prenom" required>
        
        <label for="email">Email :</label>
        <input type="email" id="email" name="email" required>
        
        <label for="password">Mot de passe :</label>
        <input type="password" id="password" name="password" required>
        
        <label for="roleu">Rôle :</label>
        <select id="roleu" name="roleu" required>
            <option value="formateur">Formateur</option>
            <option value="etudiant">Étudiant</option>
        </select>
        
        <button type="submit" name="action" value="add_user">Ajouter</button>
    </form>

    <!-- Créer un groupe -->
    <?php
   

    $host = "localhost";
    $dbname = "examens_db";
    $username = "root";
    $password = "DD202";

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Erreur : " . $e->getMessage());
    }

    // Récupération des données nécessaires
    $formateurs = $conn->query("SELECT id_u, nom , prenom FROM utilisateurs WHERE roleu = 'formateur'")->fetchAll(PDO::FETCH_ASSOC);
    $etudiants = $conn->query("SELECT id_u, nom ,prenom FROM utilisateurs WHERE roleu = 'etudiant'")->fetchAll(PDO::FETCH_ASSOC);
    $groupes = $conn->query("SELECT id_g, nom FROM groupes")->fetchAll(PDO::FETCH_ASSOC);
    $branches = $conn->query("SELECT id_b, nom_branche FROM branches")->fetchAll(PDO::FETCH_ASSOC);
    $modules = $conn->query("SELECT id_m, nom FROM modules")->fetchAll(PDO::FETCH_ASSOC);
    $filieres_stmt = $conn->query("SELECT * FROM filieres");
    $filieres = $filieres_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si le formulaire est soumis pour créer un groupe
    
 

$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $groupes = $conn->query("SELECT id_g, nom FROM groupes")->fetchAll(PDO::FETCH_ASSOC);
    $branches = $conn->query("SELECT id_b, nom_branche FROM branches")->fetchAll(PDO::FETCH_ASSOC);
    $modules = $conn->query("SELECT id_m, nom FROM modules")->fetchAll(PDO::FETCH_ASSOC);
    $filieres_stmt = $conn->query("SELECT * FROM filieres");
    $filieres = $filieres_stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'add_group':
                try {
                    $nom_groupe = trim($_POST['nom_groupe']);
                    $filiere_id = $_POST['filiere_id'];
                    $type_groupe = $_POST['type_groupe'];
                    $niveau = $_POST['niveau'];
                    $id_branche = $type_groupe === 'branche' ? $_POST['id_b'] : null;
    
                    // Validation des champs
                    if (empty($nom_groupe) || empty($filiere_id) || empty($type_groupe) || empty($niveau)) {
                        throw new Exception("Tous les champs sont obligatoires.");
                    }
    
                    if ($type_groupe === 'branche' && empty($id_branche)) {
                        throw new Exception("Veuillez sélectionner une branche pour le groupe de type 'branche'.");
                    }
    
                    // Préparation de l'insertion
                    $stmt = $conn->prepare("
                        INSERT INTO groupes (nom, filiere_id, type_groupe, niveau, id_branche) 
                        VALUES (:nom, :filiere_id, :type_groupe, :niveau, :id_branche)
                    ");
                    $stmt->execute([
                        ':nom' => $nom_groupe,
                        ':filiere_id' => $filiere_id,
                        ':type_groupe' => $type_groupe,
                        ':niveau' => $niveau,
                        ':id_branche' => $id_branche
                    ]);
    
                    $_SESSION['success'] = "Groupe créé avec succès";
                } catch (Exception $e) {
                    $_SESSION['error'] = $e->getMessage();
                }
                header('Location: admin.php');
                exit();
                break;
        }
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
    <title>Gestion des Groupes</title>
</head>
<body>
<form method="POST">
    <h3>Créer un groupe</h3>
    
    <label for="nom_groupe">Nom du groupe :</label>
    <input type="text" name="nom_groupe" id="nom_groupe" required><br>

    <label for="filiere_id">Choisir une filière :</label>
    <select id="filiere_id" name="filiere_id" required>
        <option value="">Sélectionner une filière</option>
        <?php foreach ($filieres as $filiere): ?>
            <option value="<?= $filiere['id_f'] ?>"><?= $filiere['nom_filiere'] ?></option>
        <?php endforeach; ?>
    </select><br>

    <label>Type :</label>
    <input type="radio" id="tronc_commun" name="type_groupe" value="tronc_commun"> Tronc Commun
    <input type="radio" id="branche" name="type_groupe" value="branche"> Branche<br>

    <div id="niveauDiv">
        <!-- Le niveau sera injecté ici -->
    </div>

    <div id="brancheDiv">
        <!-- Les branches seront injectées ici -->
    </div>

    <button type="submit" name="action" value="add_group">Ajouter</button>
</form>


    <script>function chargerOptions() {
    var filiereId = document.getElementById("filiere_id").value;
    var typeGroupe = document.querySelector('input[name="type_groupe"]:checked');
    
    var niveauDiv = document.getElementById("niveauDiv");
    var brancheDiv = document.getElementById("brancheDiv");
    
    // Debug
    console.log('Filière sélectionnée:', filiereId);
    console.log('Type groupe sélectionné:', typeGroupe ? typeGroupe.value : 'aucun');
    
    // Réinitialiser les divs
    niveauDiv.innerHTML = '';
    brancheDiv.innerHTML = '';
    
    if (!typeGroupe || !filiereId) {
        console.log('Données manquantes:', {filiereId, typeGroupe});
        return;
    }
    
    var typeValue = typeGroupe.value;

    if (typeValue === "tronc_commun") {
        niveauDiv.innerHTML = '<input type="hidden" name="niveau" value="1ere_annee">';
        chargerBranches(filiereId, 'tronc_commun');
    } 
    else if (typeValue === "branche") {
        niveauDiv.innerHTML = '<input type="hidden" name="niveau" value="2eme_annee">';
        chargerBranches(filiereId, 'branche');
    }
}

function chargerBranches(filiereId, type) {
    var brancheDiv = document.getElementById("brancheDiv");
    brancheDiv.innerHTML = '<p>Chargement des branches...</p>';
    
    const url = `get_branches.php?id_filiere=${encodeURIComponent(filiereId)}&type=${encodeURIComponent(type)}`;
    console.log('URL de requête:', url);
    
    fetch(url)
        .then(response => {
            console.log('Statut de la réponse:', response.status);
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Données reçues:', data);
            
            if (data.error) {
                brancheDiv.innerHTML = `<p style="color: red;">Erreur: ${data.error}</p>`;
                if (data.debug) {
                    console.log('Debug info:', data.debug);
                }
                return;
            }
            
            let html = '<label for="id_b">Choisir une branche:</label>' +
                      '<select id="id_b" name="id_b" required>' +
                      '<option value="">Sélectionner une branche</option>';
            
            data.forEach(branche => {
                html += `<option value="${branche.id_b}">${branche.nom_branche}</option>`;
            });
            
            html += '</select>';
            brancheDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Erreur détaillée:', error);
            brancheDiv.innerHTML = `<p style="color: red;">Erreur lors du chargement des branches: ${error.message}</p>`;
        });
}

document.addEventListener('DOMContentLoaded', function() {
    const filiereSelect = document.getElementById("filiere_id");
    const typeRadios = document.querySelectorAll('input[name="type_groupe"]');
    
    if (filiereSelect) {
        filiereSelect.addEventListener("change", chargerOptions);
        console.log('Event listener ajouté pour filiere_id');
    }
    
    typeRadios.forEach(radio => {
        radio.addEventListener("change", chargerOptions);
        console.log('Event listener ajouté pour radio button:', radio.value);
    });
});
    </script>
</body>
</html>
    <!-- Assigner un étudiant à un groupe -->
    <form method="POST" action="admin_action.php">
        <h3>Assigner un étudiant à un groupe</h3>
        <label for="etudiant_id">Étudiant :</label>
        <select id="etudiant_id" name="etudiant_id" required>
            <?php if (empty($etudiants)): ?>
                <option disabled>Aucun étudiant disponible</option>
            <?php else: ?>
                <?php foreach ($etudiants as $etudiant): ?>
                    <option value="<?= $etudiant['id_u'] ?>"><?= $etudiant['nom'] ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        
        <label for="groupe_id">Groupe :</label>
        <select id="groupe_id" name="groupe_id" required>
            <?php if (empty($groupes)): ?>
                <option disabled>Aucun groupe disponible</option>
            <?php else: ?>
                <?php foreach ($groupes as $groupe): ?>
                    <option value="<?= $groupe['id_g'] ?>"><?= $groupe['nom'] ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        
        <button type="submit" name="action" value="assign_group">Assigner</button>
    </form>
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
            INSERT INTO module_filiere_branche 
            (module_id, filiere_id, branche_id) 
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
<body><h1>Module Insertion</h1>
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
  <?php
// Récupération des données nécessaires
$formateurs = $conn->query("SELECT id_u, nom ,prenom FROM utilisateurs WHERE roleu = 'formateur'")->fetchAll(PDO::FETCH_ASSOC);
$etudiants = $conn->query("SELECT id_u, nom ,prenom FROM utilisateurs WHERE roleu = 'etudiant'")->fetchAll(PDO::FETCH_ASSOC);
$groupes = $conn->query("SELECT id_g, nom FROM groupes")->fetchAll(PDO::FETCH_ASSOC);
$modules = $conn->query("SELECT id_m, nom FROM modules")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des utilisateurs
$utilisateurs = $conn->query("SELECT id_u, nom, prenom ,email, roleu, statut FROM utilisateurs WHERE roleu = 'etudiant ' or roleu = 'formateur' ")->fetchAll(PDO::FETCH_ASSOC);
?>
<h1>gestion des etudiants</h1>
    <table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prenom</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($utilisateurs as $user): ?>
            <tr>
                <td><?= $user['id_u'] ?></td>
                <td><?= htmlspecialchars($user['nom']) ?></td>
                <td><?= htmlspecialchars($user['prenom']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['roleu']) ?></td>
                <td><?= htmlspecialchars($user['statut']) ?></td>
                <td>
                    <?php if ($user['statut'] === 'actif'): ?>
                        <form method="post" action="admin_action.php" style="display:inline;">
                            <input type="hidden" name="action" value="block_user">
                            <input type="hidden" name="user_id" value="<?= $user['id_u'] ?>">
                            <button type="submit" onclick="return confirm('Voulez-vous vraiment bloquer cet utilisateur ?');">Bloquer</button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="admin_action.php" style="display:inline;">
                            <input type="hidden" name="action" value="unblock_user">
                            <input type="hidden" name="user_id" value="<?= $user['id_u'] ?>">
                            <button type="submit" onclick="return confirm('Voulez-vous vraiment débloquer cet utilisateur ?');">Débloquer</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>