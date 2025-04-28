<?php
session_start();
require_once 'db.php';

// Set content type to JSON for API responses
header('Content-Type: application/json');

// Handle form data request
if (isset($_GET['action']) && $_GET['action'] === 'get_form_data') {
    try {
        // Get filières (ajustez selon votre structure de base de données)
        $stmt = $pdo->query("SELECT id_f as id, nom_filiere as nom FROM filieres");
        $filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get branches
        $stmt = $pdo->query("SELECT id_b as id, nom_branche as nom, filiere_id FROM branches");
        $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get groupes
        $stmt = $pdo->query("SELECT id_g as id, nom, id_branche FROM groupes");
        $groupes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'filieres' => $filieres,
            'branches' => $branches,
            'groupes' => $groupes
        ]);
        exit;
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur de base de données: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialisation du tableau d'erreurs
    $errors = [];
    $response = ['success' => false, 'errors' => []];

    // Récupération des données du formulaire
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $date_naissance = $_POST['date_naissance'] ?? '';
    $filiere_id = intval($_POST['filiere'] ?? 0);
    $branche_id = intval($_POST['branche'] ?? 0);
    $groupe_id = intval($_POST['groupe'] ?? 0);
    $promotion = trim($_POST['promotion'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation des données
    if (empty($nom)) $errors['nom'] = "Le nom est requis";
    if (empty($prenom)) $errors['prenom'] = "Le prénom est requis";
    if (empty($date_naissance)) $errors['date_naissance'] = "La date de naissance est requise";
    if ($filiere_id <= 0) $errors['filiere'] = "La filière est requise";
    if ($branche_id <= 0) $errors['branche'] = "La branche est requise";
    if ($groupe_id <= 0) $errors['groupe'] = "Le groupe est requis";
    if (empty($promotion)) $errors['promotion'] = "La promotion est requise";
    
    if (empty($email)) {
        $errors['email'] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "L'email n'est pas valide";
    }
    
    if (strlen($password) < 6) {
        $errors['password'] = "Le mot de passe doit contenir au moins 6 caractères";
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Les mots de passe ne correspondent pas";
    }

    // Vérification de l'email existant
    if (empty($errors['email'])) {
        $stmt = $pdo->prepare("SELECT id_u FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = "Cette adresse email est déjà utilisée";
        }
    }

    // Si erreurs, retourner les erreurs
    if (!empty($errors)) {
        $response['errors'] = $errors;
        echo json_encode($response);
        exit;
    }

    // Hash du mot de passe
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Début de la transaction
    $pdo->beginTransaction();

    try {
        // 1. Création de l'utilisateur
        $stmt = $pdo->prepare("
            INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, date_naissance, roleu, statut) 
            VALUES (?, ?, ?, ?, ?, 'etudiant', 'actif')
        ");
        $stmt->execute([$nom, $prenom, $email, $hashed_password, $date_naissance]);
        $user_id = $pdo->lastInsertId();

        // 2. Création de l'étudiant
        $stmt = $pdo->prepare("
            INSERT INTO etudiants (utilisateur_id, promotion) 
            VALUES (?, ?)
        ");
        $stmt->execute([$user_id, $promotion]);
        $etudiant_id = $pdo->lastInsertId();

        // 3. Association de l'étudiant au groupe
        $stmt = $pdo->prepare("
            INSERT INTO etudiants_groupes (etudiant_id, id_groupe) 
            VALUES (?, ?)
        ");
        $stmt->execute([$etudiant_id, $groupe_id]);

        // Validation de la transaction
        $pdo->commit();

        // Définition des variables de session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_role'] = 'etudiant';
        $_SESSION['user_nom'] = $nom;
        $_SESSION['user_prenom'] = $prenom;
        $_SESSION['user_email'] = $email;
        $_SESSION['promotion'] = $promotion;
        $_SESSION['filiere_id'] = $filiere_id;
        $_SESSION['branche_id'] = $branche_id;
        $_SESSION['groupe_id'] = $groupe_id;

        // Réponse de succès
        $response['success'] = true;
        $response['redirect'] = 'etudiant.php';
        echo json_encode($response);
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $response['errors']['database'] = "Une erreur est survenue lors de l'inscription. Veuillez réessayer.";
        echo json_encode($response);
        exit;
    }
}

// Si aucune action valide, retourner une erreur
echo json_encode([
    'success' => false,
    'message' => 'Action non valide'
]);
exit;