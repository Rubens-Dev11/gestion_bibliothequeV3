<?php 
session_start(); 
if (!isset($_SESSION['utilisateur'])) {     
    header('Location: connexion.php');     
    exit(); 
}  

$db = new PDO('mysql:host=localhost;dbname=gestion_bibliotheque;charset=utf8', 'root', '');  

// Récupérer les livres par catégorie
$stmt = $db->query("SELECT DISTINCT categorie FROM livres ORDER BY categorie"); 
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$livres_par_categorie = [];
foreach ($categories as $categorie) {
    $stmt = $db->prepare("SELECT * FROM livres WHERE categorie = ? ORDER BY date_ajout DESC");
    $stmt->execute([$categorie]);
    $livres_par_categorie[$categorie] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer tous les livres pour la recherche
$stmt = $db->query("SELECT * FROM livres ORDER BY titre");
$tous_livres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de la réservation 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserver'])) {     
    $livre_id = $_POST['livre_id'];     
    $utilisateur_id = $_SESSION['utilisateur']['id'];          
    
    // Vérifier si le livre existe
    $stmt = $db->prepare("SELECT * FROM livres WHERE id = ?");     
    $stmt->execute([$livre_id]);     
    $livre = $stmt->fetch();          
    
    if ($livre) {         
        // Vérifier si l'utilisateur n'a pas déjà réservé ce livre
        $stmt = $db->prepare("SELECT * FROM reservations WHERE utilisateur_id = ? AND livre_id = ? AND statut = 'en_attente'");
        $stmt->execute([$utilisateur_id, $livre_id]);
        
        if (!$stmt->fetch()) {
            // Créer la réservation         
            $stmt = $db->prepare("INSERT INTO reservations (utilisateur_id, livre_id) VALUES (?, ?)");         
            $stmt->execute([$utilisateur_id, $livre_id]);                  
            
            // Envoyer un message à l'admin         
            $contenu = "Nouvelle demande de réservation pour le livre: " . $livre['titre'];         
            $stmt = $db->prepare("INSERT INTO messages (expediteur_id, destinataire_id, reservation_id, contenu) VALUES (?, 1, ?, ?)");         
            $stmt->execute([$utilisateur_id, $db->lastInsertId(), $contenu]);                  
            
            $message = "Votre demande de réservation a été envoyée avec succès !";
        } else {
            $message = "Vous avez déjà une demande en attente pour ce livre.";
        }
    } 
} 
?> 

<!DOCTYPE html> 
<html lang="fr"> 
<head>     
    <meta charset="UTF-8">     
    <meta name="viewport" content="width=device-width, initial-scale=1.0">     
    <title>Bibliothèque IUGET</title>     
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head> 
<body>  
    <style>
        :root {
    --primary: #5dade2;
    --primary-dark: #3498db;
    --success: #52c41a;
    --warning: #faad14;
    --danger: #ff4d4f;
    --bg-light: #f5f7fa;
    --bg-white: #ffffff;
    --text-dark: #2d3748;
    --text-gray: #718096;
    --border-light: #e2e8f0;
    --shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    --shadow-hover: 0 4px 16px rgba(0, 0, 0, 0.15);
    --border-radius: 12px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg-light);
    color: var(--text-dark);
    line-height: 1.6;
}

/* Header */
header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 15px 20px;
    box-shadow: var(--shadow);
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1400px;
    margin: 0 auto;
}

.header-title {
    font-size: 1.5rem;
    font-weight: 700;
}

.header-nav {
    display: flex;
    align-items: center;
    gap: 15px;
    font-weight: 500;
}

header a {
    color: white;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 6px;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.1);
    font-size: 0.9rem;
}

header a:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
}

/* Search Section */
.search-section {
    background: var(--bg-white);
    padding: 20px;
    margin: 20px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.search-container {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.search-input-container {
    flex: 1;
    min-width: 250px;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 12px 40px 12px 15px;
    border: 2px solid var(--border-light);
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(93, 173, 226, 0.1);
}

.search-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-gray);
}

.filter-container {
    display: flex;
    gap: 10px;
}

.filter-btn {
    padding: 10px 15px;
    border: 2px solid var(--border-light);
    background: var(--bg-white);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    color: var(--text-gray);
}

.filter-btn.active {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
}

.filter-btn:hover {
    border-color: var(--primary);
}

/* Search Results */
.search-results {
    margin: 20px;
    display: none;
}

.search-results.show {
    display: block;
}

.results-header {
    background: var(--bg-white);
    padding: 15px 20px;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
    border-bottom: 1px solid var(--border-light);
    font-weight: 600;
    color: var(--text-dark);
}

.results-grid {
    background: var(--bg-white);
    padding: 20px;
    border-radius: 0 0 var(--border-radius) var(--border-radius);
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    box-shadow: var(--shadow);
}

.result-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 15px;
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.result-item:hover {
    background: var(--bg-light);
    transform: translateY(-2px);
}

.result-image {
    width: 60px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 10px;
    box-shadow: var(--shadow);
}

.result-placeholder {
    width: 60px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    margin-bottom: 10px;
}

.result-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 5px;
    line-height: 1.2;
}

.result-author {
    font-size: 0.75rem;
    color: var(--text-gray);
}

/* Container */
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

/* Message de confirmation */
.message {
    background: linear-gradient(135deg, var(--success) 0%, #389e0d 100%);
    color: white;
    padding: 15px 20px;
    border-radius: var(--border-radius);
    margin-bottom: 20px;
    box-shadow: var(--shadow);
    font-weight: 500;
}

/* Section de catégorie */
.category-section {
    margin-bottom: 40px;
}

.category-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 20px;
    padding-bottom: 8px;
    border-bottom: 3px solid var(--primary);
    display: inline-block;
}

/* Conteneur des livres avec défilement horizontal */
.books-carousel {
    position: relative;
    overflow: hidden;
}

.books-wrapper {
    display: flex;
    gap: 15px;
    overflow-x: auto;
    padding: 10px 0 15px;
    scroll-behavior: smooth;
    scrollbar-width: thin;
    scrollbar-color: var(--primary) transparent;
}

.books-wrapper::-webkit-scrollbar {
    height: 6px;
}

.books-wrapper::-webkit-scrollbar-track {
    background: var(--border-light);
    border-radius: 3px;
}

.books-wrapper::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 3px;
}

/* Boutons de navigation */
.carousel-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: var(--primary);
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    z-index: 10;
    font-size: 1rem;
}

.carousel-nav:hover {
    background: var(--primary-dark);
    transform: translateY(-50%) scale(1.1);
}

.carousel-nav.prev {
    left: -15px;
}

.carousel-nav.next {
    right: -15px;
}

/* Carte de livre */
.livre-card {
    flex: 0 0 auto;
    width: 160px;
    background: var(--bg-white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
}

.livre-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
}

.livre-img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-bottom: 1px solid var(--border-light);
}

.livre-content {
    padding: 15px 12px;
}

.livre-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 6px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.livre-author {
    font-size: 0.8rem;
    color: var(--text-gray);
    margin-bottom: 5px;
}

.livre-category {
    font-size: 0.75rem;
    color: var(--primary);
    font-weight: 500;
    margin-bottom: 12px;
}

/* Status du livre */
.livre-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
    margin-bottom: 12px;
}

.status-disponible {
    background: rgba(82, 196, 26, 0.1);
    color: var(--success);
}

.status-emprunte {
    background: rgba(255, 77, 79, 0.1);
    color: var(--danger);
}

.status-numerique {
    background: rgba(93, 173, 226, 0.1);
    color: var(--primary);
}

/* Boutons d'action */
.livre-actions {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.btn {
    width: 100%;
    padding: 8px 10px;
    border: none;
    border-radius: 5px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    text-align: center;
    display: inline-block;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}

.btn-secondary {
    background: var(--bg-light);
    color: var(--text-gray);
    border: 1px solid var(--border-light);
}

.btn-secondary:hover {
    background: var(--border-light);
    color: var(--text-dark);
}

.btn-success {
    background: var(--success);
    color: white;
}

.btn-success:hover {
    background: #389e0d;
    transform: translateY(-1px);
}

.btn-disabled {
    background: var(--border-light);
    color: var(--text-gray);
    cursor: not-allowed;
    opacity: 0.6;
}

/* Responsive */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .header-title {
        font-size: 1.3rem;
    }
    
    .header-nav {
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
    }
    
    .search-section {
        margin: 15px;
        padding: 15px;
    }
    
    .search-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-input-container {
        min-width: auto;
    }
    
    .filter-container {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .container {
        padding: 15px;
    }
    
    .category-title {
        font-size: 1.3rem;
    }
    
    .livre-card {
        width: 140px;
    }
    
    .livre-img {
        height: 190px;
    }
    
    .carousel-nav {
        display: none;
    }
    
    .results-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 10px;
        padding: 15px;
    }
}

@media (max-width: 480px) {
    .search-section {
        margin: 10px;
        padding: 10px;
    }
    
    .books-wrapper {
        gap: 10px;
    }
    
    .livre-card {
        width: 120px;
    }
    
    .livre-img {
        height: 160px;
    }
    
    .livre-content {
        padding: 10px 8px;
    }
    
    .results-grid {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }
}
    </style>   
    
    <header>         
        <div class="header-content">
            <div class="header-title"><i class="fas fa-book-open"></i> IUGET Bibliothèque</div>         
            <div class="header-nav">             
                Bonjour <strong><?= htmlspecialchars($_SESSION['utilisateur']['prenom']) ?></strong>!             
                <a href="messagerie.php"><i class="fas fa-envelope"></i> Messagerie</a>             
                <a href="deconnexion.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>         
            </div>
        </div>     
    </header>

    <!-- Section de recherche -->
    <div class="search-section">
        <div class="search-container">
            <div class="search-input-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Rechercher un livre...">
                <i class="fas fa-search search-icon"></i>
            </div>
            <div class="filter-container">
                <button class="filter-btn active" data-filter="tout">Tout</button>
                <button class="filter-btn" data-filter="titre">Titre</button>
                <button class="filter-btn" data-filter="auteur">Auteur</button>
            </div>
        </div>
    </div>

    <!-- Résultats de recherche -->
    <div class="search-results" id="searchResults">
        <div class="results-header">
            <span id="resultsCount">0 résultat(s) trouvé(s)</span>
        </div>
        <div class="results-grid" id="resultsGrid">
        </div>
    </div>
          
    <div class="container">                  
        <?php if (isset($message)): ?>             
            <div class="message"><i class="fas fa-check-circle"></i> <?= $message ?></div>         
        <?php endif; ?>                  
        
        <div id="categoriesContainer">
            <?php foreach ($livres_par_categorie as $categorie => $livres): ?>
                <?php if (!empty($livres)): ?>
                    <div class="category-section">
                        <h2 class="category-title"><?= htmlspecialchars($categorie) ?></h2>
                        
                        <div class="books-carousel">
                            <button class="carousel-nav prev" onclick="scrollBooks('<?= $categorie ?>', -1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            
                            <div class="books-wrapper" id="books-<?= str_replace(' ', '_', $categorie) ?>">
                                <?php foreach ($livres as $livre): ?>
                                    <div class="livre-card">
                                        <?php if ($livre['couverture']): ?>
                                            <img src="<?= htmlspecialchars($livre['couverture']) ?>" alt="Couverture" class="livre-img">
                                        <?php else: ?>
                                            <div class="livre-img" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                                                <i class="fas fa-book"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="livre-content">
                                            <h3 class="livre-title"><?= htmlspecialchars($livre['titre']) ?></h3>
                                            <p class="livre-author"><i class="fas fa-user"></i> <?= htmlspecialchars($livre['auteur']) ?></p>
                                            
                                            <!-- Tous les livres sont maintenant toujours disponibles -->
                                            <?php if ($livre['type'] === 'numerique'): ?>
                                                <span class="livre-status status-numerique"><i class="fas fa-download"></i> Numérique</span>
                                            <?php else: ?>
                                                <span class="livre-status status-disponible"><i class="fas fa-check-circle"></i> Disponible</span>
                                            <?php endif; ?>
                                            
                                            <div class="livre-actions">
                                                <?php if ($livre['type'] === 'numerique' && $livre['fichier']): ?>
                                                    <a href="<?= htmlspecialchars($livre['fichier']) ?>" download class="btn btn-success">
                                                        <i class="fas fa-download"></i> Télécharger
                                                    </a>
                                                <?php else: ?>
                                                    <?php
                                                    // Vérifier si l'utilisateur a déjà une réservation en attente
                                                    $stmt = $db->prepare("SELECT * FROM reservations WHERE utilisateur_id = ? AND livre_id = ? AND statut = 'en_attente'");
                                                    $stmt->execute([$_SESSION['utilisateur']['id'], $livre['id']]);
                                                    $reservation_existante = $stmt->fetch();
                                                    ?>
                                                    
                                                    <?php if ($reservation_existante): ?>
                                                        <button class="btn btn-disabled" disabled>
                                                            <i class="fas fa-clock"></i> En attente
                                                        </button>
                                                    <?php else: ?>
                                                        <form method="post" style="width: 100%;">
                                                            <input type="hidden" name="livre_id" value="<?= $livre['id'] ?>">
                                                            <button type="submit" name="reserver" class="btn btn-primary">
                                                                <i class="fas fa-bookmark"></i> Réserver
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-secondary" onclick="showBookDetails(<?= $livre['id'] ?>)">
                                                    <i class="fas fa-info-circle"></i> Détails
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button class="carousel-nav next" onclick="scrollBooks('<?= $categorie ?>', 1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Données des livres pour la recherche
        const livres = <?= json_encode($tous_livres) ?>;
        
        let currentFilter = 'tout';
        
        // Gestion des filtres
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.dataset.filter;
                
                const searchTerm = document.getElementById('searchInput').value;
                if (searchTerm) {
                    performSearch(searchTerm);
                }
            });
        });
        
        // Fonction de recherche
        function performSearch(searchTerm) {
            if (!searchTerm.trim()) {
                hideSearchResults();
                return;
            }
            
            let results = [];
            const term = searchTerm.toLowerCase();
            
            switch (currentFilter) {
                case 'titre':
                    results = livres.filter(livre => 
                        livre.titre.toLowerCase().includes(term)
                    );
                    break;
                case 'auteur':
                    results = livres.filter(livre => 
                        livre.auteur.toLowerCase().includes(term)
                    );
                    break;
                default: // tout
                    results = livres.filter(livre => 
                        livre.titre.toLowerCase().includes(term) ||
                        livre.auteur.toLowerCase().includes(term) ||
                        livre.categorie.toLowerCase().includes(term)
                    );
            }
            
            displaySearchResults(results, searchTerm);
        }
        
        // Affichage des résultats
        function displaySearchResults(results, searchTerm) {
            const resultsContainer = document.getElementById('searchResults');
            const resultsGrid = document.getElementById('resultsGrid');
            const resultsCount = document.getElementById('resultsCount');
            const categoriesContainer = document.getElementById('categoriesContainer');
            
            // Masquer les catégories
            categoriesContainer.style.display = 'none';
            
            // Afficher les résultats
            resultsContainer.classList.add('show');
            resultsCount.textContent = `${results.length} résultat(s) trouvé(s) pour "${searchTerm}"`;
            
            resultsGrid.innerHTML = '';
            
            results.forEach(livre => {
                const resultItem = document.createElement('div');
                resultItem.className = 'result-item';
                resultItem.onclick = () => showBookDetails(livre.id);
                
                const imageHtml = livre.couverture 
                    ? `<img src="${livre.couverture}" alt="Couverture" class="result-image">`
                    : `<div class="result-placeholder"><i class="fas fa-book"></i></div>`;
                
                resultItem.innerHTML = `
                    ${imageHtml}
                    <div class="result-title">${livre.titre}</div>
                    <div class="result-author">${livre.auteur}</div>
                `;
                
                resultsGrid.appendChild(resultItem);
            });
        }
        
        // Masquer les résultats
        function hideSearchResults() {
            document.getElementById('searchResults').classList.remove('show');
            document.getElementById('categoriesContainer').style.display = 'block';
        }
        
        // Gestion de la recherche en temps réel
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value;
            if (searchTerm.trim()) {
                performSearch(searchTerm);
            } else {
                hideSearchResults();
            }
        });
        
        function scrollBooks(category, direction) {
            const container = document.getElementById('books-' + category.replace(/ /g, '_'));
            const scrollAmount = 180; // largeur d'une carte + gap
            container.scrollBy({
                left: direction * scrollAmount,
                behavior: 'smooth'
            });
        }
        
        function showBookDetails(bookId) {
            // Trouver le livre
            const livre = livres.find(l => l.id == bookId);
            if (livre) {
                alert(`Détails du livre:\n\nTitre: ${livre.titre}\nAuteur: ${livre.auteur}\nCatégorie: ${livre.categorie}\nType: ${livre.type}\n\nFonctionnalité complète à implémenter avec une modal.`);
            }
        }
        
        // Animation d'apparition des cartes
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.livre-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });
    </script>
</body> 
</html>