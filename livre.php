<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login_admin.php');
    exit();
}

// Connexion à la base de données
$db = new PDO('mysql:host=localhost;dbname=gestion_bibliotheque;charset=utf8', 'root', '');

// MODEL
function getLivres($db) {
    $stmt = $db->query("SELECT * FROM livres ORDER BY date_ajout DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ajouterLivre($db, $data) {
    $stmt = $db->prepare("INSERT INTO livres (type, categorie, titre, auteur, isbn, couverture, fichier) 
                         VALUES (:type, :categorie, :titre, :auteur, :isbn, :couverture, :fichier)");
    return $stmt->execute($data);
}

function supprimerLivre($db, $id) {
    // D'abord, supprimer les réservations associées à ce livre
    $db->beginTransaction();
    try {
        // Supprimer les réservations
        $stmt = $db->prepare("DELETE FROM reservations WHERE livre_id = ?");
        $stmt->execute([$id]);
        
        // Ensuite supprimer le livre
        $stmt = $db->prepare("DELETE FROM livres WHERE id = ?");
        $stmt->execute([$id]);
        
        $db->commit();
        return true;
    } catch (PDOException $e) {
        $db->rollBack();
        return false;
    }
}

function getLivreById($db, $id) {
    $stmt = $db->prepare("SELECT * FROM livres WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function modifierLivre($db, $data) {
    $stmt = $db->prepare("UPDATE livres SET 
                         type = :type, 
                         categorie = :categorie, 
                         titre = :titre, 
                         auteur = :auteur, 
                         isbn = :isbn, 
                         couverture = :couverture, 
                         fichier = :fichier 
                         WHERE id = :id");
    return $stmt->execute($data);
}

// CONTROLLER
$action = $_GET['action'] ?? 'liste';
$message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajouter'])) {
        $data = [
            ':type' => $_POST['type'],
            ':categorie' => $_POST['categorie'],
            ':titre' => $_POST['titre'],
            ':auteur' => $_POST['auteur'],
            ':isbn' => $_POST['isbn'],
            ':couverture' => '',
            ':fichier' => ''
        ];
        
        // Gestion de l'upload de la couverture
        if (!empty($_FILES['couverture']['name'])) {
            $uploadDir = 'uploads/couvertures/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = uniqid() . '_' . basename($_FILES['couverture']['name']);
            move_uploaded_file($_FILES['couverture']['tmp_name'], $uploadDir . $fileName);
            $data[':couverture'] = $uploadDir . $fileName;
        }
        
        // Gestion de l'upload du fichier (pour livres numériques)
        if ($_POST['type'] === 'numerique' && !empty($_FILES['fichier']['name'])) {
            $uploadDir = 'uploads/fichiers/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = uniqid() . '_' . basename($_FILES['fichier']['name']);
            move_uploaded_file($_FILES['fichier']['tmp_name'], $uploadDir . $fileName);
            $data[':fichier'] = $uploadDir . $fileName;
        }
        
        if (ajouterLivre($db, $data)) {
            $message = "Livre ajouté avec succès!";
        } else {
            $message = "Erreur lors de l'ajout du livre";
        }
    }
    
    if (isset($_POST['modifier'])) {
        $data = [
            ':id' => $_POST['id'],
            ':type' => $_POST['type'],
            ':categorie' => $_POST['categorie'],
            ':titre' => $_POST['titre'],
            ':auteur' => $_POST['auteur'],
            ':isbn' => $_POST['isbn'],
            ':couverture' => $_POST['couverture_actuelle'],
            ':fichier' => $_POST['fichier_actuel']
        ];
        
        // Gestion de l'upload de la nouvelle couverture
        if (!empty($_FILES['couverture']['name'])) {
            $uploadDir = 'uploads/couvertures/';
            $fileName = uniqid() . '_' . basename($_FILES['couverture']['name']);
            move_uploaded_file($_FILES['couverture']['tmp_name'], $uploadDir . $fileName);
            $data[':couverture'] = $uploadDir . $fileName;
            
            // Supprimer l'ancienne couverture si elle existe
            if (!empty($_POST['couverture_actuelle']) && file_exists($_POST['couverture_actuelle'])) {
                unlink($_POST['couverture_actuelle']);
            }
        }
        
        // Gestion de l'upload du nouveau fichier
        if ($_POST['type'] === 'numerique' && !empty($_FILES['fichier']['name'])) {
            $uploadDir = 'uploads/fichiers/';
            $fileName = uniqid() . '_' . basename($_FILES['fichier']['name']);
            move_uploaded_file($_FILES['fichier']['tmp_name'], $uploadDir . $fileName);
            $data[':fichier'] = $uploadDir . $fileName;
            
            // Supprimer l'ancien fichier s'il existe
            if (!empty($_POST['fichier_actuel']) && file_exists($_POST['fichier_actuel'])) {
                unlink($_POST['fichier_actuel']);
            }
        }
        
        if (modifierLivre($db, $data)) {
            $message = "Livre modifié avec succès!";
        } else {
            $message = "Erreur lors de la modification du livre";
        }
    }
}

// Suppression d'un livre
if (isset($_GET['supprimer'])) {
    // Vérifier s'il y a des réservations actives pour ce livre
    $stmt = $db->prepare("SELECT COUNT(*) FROM reservations WHERE livre_id = ? AND statut = 'active'");
    $stmt->execute([$_GET['supprimer']]);
    $reservationsActives = $stmt->fetchColumn();
    
    if ($reservationsActives > 0) {
        // Annuler automatiquement les réservations actives
        $stmt = $db->prepare("UPDATE reservations SET statut = 'annulee' WHERE livre_id = ? AND statut = 'active'");
        $stmt->execute([$_GET['supprimer']]);
    }
    
    if (supprimerLivre($db, $_GET['supprimer'])) {
        $message = "Livre supprimé avec succès! Les réservations associées ont été automatiquement annulées.";
    } else {
        $message = "Erreur lors de la suppression du livre";
    }
}

// VIEW
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des livres</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #3498db;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
        }

        .header {
            background-color: var(--secondary-color);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header h1 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
        }

        .header h1 i {
            margin-right: 10px;
        }

        .nav-menu {
            display: flex;
            list-style: none;
        }

        .nav-item {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            margin-left: 0.5rem;
            border-radius: 4px;
            display: flex;
            align-items: center;
            transition: background-color 0.3s;
        }

        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-item i {
            margin-right: 8px;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            font-weight: 500;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-section, .list-section {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        h2 {
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary-color);
        }

        input[type="text"],
        input[type="file"],
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="file"]:focus,
        select:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
            margin-right: 1rem;
        }

        button:hover {
            background-color: var(--secondary-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: var(--light-color);
            color: var(--secondary-color);
            font-weight: 600;
        }

        tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .livre-img {
            max-width: 60px;
            max-height: 60px;
            border-radius: 4px;
        }

        .actions a {
            color: var(--primary-color);
            text-decoration: none;
            margin-right: 1rem;
            transition: color 0.3s;
        }

        .actions a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .actions a:last-child {
            color: var(--danger-color);
        }

        .actions a:last-child:hover {
            color: #c82333;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .nav-menu {
                margin-top: 1rem;
                width: 100%;
                flex-wrap: wrap;
            }

            .nav-item {
                margin: 0.25rem;
                padding: 0.5rem;
                font-size: 0.9rem;
            }

            table {
                display: block;
                overflow-x: auto;
            }

            .form-section, .list-section {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 0.5rem;
            }

            button {
                width: 100%;
                margin-bottom: 1rem;
            }

            .actions {
                display: flex;
                flex-direction: column;
            }

            .actions a {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><!-- <i class="fas fa-book"></i>--> 📚Gestion des livres et 📄Fichiers</h1> 
        <div class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin_utilisateurs.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Utilisateurs</span>
            </a>
            <a href="livre.php" class="nav-item">
                <i class="fas fa-book-open"></i>
                <span>Livres PDF</span>
            </a>
            <a href="admin_utilisateurs.php" class="nav-item">
                <i class="fas fa-graduation-cap"></i>
                <span>Étudiants</span>
            </a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'Erreur') !== false ? 'error' : 'success' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <div class="form-section">
            <h2><?= ($action === 'modifier' && isset($_GET['id'])) ? 'Modifier un livre' : 'Ajouter un livre' ?></h2>
            
            <?php if ($action === 'modifier' && isset($_GET['id'])): 
                $livre = getLivreById($db, $_GET['id']);
            ?>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $livre['id'] ?>">
                    <input type="hidden" name="couverture_actuelle" value="<?= $livre['couverture'] ?>">
                    <input type="hidden" name="fichier_actuel" value="<?= $livre['fichier'] ?>">
                    
                    <div class="form-group">
                        <label for="type">Type de livre</label>
                        <select name="type" id="type" required>
                            <option value="physique" <?= $livre['type'] === 'physique' ? 'selected' : '' ?>>Physique</option>
                            <option value="numerique" <?= $livre['type'] === 'numerique' ? 'selected' : '' ?>>Numérique</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="categorie">Catégorie</label>
                        <input type="text" name="categorie" id="categorie" value="<?= htmlspecialchars($livre['categorie']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="titre">Titre</label>
                        <input type="text" name="titre" id="titre" value="<?= htmlspecialchars($livre['titre']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="auteur">Auteur</label>
                        <input type="text" name="auteur" id="auteur" value="<?= htmlspecialchars($livre['auteur']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="isbn">ISBN</label>
                        <input type="text" name="isbn" id="isbn" value="<?= htmlspecialchars($livre['isbn']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="couverture">Image de couverture (facultative)</label>
                        <input type="file" name="couverture" id="couverture">
                        <?php if (!empty($livre['couverture'])): ?>
                            <p>Image actuelle: <img src="<?= $livre['couverture'] ?>" alt="Couverture" class="livre-img"></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($livre['type'] === 'numerique'): ?>
                        <div class="form-group" id="fichier-group">
                            <label for="fichier">Fichier (PDF ou TXT)</label>
                            <input type="file" name="fichier" id="fichier" accept=".pdf,.txt">
                            <?php if (!empty($livre['fichier'])): ?>
                                <p>Fichier actuel: <?= basename($livre['fichier']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" name="modifier"><i class="fas fa-save"></i> Modifier</button>
                    <a href="livre.php"><button type="button"><i class="fas fa-arrow-left"></i> Retour à l'ajout</button></a>
                </form>
            <?php else: ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="type">Type de livre</label>
                        <select name="type" id="type" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="physique">Physique</option>
                            <option value="numerique">Numérique</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="categorie">Catégorie</label>
                        <input type="text" name="categorie" id="categorie" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="titre">Titre</label>
                        <input type="text" name="titre" id="titre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="auteur">Auteur</label>
                        <input type="text" name="auteur" id="auteur" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="isbn">ISBN</label>
                        <input type="text" name="isbn" id="isbn" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="couverture">Image de couverture (facultative)</label>
                        <input type="file" name="couverture" id="couverture">
                    </div>
                    
                    <div class="form-group" id="fichier-group" style="display: none;">
                        <label for="fichier">Fichier (PDF ou TXT)</label>
                        <input type="file" name="fichier" id="fichier" accept=".pdf,.txt">
                    </div>
                    
                    <button type="submit" name="ajouter"><i class="fas fa-plus"></i> Ajouter</button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="list-section">
            <h2>Liste des livres</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Catégorie</th>
                        <th>Titre</th>
                        <th>Auteur</th>
                        <th>ISBN</th>
                        <th>Couverture</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (getLivres($db) as $livre): ?>
                        <tr>
                            <td><?= $livre['id'] ?></td>
                            <td><?= ucfirst($livre['type']) ?></td>
                            <td><?= htmlspecialchars($livre['categorie']) ?></td>
                            <td><?= htmlspecialchars($livre['titre']) ?></td>
                            <td><?= htmlspecialchars($livre['auteur']) ?></td>
                            <td><?= htmlspecialchars($livre['isbn']) ?></td>
                            <td>
                                <?php if (!empty($livre['couverture'])): ?>
                                    <img src="<?= $livre['couverture'] ?>" alt="Couverture" class="livre-img">
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="livre.php?action=modifier&id=<?= $livre['id'] ?>"><i class="fas fa-edit"></i> Modifier</a>
                                <a href="livre.php?supprimer=<?= $livre['id'] ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce livre? Les réservations associées seront automatiquement annulées.')"><i class="fas fa-trash-alt"></i> Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Afficher/masquer le champ fichier selon le type sélectionné
        document.getElementById('type').addEventListener('change', function() {
            const fichierGroup = document.getElementById('fichier-group');
            if (this.value === 'numerique') {
                fichierGroup.style.display = 'block';
                document.getElementById('fichier').setAttribute('required', '');
            } else {
                fichierGroup.style.display = 'none';
                document.getElementById('fichier').removeAttribute('required');
            }
        });

        // Nouveau code pour faire disparaître les messages flash
    document.addEventListener('DOMContentLoaded', function() {
        const messages = document.querySelectorAll('.message');
        messages.forEach(message => {
            setTimeout(() => {
                message.style.transition = 'opacity 0.5s ease';
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            }, 5000);
        });
    });

    </script>
</body>
</html>