<?php
session_start();

$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si l'utilisateur est connecté et est un formateur
    if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'formateur') {
        die("Accès refusé. Vous devez être connecté en tant que formateur.");
    }

    // Récupérer l'ID du formateur
    $formateur_id = $_SESSION['user']['id'];

    // Récupérer les modules assignés au formateur
    $stmtModules = $conn->prepare("
        SELECT id_m, nom, id_branche 
        FROM modules
        WHERE formateur_id = :formateur_id
    ");
    $stmtModules->execute(['formateur_id' => $formateur_id]);
    $modules = $stmtModules->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les groupes basés sur l'id_branche des modules assignés au formateur
    $id_branches = array_column($modules, 'id_branche');
    $placeholders = implode(',', array_fill(0, count($id_branches), '?'));

    $stmtGroupes = $conn->prepare("
        SELECT id_g, nom 
        FROM groupes
        WHERE id_branche IN ($placeholders)
    ");
    $stmtGroupes->execute($id_branches);
    $groupes = $stmtGroupes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Examen</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9f9f9;
            padding: 20px;
        }
        h1, h3 {
            color: #d32f2f;
        }
        form {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: auto;
        }
        .variante {
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 15px;
            border-radius: 8px;
        }
        .question-item {
            margin-bottom: 20px;
        }
        .options-container {
            margin-top: 10px;
        }
        button {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Créer un Examen</h1>
        <form action="creerExamen33.php" method="POST">
            <!-- Champs de base pour l'examen -->
            <div class="mb-3">
                <label for="titre" class="form-label">Titre de l'examen :</label>
                <input type="text" id="titre" name="titre" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="module_id" class="form-label">Module :</label>
                <select id="module_id" name="module_id" class="form-select" required>
                    <?php foreach ($modules as $module): ?>
                        <option value="<?= htmlspecialchars($module['id_m']) ?>">
                            <?= htmlspecialchars($module['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="heure_debut" class="form-label">Heure de début :</label>
                <input type="datetime-local" id="heure_debut" name="heure_debut" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="heure_fin" class="form-label">Heure de fin :</label>
                <input type="datetime-local" id="heure_fin" name="heure_fin" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="duree" class="form-label">Durée (en minutes) :</label>
                <input type="number" id="duree" name="duree" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="groupe_id" class="form-label">Groupe autorisé :</label>
                <select id="groupe_id" name="groupe_id" class="form-select" required>
                    <?php foreach ($groupes as $groupe): ?>
                        <option value="<?= htmlspecialchars($groupe['id_g']) ?>">
                            <?= htmlspecialchars($groupe['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Section pour les variantes -->
            <h3>Variantes</h3>
            <div id="variantes">
                <div class="variante mb-4">
                    <label>Nom de la variante :</label>
                    <input type="text" name="nom_variante[]" class="form-control mb-2" required>
                    <h4>Questions</h4>
                    <div class="questions"></div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="ajouterQuestion(this)">Ajouter une question</button>
                </div>
            </div>
            <button type="button" class="btn btn-secondary mb-3" onclick="ajouterVariante()">Ajouter une variante</button>

            <button type="submit" class="btn btn-primary">Créer l'examen</button>
        </form>
    </div>    <script>
        // Ajouter une variante
        function ajouterVariante() {
            const variantesContainer = document.getElementById('variantes');
            const varianteHTML = `
                <div class="variante mb-4">
                    <label>Nom de la variante :</label>
                    <input type="text" name="nom_variante[]" class="form-control mb-2" required>
                    <h4>Questions</h4>
                    <div class="questions"></div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="ajouterQuestion(this)">Ajouter une question</button>
                </div>`;
            variantesContainer.insertAdjacentHTML('beforeend', varianteHTML);
        }

        // Ajouter une question
        function ajouterQuestion(button) {
            const questionsContainer = button.previousElementSibling;
            const questionHTML = `
                <div class="question-item mb-4">
                    <label>Type de question :</label>
                    <select name="type_question[${questionsContainer.childElementCount}][]" class="form-select mb-2" onchange="gererOptions(this, ${questionsContainer.childElementCount})" required>
                        <option value="text">Texte libre</option>
                        <option value="qcm">Choix multiple</option>
                        <option value="true_false">Vrai ou Faux</option>
                    </select>
                    <input type="text" name="questions[${questionsContainer.childElementCount}][]" class="form-control mb-2" placeholder="Texte de la question" required>
                    <div class="options-container" id="options-container-${questionsContainer.childElementCount}"></div>
                    <label for="note_max_${questionsContainer.childElementCount}" class="form-label">Note maximale :</label>
                    <input type="number" id="note_max_${questionsContainer.childElementCount}" name="note_max[${questionsContainer.childElementCount}][]" class="form-control mb-2" placeholder="Note maximale" required>
                </div>`;
            questionsContainer.insertAdjacentHTML('beforeend', questionHTML);
        }

        // Gérer les options en fonction du type de question
        function gererOptions(selectElement, index) {
            const container = selectElement.closest('.question-item').querySelector('.options-container');
            container.innerHTML = "";

            if (selectElement.value === "qcm") {
                container.innerHTML += `
                    <button type="button" class="btn btn-secondary btn-sm mb-2" onclick="ajouterOption(${index})">Ajouter une option</button>
                    <div id="qcm-options-${index}"></div>`;
            } else if (selectElement.value === "true_false") {
                container.innerHTML += `
                    <div class="mb-1">
                        <label><input type="radio" name="correct[${index}][]" value="Vrai" required> Vrai</label>
                        <label><input type="radio" name="correct[${index}][]" value="Faux" required> Faux</label>
                    </div>`;
            }
        }

        // Ajouter une option pour les questions QCM
        function ajouterOption(index) {
            const optionContainer = document.getElementById(`qcm-options-${index}`);
            const optionCount = optionContainer.childElementCount;
            const optionHTML = `
                <div class="mb-1">
                    <input type="text" name="options[${index}][]" class="form-control mb-1" placeholder="Option ${optionCount + 1}" required>
                    <label><input type="checkbox" name="correct[${index}][]" value="${optionCount + 1}"> Correct</label>
                </div>`;
            optionContainer.insertAdjacentHTML('beforeend', optionHTML);
        }

        // Calculer la durée automatiquement
        document.getElementById('heure_fin').addEventListener('input', function () {
            const heureDebut = document.getElementById('heure_debut').value;
            const heureFin = document.getElementById('heure_fin').value;

            if (heureDebut && heureFin) {
                const debut = new Date(heureDebut);
                const fin = new Date(heureFin);
                const duree = (fin - debut) / (1000 * 60); // Durée en minutes
                document.getElementById('duree').value = duree > 0 ? duree : "Invalide";
            }
        });
    </script>
</body>
</html>