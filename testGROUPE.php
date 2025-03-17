<?php
session_start();

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