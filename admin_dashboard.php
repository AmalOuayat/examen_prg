<?php
session_start();

$nom = isset($_SESSION['user']['nom']) ? htmlspecialchars($_SESSION['user']['nom']) : "Invité";
$role = isset($_SESSION['user']['roleu']) ? htmlspecialchars($_SESSION['user']['roleu']) : "Inconnu";

// Connexion à la base de données (à adapter avec vos informations)
$servername = "localhost";
$username = "root";
$password = "DD202";
$dbname = "examens_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer le nombre d'examens par groupe
    $stmt = $conn->prepare("
        SELECT g.nom, COUNT(e.id_ex) AS total_examens
        FROM groupes g
        LEFT JOIN examens3 e ON g.id_g = e.groupe_id
        GROUP BY g.id_g
    ");
    $stmt->execute();
    $examensParGroupe = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nombre total d'utilisateurs
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM utilisateurs");
    $stmt->execute();
    $totalUtilisateurs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Nombre total d'examens
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM examens3");
    $stmt->execute();
    $totalExamens = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données : " . $e->getMessage();
    $examensParGroupe = [];
    $totalUtilisateurs = 0;
    $totalExamens = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Tableau de bord - ExamPro</title>
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

        /* Reset & base */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-color);
            color: var(--dark-color);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            font-weight: 600;
            font-size: 2.2rem;
            margin-bottom: 30px;
            color: var(--primary-color);
            text-align: center;
        }

        /* Container des cartes */
        .dashboard-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            width: 100%;
            max-width: 900px;
            margin-bottom: 30px;
        }

        /* Carte */
        .card {
            background-color: #fff;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid #eee;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 10px;
            color: var(--secondary-color);
        }

        .card-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--dark-color);
        }

        /* Diagramme */
        .chart-container {
            width: 100%;
            max-width: 900px;
            background-color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid #eee;
            padding: 20px;
        }

        /* Responsive */
        @media (max-width: 600px) {
            h1 {
                font-size: 1.8rem;
            }

            .dashboard-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <h1>Bonjour <strong><?php echo $role . ' ' . $nom; ?> !</strong></h1>

    <div class="dashboard-container">
        <div class="card">
            <h2 class="card-title">Total Utilisateurs</h2>
            <p class="card-value"><?php echo $totalUtilisateurs; ?></p>
        </div>

        <div class="card">
            <h2 class="card-title">Total Examens</h2>
            <p class="card-value"><?php echo $totalExamens; ?></p>
        </div>
    </div>

    <div class="chart-container">
        <canvas id="examensParGroupeChart"></canvas>
    </div>

    <script>
        const examensParGroupeData = <?php echo json_encode($examensParGroupe); ?>;

        const labels = examensParGroupeData.map(item => item.nom);
        const data = examensParGroupeData.map(item => item.total_examens);

        const ctx = document.getElementById('examensParGroupeChart').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Nombre d\'examens par groupe',
                    data: data,
                    backgroundColor: 'rgba(15, 158, 247, 0.7)', // Couleur primaire
                    borderColor: 'rgba(15, 158, 247, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nombre d\'examens'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Nom du groupe'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Répartition des examens par groupe',
                        font: {
                            size: 18
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>
