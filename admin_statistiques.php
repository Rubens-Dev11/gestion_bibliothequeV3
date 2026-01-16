<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login_admin.php');
    exit();
}

// Connexion à la base de données
$db = new PDO('mysql:host=localhost;dbname=gestion_bibliotheque;charset=utf8', 'root', '');

// Récupération des statistiques générales
function getGeneralStats($db) {
    $stats = [];
    
    // Total utilisateurs
    $stmt = $db->query("SELECT COUNT(*) as total FROM utilisateurs");
    $stats['total_users'] = $stmt->fetch()['total'];
    
    // Total livres
    $stmt = $db->query("SELECT COUNT(*) as total FROM livres");
    $stats['total_books'] = $stmt->fetch()['total'];
    
    // Réservations approuvées (emprunts actifs)
    $stmt = $db->query("SELECT COUNT(*) as total FROM reservations WHERE statut = 'approuve'");
    $stats['active_loans'] = $stmt->fetch()['total'];
    
    // Réservations en retard (simulé - vous pouvez ajouter une date de retour prévue)
    $stats['overdue_loans'] = 0; // À adapter selon votre logique métier
    
    return $stats;
}

// Évolution des emprunts par mois
function getLoanEvolution($db) {
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(date_reservation, '%Y-%m') as mois,
            COUNT(*) as total_emprunts,
            COUNT(CASE WHEN statut = 'approuve' THEN 1 END) as emprunts_approuves
        FROM reservations 
        WHERE date_reservation >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(date_reservation, '%Y-%m')
        ORDER BY mois
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Performance par filière (basé sur la spécialité des étudiants)
function getPerformanceByField($db) {
    $stmt = $db->query("
        SELECT 
            u.specialite as filiere,
            COUNT(r.id) as emprunts_par_etudiant
        FROM utilisateurs u
        LEFT JOIN reservations r ON u.id = r.utilisateur_id
        WHERE u.type = 'etudiant' AND u.specialite IS NOT NULL AND u.specialite != ''
        GROUP BY u.specialite
        HAVING COUNT(r.id) > 0
        ORDER BY emprunts_par_etudiant DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Catégories les plus populaires
function getPopularCategories($db) {
    $stmt = $db->query("
        SELECT 
            l.categorie,
            COUNT(r.id) as nombre_emprunts
        FROM livres l
        LEFT JOIN reservations r ON l.id = r.livre_id
        WHERE r.id IS NOT NULL
        GROUP BY l.categorie
        ORDER BY nombre_emprunts DESC
        LIMIT 10
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Types de livres (physique vs numérique)
function getBookTypes($db) {
    $stmt = $db->query("
        SELECT 
            type,
            COUNT(*) as nombre
        FROM livres
        GROUP BY type
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupération des données
$generalStats = getGeneralStats($db);
$loanEvolution = getLoanEvolution($db);
$performanceByField = getPerformanceByField($db);
$popularCategories = getPopularCategories($db);
$bookTypes = getBookTypes($db);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - Gestion Bibliothèque</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.2);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2ecc71, #f39c12, #e74c3c);
        }

        .stat-card.blue::before { background: #3498db; }
        .stat-card.green::before { background: #2ecc71; }
        .stat-card.orange::before { background: #f39c12; }
        .stat-card.red::before { background: #e74c3c; }

        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #7f8c8d;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .chart-title {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-canvas {
            max-height: 400px;
        }

        .additional-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-list {
            list-style: none;
        }

        .info-list li {
            padding: 8px 0;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-list li:last-child {
            border-bottom: none;
        }

        .badge {
            background: #3498db;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #3498db;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(52, 152, 219, 0.3);
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            background: #2980b9;
            transform: rotate(180deg);
        }

        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

       
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-chart-bar"></i> Tableau de Bord - Statistiques</h1>
        <a href="admin_dashboard.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>retour au dashb</span>
                </a>
    </div>

    

    <!-- Statistiques générales -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-number"><?= $generalStats['total_users'] ?></div>
            <div class="stat-label">
                <i class="fas fa-users"></i>
                Total Utilisateurs
            </div>
        </div>
        
        <div class="stat-card green">
            <div class="stat-number"><?= $generalStats['total_books'] ?></div>
            <div class="stat-label">
                <i class="fas fa-book"></i>
                Total Livres
            </div>
        </div>
        
        <div class="stat-card orange">
            <div class="stat-number"><?= $generalStats['active_loans'] ?></div>
            <div class="stat-label">
                <i class="fas fa-bookmark"></i>
                Total réservation
            </div>
        </div>
        
        <div class="stat-card red">
            <div class="stat-number"><?= $generalStats['overdue_loans'] ?></div>
            <div class="stat-label">
                <i class="fas fa-exclamation-triangle"></i>
                Non rendu
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="charts-grid">
        <!-- Évolution des emprunts -->
        <div class="chart-container">
            <h3 class="chart-title">
                <i class="fas fa-chart-line"></i>
                Évolution des Activités
            </h3>
            <canvas id="loanEvolutionChart" class="chart-canvas"></canvas>
        </div>

        <!-- Performance par filière -->
        <div class="chart-container">
            <h3 class="chart-title">
                <i class="fas fa-graduation-cap"></i>
                Performance par Filière
            </h3>
            <canvas id="performanceChart" class="chart-canvas"></canvas>
        </div>

        <!-- Catégories populaires -->
        <div class="chart-container">
            <h3 class="chart-title">
                <i class="fas fa-star"></i>
                Catégories Populaires
            </h3>
            <canvas id="categoriesChart" class="chart-canvas"></canvas>
        </div>

        <!-- Types de livres -->
        <div class="chart-container">
            <h3 class="chart-title">
                <i class="fas fa-pie-chart"></i>
                Répartition des Types de Livres
            </h3>
            <canvas id="bookTypesChart" class="chart-canvas"></canvas>
        </div>
    </div>

    <!-- Informations additionnelles -->
    <div class="additional-stats">
        <div class="info-card">
            <h3><i class="fas fa-trophy"></i> Top Catégories</h3>
            <ul class="info-list">
                <?php foreach(array_slice($popularCategories, 0, 5) as $category): ?>
                <li>
                    <span><?= htmlspecialchars($category['categorie']) ?></span>
                    <span class="badge"><?= $category['nombre_emprunts'] ?> Réservation</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="info-card">
            <h3><i class="fas fa-info-circle"></i> Informations Système</h3>
            <ul class="info-list">
                <li>
                    <span>Dernière mise à jour</span>
                    <span class="badge"><?= date('d/m/Y H:i') ?></span>
                </li>
                <li>
                    <span>Types de livres</span>
                    <span class="badge"><?= count($bookTypes) ?> types</span>
                </li>
                <li>
                    <span>Catégories actives</span>
                    <span class="badge"><?= count($popularCategories) ?></span>
                </li>
            </ul>
        </div>
    </div>

    <button class="refresh-btn" onclick="location.reload()">
        <i class="fas fa-sync-alt"></i>
    </button>

    

    <script>
        // Configuration globale des graphiques
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.color = '#2c3e50';

        // Données PHP vers JavaScript
        const loanEvolutionData = <?= json_encode($loanEvolution) ?>;
        const performanceData = <?= json_encode($performanceByField) ?>;
        const categoriesData = <?= json_encode($popularCategories) ?>;
        const bookTypesData = <?= json_encode($bookTypes) ?>;

        // Graphique d'évolution des emprunts
        const ctx1 = document.getElementById('loanEvolutionChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: loanEvolutionData.map(item => {
                    const [year, month] = item.mois.split('-');
                    return new Date(year, month - 1).toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Total Réservations',
                    data: loanEvolutionData.map(item => item.total_emprunts),
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Réservations Approuvés',
                    data: loanEvolutionData.map(item => item.emprunts_approuves),
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                }
            }
        });

        // Graphique performance par filière
        const ctx2 = document.getElementById('performanceChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: performanceData.map(item => item.filiere),
                datasets: [{
                    label: 'Réservations par Étudiant',
                    data: performanceData.map(item => item.emprunts_par_etudiant),
                    backgroundColor: [
                        '#3498db', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6', '#1abc9c'
                    ],
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Graphique catégories populaires
        const ctx3 = document.getElementById('categoriesChart').getContext('2d');
        new Chart(ctx3, {
            type: 'doughnut',
            data: {
                labels: categoriesData.map(item => item.categorie),
                datasets: [{
                    data: categoriesData.map(item => item.nombre_emprunts),
                    backgroundColor: [
                        '#3498db', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6', 
                        '#1abc9c', '#34495e', '#f1c40f', '#e67e22', '#95a5a6'
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Graphique types de livres
        const ctx4 = document.getElementById('bookTypesChart').getContext('2d');
        new Chart(ctx4, {
            type: 'pie',
            data: {
                labels: bookTypesData.map(item => 
                    item.type === 'physique' ? 'Livres Physiques' : 'Livres Numériques'
                ),
                datasets: [{
                    data: bookTypesData.map(item => item.nombre),
                    backgroundColor: ['#3498db', '#2ecc71'],
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Animation d'entrée pour les cartes statistiques
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.6s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });

        // Mise à jour automatique toutes les 5 minutes
        setInterval(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>