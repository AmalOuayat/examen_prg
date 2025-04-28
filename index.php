<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamPro - Your Online Examination Platform</title>
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.svg" type="image/x-icon">
    <!-- Google Fonts -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap">
    <!-- Font Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Thème sombre (défaut) */
            --primary-color: #0f9ef7;
            --primary-dark: #0d8de0;
            --secondary-color: #6c757d;
            --bg-color: #121212;
            --bg-secondary: #151515;
            --bg-footer: #101010;
            --text-color: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.7);
            --text-muted-light: rgba(255, 255, 255, 0.5);
            --card-bg: rgba(255, 255, 255, 0.05);
            --card-border: rgba(255, 255, 255, 0.05);
            --card-hover-border: rgba(15, 158, 247, 0.2);
            --header-bg: rgba(18, 18, 18, 0.95);
            --border-radius: 8px;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        /* Thème clair */
        [data-theme="light"] {
            --bg-color: #f8f9fa;
            --bg-secondary: #ffffff;
            --bg-footer: #e9ecef;
            --text-color: #212529;
            --text-muted: rgba(33, 37, 41, 0.7);
            --text-muted-light: rgba(33, 37, 41, 0.5);
            --card-bg: #ffffff;
            --card-border: rgba(0, 0, 0, 0.1);
            --card-hover-border: rgba(15, 158, 247, 0.4);
            --header-bg: rgba(248, 249, 250, 0.95);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Public Sans', sans-serif;
            color: var(--text-color);
            background-color: var(--bg-color);
            overflow-x: hidden;
            line-height: 1.6;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .container {
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        header {
            background-color: var(--header-bg);
            position: fixed;
            width: 100%;
            z-index: 1000;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease, padding 0.3s ease, box-shadow 0.3s ease;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .logo-icon {
            color: var(--primary-color);
            font-size: 28px;
            transform: rotate(45deg);
        }

        .logo-text {
            color: var(--text-color);
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: color 0.3s ease;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-color);
            font-size: 18px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s ease;
        }

        .theme-toggle:hover {
            background-color: rgba(127, 127, 127, 0.1);
        }

        .btn {
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            display: inline-block;
            text-align: center;
            cursor: pointer;
            border: none;
        }

        .btn-login {
            background-color: rgba(15, 158, 247, 0.1);
            color: var(--primary-color);
            border: 1px solid rgba(15, 158, 247, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-login:hover {
            background-color: rgba(15, 158, 247, 0.2);
            transform: translateY(-3px);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            padding: 100px 0 60px;
            background: linear-gradient(135deg, var(--bg-color) 0%, var(--bg-secondary) 100%);
            overflow: hidden;
            transition: background 0.3s ease;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
            z-index: 0;
            transition: opacity 0.3s ease;
        }

        [data-theme="light"] .hero::before {
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23000000' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
        }

        .hero-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 24px;
            max-width: 800px;
        }

        .highlight {
            color: var(--primary-color);
            position: relative;
        }

        .highlight::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 0;
            width: 100%;
            height: 8px;
            background-color: var(--primary-color);
            opacity: 0.2;
            border-radius: 4px;
            z-index: -1;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: var(--text-muted);
            max-width: 600px;
            margin-bottom: 40px;
        }

        .hero-actions {
            display: flex;
            gap: 16px;
            margin-top: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }

        /* Features Section */
        .features {
            padding: 80px 0;
            background-color: var(--bg-secondary);
            transition: background-color 0.3s ease;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            margin-bottom: 16px;
            position: relative;
            display: inline-block;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--primary-color);
        }

        .section-title p {
            color: var(--text-muted);
            max-width: 700px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .feature-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 30px;
            transition: var(--transition);
            border: 1px solid var(--card-border);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border-color: var(--card-hover-border);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .feature-title {
            font-size: 1.4rem;
            margin-bottom: 15px;
        }

        .feature-desc {
            color: var(--text-muted);
            font-size: 1rem;
        }

        /* Geometric Shapes */
        .shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .shape {
            position: absolute;
            background-color: var(--primary-color);
            opacity: 0.05;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
        }

        .shape-1 {
            top: -200px;
            right: -200px;
            width: 600px;
            height: 600px;
            animation: float 15s ease-in-out infinite;
        }

        .shape-2 {
            bottom: -300px;
            left: -150px;
            width: 500px;
            height: 500px;
            animation: float 18s ease-in-out infinite reverse;
        }

        .shape-3 {
            top: 40%;
            right: 20%;
            width: 300px;
            height: 300px;
            animation: float 12s ease-in-out infinite 2s;
        }

        @keyframes float {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }

            50% {
                transform: translate(20px, -30px) rotate(5deg);
            }

            100% {
                transform: translate(0, 0) rotate(0deg);
            }
        }

        /* Footer */
        footer {
            background-color: var(--bg-footer);
            padding: 40px 0;
            text-align: center;
            transition: background-color 0.3s ease;
        }

        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .footer-logo-icon {
            color: var(--primary-color);
            font-size: 24px;
            transform: rotate(45deg);
        }

        .footer-logo-text {
            color: var(--text-color);
            font-size: 20px;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .footer-links {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
            justify-content: center;
        }

        .footer-link {
            color: var(--text-muted);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-link:hover {
            color: var(--primary-color);
        }

        .footer-social {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .social-icon {
            color: var(--text-muted);
            font-size: 20px;
            transition: var(--transition);
        }

        .social-icon:hover {
            color: var(--primary-color);
            transform: translateY(-3px);
        }

        .copyright {
            color: var(--text-muted-light);
            font-size: 0.9rem;
            margin-top: 20px;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.8rem;
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }

            .features-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .hero-title {
                font-size: 1.8rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .hero-actions {
                flex-direction: column;
                gap: 12px;
            }

            .btn {
                width: 100%;
            }

            .section-title h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <header>
        <div class="container">
            <div class="header-content">
                <a href="#" class="logo">
                    <i class="fas fa-graduation-cap logo-icon"></i>
                    <span class="logo-text">ExamPro</span>
                </a>
                <div class="nav-links">
                    <button id="theme-toggle" class="theme-toggle" aria-label="Toggle theme">
                        <i class="fas fa-moon"></i>
                    </button>
                    <a href="formulaire.html" class="btn btn-login">
                        <i class="fas fa-user"></i>
                        login
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">
                    La plateforme d'examens en ligne
                    <span class="highlight">simple et efficace</span>
                </h1>
                <p class="hero-subtitle">
                    ExamPro vous offre une solution complète pour créer, gérer et passer des examens en ligne.
                    Notre système sécurisé garantit l'intégrité de vos évaluations tout en offrant une expérience
                    utilisateur intuitive.
                </p>
                <div class="hero-actions">
                    <a href="formulaire.html" class="btn btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        Accéder à mon espace
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-title">
                <h2>Pourquoi choisir ExamPro ?</h2>
                <p>Découvrez les avantages de notre plateforme d'examen en ligne</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">Sécurité maximale</h3>
                    <p class="feature-desc">
                        Système anti-triche avancé, surveillance automatisée et vérification d'identité pour garantir
                        l'intégrité de vos examens.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="feature-title">Gain de temps</h3>
                    <p class="feature-desc">
                        Correction automatique et instantanée, statistiques détaillées et génération de rapports en un
                        clic.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h3 class="feature-title">Accessibilité</h3>
                    <p class="feature-desc">
                        Passez vos examens n'importe où, n'importe quand, sur n'importe quel appareil connecté à
                        internet.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="feature-title">Analyses détaillées</h3>
                    <p class="feature-desc">
                        Suivez la progression des étudiants avec des statistiques avancées et des rapports
                        personnalisés.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Collaboration</h3>
                    <p class="feature-desc">
                        Partagez des examens entre enseignants, créez des groupes et gérez les accès facilement.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="feature-title">Responsive Design</h3>
                    <p class="feature-desc">
                        Interface adaptée à tous les appareils pour une expérience utilisateur optimale sur mobile,
                        tablette ou ordinateur.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <i class="fas fa-graduation-cap footer-logo-icon"></i>
                    <span class="footer-logo-text">ExamPro</span>
                </div>
                <div class="footer-links">
                    <a href="#" class="footer-link">Accueil</a>
                    <a href="#" class="footer-link">À propos</a>
                    <a href="#" class="footer-link">Fonctionnalités</a>
                    <a href="#" class="footer-link">Contact</a>
                    <a href="#" class="footer-link">Aide</a>
                    <a href="formulaire.html" class="footer-link">Connexion</a>
                </div>
                <div class="footer-social">
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                </div>
                <p class="copyright">© 2025 ExamPro. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Add scroll effect to header
        window.addEventListener('scroll', function () {
            const header = document.querySelector('header');
            if (window.scrollY > 50) {
                header.style.padding = '10px 0';
                header.style.backgroundColor = document.documentElement.getAttribute('data-theme') === 'light' ?
                    'rgba(248, 249, 250, 0.98)' : 'rgba(18, 18, 18, 0.98)';
                header.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.3)';
            } else {
                header.style.padding = '15px 0';
                header.style.backgroundColor = document.documentElement.getAttribute('data-theme') === 'light' ?
                    'rgba(248, 249, 250, 0.95)' : 'rgba(18, 18, 18, 0.95)';
                header.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.2)';
            }
        });

        // Theme toggle functionality
        document.addEventListener('DOMContentLoaded', function () {
            const themeToggle = document.getElementById('theme-toggle');
            const themeIcon = themeToggle.querySelector('i');

            // Check for saved theme preference or use default
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);

            // Update icon to match current theme
            updateThemeIcon(savedTheme);

            // Toggle theme when button is clicked
            themeToggle.addEventListener('click', function () {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

                // Update theme and save preference
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);

                // Update the icon
                updateThemeIcon(newTheme);

                // Update header background if scrolled
                if (window.scrollY > 50) {
                    const header = document.querySelector('header');
                    header.style.backgroundColor = newTheme === 'light' ?
                        'rgba(248, 249, 250, 0.98)' : 'rgba(18, 18, 18, 0.98)';
                }
            });

            // Function to update the theme icon
            function updateThemeIcon(theme) {
                if (theme === 'dark') {
                    themeIcon.classList.remove('fa-sun');
                    themeIcon.classList.add('fa-moon');
                } else {
                    themeIcon.classList.remove('fa-moon');
                    themeIcon.classList.add('fa-sun');
                }
            }
        });
    </script>
</body>

</html>