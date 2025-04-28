<?php require_once 'db.php';
// Récupération des données nécessaires
$formateurs = $conn->query("SELECT id_u, nom ,prenom FROM utilisateurs WHERE roleu = 'formateur'")->fetchAll(PDO::FETCH_ASSOC);
$etudiants = $conn->query("SELECT id_u, nom ,prenom FROM utilisateurs WHERE roleu = 'etudiant'")->fetchAll(PDO::FETCH_ASSOC);
$groupes = $conn->query("SELECT id_g, nom FROM groupes")->fetchAll(PDO::FETCH_ASSOC);
$modules = $conn->query("SELECT id_m, nom FROM modules")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des utilisateurs
$utilisateurs = $conn->query("SELECT id_u, nom, prenom ,email, roleu, statut FROM utilisateurs WHERE roleu = 'etudiant ' or roleu = 'formateur' ")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs</title>
    <style>
        :root {
            --primary-color: #0d8de0;
            --danger-color: #f86464;
            --success-color: #63c76a;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --border-color: #e9ecef;
            --header-bg: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            --border-radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 25px;
            font-size: 28px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: var(--header-bg);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        thead {
            background-color: var(--primary-color);
            color: white;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:nth-child(even) {
            background-color: var(--light-bg);
        }

        button {
            padding: 8px 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        button[onclick*="bloquer"] {
            background-color: var(--danger-color);
            color: white;
        }

        button[onclick*="bloquer"]:hover {
            background-color: #e05555;
        }

        button[onclick*="débloquer"] {
            background-color: var(--success-color);
            color: white;
        }

        button[onclick*="débloquer"]:hover {
            background-color: #56b85d;
        }

        .status-pill {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-actif {
            background-color: #e6f4ea;
            color: var(--success-color);
        }

        .status-bloque {
            background-color: #feeaea;
            color: var(--danger-color);
        }

        .role-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .role-formateur {
            background-color: #e6f0ff;
            color: #4a6cf7;
        }

        .role-etudiant {
            background-color: #fff0e6;
            color: #ff8c42;
        }

        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
            }

            .container {
                padding: 15px;
            }

            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Gestion des utilisateurs</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utilisateurs as $user): ?>
                    <tr>
                        <td><?= $user['id_u'] ?></td>
                        <td><?= htmlspecialchars($user['nom']) ?></td>
                        <td><?= htmlspecialchars($user['prenom']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span
                                class="role-badge <?= $user['roleu'] === 'formateur' ? 'role-formateur' : 'role-etudiant' ?>">
                                <?= htmlspecialchars($user['roleu']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-pill <?= $user['statut'] === 'actif' ? 'status-actif' : 'status-bloque' ?>">
                                <?= htmlspecialchars($user['statut']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['statut'] === 'actif'): ?>
                                <form method="post" action="admin_action.php" style="display:inline;">
                                    <input type="hidden" name="action" value="block_user">
                                    <input type="hidden" name="user_id" value="<?= $user['id_u'] ?>">
                                    <button type="submit"
                                        onclick="return confirm('Voulez-vous vraiment bloquer cet utilisateur ?');">Bloquer</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="admin_action.php" style="display:inline;">
                                    <input type="hidden" name="action" value="unblock_user">
                                    <input type="hidden" name="user_id" value="<?= $user['id_u'] ?>">
                                    <button type="submit"
                                        onclick="return confirm('Voulez-vous vraiment débloquer cet utilisateur ?');">Débloquer</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>