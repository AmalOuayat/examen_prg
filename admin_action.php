<?php
session_start();

// Database connection
$host = "localhost";
$dbname = "examens_db";
$username = "root";
$password = "DD202";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de connexion : " . $e->getMessage();
    header('Location: admin.php');
    exit();
}

// Get the action from POST
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add_user':
        handleAddUser($conn);
        break;
    case 'assign_group':
        handleAssignGroup($conn);
        break;
    case 'block_user':
        handleBlockUser($conn);
        break;
    case 'unblock_user':
        handleUnblockUser($conn);
        break;
    default:
        $_SESSION['error'] = "Action non valide";
        header('Location: admin.php');
        exit();
}

// Function to handle user creation
function handleAddUser($conn) {
    try {
        // Validate input
        $required_fields = ['nom', 'prenom', 'email', 'password', 'roleu'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Le champ $field est requis");
            }
        }

        // Hash password
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Insert user
        $stmt = $conn->prepare("
            INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, roleu)
            VALUES (:nom, :prenom, :email, :password, :roleu)
        ");

        $stmt->execute([
            ':nom' => $_POST['nom'],
            ':prenom' => $_POST['prenom'],
            ':email' => $_POST['email'],
            ':password' => $hashed_password,
            ':roleu' => $_POST['roleu']
        ]);

        // If user is a student, create student record
        if ($_POST['roleu'] === 'etudiant') {
            $user_id = $conn->lastInsertId();
            $stmt = $conn->prepare("
                INSERT INTO etudiants (utilisateur_id, promotion)
                VALUES (:user_id, :promotion)
            ");
            
            $promotion = date('Y', strtotime('+2 years')); // Set promotion to 2 years from now
            $stmt->execute([
                ':user_id' => $user_id,
                ':promotion' => $promotion
            ]);
        }

        $_SESSION['success'] = "Utilisateur ajouté avec succès";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout de l'utilisateur : " . $e->getMessage();
    }

    header('Location: admin.php');
    exit();
}

// Function to handle group assignment
function handleAssignGroup($conn) {
    try {
        // Validate input
        if (empty($_POST['etudiant_id']) || empty($_POST['groupe_id'])) {
            throw new Exception("L'ID de l'étudiant et du groupe sont requis");
        }

        // First, get the student record for this user
        $stmt = $conn->prepare("
            SELECT id_etudiant 
            FROM etudiants 
            WHERE utilisateur_id = :user_id
        ");
        $stmt->execute([':user_id' => $_POST['etudiant_id']]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            throw new Exception("Étudiant non trouvé");
        }

        // Check if student is already in the group
        $stmt = $conn->prepare("
            SELECT * FROM etudiants_groupes 
            WHERE etudiant_id = :etudiant_id AND id_groupe = :groupe_id
        ");
        $stmt->execute([
            ':etudiant_id' => $student['id_etudiant'],
            ':groupe_id' => $_POST['groupe_id']
        ]);

        if ($stmt->rowCount() > 0) {
            throw new Exception("L'étudiant est déjà dans ce groupe");
        }

        // Add student to group
        $stmt = $conn->prepare("
            INSERT INTO etudiants_groupes (etudiant_id, id_groupe)
            VALUES (:etudiant_id, :groupe_id)
        ");
        
        $stmt->execute([
            ':etudiant_id' => $student['id_etudiant'],
            ':groupe_id' => $_POST['groupe_id']
        ]);

        $_SESSION['success'] = "Étudiant assigné au groupe avec succès";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de l'assignation au groupe : " . $e->getMessage();
    }

    header('Location: admin.php');
    exit();
}

// Function to handle blocking users
function handleBlockUser($conn) {
    try {
        if (empty($_POST['user_id'])) {
            throw new Exception("ID utilisateur requis");
        }

        $stmt = $conn->prepare("
            UPDATE utilisateurs 
            SET statut = 'inactif' 
            WHERE id_u = :user_id
        ");
        
        $stmt->execute([':user_id' => $_POST['user_id']]);

        $_SESSION['success'] = "Utilisateur bloqué avec succès";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors du blocage de l'utilisateur : " . $e->getMessage();
    }

    header('Location: admin.php');
    exit();
}

// Function to handle unblocking users
function handleUnblockUser($conn) {
    try {
        if (empty($_POST['user_id'])) {
            throw new Exception("ID utilisateur requis");
        }

        $stmt = $conn->prepare("
            UPDATE utilisateurs 
            SET statut = 'actif' 
            WHERE id_u = :user_id
        ");
        
        $stmt->execute([':user_id' => $_POST['user_id']]);

        $_SESSION['success'] = "Utilisateur débloqué avec succès";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors du déblocage de l'utilisateur : " . $e->getMessage();
    }

    header('Location: admin.php');
    exit();
}