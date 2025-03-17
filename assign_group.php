<?php
session_start();

$host = "localhost";
$dbname = "examens_db";

$username = "root";
$password = "DD202";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les groupes
    $stmt = $conn->query("SELECT id, nom FROM groupes");
    $groupes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ID de l'étudiant à assigner
    $etudiant_id = $_GET['etudiant_id'] ?? null;

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $groupe_id = $_POST['groupe_id'];

        $stmt = $conn->prepare("INSERT INTO etudiants_groupes (etudiant_id, groupe_id) VALUES (:etudiant_id, :groupe_id)");
        $stmt->execute([
            ':etudiant_id' => $etudiant_id,
            ':groupe_id' => $groupe_id
        ]);

        echo "Étudiant assigné au groupe avec succès.";
        exit();
    }
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>

<form method="POST">
    <h2>Assigner l'étudiant à un groupe</h2>
    <label for="groupe_id">Choisir un groupe :</label>
    <select id="groupe_id" name="groupe_id" required>
        <?php foreach ($groupes as $groupe): ?>
            <option value="<?= $groupe['id'] ?>"><?= $groupe['nom'] ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Assigner</button>
</form>
