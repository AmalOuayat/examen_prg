<?php
session_start();

// Active l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Paramètres de connexion
$host = "localhost";
$username = "root";
$password = "DD202";
$dbname = "examens_db";

// Établir la connexion
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES utf8mb4");
} catch(PDOException $e) {
    die("ERREUR CRITIQUE: Impossible de se connecter à la base de données. Message: " . $e->getMessage());
}

// Variable pour suivre le traitement du formulaire
$form_submitted = false;
$success_message = "";

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_submitted = true;
    
    // Récupération et validation des données
    $errors = [];
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $date_naissance = $_POST['date_naissance'] ?? '';
    $filiere = intval($_POST['filiere'] ?? 0);
    $branche = intval($_POST['branche'] ?? 0);
    $groupe = intval($_POST['groupe'] ?? 0);
    $promotion = trim($_POST['promotion'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $niveau = $_POST['niveau'] ?? '1ere_annee';
    
    // Debug - Afficher les données reçues
    // echo "<pre>"; print_r($_POST); echo "</pre>";
    
    // Récupérer l'année académique
    try {
        $annee_academique_query = $pdo->query("SELECT id_a FROM annees_ac ORDER BY id_a DESC LIMIT 1");
        $annee_academique = $annee_academique_query->fetch();
        if (!$annee_academique) {
            // Si aucune année académique n'existe, on en crée une
            $pdo->exec("INSERT INTO annees_ac (annee) VALUES ('2024-2025')");
            $annee_academique_id = $pdo->lastInsertId();
        } else {
            $annee_academique_id = $annee_academique['id_a'];
        }
    } catch(PDOException $e) {
        $errors['system'] = "Erreur système avec l'année académique: " . $e->getMessage();
    }
    
    // Validation des champs
    if (empty($nom)) $errors['nom'] = "Nom requis";
    if (empty($prenom)) $errors['prenom'] = "Prénom requis";
    if (empty($date_naissance)) $errors['date_naissance'] = "Date de naissance requise";
    if ($filiere <= 0) $errors['filiere'] = "Filière requise";
    if ($branche <= 0) $errors['branche'] = "Branche requise";
    if ($groupe <= 0) $errors['groupe'] = "Groupe requis";
    if (empty($promotion)) $errors['promotion'] = "Promotion requise";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Email invalide";
    if (strlen($password) < 6) $errors['password'] = "6 caractères minimum";
    if ($password !== $confirm_password) $errors['confirm_password'] = "Mots de passe différents";

    // Si pas d'erreurs, traitement
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
// Vérifier si l'email existe déjà
$stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetchColumn() > 0) {
    $errors['email'] = "Cet email est déjà utilisé.";
}

            // 1. Création utilisateur
            $stmt = $pdo->prepare("INSERT INTO utilisateurs 
                              (nom, prenom, email, mot_de_passe, date_naissance, roleu, statut) 
                              VALUES (?, ?, ?, ?, ?, 'etudiant', 'inactif')");
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([$nom, $prenom, $email, $hashed_password, $date_naissance]);
            $user_id = $pdo->lastInsertId();

            // 2. Création étudiant
            $stmt = $pdo->prepare("INSERT INTO etudiants (utilisateur_id, promotion) VALUES (?, ?)");
            $stmt->execute([$user_id, $promotion]);
            $etudiant_id = $pdo->lastInsertId();

            // 3. Association au groupe
            $stmt = $pdo->prepare("INSERT INTO etudiants_groupes (etudiant_id, id_groupe) VALUES (?, ?)");
            $stmt->execute([$etudiant_id, $groupe]);

            // 4. Parcours étudiant - Vérifions les noms des colonnes
            $stmt = $pdo->prepare("INSERT INTO parcours_etudiants 
                              (etudiant_id, annee_academique_id, filiere_id, branche_id, groupe_id, niveau) 
                              VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$etudiant_id, $annee_academique_id, $filiere, $branche, $groupe, $niveau]);

            $pdo->commit();
            $success_message = "Inscription réussie ! Votre compte doit être activé par un administrateur avant de pouvoir vous connecter.";
            
            // Réinitialiser les variables pour un nouveau formulaire propre
            $nom = $prenom = $date_naissance = $promotion = $email = "";
            $filiere = $branche = $groupe = 0;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['database'] = "Erreur d'inscription: " . $e->getMessage();
            
            // Debug - Afficher les informations de l'erreur
            echo "<div style='background-color: #ffcccc; padding: 10px; margin: 10px; border: 1px solid red;'>";
            echo "<strong>Erreur PDO:</strong> " . $e->getMessage();
            echo "<br><strong>Code:</strong> " . $e->getCode();
            echo "<br><strong>Trace:</strong> <pre>" . $e->getTraceAsString() . "</pre>";
            echo "</div>";
        }
    } else {
        // Debug - Afficher les erreurs de validation
        // echo "<pre>Erreurs: "; print_r($errors); echo "</pre>";
    }
}

// Récupération des données pour les selects
$filieres = $pdo->query("SELECT id_f as id, nom_filiere as nom FROM filieres")->fetchAll();
$branches = $pdo->query("SELECT id_b as id, nom_branche as nom, filiere_id FROM branches")->fetchAll();
$groupes = $pdo->query("SELECT id_g as id, nom, id_branche FROM groupes")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Étudiant</title>
    <!-- Styles ici (inchangés) -->
    <style>
  :root {
    --primary:rgb(23, 78, 166);       /* bleu vif */
    --gray-100: #f9fafb;      /* texte clair */
    --gray-300: #9ca3af;      /* bordures claires */
    --gray-700: #4b5563;      /* bordures et labels foncés */
    --gray-800: #121212;      /* fond container */
    --gray-900:rgb(10, 12, 17);      /* fond global */
    --white: #ffffff;         /* blanc pur */
    --danger: #f87171;        /* rouge vif */
  }

  *, *::before, *::after {
    box-sizing: border-box;
  }

  body {
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");

    font-family: 'Inter', sans-serif;
    margin: 0;
    background-color: var(--gray-900);
    color: var(--gray-100);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 2rem;
  }

  .container {
    background-color: var(--gray-800);
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
    max-width: 700px;
    width: 100%;
  }

  h1, .form-section h2 {
    color: var(--primary);
    margin-bottom: 1rem;
  }

  .form-section h2 {
    font-size: 1.25rem;
    font-weight: 600;
    border-bottom: 1px solid var(--gray-700);
    padding-bottom: 0.5rem;
  }

  .form-group {
    margin-bottom: 1.25rem;
  }

  label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gray-100);
  }

  input, select {
    width: 100%;
    padding: 0.75rem 1rem;
    background-color: var(--gray-900);
    color: var(--gray-100);
    border: 1px solid var(--gray-700);
    border-radius: 8px;
    font-size: 1rem;
    transition: border 0.2s, box-shadow 0.2s;
  }

  input::placeholder {
    color: var(--gray-300);
  }

  input:focus, select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.4);
    outline: none;
  }

  .form-row {
    display: flex;
    gap: 1rem;
  }

  .error-message, .error {
    color: var(--danger);
    font-size: 0.9rem;
    margin-top: 0.25rem;
  }

  .btn-submit {
    background-color: var(--primary);
    color: var(--white);
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
    width: 100%;
    margin-top: 1rem;
  }

  .btn-submit:hover {
    background-color: #2563eb;
  }

  a {
    color: var(--primary);
    text-decoration: none;
  }
  a:hover {
    text-decoration: underline;
  }

  .login-link {
    text-align: center;
    margin-top: 1rem;
    color: var(--gray-300);
  }
</style>

</head>
<body>
    <div class="container">
        <h1>Inscription Étudiant</h1>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?= $success_message ?></div>
            <div class="login-link" style="margin-bottom: 1rem;">
                <a href="formulaire.html" class="btn-submit" style="display: inline-block; text-align: center; text-decoration: none;">Se connecter</a>
            </div>
        <?php else: ?>
            
            <?php if (!empty($errors['database']) || !empty($errors['system'])): ?>
                <div class="error" style="text-align: center; margin-bottom: 20px;">
                    <?= $errors['database'] ?? $errors['system'] ?>
                </div>
            <?php endif; ?>
            
            <?php if ($form_submitted && empty($errors)): ?>
                <div class="success-message">Traitement en cours...</div>
            <?php endif; ?>
            
            <form method="post" id="inscription-form">
                <!-- Section infos personnelles -->
                <div class="form-section">
                    <h2>Informations personnelles</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom</label>
                            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($nom ?? '') ?>">
                            <?php if (!empty($errors['nom'])): ?>
                                <div class="error"><?= $errors['nom'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($prenom ?? '') ?>">
                            <?php if (!empty($errors['prenom'])): ?>
                                <div class="error"><?= $errors['prenom'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_naissance">Date de naissance</label>
                        <input type="date" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($date_naissance ?? '') ?>">
                        <?php if (!empty($errors['date_naissance'])): ?>
                            <div class="error"><?= $errors['date_naissance'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Section parcours académique -->
                <div class="form-section">
                    <h2>Parcours académique</h2>
                    <div class="form-group">
                        <label for="filiere">Filière</label>
                        <select id="filiere" name="filiere">
                            <option value="">Sélectionnez une filière</option>
                            <?php foreach ($filieres as $filiere): ?>
                                <option value="<?= $filiere['id'] ?>" <?= ($filiere['id'] == ($_POST['filiere'] ?? '')) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($filiere['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['filiere'])): ?>
                            <div class="error"><?= $errors['filiere'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="branche">Branche</label>
                            <select id="branche" name="branche">
                                <option value="">Sélectionnez une branche</option>
                                <?php foreach ($branches as $branche): ?>
                                    <?php if ($branche['filiere_id'] == ($_POST['filiere'] ?? 0)): ?>
                                        <option value="<?= $branche['id'] ?>" <?= ($branche['id'] == ($_POST['branche'] ?? '')) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($branche['nom']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['branche'])): ?>
                                <div class="error"><?= $errors['branche'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="groupe">Groupe</label>
                            <select id="groupe" name="groupe">
                                <option value="">Sélectionnez un groupe</option>
                                <?php foreach ($groupes as $groupe): ?>
                                    <?php if ($groupe['id_branche'] == ($_POST['branche'] ?? 0)): ?>
                                        <option value="<?= $groupe['id'] ?>" <?= ($groupe['id'] == ($_POST['groupe'] ?? '')) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($groupe['nom']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['groupe'])): ?>
                                <div class="error"><?= $errors['groupe'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="promotion">Promotion</label>
                            <input type="text" id="promotion" name="promotion" value="<?= htmlspecialchars($promotion ?? '') ?>" placeholder="Ex: 2025">
                            <?php if (!empty($errors['promotion'])): ?>
                                <div class="error"><?= $errors['promotion'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="niveau">Niveau</label>
                            <select id="niveau" name="niveau">
                                <option value="1ere_annee" <?= (($niveau ?? '') == '1ere_annee') ? 'selected' : '' ?>>1ère année</option>
                                <option value="2eme_annee" <?= (($niveau ?? '') == '2eme_annee') ? 'selected' : '' ?>>2ème année</option>
                            </select>
                            <?php if (!empty($errors['niveau'])): ?>
                                <div class="error"><?= $errors['niveau'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Section informations de connexion -->
                <div class="form-section">
                    <h2>Informations de connexion</h2>
                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>">
                        <?php if (!empty($errors['email'])): ?>
                            <div class="error"><?= $errors['email'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <input type="password" id="password" name="password">
                            <?php if (!empty($errors['password'])): ?>
                                <div class="error"><?= $errors['password'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                            <?php if (!empty($errors['confirm_password'])): ?>
                                <div class="error"><?= $errors['confirm_password'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 0.5rem;">
                        <p style="color: var(--gray-300); font-size: 0.9rem;">Après votre inscription, un administrateur devra activer votre compte avant que vous puissiez vous connecter.</p>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit" id="submit-btn">S'inscrire</button>
            </form>
        <?php endif; ?>
        
        <div class="login-link">
            Vous avez déjà un compte? <a href="formulaire.html">Connectez-vous</a>
        </div>
    </div>

    <script>
        // Gestion dynamique des selects (code inchangé)
        document.getElementById('filiere').addEventListener('change', function() {
            const filiereId = this.value;
            const brancheSelect = document.getElementById('branche');
            const groupeSelect = document.getElementById('groupe');
            
            // Filtrer les branches
            brancheSelect.innerHTML = '<option value="">Sélectionnez une branche</option>';
            groupeSelect.innerHTML = '<option value="">Sélectionnez un groupe</option>';
            
            <?php foreach ($branches as $branche): ?>
                if (<?= $branche['filiere_id'] ?> == filiereId) {
                    const option = document.createElement('option');
                    option.value = <?= $branche['id'] ?>;
                    option.textContent = '<?= addslashes($branche['nom']) ?>';
                    brancheSelect.appendChild(option);
                }
            <?php endforeach; ?>
        });
        
        document.getElementById('branche').addEventListener('change', function() {
            const brancheId = this.value;
            const groupeSelect = document.getElementById('groupe');
            
            // Filtrer les groupes
            groupeSelect.innerHTML = '<option value="">Sélectionnez un groupe</option>';
            
            <?php foreach ($groupes as $groupe): ?>
                if (<?= $groupe['id_branche'] ?> == brancheId) {
                    const option = document.createElement('option');
                    option.value = <?= $groupe['id'] ?>;
                    option.textContent = '<?= addslashes($groupe['nom']) ?>';
                    groupeSelect.appendChild(option);
                }
            <?php endforeach; ?>
        });
        
        // Ajouter un gestionnaire d'événements pour le formulaire
        document.getElementById('inscription-form').addEventListener('submit', function(e) {
            // Désactiver le bouton lors de la soumission pour éviter les doubles soumissions
            document.getElementById('submit-btn').disabled = true;
            document.getElementById('submit-btn').innerHTML = 'Traitement en cours...';
            
            // Laisser le formulaire être soumis normalement
        });
    </script>
</body>
</html>