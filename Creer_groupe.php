<?php
session_start();

$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des données nécessaires
    $filieres = $conn->query("SELECT * FROM filieres")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de connexion : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Groupes</title>
    <style>
        form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-top: 10px;
        }

        input,
        select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        button {
            margin-top: 15px;
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .success-message {
            color: green;
            margin: 10px 0;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 4px;
        }

        .error-message {
            color: red;
            margin: 10px 0;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
        }

        input[type="radio"] {
            width: auto;
            margin-right: 10px;
        }

        .radio-group {
            margin: 10px 0;
        }
    </style>
</head>

<body>

    <form method="POST" action="admin_action.php">
        <h3>Créer un groupe</h3>
        <?php
        // Afficher les messages de session
        if (isset($_SESSION['success'])) {
            echo '<div class="success-message">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        ?>
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


    <script>
        function chargerOptions() {
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
                console.log('Données manquantes:', { filiereId, typeGroupe });
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

        document.addEventListener('DOMContentLoaded', function () {
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