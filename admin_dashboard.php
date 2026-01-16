<?php
 session_start();
 if (!isset($_SESSION['admin'])) {
     header('Location: login_admin.php');
     exit();
 }

// Configuration de la base de données
$db = new PDO('mysql:host=localhost;dbname=gestion_bibliotheque;charset=utf8', 'root', '');

// Récupération des statistiques
$stats = [
    'total_livres' => $db->query("SELECT COUNT(*) FROM livres")->fetchColumn(),
    'total_utilisateurs' => $db->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn(),
    'reservations_en_attente' => $db->query("SELECT COUNT(*) FROM reservations WHERE statut = 'en_attente'")->fetchColumn(),
    'messages_non_lus' => $db->query("SELECT COUNT(*) FROM messages WHERE destinataire_id = 1 AND lu = FALSE")->fetchColumn(),
    'livres_populaires' => $db->query("
        SELECT l.titre, COUNT(r.id) as reservations 
        FROM livres l
        LEFT JOIN reservations r ON l.id = r.livre_id
        GROUP BY l.id
        ORDER BY reservations DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC),
    'activite_recente' => $db->query("
        SELECT 
            u.prenom, u.nom, 
            l.titre as livre_titre, 
            r.date_reservation,
            r.statut
        FROM reservations r
        JOIN utilisateurs u ON r.utilisateur_id = u.id
        JOIN livres l ON r.livre_id = l.id
        ORDER BY r.date_reservation DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC)
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

.dashboard {
    display: flex;
    min-height: 100vh;
}

/* Sidebar */
.sidebar {
    width: 280px;
    background: var(--bg-white);
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.08);
    display: flex;
    flex-direction: column;
    position: fixed;
    height: 100vh;
    z-index: 100;
}

.sidebar-header {
    padding: 30px 25px;
    border-bottom: 1px solid var(--border-light);
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
}

.sidebar-header h2 {
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.sidebar-header img {
    width: 32px;
    height: 32px;
}

.admin-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.admin-avatar {
    width: 45px;
    height: 45px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    backdrop-filter: blur(10px);
}

.admin-name {
    font-weight: 600;
    font-size: 0.95rem;
}

.admin-email {
    font-size: 0.8rem;
    opacity: 0.8;
}

.nav-menu {
    flex: 1;
    padding: 25px 0;
    overflow-y: auto;
}

.nav-item {
    display: flex;
    align-items: center;
    padding: 14px 25px;
    color: var(--text-gray);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
    position: relative;
    margin: 2px 0;
}

.nav-item:hover {
    background: var(--bg-light);
    color: var(--primary);
    border-left-color: var(--primary);
}

.nav-item.active {
    background: linear-gradient(90deg, rgba(93, 173, 226, 0.1) 0%, rgba(93, 173, 226, 0.05) 100%);
    color: var(--primary);
    border-left-color: var(--primary);
    font-weight: 600;
}

.nav-item i {
    width: 20px;
    margin-right: 15px;
    font-size: 1.1rem;
}

.nav-item .badge {
    background: var(--danger);
    color: white;
    font-size: 0.75rem;
    padding: 3px 8px;
    border-radius: 12px;
    margin-left: auto;
    min-width: 20px;
    text-align: center;
}

/* Main Content */
.main-content {
    flex: 1;
    margin-left: 280px;
    padding: 30px;
    background: var(--bg-light);
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 35px;
    background: var(--bg-white);
    padding: 25px 30px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.header h1 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-dark);
}

.logout-btn {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
}

.logout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(231, 76, 60, 0.4);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    margin-bottom: 35px;
}

.stat-card {
    background: var(--bg-white);
    padding: 30px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    opacity: 0;
    transform: translateY(20px);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
}

.stat-card.success::before {
    background: linear-gradient(90deg, var(--success) 0%, #389e0d 100%);
}

.stat-card.warning::before {
    background: linear-gradient(90deg, var(--warning) 0%, #d48806 100%);
}

.stat-card.danger::before {
    background: linear-gradient(90deg, var(--danger) 0%, #cf1322 100%);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
}

.stat-title {
    font-size: 0.9rem;
    color: var(--text-gray);
    margin-bottom: 12px;
    font-weight: 500;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 15px;
}

.stat-change {
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.stat-change.positive {
    color: var(--success);
}

.stat-change.negative {
    color: var(--danger);
}

/* Charts Grid */
.charts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 35px;
}

.chart-container {
    background: var(--bg-white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.chart-header {
    padding: 25px 30px 20px;
    border-bottom: 1px solid var(--border-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chart-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-dark);
}

.chart-header a {
    color: var(--primary);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
}

.chart-header a:hover {
    text-decoration: underline;
}

.chart {
    padding: 20px 30px 30px;
}

.chart table {
    width: 100%;
    border-collapse: collapse;
}

.chart table td {
    padding: 12px 0;
    border-bottom: 1px solid var(--border-light);
    vertical-align: middle;
}

.activity-status {
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.activity-status.status-en_attente {
    background: rgba(250, 173, 20, 0.1);
    color: var(--warning);
}

.activity-status.status-approuve {
    background: rgba(82, 196, 26, 0.1);
    color: var(--success);
}

.activity-status.status-refuse {
    background: rgba(255, 77, 79, 0.1);
    color: var(--danger);
}

/* Activity Container */
.activity-container {
    background: var(--bg-white);
    padding: 30px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.activity-container h2 {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 25px;
}

.activity-container > div:last-child {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
}

.activity-container a {
    text-decoration: none;
    color: inherit;
}

.activity-container > div:last-child > a > div {
    background: var(--bg-light);
    padding: 25px 20px;
    border-radius: 10px;
    text-align: center;
    transition: all 0.3s ease;
    border: 2px dashed var(--border-light);
    cursor: pointer;
}

.activity-container > div:last-child > a > div:hover {
    background: white;
    border-color: var(--primary);
    transform: translateY(-3px);
    box-shadow: var(--shadow);
}

.activity-container i {
    font-size: 1.8rem;
    color: var(--primary);
    margin-bottom: 15px;
    display: block;
}

.activity-container > div:last-child > a > div > div:last-child {
    font-weight: 500;
    color: var(--text-dark);
}

/* Responsive */
@media (max-width: 1200px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .sidebar.open {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .header {
        padding: 20px;
    }
    
    .header h1 {
        font-size: 1.5rem;
    }
}
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><img src="assets/iuget.jpg" alt="Logo"> Bibliothèque</h2>
                <div class="admin-info">
                    <div class="admin-avatar">
                        <?= strtoupper(substr($_SESSION['admin']['prenom'], 0, 1) . substr($_SESSION['admin']['nom'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="admin-name"><?= $_SESSION['admin']['prenom'] . ' ' . $_SESSION['admin']['nom'] ?></div>
                        <div class="admin-email"><?= $_SESSION['admin']['email'] ?></div>
                    </div>
                </div>
            </div>
            <div class="nav-menu">
                <div class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de bord</span>
                </div>
                <a href="livre.php" class="nav-item">
                    <i class="fas fa-book"></i>
                    <span>Gestion des livres</span>
                </a>
                <a href="admin_utilisateurs.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Gestion des utilisateurs</span>
                </a>
                <a href="admin_messagerie.php" class="nav-item">
                    <i class="fas fa-envelope"></i>
                    <span>Messagerie</span>
                    <?php if ($stats['messages_non_lus'] > 0): ?>
                        <span class="badge"><?= $stats['messages_non_lus'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin_registre.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Registre </span>
                </a>
                <a href="admin_statistiques.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Statistiques avancées</span>
                </a>
                
                    <a href="admin_administrateurs.php" class="nav-item">
                        <i class="fas fa-user-shield"></i>
                        <span>Gestion administrateurs</span>
                    </a>
                
                <a href="admin_parametres.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Paramètres</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Tableau de bord</h1>
                <a href="deconnexion.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Déconnexion
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-title">Livres enregistrés</div>
                    <div class="stat-value"><?= $stats['total_livres'] ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +5% depuis le mois dernier
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-title">Utilisateurs inscrits</div>
                    <div class="stat-value"><?= $stats['total_utilisateurs'] ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +12% depuis le mois dernier
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-title">Réservations en attente</div>
                    <div class="stat-value"><?= $stats['reservations_en_attente'] ?></div>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-down"></i>
                        -3% depuis hier
                    </div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-title">Messages non lus</div>
                    <div class="stat-value"><?= $stats['messages_non_lus'] ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +2 depuis hier
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-grid">
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">Livres les plus populaires</div>
                        <a href="livre.php">Voir tout</a>
                    </div>
                    <div class="chart">
                        <table style="width: 100%; border-collapse: collapse;">
                            <?php foreach ($stats['livres_populaires'] as $livre): ?>
                            <tr>
                                <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><?= htmlspecialchars($livre['titre']) ?></td>
                                <td style="text-align: right; border-bottom: 1px solid #eee;"><?= $livre['reservations'] ?> réservations</td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">Activité récente</div>
                        <a href="admin_registre.php">Voir tout</a>
                    </div>
                    <div class="chart">
                        <table style="width: 100%; border-collapse: collapse;">
                            <?php foreach ($stats['activite_recente'] as $activite): ?>
                            <tr>
                                <td style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                    <?= htmlspecialchars($activite['prenom'] . ' ' . $activite['nom']) ?>
                                    <div style="font-size: 0.8rem; color: #666;"><?= htmlspecialchars($activite['livre_titre']) ?></div>
                                </td>
                                <td style="text-align: right; border-bottom: 1px solid #eee;">
                                    <span class="activity-status status-<?= $activite['statut'] ?>">
                                        <?= 
                                            $activite['statut'] === 'en_attente' ? 'En attente' : 
                                            ($activite['statut'] === 'approuve' ? 'Approuvé' : 'Refusé')
                                        ?>
                                    </span>
                                    <div style="font-size: 0.8rem; color: #666;">
                                        <?= date('d/m/Y H:i', strtotime($activite['date_reservation'])) ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="activity-container">
                <h2 style="margin-bottom: 20px;">Actions rapides</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                    <a href="livre.php?action=ajouter" style="text-decoration: none;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center; transition: all 0.3s; border: 1px dashed #ddd;">
                            <i class="fas fa-plus-circle" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 10px;"></i>
                            <div>Ajouter un livre</div>
                        </div>
                    </a>
                    <a href="admin_messagerie.php" style="text-decoration: none;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center; transition: all 0.3s; border: 1px dashed #ddd;">
                            <i class="fas fa-envelope" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 10px;"></i>
                            <div>Voir les messages</div>
                        </div>
                    </a>
                    <a href="admin_utilisateurs.php" style="text-decoration: none;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center; transition: all 0.3s; border: 1px dashed #ddd;">
                            <i class="fas fa-user-edit" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 10px;"></i>
                            <div>Gérer les utilisateurs</div>
                        </div>
                    </a>
                    <a href="admin_registre.php" style="text-decoration: none;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center; transition: all 0.3s; border: 1px dashed #ddd;">
                            <i class="fas fa-clipboard-check" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 10px;"></i>
                            <div>Vérifier le registre</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Animation simple pour les cartes de stats
        document.querySelectorAll('.stat-card').forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = 1;
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    </script>
</body>
</html>