<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login_admin.php');
    exit();
}

$db = new PDO('mysql:host=localhost;dbname=gestion_bibliotheque;charset=utf8', 'root', '');

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajouter'])) {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO administrateurs (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)")
           ->execute([$_POST['nom'], $_POST['prenom'], $_POST['email'], $hash]);
    }
    elseif (isset($_POST['supprimer'])) {
        $db->prepare("DELETE FROM administrateurs WHERE id = ?")->execute([$_POST['id']]);
    }
}

$admins = $db->query("SELECT * FROM administrateurs")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestion des Admins</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; }
        .header { background: #333; color: white; padding: 1rem; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.5rem; border: 1px solid #ddd; }
        form { margin-bottom: 2rem; background: #f5f5f5; padding: 1rem; }
    </style>
</head>
<body>
    
    
    <div class="container">
        <h2>Gestion des Administrateurs</h2>
        
        <form method="POST">
            <h3>Ajouter un admin</h3>
            <input type="text" name="nom" placeholder="Nom" required>
            <input type="text" name="prenom" placeholder="Prénom" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit" name="ajouter">Ajouter</button>
        </form>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                <tr>
                    <td><?= $admin['id'] ?></td>
                    <td><?= htmlspecialchars($admin['nom']) ?></td>
                    <td><?= htmlspecialchars($admin['prenom']) ?></td>
                    <td><?= htmlspecialchars($admin['email']) ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                            <button type="submit" name="supprimer">Supprimer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>