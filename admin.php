<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamPro - Espace Administration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

            /* Variables additionnelles dérivées de la nouvelle palette */
            --primary-light: #61c1ff;
            --secondary-light: #adb5bd;
            --gray-dark: #343a40;
            --gray: #6c757d;
            --gray-light: #dee2e6;
            --danger: #dc3545;
            --warning: #ffc107;
            --success: #28a745;

            /* Variables de mise en page */
            --sidebar-width: 280px;
            --header-height: 70px;
            --card-shadow: var(--box-shadow);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: var(--dark-color);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Structure principale */
        .app-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--dark-color);
            height: 100vh;
            position: fixed;
            z-index: 50;
            transition: var(--transition);
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
        }

        .sidebar-collapsed {
            transform: translateX(-100%);
        }

        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
        }

        .logo-icon {
            font-size: 24px;
            color: var(--primary-color);
            transform: rotate(45deg);
        }

        .logo-text {
            font-size: 20px;
            font-weight: 700;
            color: white;
        }

        .admin-badge {
            background: var(--primary-color);
            color: white;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 20px;
            text-transform: uppercase;
            margin-left: 8px;
            letter-spacing: 1px;
        }

        .sidebar-content {
            padding: 20px 0;
            flex-grow: 1;
            overflow-y: auto;
        }

        .nav-section {
            margin-bottom: 15px;
        }

        .nav-section-title {
            color: var(--gray-light);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 10px 25px;
            margin-top: 15px;
            opacity: 0.7;
        }

        .nav-links {
            list-style: none;
        }

        .nav-item {
            margin: 4px 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: var(--gray-light);
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: var(--transition);
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .nav-link.active {
            background: rgba(15, 158, 247, 0.2);
            color: var(--primary-light);
            border-left-color: var(--primary-color);
        }

        .nav-link-icon {
            margin-right: 14px;
            font-size: 18px;
            width: 22px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-profile {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: var(--border-radius);
            background: rgba(255, 255, 255, 0.05);
            cursor: pointer;
        }

        .user-profile:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-weight: bold;
            color: white;
        }

        .user-info {
            flex-grow: 1;
        }

        .user-name {
            color: white;
            font-weight: 500;
            font-size: 14px;
        }

        .user-role {
            color: var(--gray-light);
            font-size: 12px;
        }

        .user-menu-icon {
            color: var(--gray-light);
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: var(--transition);
        }

        .main-content-expanded {
            margin-left: 0;
        }

        /* Header */
        .main-header {
            height: var(--header-height);
            background: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 40;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .header-left {
            display: flex;
            align-items: center;
        }

        .toggle-sidebar {
            background: none;
            border: none;
            color: var(--gray);
            font-size: 20px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .toggle-sidebar:hover {
            background: var(--light-color);
            color: var(--primary-color);
        }

        .page-title {
            margin-left: 20px;
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .search-bar {
            position: relative;
        }

        .search-input {
            padding: 10px 15px 10px 40px;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-light);
            background: var(--light-color);
            width: 250px;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(15, 158, 247, 0.1);
            width: 300px;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .action-button {
            background: none;
            border: none;
            color: var(--gray);
            font-size: 18px;
            width: 40px;
            height: 40px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .action-button:hover {
            background: var(--light-color);
            color: var(--primary-color);
        }

        .notification-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: var(--danger);
            color: white;
            font-size: 10px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Content Area */
        .content-area {
            padding: 30px;
            height: calc(100vh - var(--header-height));
            overflow-y: auto;
        }

        .iframe-container {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            height: 100%;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Dashboard Section */
        .dashboard-section {
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .card-icon.blue {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        }

        .card-icon.green {
            background: linear-gradient(135deg, var(--success), #1b8238);
        }

        .card-icon.orange {
            background: linear-gradient(135deg, var(--warning), #e0a800);
        }

        .card-icon.red {
            background: linear-gradient(135deg, var(--danger), #bd2130);
        }

        .card-title {
            color: var(--gray);
            font-size: 14px;
            margin-bottom: 5px;
        }

        .card-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .card-trend {
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .trend-up {
            color: var(--success);
        }

        .trend-down {
            color: var(--danger);
        }

        .trend-icon {
            margin-right: 5px;
        }

        /* Mobile Styles */
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 45;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .mobile-overlay.active {
            opacity: 1;
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .dashboard-cards {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar-visible {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .search-input {
                width: 180px;
            }

            .search-input:focus {
                width: 220px;
            }
        }

        @media (max-width: 576px) {
            .main-header {
                padding: 0 15px;
            }

            .page-title {
                display: none;
            }

            .search-bar {
                display: none;
            }

            .content-area {
                padding: 15px;
            }

            .dashboard-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- Mobile Overlay -->
        <div class="mobile-overlay" id="mobile-overlay"></div>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <a href="#" class="logo">
                    <i class="fas fa-graduation-cap logo-icon"></i>
                    <span class="logo-text">ExamPro</span>
                    <span class="admin-badge">Admin</span>
                </a>
            </div>

            <!-- Sidebar Navigation -->
            <div class="sidebar-content">
                <div class="nav-section">
                    <ul class="nav-links">
                        <li class="nav-item">
                            <a href="admin_dashboard.php" target="content-frame" class="nav-link active">
                                <i class="fas fa-home nav-link-icon"></i>
                                <span>Tableau de bord</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <h5 class="nav-section-title">Gestion des utilisateurs</h5>
                    <ul class="nav-links">
                        <li class="nav-item">
                            <a href="Ajouter_utilisateur.php" target="content-frame" class="nav-link">
                                <i class="fas fa-user-plus nav-link-icon"></i>
                                <span>Ajouter utilisateur</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="Liste_utili.php" target="content-frame" class="nav-link">
                                <i class="fas fa-users nav-link-icon"></i>
                                <span>Liste des utilisateurs</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <h5 class="nav-section-title">Gestion des groupes</h5>
                    <ul class="nav-links">
                        <li class="nav-item">
                            <a href="Creer_groupe.php" target="content-frame" class="nav-link">
                                <i class="fas fa-layer-group nav-link-icon"></i>
                                <span>Créer un groupe</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="Assigner_etud_group.php" target="content-frame" class="nav-link">
                                <i class="fas fa-user-tag nav-link-icon"></i>
                                <span>Assigner étudiants</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <h5 class="nav-section-title">Gestion des examens</h5>
                    <ul class="nav-links">
                        <li class="nav-item">
                            <a href="insert_module.php" target="content-frame" class="nav-link">
                                <i class="fas fa-book nav-link-icon"></i>
                                <span>Ajouter module</span>
                            </a>
                        </li>
                        
                    </ul>
                </div>
            </div>

            <!-- Sidebar Footer with User Profile -->
            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-info">
                        <div class="user-name">Admin</div>
                        <div class="user-role">Administrateur</div>
                    </div>
                    <i class="fas fa-chevron-down user-menu-icon"></i>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content" id="main-content">
            <!-- Main Header -->
            <header class="main-header">
                <div class="header-left">
                    <button class="toggle-sidebar" id="toggle-sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">Tableau de bord</h1>
                </div>

                <div class="header-right">
                    <div class="search-bar">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Rechercher...">
                    </div>

                    <div class="header-actions">
                        <button class="action-button">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge">3</span>
                        </button>
                        <button class="action-button">
                            <i class="fas fa-cog"></i>
                        </button>
                        <a href="logout.php" class="action-button" title="Déconnexion">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Main Content Area with iframe -->
            <div class="content-area">
                <div class="iframe-container">
                    <iframe src="admin_dashboard.php" name="content-frame" id="content-frame" frameborder="0"
                        title="Contenu principal"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // DOM Elements
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const toggleSidebarBtn = document.getElementById('toggle-sidebar');
            const mobileOverlay = document.getElementById('mobile-overlay');
            const navLinks = document.querySelectorAll('.nav-link');
            const contentFrame = document.getElementById('content-frame');

            // Function to check window size
            function checkWindowSize() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('sidebar-visible');
                    mainContent.classList.add('main-content-expanded');
                } else {
                    // Restore from localStorage for desktop
                    const sidebarHidden = localStorage.getItem('sidebarHidden') === 'true';
                    if (sidebarHidden) {
                        sidebar.classList.add('sidebar-collapsed');
                        mainContent.classList.add('main-content-expanded');
                    } else {
                        sidebar.classList.remove('sidebar-collapsed');
                        mainContent.classList.remove('main-content-expanded');
                    }
                }
            }

            // Toggle sidebar function
            function toggleSidebar() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.toggle('sidebar-visible');
                    mobileOverlay.classList.toggle('active');
                } else {
                    sidebar.classList.toggle('sidebar-collapsed');
                    mainContent.classList.toggle('main-content-expanded');

                    // Save preference to localStorage
                    localStorage.setItem('sidebarHidden', sidebar.classList.contains('sidebar-collapsed'));
                }
            }

            // Event listeners
            toggleSidebarBtn.addEventListener('click', toggleSidebar);
            mobileOverlay.addEventListener('click', function () {
                sidebar.classList.remove('sidebar-visible');
                mobileOverlay.classList.remove('active');
            });

            // Set active nav link
            navLinks.forEach(link => {
                link.addEventListener('click', function () {
                    // Remove active class from all links
                    navLinks.forEach(l => l.classList.remove('active'));

                    // Add active class to clicked link
                    this.classList.add('active');

                    // Update page title
                    const pageTitle = document.querySelector('.page-title');
                    pageTitle.textContent = this.querySelector('span').textContent;

                    // Close sidebar on mobile after clicking
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('sidebar-visible');
                        mobileOverlay.classList.remove('active');
                    }
                });
            });

            // Handle iframe load events
            contentFrame.addEventListener('load', function () {
                const frameTitle = contentFrame.contentDocument.title || 'Tableau de bord';
                document.querySelector('.page-title').textContent = frameTitle;

                // Find and set active nav link based on iframe src
                const currentPage = contentFrame.contentWindow.location.href.split('/').pop();
                navLinks.forEach(link => {
                    const linkHref = link.getAttribute('href');
                    if (linkHref === currentPage) {
                        navLinks.forEach(l => l.classList.remove('active'));
                        link.classList.add('active');
                    }
                });
            });

            // Init on load
            checkWindowSize();

            // Check window size on resize
            window.addEventListener('resize', checkWindowSize);
        });
    </script>
</body>

</html>