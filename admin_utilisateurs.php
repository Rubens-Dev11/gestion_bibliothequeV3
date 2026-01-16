<?php
session_start();
$db = new PDO('mysql:host=localhost;dbname=gestion_bibliotheque;charset=utf8', 'root', '');

$erreurs = [];
$success = '';
$mode_edition = false;
$utilisateur_modifie = null;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'modifier') {
        // Validation des données
        $id = (int)$_POST['id'];
        $type = $_POST['type'] ?? '';
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = preg_replace('/[^0-9+\-\s]/', '', $_POST['telephone'] ?? '');
        $mot_de_passe = $_POST['mot_de_passe'] ?? '';
        $specialite = trim($_POST['specialite'] ?? '');
        $niveau = $_POST['niveau'] ?? '';
        $matiere = trim($_POST['matiere'] ?? '');

        // Validation des champs obligatoires
        if (empty($nom)) {
            $erreurs[] = "Le nom est obligatoire";
        }
        if (empty($prenom)) {
            $erreurs[] = "Le prénom est obligatoire";
        }
        if (empty($email)) {
            $erreurs[] = "L'email est obligatoire";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = "Format d'email invalide";
        }
        if (!empty($mot_de_passe) && strlen($mot_de_passe) < 6) {
            $erreurs[] = "Le mot de passe doit contenir au moins 6 caractères";
        }
        if (!in_array($type, ['etudiant', 'professeur'])) {
            $erreurs[] = "Type d'utilisateur invalide";
        }

        // Validation spécifique selon le type
        if ($type === 'etudiant') {
            if (empty($specialite)) {
                $erreurs[] = "La spécialité est obligatoire pour les étudiants";
            }
            if (empty($niveau) || !in_array($niveau, ['L1', 'L2', 'L3', 'M1', 'M2'])) {
                $erreurs[] = "Le niveau est obligatoire pour les étudiants";
            }
        } elseif ($type === 'professeur') {
            if (empty($matiere)) {
                $erreurs[] = "La matière principale est obligatoire pour les professeurs";
            }
        }

        // Vérification email unique (sauf pour l'utilisateur actuel)
        if (empty($erreurs)) {
            $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                $erreurs[] = "Cet email est déjà utilisé";
            }
        }

        // Mise à jour en base si pas d'erreurs
        if (empty($erreurs)) {
            $donnees = [
                'type' => $type,
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone,
                'specialite' => $type === 'etudiant' ? $specialite : null,
                'niveau' => $type === 'etudiant' ? $niveau : null,
                'matiere' => $type === 'professeur' ? $matiere : null,
                'id' => $id
            ];

            $sql = "UPDATE utilisateurs SET type = :type, nom = :nom, prenom = :prenom, email = :email, 
                    telephone = :telephone, specialite = :specialite, niveau = :niveau, matiere = :matiere";
            
            if (!empty($mot_de_passe)) {
                $sql .= ", mot_de_passe = :mot_de_passe";
                $donnees['mot_de_passe'] = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = :id";
            
            try {
                $stmt = $db->prepare($sql);
                if ($stmt->execute($donnees)) {
                    $success = "Utilisateur modifié avec succès!";
                } else {
                    $erreurs[] = "Erreur lors de la modification";
                }
            } catch (PDOException $e) {
                $erreurs[] = "Erreur de base de données: " . $e->getMessage();
            }
        }
    } elseif ($action === 'supprimer') {
        $id = (int)$_POST['id'];
        try {
            $stmt = $db->prepare("DELETE FROM utilisateurs WHERE id = ?");
            if ($stmt->execute([$id])) {
                $success = "Utilisateur supprimé avec succès!";
            } else {
                $erreurs[] = "Erreur lors de la suppression";
            }
        } catch (PDOException $e) {
            $erreurs[] = "Erreur de base de données: " . $e->getMessage();
        }
    }
}

// Récupération de l'utilisateur à modifier
if (isset($_GET['modifier'])) {
    $mode_edition = true;
    $id = (int)$_GET['modifier'];
    $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([$id]);
    $utilisateur_modifie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$utilisateur_modifie) {
        $erreurs[] = "Utilisateur introuvable";
        $mode_edition = false;
    }
}

// Récupération des utilisateurs avec filtrage
$recherche = $_GET['recherche'] ?? '';
$type_filtre = $_GET['type_filtre'] ?? '';

$sql = "SELECT * FROM utilisateurs WHERE 1=1";
$params = [];

if (!empty($recherche)) {
    $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR specialite LIKE ? OR matiere LIKE ?)";
    $terme = "%$recherche%";
    $params = array_fill(0, 5, $terme);
}

if (!empty($type_filtre)) {
    $sql .= " AND type = ?";
    $params[] = $type_filtre;
}

$sql .= " ORDER BY nom, prenom";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #52a9db 0%, #4a8bc2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 300;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
        }

        .nav-links a.active {
            background: rgba(255,255,255,0.2);
        }

        /* Container principal */
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Formulaire de modification */
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            display: none;
        }

        .form-container.active {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-title {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            font-weight: 300;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
            font-size: 0.9rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #fff;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #52a9db;
            box-shadow: 0 0 0 3px rgba(82, 169, 219, 0.1);
        }

        /* Radio buttons */
        .radio-group {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .radio-option {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 0.75rem 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            background: #fff;
            transition: all 0.3s ease;
            min-width: 100px;
            justify-content: center;
        }

        .radio-option:hover {
            border-color: #52a9db;
            background: rgba(82, 169, 219, 0.05);
        }

        .radio-option input[type="radio"] {
            margin-right: 0.5rem;
            width: auto;
            padding: 0;
        }

        .radio-option.selected {
            border-color: #52a9db;
            background: rgba(82, 169, 219, 0.1);
            color: #52a9db;
        }

        /* Champs conditionnels */
        .type-fields {
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.4s ease;
        }

        .type-fields.active {
            opacity: 1;
            max-height: 200px;
        }

        /* Boutons */
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #52a9db 0%, #4a8bc2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(82, 169, 219, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        /* Barre de recherche et filtres */
        .search-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .search-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            align-items: end;
        }

        .search-group {
            position: relative;
        }

        .search-input {
            padding: 0.75rem 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 0.95rem;
            width: 100%;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #52a9db;
        }

        .filter-select {
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            background: white;
            min-width: 150px;
        }

        /* Tableau */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .table-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .table-header h2 {
            color: #52a9db;
            font-size: 1.3rem;
            font-weight: 500;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .badge-etudiant {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-professeur {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        /* Boutons d'actions */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn-edit {
            background: #17a2b8;
            color: white;
        }

        .btn-edit:hover {
            background: #138496;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        /* Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }

        /* États vides */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state h3 {
            margin-bottom: 1rem;
            color: #495057;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .search-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .container {
                padding: 0 1rem;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }
            
            .nav-links {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            table {
                font-size: 0.9rem;
            }
            
            th, td {
                padding: 0.75rem 0.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 0.25rem;
            }
        }

        /* Animation pour les icônes */
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .loading {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>👥Gestion des utilisateurs</h1>
        <div class="nav-links">
            <a href="dashboard.php">⚙️ Dashboard</a>
            <a href="admin_utilisateurs.php" class="active">👥 Utilisateurs</a>
            <a href="livre.php"> 📚 Livres et 📄Fichiers</a>
        </div>
    </div>

    <div class="container">
        <!-- Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php foreach ($erreurs as $erreur): ?>
            <div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div>
        <?php endforeach; ?>

        <!-- Formulaire de modification -->
        <div class="form-container <?= $mode_edition ? 'active' : '' ?>" id="editForm">
            <div class="form-title">
                <?= $mode_edition ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur' ?>
                <button type="button" onclick="fermerFormulaire()" style="float: right; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6c757d;">×</button>
            </div>
            
            <form method="post">
                <input type="hidden" name="action" value="modifier">
                <?php if ($mode_edition): ?>
                    <input type="hidden" name="id" value="<?= $utilisateur_modifie['id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Type d'utilisateur :</label>
                    <div class="radio-group">
                        <div class="radio-option <?= ($utilisateur_modifie['type'] ?? 'etudiant') === 'etudiant' ? 'selected' : '' ?>" data-type="etudiant">
                            <input type="radio" name="type" value="etudiant" id="etudiant" <?= ($utilisateur_modifie['type'] ?? 'etudiant') === 'etudiant' ? 'checked' : '' ?>>
                            <label for="etudiant">Étudiant</label>
                        </div>
                        <div class="radio-option <?= ($utilisateur_modifie['type'] ?? '') === 'professeur' ? 'selected' : '' ?>" data-type="professeur">
                            <input type="radio" name="type" value="professeur" id="professeur" <?= ($utilisateur_modifie['type'] ?? '') === 'professeur' ? 'checked' : '' ?>>
                            <label for="professeur">Professeur</label>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($utilisateur_modifie['nom'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($utilisateur_modifie['prenom'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($utilisateur_modifie['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="text" id="telephone" name="telephone" value="<?= htmlspecialchars($utilisateur_modifie['telephone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe <?= $mode_edition ? '(laisser vide pour ne pas modifier)' : '*' ?></label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" <?= !$mode_edition ? 'required' : '' ?> minlength="6">
                </div>

                <!-- Champs étudiants -->
                <div id="etudiant-fields" class="type-fields <?= ($utilisateur_modifie['type'] ?? 'etudiant') === 'etudiant' ? 'active' : '' ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="specialite">Spécialité *</label>
                            <input type="text" id="specialite" name="specialite" value="<?= htmlspecialchars($utilisateur_modifie['specialite'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="niveau">Niveau *</label>
                            <select id="niveau" name="niveau">
                                <option value="">Sélectionner un niveau</option>
                                <option value="L1" <?= ($utilisateur_modifie['niveau'] ?? '') === 'L1' ? 'selected' : '' ?>>Licence 1</option>
                                <option value="L2" <?= ($utilisateur_modifie['niveau'] ?? '') === 'L2' ? 'selected' : '' ?>>Licence 2</option>
                                <option value="L3" <?= ($utilisateur_modifie['niveau'] ?? '') === 'L3' ? 'selected' : '' ?>>Licence 3</option>
                                <option value="M1" <?= ($utilisateur_modifie['niveau'] ?? '') === 'M1' ? 'selected' : '' ?>>Master 1</option>
                                <option value="M2" <?= ($utilisateur_modifie['niveau'] ?? '') === 'M2' ? 'selected' : '' ?>>Master 2</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Champs professeurs -->
                <div id="professeur-fields" class="type-fields <?= ($utilisateur_modifie['type'] ?? '') === 'professeur' ? 'active' : '' ?>">
                    <div class="form-group">
                        <label for="matiere">Matière principale *</label>
                        <input type="text" id="matiere" name="matiere" value="<?= htmlspecialchars($utilisateur_modifie['matiere'] ?? '') ?>">
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        💾 <?= $mode_edition ? 'Modifier' : 'Ajouter' ?>
                    </button>
                    <button type="button" onclick="fermerFormulaire()" class="btn btn-secondary">
                        ❌ Annuler
                    </button>
                </div>
            </form>
        </div>

        <!-- Barre de recherche et filtres -->
        <div class="search-filters">
            <form method="get" class="search-row">
                <div class="search-group">
                    <label for="recherche">Rechercher un utilisateur</label>
                    <input type="text" id="recherche" name="recherche" class="search-input" 
                           value="<?= htmlspecialchars($recherche) ?>" 
                           placeholder="Nom, prénom, email, spécialité...">
                </div>
                
                <div class="form-group">
                    <label for="type_filtre">Type</label>
                    <select id="type_filtre" name="type_filtre" class="filter-select">
                        <option value="">Tous les types</option>
                        <option value="etudiant" <?= $type_filtre === 'etudiant' ? 'selected' : '' ?>>Étudiants</option>
                        <option value="professeur" <?= $type_filtre === 'professeur' ? 'selected' : '' ?>>Professeurs</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">🔍 Rechercher</button>
            </form>
        </div>

        <!-- Tableau des utilisateurs -->
        <div class="table-container">
            <div class="table-header">
                <h2>Liste des utilisateurs (<?= count($utilisateurs) ?> résultat<?= count($utilisateurs) > 1 ? 's' : '' ?>)</h2>
            </div>
            
            <?php if (empty($utilisateurs)): ?>
                <div class="empty-state">
                    <h3>Aucun utilisateur trouvé</h3>
                    <p>Aucun utilisateur ne correspond à vos critères de recherche.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Spécialité/Matière</th>
                            <th>Niveau</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilisateurs as $utilisateur): ?>
                            <tr>
                                <td><?= $utilisateur['id'] ?></td>
                                <td>
                                    <span class="badge badge-<?= $utilisateur['type'] ?>">
                                        <?= $utilisateur['type'] === 'etudiant' ? '🎓 Étudiant' : '👨‍🏫 Professeur' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($utilisateur['nom']) ?></td>
                                <td><?= htmlspecialchars($utilisateur['prenom']) ?></td>
                                <td><?= htmlspecialchars($utilisateur['email']) ?></td>
                                <td><?= htmlspecialchars($utilisateur['telephone'] ?: '-') ?></td>
                                <td>
                                    <?php if ($utilisateur['type'] === 'etudiant'): ?>
                                        <?= htmlspecialchars($utilisateur['specialite'] ?: '-') ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($utilisateur['matiere'] ?: '-') ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $utilisateur['type'] === 'etudiant' ? htmlspecialchars($utilisateur['niveau'] ?: '-') : '-' ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?modifier=<?= $utilisateur['id'] ?>" class="btn-sm btn-edit">
                                            ✏️ Modifier
                                        </a>
                                        <button onclick="confirmerSuppression(<?= $utilisateur['id'] ?>, '<?= htmlspecialchars($utilisateur['nom'] . ' ' . $utilisateur['prenom']) ?>')" class="btn-sm btn-delete">
                                            🗑️ Supprimer
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 400px; margin: 2rem;">
            <h3 style="margin-bottom: 1rem; color: #dc3545;">Confirmer la suppression</h3>
            <p id="deleteMessage" style="margin-bottom: 1.5rem; color: #6c757d;"></p>
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button onclick="fermerModalSuppression()" class="btn btn-secondary">Annuler</button>
                <form method="post" style="display: inline;" id="deleteForm">
                    <input type="hidden" name="action" value="supprimer">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn btn-delete">Supprimer</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Gestion des radio buttons stylés
        document.querySelectorAll('.radio-option').forEach(option => {
            option.addEventListener('click', function() {
                // Enlever la sélection de tous les options
                document.querySelectorAll('.radio-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Ajouter la sélection à l'option cliquée
                this.classList.add('selected');
                
                // Cocher le radio button correspondant
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Déclencher l'événement change
                radio.dispatchEvent(new Event('change'));
            });
        });

        // Afficher les champs selon le type d'utilisateur
        document.querySelectorAll('input[name="type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const etudiantFields = document.getElementById('etudiant-fields');
                const professeurFields = document.getElementById('professeur-fields');
                
                if (this.value === 'etudiant') {
                    etudiantFields.classList.add('active');
                    professeurFields.classList.remove('active');
                } else {
                    etudiantFields.classList.remove('active');
                    professeurFields.classList.add('active');
                }
            });
        });

        // Fonctions pour la gestion du formulaire
        function fermerFormulaire() {
            document.getElementById('editForm').classList.remove('active');
            // Rediriger vers la page sans paramètre modifier
            window.location.href = 'admin_utilisateurs.php';
        }

        // Fonctions pour la suppression
        function confirmerSuppression(id, nom) {
            document.getElementById('deleteMessage').textContent = `Êtes-vous sûr de vouloir supprimer l'utilisateur "${nom}" ? Cette action est irréversible.`;
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function fermerModalSuppression() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                fermerModalSuppression();
            }
        });

        // Auto-submit du formulaire de recherche
        document.getElementById('recherche').addEventListener('input', function() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });

        document.getElementById('type_filtre').addEventListener('change', function() {
            this.form.submit();
        });

        // Animation des lignes du tableau
        document.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.01)';
                this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.boxShadow = 'none';
            });
        });

        // Validation en temps réel
        document.getElementById('email').addEventListener('input', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(this.value) && this.value.length > 0) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '';
            }
        });

        // Validation du mot de passe
        const motDePasseInput = document.getElementById('mot_de_passe');
        if (motDePasseInput) {
            motDePasseInput.addEventListener('input', function() {
                if (this.value.length < 6 && this.value.length > 0) {
                    this.style.borderColor = '#dc3545';
                } else {
                    this.style.borderColor = '';
                }
            });
        }

        // Animation de chargement pour les boutons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.style.opacity = '0.7';
                    submitBtn.style.pointerEvents = 'none';
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '⏳ Traitement...';
                    
                    // Restaurer le bouton si la page ne se recharge pas
                    setTimeout(() => {
                        submitBtn.style.opacity = '1';
                        submitBtn.style.pointerEvents = 'auto';
                        submitBtn.innerHTML = originalText;
                    }, 3000);
                }
            });
        });

        // Smooth scroll vers le formulaire si mode édition
        <?php if ($mode_edition): ?>
        document.getElementById('editForm').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
        <?php endif; ?>

        // Raccourcis clavier
        document.addEventListener('keydown', function(e) {
            // Échap pour fermer le modal ou le formulaire
            if (e.key === 'Escape') {
                if (document.getElementById('deleteModal').style.display === 'flex') {
                    fermerModalSuppression();
                } else if (document.getElementById('editForm').classList.contains('active')) {
                    fermerFormulaire();
                }
            }
            
            // Ctrl+F pour focus sur la recherche
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('recherche').focus();
            }
        });

        // Mise à jour automatique du compteur de résultats
        function updateResultCount() {
            const visibleRows = document.querySelectorAll('tbody tr').length;
            const headerTitle = document.querySelector('.table-header h2');
            if (headerTitle) {
                headerTitle.textContent = `Liste des utilisateurs (${visibleRows} résultat${visibleRows > 1 ? 's' : ''})`;
            }
        }

        // Highlight des termes de recherche
        function highlightSearchTerms() {
            const searchTerm = document.getElementById('recherche').value.toLowerCase();
            if (searchTerm.length > 0) {
                document.querySelectorAll('tbody td').forEach(cell => {
                    const text = cell.textContent;
                    if (text.toLowerCase().includes(searchTerm)) {
                        const regex = new RegExp(`(${searchTerm})`, 'gi');
                        cell.innerHTML = text.replace(regex, '<mark style="background: #ffeb3b; padding: 0;">$1</mark>');
                    }
                });
            }
        }

        // Appeler le highlight au chargement de la page
        document.addEventListener('DOMContentLoaded', highlightSearchTerms);
    </script>
</body>
</html>