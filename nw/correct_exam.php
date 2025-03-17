<?php
$servername = "localhost";
$username = "root"; // Remplace par ton nom d'utilisateur MySQL
$password = "DD202"; // Remplace par ton mot de passe MySQL
$database = "examens_db";

// Connexion à la base de données
$conn = new mysqli($servername, $username, $password, $database);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

// Requête SQL pour récupérer les groupes et leurs étudiants
$sql = "SELECT g.nom AS groupe, u.nom AS etudiant
        FROM groupes g
        LEFT JOIN etudiants_groupes eg ON g.id_g = eg.id_groupe
        LEFT JOIN etudiants e ON eg.etudiant_id = e.id_etudiant
        LEFT JOIN utilisateurs u ON e.utilisateur_id = u.id_u
        ORDER BY g.nom, u.nom";

$result = $conn->query($sql);

$groupes = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $groupes[$row["groupe"]][] = $row["etudiant"] ?? "Aucun étudiant";
    }
} else {
    echo "Aucun groupe trouvé.";
}

// Affichage des groupes et étudiants
foreach ($groupes as $groupe => $etudiants) {
    echo "<h3>$groupe</h3><ul>";
    foreach ($etudiants as $etudiant) {
        echo "<li>$etudiant</li>";
    }
    echo "</ul>";
}

// Fermer la connexion
$conn->close();
?>
