<?php
session_start();
$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if (!isset($_SESSION['user']) || $_SESSION['user']['roleu'] !== 'formateur') {
        die("Accès refusé. Vous devez être connecté en tant que formateur.");
    }
    $formateur_id = $_SESSION['user']['id'];
    // Récupérer les modules assignés au formateur
    $stmtModules = $conn->prepare("SELECT id_m, nom, id_branche FROM modules WHERE formateur_id = :formateur_id");
    $stmtModules->execute(['formateur_id' => $formateur_id]);
    $modules = $stmtModules->fetchAll(PDO::FETCH_ASSOC);
    // Récupérer les groupes basés sur l'id_branche des modules assignés
    $id_branches = array_column($modules, 'id_branche');
    if(count($id_branches) > 0){
      $placeholders = implode(',', array_fill(0, count($id_branches), '?'));
      $stmtGroupes = $conn->prepare("SELECT id_g, nom FROM groupes WHERE id_branche IN ($placeholders)");
      $stmtGroupes->execute($id_branches);
      $groupes = $stmtGroupes->fetchAll(PDO::FETCH_ASSOC);
    } else {
      $groupes = [];
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
  <title>Créer un Examen</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
      font-family: 'Arial', sans-serif;
      background-color: var(--light-color);
      color: var(--dark-color);
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

    h3 {
      color: var(--primary-dark);
      margin-top: 25px;
    }

    form {
      background: #fff;
      padding: 20px;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      max-width: 800px;
      margin: auto;
    }

    label {
      font-weight: bold;
      color: var(--secondary-color);
      display: block;
      margin-bottom: 5px;
    }

    input[type="text"],
    input[type="number"],
    input[type="datetime-local"],
    select {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ddd;
      border-radius: var(--border-radius);
      box-sizing: border-box;
      font-size: 1rem;
      transition: border-color 0.3s;
    }

    input[type="text"]:focus,
    input[type="number"]:focus,
    input[type="datetime-local"]:focus,
    select:focus {
      outline: none;
      border-color: var(--primary-color);
    }

    .variante {
      border: 1px solid #ddd;
      padding: 15px;
      margin-top: 15px;
      border-radius: var(--border-radius);
      background-color: #f9f9f9;
    }

    .question-item {
      margin-bottom: 20px;
      padding: 15px;
      border: 1px solid #eee;
      border-radius: var(--border-radius);
      background-color: #fff;
    }

    .options-container {
      margin-top: 10px;
    }

    button {
      padding: 10px 20px;
      font-size: 1rem;
      border: none;
      border-radius: var(--border-radius);
      cursor: pointer;
      transition: background-color 0.3s, transform 0.3s;
    }

    button:hover {
      transform: translateY(-2px);
    }

    .btn-primary {
      background-color: var(--primary-color);
      color: #fff;
    }

    .btn-primary:hover {
      background-color: var(--primary-dark);
    }

    .btn-secondary {
      background-color: var(--secondary-color);
      color: #fff;
    }

    .btn-secondary:hover {
      background-color: #5a6268;
    }

    .btn-info {
      background-color: #17a2b8;
      color: #fff;
    }

    .btn-info:hover {
      background-color: #138496;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
    }

    .modal-content {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background-color: #fff;
      padding: 20px;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      max-width: 80%;
      max-height: 80%;
      overflow: auto;
    }

    .modal-close {
      position: absolute;
      top: 10px;
      right: 10px;
      font-size: 1.2rem;
      cursor: pointer;
      color: var(--secondary-color);
    }
  </style>
</head>
<body>
  <div class="container mt-5">
    <h1>Créer un Examen</h1>
    <form action="creerExamen33.php" method="POST" id="examForm">
      <!-- Informations de base de l'examen -->
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
      
      <!-- Champ caché pour le nombre de variantes -->
      <input type="hidden" name="nombre_variantes" id="nombre_variantes" value="1">

      <!-- Section pour les variantes -->
      <h3>Variantes</h3>
      <div id="variantes">
        <div class="variante mb-4" data-variante-index="0">
          <label>Nom de la variante :</label>
          <input type="text" name="nom_variante[0]" class="form-control mb-2" required>
          <h4>Questions</h4>
          <div class="questions"></div>
          <button type="button" class="btn btn-secondary btn-sm" onclick="ajouterQuestion(this)">Ajouter une question</button>
        </div>
      </div>
      <button type="button" class="btn btn-secondary mb-3" onclick="ajouterVariante()">Ajouter une variante</button>
      <button type="button" class="btn btn-info mb-3" onclick="debugFormData()">Vérifier les données du formulaire</button>
      <button type="submit" class="btn btn-primary">Créer l'examen</button>
    </form>
  </div>
  
  <script>
  // Compteur global pour les variantes
  let varianteCounter = 1;
  
  // Ajouter une variante
  function ajouterVariante() {
      const container = document.getElementById('variantes');
      const varianteIndex = varianteCounter;
      
      const html = `<div class="variante mb-4" data-variante-index="${varianteIndex}">
          <label>Nom de la variante :</label>
          <input type="text" name="nom_variante[${varianteIndex}]" class="form-control mb-2" required>
          <h4>Questions</h4>
          <div class="questions"></div>
          <button type="button" class="btn btn-secondary btn-sm" onclick="ajouterQuestion(this)">Ajouter une question</button>
      </div>`;
      
      container.insertAdjacentHTML('beforeend', html);
      varianteCounter++;
      
      // Mettre à jour le nombre total de variantes
      document.getElementById('nombre_variantes').value = varianteCounter;
  }

  // Ajouter une question dans la variante courante
  function ajouterQuestion(button) {
      const varianteElement = button.closest('.variante');
      const varianteIndex = varianteElement.getAttribute('data-variante-index');
      const questionsContainer = varianteElement.querySelector('.questions');
      const questionIndex = questionsContainer.childElementCount;
      
      const html = `<div class="question-item mb-4" data-question-index="${questionIndex}">
          <label>Type de question :</label>
          <select name="type_question[${varianteIndex}][${questionIndex}]" class="form-select mb-2" onchange="gererOptions(this, ${varianteIndex}, ${questionIndex})" required>
              <option value="text">Texte libre</option>
              <option value="qcm">Choix multiple</option>
              <option value="true_false">Vrai ou Faux</option>
          </select>
          <input type="text" name="questions[${varianteIndex}][${questionIndex}]" class="form-control mb-2" placeholder="Texte de la question" required>
          <div class="options-container" id="options-container-${varianteIndex}-${questionIndex}"></div>
          <label>Note maximale :</label>
          <input type="number" name="note_max[${varianteIndex}][${questionIndex}]" class="form-control mb-2" placeholder="Note maximale" required>
      </div>`;
      
      questionsContainer.insertAdjacentHTML('beforeend', html);
  }

  // Gérer les options selon le type de question
  function gererOptions(selectElement, varianteIndex, questionIndex) {
      const container = document.getElementById(`options-container-${varianteIndex}-${questionIndex}`);
      container.innerHTML = "";
      
      if (selectElement.value === "qcm") {
          container.insertAdjacentHTML('beforeend', `
          <button type="button" class="btn btn-secondary btn-sm mb-2" onclick="ajouterOption(${varianteIndex}, ${questionIndex})">Ajouter une option</button>
          <div id="qcm-options-${varianteIndex}-${questionIndex}"></div>`);
      } else if (selectElement.value === "true_false") {
          container.insertAdjacentHTML('beforeend', `
          <div class="mb-1">
              <label><input type="radio" name="correct[${varianteIndex}][${questionIndex}]" value="Vrai" required> Vrai</label>
              <label><input type="radio" name="correct[${varianteIndex}][${questionIndex}]" value="Faux" required> Faux</label>
          </div>`);
      }
  }

  // Ajouter une option pour les questions QCM
  function ajouterOption(varianteIndex, questionIndex) {
      const container = document.getElementById(`qcm-options-${varianteIndex}-${questionIndex}`);
      const optionIndex = container.childElementCount;
      
      const html = `<div class="mb-1">
          <input type="text" name="options[${varianteIndex}][${questionIndex}][${optionIndex}]" class="form-control mb-1" placeholder="Option ${optionIndex + 1}" required>
          <label><input type="checkbox" name="correct[${varianteIndex}][${questionIndex}][${optionIndex}]" value="1"> Correct</label>
      </div>`;
      
      container.insertAdjacentHTML('beforeend', html);
  }

  // Calculer la durée automatiquement
  document.getElementById('heure_fin').addEventListener('input', function () {
      const heureDebut = document.getElementById('heure_debut').value;
      const heureFin = document.getElementById('heure_fin').value;

      if (heureDebut && heureFin) {
          const debut = new Date(heureDebut);
          const fin = new Date(heureFin);
          const duree = Math.floor((fin - debut) / (1000 * 60)); // Durée en minutes
          document.getElementById('duree').value = duree > 0 ? duree : "";
      }
  });
  
  // Fonction de débogage pour vérifier les données avant soumission
  function debugFormData() {
      const formData = new FormData(document.getElementById('examForm'));
      let debugOutput = 'Données du formulaire:\n\n';
      
      for (let [key, value] of formData.entries()) {
          debugOutput += `${key}: ${value}\n`;
      }
      
      // Crée une fenêtre modale pour afficher les données
      const modal = document.createElement('div');
      modal.style.position = 'fixed';
      modal.style.top = '50%';
      modal.style.left = '50%';
      modal.style.transform = 'translate(-50%, -50%)';
      modal.style.width = '80%';
      modal.style.maxWidth = '800px';
      modal.style.maxHeight = '80vh';
      modal.style.overflowY = 'auto';
      modal.style.backgroundColor = 'white';
      modal.style.padding = '20px';
      modal.style.boxShadow = '0 0 20px rgba(0,0,0,0.3)';
      modal.style.zIndex = '9999';
      modal.style.borderRadius = '8px';
      
      const closeBtn = document.createElement('button');
      closeBtn.textContent = 'Fermer';
      closeBtn.className = 'btn btn-primary';
      closeBtn.onclick = function() { document.body.removeChild(modal); };
      
      const pre = document.createElement('pre');
      pre.style.whiteSpace = 'pre-wrap';
      pre.style.maxHeight = 'calc(80vh - 100px)';
      pre.style.overflowY = 'auto';
      pre.textContent = debugOutput;
      
      modal.appendChild(pre);
      modal.appendChild(closeBtn);
      document.body.appendChild(modal);
  }
  </script>
</body>
</html>