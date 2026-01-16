<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiblioGest - Gestion de Bibliothèque</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --text: #2b2d42;
            --gray: #6c757d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            overflow-x: hidden;
            background-color: var(--light);
        }

        /* Animation Keyframes */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Header */
        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            animation: slideInLeft 0.8s ease-out;
        }

        .logo span {
            color: var(--accent);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
        }

        .nav-links a:hover {
            color: var(--accent);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent);
            transition: width 0.3s;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1589998059171-988d887df646?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80') no-repeat center center/cover;
            color: white;
            text-align: center;
            padding-top: 80px;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
            animation: fadeIn 1s ease-out;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 2rem;
            background: var(--accent);
            color: white;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid var(--accent);
            margin: 0 0.5rem;
            animation: fadeIn 1.2s ease-out;
        }

        .btn:hover {
            background: transparent;
            color: var(--accent);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .btn-outline {
            background: transparent;
            color: var(--accent);
            border: 2px solid var(--accent);
        }

        .btn-outline:hover {
            background: var(--accent);
            color: white;
        }

        /* Features Section */
        .features {
            padding: 5rem 0;
            background: white;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--accent);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .feature-card {
            background: var(--light);
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            opacity: 0;
            transform: translateY(30px);
        }

        .feature-card.animate {
            animation: fadeIn 0.8s forwards;
        }

        .feature-card:nth-child(1) { animation-delay: 0.2s; }
        .feature-card:nth-child(2) { animation-delay: 0.4s; }
        .feature-card:nth-child(3) { animation-delay: 0.6s; }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        /* Books Showcase */
        .showcase {
            padding: 5rem 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .book-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            opacity: 0;
            transform: translateY(30px);
        }

        .book-card.animate {
            animation: fadeIn 0.8s forwards;
        }

        .book-card:nth-child(1) { animation-delay: 0.2s; }
        .book-card:nth-child(2) { animation-delay: 0.4s; }
        .book-card:nth-child(3) { animation-delay: 0.6s; }
        .book-card:nth-child(4) { animation-delay: 0.8s; }

        .book-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .book-cover {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }

        .book-info {
            padding: 1.5rem;
        }

        .book-info h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .book-info p {
            color: var(--gray);
            margin-bottom: 1rem;
        }

        .book-actions {
            display: flex;
            justify-content: space-between;
        }

        /* Testimonials */
        .testimonials {
            padding: 5rem 0;
            background: white;
        }

        .testimonials-slider {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            overflow: hidden;
        }

        .testimonial {
            text-align: center;
            padding: 2rem;
            display: none;
        }

        .testimonial.active {
            display: block;
            animation: fadeIn 1s ease-out;
        }

        .testimonial img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
            border: 5px solid var(--light);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .testimonial-text {
            font-size: 1.1rem;
            font-style: italic;
            margin-bottom: 1.5rem;
            color: var(--text);
        }

        .testimonial-author {
            font-weight: 600;
            color: var(--primary);
        }

        /* CTA Section */
        .cta {
            padding: 5rem 0;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-align: center;
        }

        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }

        .cta p {
            max-width: 600px;
            margin: 0 auto 2rem;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 3rem 0;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-column h3 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--accent);
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column ul li {
            margin-bottom: 0.8rem;
        }

        .footer-column ul li a {
            color: #adb5bd;
            text-decoration: none;
            transition: all 0.3s;
        }

        .footer-column ul li a:hover {
            color: white;
            padding-left: 5px;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            color: white;
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: var(--accent);
            transform: translateY(-3px);
        }

        .copyright {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #adb5bd;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .btn {
                display: block;
                margin: 0.5rem auto;
                max-width: 200px;
            }
        }

        /* Animation on Scroll */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease-out;
        }

        .animate-on-scroll.animated {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="navbar">
            <div class="logo">Bibliotheque<span> IUGET</span></div>
            <nav class="nav-links">
                <a href="#features">Fonctionnalités</a>
                <a href="#livres">Livres</a>
                <a href="#temoignages">Témoignages</a>
                <a href="login_admin.php" class="btn-outline">Espace Admin</a>
                <a href="connexion.php" class="btn">Se connecter</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Gérez votre bibliothèque en toute simplicité</h1>
            <p>Une solution complète pour la gestion des livres, des utilisateurs et des emprunts dans votre établissement</p>
            <div>
                <a href="connexion.php" class="btn">Commencer</a>
                <a href="#features" class="btn btn-outline">Découvrir</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-title">
            <h2>Nos Fonctionnalités</h2>
            <p>Découvrez ce que notre solution peut faire pour vous</p>
        </div>
        <div class="features-grid">
            <div class="feature-card animate-on-scroll">
                <div class="feature-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <h3>Gestion des Livres</h3>
                <p>Ajoutez, modifiez et suivez facilement tous vos livres physiques et numériques</p>
            </div>
            <div class="feature-card animate-on-scroll">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Gestion des Utilisateurs</h3>
                <p>Gérez les étudiants et professeurs avec des profils détaillés et des historiques</p>
            </div>
            <div class="feature-card animate-on-scroll">
                <div class="feature-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <h3>Suivi des Emprunts</h3>
                <p>Visualisez en temps réel les emprunts, retours et retards</p>
            </div>
        </div>
    </section>

    <!-- Books Showcase -->
    <section class="showcase" id="livres">
        <div class="section-title">
            <h2>Dernières Ajouts</h2>
            <p>Découvrez nos nouveaux livres disponibles</p>
        </div>
        <div class="books-grid">
            <div class="book-card animate-on-scroll">
                <img src="https://m.media-amazon.com/images/I/81BdK4VtiLL._AC_UF1000,1000_QL80_.jpg" alt="La Roue du Temps" class="book-cover">
                <div class="book-info">
                    <h3>Comptabilité</h3>
                    <p>Nsangou Ibrahim</p>
                    <div class="book-actions">
                        <a href="#" class="btn btn-small">Détails</a>
                        <a href="#" class="btn btn-small btn-outline">Réserver</a>
                    </div>
                </div>
            </div>
            <div class="book-card animate-on-scroll">
                <img src="https://m.media-amazon.com/images/I/91SsZj+AfAL._AC_UF1000,1000_QL80_.jpg" alt="Fondation" class="book-cover">
                <div class="book-info">
                    <h3>Les 04 accords toltèque</h3>
                    <p>Betote Isaac</p>
                    <div class="book-actions">
                        <a href="#" class="btn btn-small">Détails</a>
                        <a href="#" class="btn btn-small btn-outline">Réserver</a>
                    </div>
                </div>
            </div>
            <div class="book-card animate-on-scroll">
                <img src="https://m.media-amazon.com/images/I/71YHjVXyR0L._AC_UF1000,1000_QL80_.jpg" alt="La Tulipe Noire" class="book-cover">
                <div class="book-info">
                    <h3>Le pouvoir du présent</h3>
                    <p>Simo steve</p>
                    <div class="book-actions">
                        <a href="#" class="btn btn-small">Détails</a>
                        <a href="#" class="btn btn-small btn-outline">Réserver</a>
                    </div>
                </div>
            </div>
            <div class="book-card animate-on-scroll">
                <img src="https://m.media-amazon.com/images/I/81aY1lxk+LL._AC_UF1000,1000_QL80_.jpg" alt="Les Mille et Une Nuits" class="book-cover">
                <div class="book-info">
                    <h3>La maitrise de soi</h3>
                    <p>Traditionnel</p>
                    <div class="book-actions">
                        <a href="#" class="btn btn-small">Détails</a>
                        <a href="#" class="btn btn-small btn-outline">Réserver</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials" id="temoignages">
        <div class="section-title">
            <h2>Témoignages</h2>
            <p>Ce que nos utilisateurs disent de nous</p>
        </div>
        <div class="testimonials-slider">
            <div class="testimonial active">
                <img src="https://randomuser.me/api/portraits/women/iuget.jpg" alt="Marie">
                <p class="testimonial-text">"Cette solution a révolutionné la gestion de notre bibliothèque universitaire. Tout est maintenant fluide et organisé."</p>
                <p class="testimonial-author">Marie, Bibliothécaire</p>
            </div>
            <div class="testimonial">
                <img src="https://randomuser.me/api/portraits/men/iuget.jpg" alt="Mbiandi">
                <p class="testimonial-text">"En tant que professeur, je peux maintenant recommander des livres à mes étudiants directement via la plateforme."</p>
                <p class="testimonial-author">Mbiandi, Professeur</p>
            </div>
            <div class="testimonial">
                <img src="https://randomuser.me/api/portraits/women/iuget.jpg" alt="christian">
                <p class="testimonial-text">"L'interface est tellement intuitive que même les moins technophiles parmi nous l'ont adoptée rapidement."</p>
                <p class="testimonial-author">christian, Étudiante</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <h2>Prêt à transformer votre bibliothèque ?</h2>
        <p>Rejoignez les autres étudiants qui utilisent déjà notre solution</p>
        <a href="connexion.php" class="btn">Commencer maintenant</a>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>Bibliotheque iuget</h3>
                <p>La solution tout-en-un pour la gestion moderne de votre bibliothèque.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3>Liens rapides</h3>
                <ul>
                    <li><a href="#features">Fonctionnalités</a></li>
                    <li><a href="#livres">Livres</a></li>
                    <li><a href="#temoignages">Témoignages</a></li>
                    <li><a href="login_admin.php">Espace Admin</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Contact</h3>
                <ul>
                    <li><i class="fas fa-envelope"></i> iuget.cm </li>
                    <li><i class="fas fa-phone"></i> +237 6 99 31 47 23</li>
                    <li><i class="fas fa-map-marker-alt"></i> Rond point maeture, Douala</li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 Bibliotheque-iuget. By Donfack Rubens étudiant de L'IUGET.</p>
        </div>
    </footer>

    <script>
        // Animation on Scroll
        function animateOnScroll() {
            const elements = document.querySelectorAll('.animate-on-scroll');
            
            elements.forEach(element => {
                const elementPosition = element.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.3;
                
                if (elementPosition < screenPosition) {
                    element.classList.add('animated');
                }
            });
        }

        window.addEventListener('scroll', animateOnScroll);
        window.addEventListener('load', animateOnScroll);

        // Testimonial Slider
        let currentTestimonial = 0;
        const testimonials = document.querySelectorAll('.testimonial');

        function showTestimonial(index) {
            testimonials.forEach(testimonial => {
                testimonial.classList.remove('active');
            });
            testimonials[index].classList.add('active');
        }

        function nextTestimonial() {
            currentTestimonial = (currentTestimonial + 1) % testimonials.length;
            showTestimonial(currentTestimonial);
        }

        // Change testimonial every 5 seconds
        setInterval(nextTestimonial, 5000);
    </script>
</body>
</html>