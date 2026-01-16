<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login_admin.php');
    exit();
}

$db = new PDO('mysql:host=localhost;dbname=gestion_bibliotheque;charset=utf8', 'root', '');

// Filtres et recherche
$filtre_statut = $_GET['statut'] ?? 'tous';
$filtre_type = $_GET['type'] ?? 'tous';
$recherche = $_GET['recherche'] ?? '';

// Construction de la requête
$sql = "
    SELECT 
        r.*, 
        u.nom AS user_nom, 
        u.prenom AS user_prenom,
        u.type AS user_type,
        u.specialite,
        u.niveau,
        u.matiere,
        l.titre AS livre_titre,
        l.type AS livre_type,
        l.categorie
    FROM reservations r
    JOIN utilisateurs u ON r.utilisateur_id = u.id
    JOIN livres l ON r.livre_id = l.id
";

$conditions = [];
$params = [];

if ($filtre_statut !== 'tous') {
    $conditions[] = "r.statut = ?";
    $params[] = $filtre_statut;
}

if ($filtre_type !== 'tous') {
    $conditions[] = "u.type = ?";
    $params[] = $filtre_type;
}

if (!empty($recherche)) {
    $conditions[] = "(u.nom LIKE ? OR u.prenom LIKE ? OR l.titre LIKE ?)";
    $params = array_merge($params, ["%$recherche%", "%$recherche%", "%$recherche%"]);
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY r.date_reservation DESC";

// Exécution de la requête
$stmt = $db->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="registre_pret_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes CSV
    fputcsv($output, [
        'Date', 
        'Utilisateur', 
        'Type', 
        'Détails', 
        'Livre', 
        'Catégorie', 
        'Type', 
        'Statut'
    ], ';');
    
    // Données
    foreach ($reservations as $row) {
        $details = '';
        if ($row['user_type'] === 'etudiant') {
            $details = $row['specialite'] . ' - ' . $row['niveau'];
        } else {
            $details = $row['matiere'];
        }
        
        fputcsv($output, [
            $row['date_reservation'],
            $row['user_prenom'] . ' ' . $row['user_nom'],
            ucfirst($row['user_type']),
            $details,
            $row['livre_titre'],
            $row['categorie'],
            ucfirst($row['livre_type']),
            $row['statut'] === 'en_attente' ? 'En attente' : 
                ($row['statut'] === 'approuve' ? 'Approuvé' : 'Refusé')
        ], ';');
    }
    
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registre des prêts</title>
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
    min-height: 100vh;
}

/* Container */
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px;
}

/* Title */
h1 {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 25px 30px;
    border-radius: var(--border-radius);
    margin-bottom: 30px;
    font-size: 1.8rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: var(--shadow);
}

/* Filters Section */
.filters {
    background: var(--bg-white);
    padding: 25px 30px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    margin-bottom: 25px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-group label {
    font-weight: 600;
    color: var(--text-dark);
    font-size: 0.9rem;
}

.filter-group select,
.filter-group input {
    padding: 12px 15px;
    border: 2px solid var(--border-light);
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    background: white;
}

.filter-group select:focus,
.filter-group input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(93, 173, 226, 0.1);
}

.filters button {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    justify-self: start;
    height: fit-content;
}

.filters button:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

/* Actions */
.actions {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
    justify-content: flex-end;
}

.btn-export button,
.btn-reset button {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-export button {
    background: linear-gradient(135deg, var(--success) 0%, #389e0d 100%);
    color: white;
}

.btn-export button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(82, 196, 26, 0.3);
}

.btn-reset button {
    background: linear-gradient(135deg, var(--warning) 0%, #d48806 100%);
    color: white;
}

.btn-reset button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(250, 173, 20, 0.3);
}

/* Table */
table {
    width: 100%;
    background: var(--bg-white);
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow);
    border-collapse: collapse;
}

thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

th {
    padding: 20px 15px;
    text-align: left;
    font-weight: 600;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

tbody tr {
    border-bottom: 1px solid var(--border-light);
    transition: all 0.3s ease;
}

tbody tr:hover {
    background: rgba(93, 173, 226, 0.05);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

tbody tr:last-child {
    border-bottom: none;
}

td {
    padding: 18px 15px;
    vertical-align: middle;
    font-size: 0.9rem;
}

/* User Type Badges */
.user-type {
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.user-type.student {
    background: rgba(93, 173, 226, 0.1);
    color: var(--primary);
}

.user-type.teacher {
    background: rgba(138, 43, 226, 0.1);
    color: #8a2be2;
}

/* Status Badges */
.badge {
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.badge-pending {
    background: rgba(250, 173, 20, 0.1);
    color: var(--warning);
}

.badge-pending::before {
    content: '\f017';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
}

.badge-approved {
    background: rgba(82, 196, 26, 0.1);
    color: var(--success);
}

.badge-approved::before {
    content: '\f00c';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
}

.badge-rejected {
    background: rgba(255, 77, 79, 0.1);
    color: var(--danger);
}

.badge-rejected::before {
    content: '\f00d';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
}

/* Navigation Item */
.nav-item {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 50px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: var(--shadow-hover);
    transition: all 0.3s ease;
    font-weight: 500;
    z-index: 1000;
}

.nav-item:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 8px 25px rgba(93, 173, 226, 0.4);
}

/* Empty State */
.container p {
    background: var(--bg-white);
    padding: 40px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    text-align: center;
    color: var(--text-gray);
    font-size: 1.1rem;
}

.container p::before {
    content: '\f2b9';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 3rem;
    color: var(--border-light);
    display: block;
    margin-bottom: 20px;
}

/* Responsive */
@media (max-width: 1200px) {
    table {
        font-size: 0.8rem;
    }
    
    th, td {
        padding: 12px 10px;
    }
}

@media (max-width: 768px) {
    .container {
        padding: 20px 15px;
    }
    
    h1 {
        font-size: 1.5rem;
        padding: 20px 25px;
    }
    
    .filters {
        grid-template-columns: 1fr;
        gap: 15px;
        padding: 20px;
    }
    
    .actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .btn-export, .btn-reset {
        width: 100%;
    }
    
    .btn-export button, .btn-reset button {
        width: 100%;
        justify-content: center;
    }
    
    /* Table responsive */
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
    
    thead, tbody, th, td, tr {
        display: block;
    }
    
    thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
    }
    
    tbody tr {
        border: 1px solid var(--border-light);
        margin-bottom: 15px;
        border-radius: 8px;
        padding: 15px;
        background: white;
    }
    
    td {
        border: none;
        position: relative;
        padding: 8px 0 8px 35%;
        text-align: left;
    }
    
    td:before {
        content: attr(data-label) ": ";
        position: absolute;
        left: 0;
        width: 30%;
        font-weight: 600;
        color: var(--text-dark);
    }
    
    .nav-item {
        bottom: 20px;
        right: 20px;
        padding: 12px 16px;
    }
}

@media (max-width: 480px) {
    .filters {
        padding: 15px;
    }
    
    h1 {
        font-size: 1.3rem;
        padding: 15px 20px;
    }
}

/* Animation pour le chargement */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

tbody tr {
    animation: fadeInUp 0.6s ease forwards;
}

tbody tr:nth-child(odd) {
    animation-delay: 0.1s;
}

tbody tr:nth-child(even) {
    animation-delay: 0.2s;
}

/* Style spécial pour bouton d'impression */
.print-container .btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.print-container .btn::before {
  content: "🖨️";
}

.print-container .btn:hover {
  background-color: #8e44ad;
}

    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-clipboard-list"></i> Registre</h1>
        
        <!-- Filtres -->
        <form method="get" action="admin_registre.php">
            <div class="filters">
                <div class="filter-group">
                    <label for="statut">Statut:</label>
                    <select id="statut" name="statut">
                        <option value="tous" <?= $filtre_statut === 'tous' ? 'selected' : '' ?>>Tous</option>
                        <option value="en_attente" <?= $filtre_statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                        <option value="approuve" <?= $filtre_statut === 'approuve' ? 'selected' : '' ?>>Approuvé</option>
                        <option value="refuse" <?= $filtre_statut === 'refuse' ? 'selected' : '' ?>>Refusé</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="type">Type:</label>
                    <select id="type" name="type">
                        <option value="tous" <?= $filtre_type === 'tous' ? 'selected' : '' ?>>Tous</option>
                        <option value="etudiant" <?= $filtre_type === 'etudiant' ? 'selected' : '' ?>>Étudiants</option>
                        <option value="professeur" <?= $filtre_type === 'professeur' ? 'selected' : '' ?>>Professeurs</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="recherche">Recherche:</label>
                    <input type="text" id="recherche" name="recherche" value="<?= htmlspecialchars($recherche) ?>" placeholder="Nom, prénom ou titre">
                </div>
                
                <button type="submit"><i class="fas fa-filter"></i> Filtrer</button>
            </div>
        </form>
        
        <!-- Actions -->
        <div class="actions">
            <!-- <a href="admin_registre.php?export=csv&<?= http_build_query($_GET) ?>" class="btn-export">
                <button><i class="fas fa-file-export"></i> Exporter en CSV</button>
            </a> -->
            <div class="btn-export">
            <button onclick="window.print()" class="fas fa-file-export">Imprimer le registre</button>
            </div>
            <a href="admin_registre.php" class="btn-reset">
                <button><i class="fas fa-sync-alt"></i> Réinitialiser</button>
            </a>
        </div>
        
        <!-- Tableau des prêts -->
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Utilisateur</th>
                    <th>Type</th>
                    <th>Détails</th>
                    <th>Livre</th>
                    <th>Catégorie</th>
                    <th>Type</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $res): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($res['date_reservation'])) ?></td>
                        <td><?= htmlspecialchars($res['user_prenom'] . ' ' . htmlspecialchars($res['user_nom'])) ?></td>
                        <td>
                            <span class="user-type <?= $res['user_type'] === 'etudiant' ? 'student' : 'teacher' ?>">
                                <?= $res['user_type'] === 'etudiant' ? 'Étudiant' : 'Professeur' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($res['user_type'] === 'etudiant'): ?>
                                <?= htmlspecialchars($res['specialite']) ?> - <?= htmlspecialchars($res['niveau']) ?>
                            <?php else: ?>
                                <?= htmlspecialchars($res['matiere']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($res['livre_titre']) ?></td>
                        <td><?= htmlspecialchars($res['categorie']) ?></td>
                        <td><?= $res['livre_type'] === 'physique' ? 'Physique' : 'Numérique' ?></td>
                        <td>
                            <span class="badge badge-<?= 
                                $res['statut'] === 'en_attente' ? 'pending' : 
                                ($res['statut'] === 'approuve' ? 'approved' : 'rejected')
                            ?>">
                                <?= 
                                    $res['statut'] === 'en_attente' ? 'En attente' : 
                                    ($res['statut'] === 'approuve' ? 'Approuvé' : 'Refusé')
                                ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

         <!-- Bouton d'impression -->
        <div class="print-container no-print">
            <button onclick="window.print()" class="btn btn-info">Imprimer le registre</button>
        </div>
        
        <?php if (empty($reservations)): ?>
            <p style="text-align: center; margin: 30px 0; color: #666;">Aucune réservation trouvée avec ces critères</p>
        <?php endif; ?>
    </div>
    <a href="admin_dashboard.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>retour au dashb</span>
                </a>
</body>
</html>