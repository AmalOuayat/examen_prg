<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Liste des Étudiants</title>
    <style>
        /* Style épuré et moderne */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 40px auto;
            max-width: 900px;
            background-color: #f9fafb;
            color: #333;
        }

        h1 {
            text-align: center;
            font-weight: 600;
            margin-bottom: 30px;
            color: #222;
        }

        .filter-container,
        .search-container {
            text-align: center;
            margin-bottom: 20px;
        }

        input[type="text"],
        select {
            padding: 10px 15px;
            border: 1.5px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            outline-offset: 2px;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        select:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 5px rgba(74, 144, 226, 0.5);
        }

        input[type="text"] {
            width: 320px;
            max-width: 90vw;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
            margin-top: 10px;
        }

        th,
        td {
            padding: 14px 20px;
            text-align: left;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        th {
            background-color: transparent;
            color: #555;
            font-weight: 600;
            box-shadow: none;
            padding-bottom: 8px;
        }

        tbody tr {
            transition: background-color 0.3s ease;
        }

        tbody tr:hover td {
            background-color: #e6f0ff;
        }

        /* Status style */
        td.status-actif {
            color: #0f9ef7;
            font-weight: 600;
        }

        td.status-inactif {
            color: #dc3545;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <h1>Liste des Étudiants</h1>

    <?php
    $pdo = new PDO("mysql:host=localhost;dbname=examens_db", "root", "DD202");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $groupes = $pdo->query("SELECT id_g, nom FROM groupes")->fetchAll(PDO::FETCH_ASSOC);

    $etudiants = $pdo->query("SELECT u.id_u, u.nom, u.prenom, u.email, u.statut, g.nom AS groupe_nom, g.id_g
        FROM utilisateurs u
        JOIN etudiants e ON u.id_u = e.utilisateur_id
        LEFT JOIN etudiants_groupes eg ON e.id_etudiant = eg.etudiant_id
        LEFT JOIN groupes g ON eg.id_groupe = g.id_g
        WHERE u.roleu = 'etudiant'")->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <!-- Filtrage par groupe -->
    <div class="filter-container">
        <select id="groupFilter" onchange="filterByGroup()">
            <option value="">Sélectionner un groupe</option>
            <?php foreach ($groupes as $groupe): ?>
                <option value="<?= $groupe['id_g'] ?>"><?= htmlspecialchars($groupe['nom']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Recherche -->
    <div class="search-container">
        <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Rechercher un étudiant..." />
    </div>

    <!-- Tableau -->
    <table id="studentsTable" aria-label="Liste des étudiants">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="studentsBody">
            <?php foreach ($etudiants as $etudiant): ?>
                <tr data-group="<?= htmlspecialchars($etudiant['id_g']) ?>">
                    <td><?= htmlspecialchars($etudiant['id_u']) ?></td>
                    <td><?= htmlspecialchars($etudiant['nom']) ?></td>
                    <td><?= htmlspecialchars($etudiant['prenom']) ?></td>
                    <td><?= htmlspecialchars($etudiant['email']) ?></td>
                    <td class="status-<?= $etudiant['statut'] === 'actif' ? 'actif' : 'inactif' ?>">
                        <?= $etudiant['statut'] === 'actif' ? 'Actif' : 'Inactif' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        function filterByGroup() {
            const selectedGroup = document.getElementById("groupFilter").value;
            const rows = document.querySelectorAll("#studentsBody tr");

            rows.forEach(row => {
                const groupId = row.dataset.group;
                row.style.display = selectedGroup === "" || groupId === selectedGroup ? "" : "none";
            });
        }

        function filterTable() {
            const filter = document.getElementById("searchInput").value.toLowerCase();
            const rows = document.querySelectorAll("#studentsBody tr");

            rows.forEach(row => {
                const cells = row.getElementsByTagName("td");
                let match = false;
                for (let i = 0; i < cells.length - 1; i++) {
                    if (cells[i].innerText.toLowerCase().includes(filter)) {
                        match = true;
                        break;
                    }
                }
                row.style.display = match ? "" : "none";
            });
        }
    </script>
</body>

</html>