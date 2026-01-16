<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login_admin.php');
    exit();
}

$db = new PDO('mysql:host=localhost;dbname=gestion_bibliotheque;charset=utf8', 'root', '');

// Initialiser les variables
$messages = [];
$current_conversation = null;
$conversation_info = null;

// Traitement des actions de suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['vider_reception'])) {
        // Supprimer tous les messages où l'admin est destinataire ou expéditeur
        $stmt = $db->prepare("DELETE FROM messages WHERE expediteur_id = ? OR destinataire_id = ?");
        if ($stmt->execute([$_SESSION['admin']['id'], $_SESSION['admin']['id']])) {
            $message = "Boîte de réception vidée avec succès!";
        } else {
            $message = "Erreur lors de la suppression des messages";
        }
    }
    
    if (isset($_POST['supprimer_conversation']) && isset($_POST['reservation_id'])) {
        // Supprimer seulement les messages de cette conversation
        $stmt = $db->prepare("DELETE FROM messages WHERE reservation_id = ?");
        if ($stmt->execute([$_POST['reservation_id']])) {
            $message = "Conversation supprimée avec succès!";
            $current_conversation = null; // Désélectionner la conversation supprimée
        } else {
            $message = "Erreur lors de la suppression de la conversation";
        }
    }
    
    // Traitement du formulaire de réponse (existant)
    if (isset($_POST['repondre'])) {
        $reservation_id = $_POST['reservation_id'];
        $contenu = trim($_POST['contenu']);
        $statut = $_POST['statut'];
        
        // Mettre à jour le statut de la réservation
        $stmt = $db->prepare("UPDATE reservations SET statut = ? WHERE id = ?");
        $stmt->execute([$statut, $reservation_id]);
        
        // Récupérer l'utilisateur concerné
        $stmt = $db->prepare("SELECT utilisateur_id FROM reservations WHERE id = ?");
        $stmt->execute([$reservation_id]);
        $reservation = $stmt->fetch();
        
        if ($reservation && !empty($contenu)) {
            // Envoyer le message
            $stmt = $db->prepare("INSERT INTO messages (expediteur_id, destinataire_id, reservation_id, contenu) 
                                 VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['admin']['id'],
                $reservation['utilisateur_id'],
                $reservation_id,
                $contenu
            ]);
        }
        
        $message = "Réponse envoyée avec succès!";
        $current_conversation = $reservation_id;
    }
}

// Récupérer la conversation courante depuis GET
if (isset($_GET['conversation'])) {
    $current_conversation = $_GET['conversation'];
}

// Récupérer toutes les conversations (modifié pour exclure celles sans messages)
$stmt = $db->prepare("
    SELECT 
        r.id AS reservation_id,
        u.id AS user_id,
        u.prenom AS user_prenom,
        u.nom AS user_nom,
        l.titre AS livre_titre,
        r.statut AS reservation_statut,
        MAX(m.date_envoi) AS last_message_date,
        COUNT(m.id) AS message_count
    FROM reservations r
    JOIN utilisateurs u ON r.utilisateur_id = u.id
    JOIN livres l ON r.livre_id = l.id
    LEFT JOIN messages m ON m.reservation_id = r.id
    GROUP BY r.id
    HAVING message_count > 0
    ORDER BY last_message_date DESC
");
$stmt->execute();
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les informations de la conversation courante
if ($current_conversation) {
    $stmt = $db->prepare("
        SELECT l.titre, u.prenom, u.nom, r.statut 
        FROM reservations r
        JOIN livres l ON r.livre_id = l.id
        JOIN utilisateurs u ON r.utilisateur_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$current_conversation]);
    $conversation_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$conversation_info) {
        $conversation_info = [
            'titre' => 'Inconnu',
            'prenom' => 'Inconnu',
            'nom' => 'Inconnu',
            'statut' => 'en_attente'
        ];
    }

    // Récupérer les messages de la conversation
    $stmt = $db->prepare("
        SELECT 
            m.*,
            u.prenom,
            u.nom,
            u.type,
            a.prenom AS admin_prenom,
            a.nom AS admin_nom
        FROM messages m
        LEFT JOIN utilisateurs u ON m.expediteur_id = u.id AND u.type != 'admin'
        LEFT JOIN administrateurs a ON m.expediteur_id = a.id
        WHERE m.reservation_id = ?
        ORDER BY m.date_envoi ASC
    ");
    $stmt->execute([$current_conversation]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Marquer comme lus
    $db->prepare("UPDATE messages SET lu = TRUE WHERE reservation_id = ? AND destinataire_id = ?")
       ->execute([$current_conversation, $_SESSION['admin']['id']]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie Admin</title>
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

        /* Header */
.header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 30px 0;
    box-shadow: var(--shadow);
    margin-bottom: 30px;
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header h1 {
    font-size: 2rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 15px;
}

.back-link {
    color: white;
    text-decoration: none;
    padding: 10px 20px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    backdrop-filter: blur(10px);
}

.back-link:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f5f5f5;
        color: #333;
    }

    .container {
        display: flex;
        min-height: 100vh;
    }

    .sidebar {
        width: 300px;
        background-color: #fff;
        border-right: 1px solid #ddd;
        padding: 20px;
        box-sizing: border-box;
    }

    .sidebar h2 {
        font-size: 18px;
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .conversation-item {
        padding: 12px 10px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .conversation-item:hover {
        background-color: #f9f9f9;
    }

    .conversation-item.active {
        background-color: #e6f2ff;
        border-left: 3px solid #4a90e2;
    }

    .conversation-item small {
        color: #777;
        font-size: 12px;
        display: block;
        margin-top: 5px;
    }

    .status-badge {
        font-size: 12px;
        padding: 2px 6px;
        border-radius: 3px;
        margin-left: 8px;
    }

    .status-en_attente {
        background-color: #fff3cd;
        color: #856404;
    }

    .status-approuve {
        background-color: #d4edda;
        color: #155724;
    }

    .status-refuse {
        background-color: #f8d7da;
        color: #721c24;
    }

    .conversation {
        flex: 1;
        padding: 20px;
        background-color: #fff;
    }

    .conversation h2 {
        margin-top: 0;
        font-size: 20px;
    }

    .conversation h3 {
        font-size: 16px;
        color: #555;
        margin-top: 5px;
    }

    .messages-list {
        max-height: 500px;
        overflow-y: auto;
        margin: 20px 0;
        border: 1px solid #eee;
        padding: 15px;
        border-radius: 5px;
        background-color: #fafafa;
    }

    .message {
        margin-bottom: 15px;
        padding: 10px;
        border-radius: 5px;
        background-color: #fff;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .message-admin {
        border-left: 3px solid #4a90e2;
    }

    .message-user {
        border-left: 3px solid #34a853;
    }

    .message-header {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        color: #666;
        margin-bottom: 5px;
    }

    .message-content {
        line-height: 1.5;
    }

    .message-form {
        margin-top: 30px;
    }

    .message-form textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        min-height: 100px;
        margin-bottom: 10px;
        box-sizing: border-box;
    }

    .message-form button {
        background-color: #4a90e2;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        cursor: pointer;
    }

    .message-form button:hover {
        background-color: #3a7bc8;
    }

    .action-buttons {
        margin: 15px 0;
    }

    .action-buttons button {
        background-color: #f44336;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    .action-buttons button:hover {
        background-color: #d32f2f;
    }

    .action-buttons button i {
        margin-right: 5px;
    }

    .nav-item {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: #4a90e2;
        color: white;
        padding: 10px 15px;
        border-radius: 4px;
        text-decoration: none;
        display: flex;
        align-items: center;
    }

    .nav-item i {
        margin-right: 8px;
    }

    .nav-item:hover {
        background-color: #3a7bc8;
    }

    /* Style pour les cases à cocher */
    [type="checkbox"] {
        margin-right: 8px;
    }

    /* Style pour les messages non lus */
    .unread {
        font-weight: bold;
    }

    /* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 0 20px;
    }
    
    .header-content {
        padding: 0 20px;
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .header h1 {
        font-size: 1.5rem;
    }
    
    .message-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .message-content {
        padding: 20px 20px;
    }
    
    .stats-bar {
        padding: 15px 20px;
    }
    
    .stat-item {
        padding: 0 10px;
    }
    
    .message-actions {
        justify-content: center;
    }
}

</style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <h1>
                <i class="fas fa-envelope"></i>
                Messagerie
            </h1>
            <a href="admin_dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Retour au tableau de bord
            </a>
        </div>
    </div>
    <div class="container">
        <!-- Sidebar avec la liste des conversations -->
        <div class="sidebar">
            <h2>Demandes de réservation</h2>
            
            <div class="action-buttons">
                <form method="post" onsubmit="return confirm('Vider toute votre boîte de réception?')">
                    <button type="submit" name="vider_reception" class="vider">
                        <i class="fas fa-trash-alt"></i> Vider la réception
                    </button>
                </form>
            </div>
            
            <?php if (empty($conversations)): ?>
                <p>Aucune conversation</p>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                    <div class="conversation-item <?= $current_conversation == $conv['reservation_id'] ? 'active' : '' ?>"
                         onclick="window.location.href='?conversation=<?= $conv['reservation_id'] ?>'">
                        <div>
                            <strong><?= htmlspecialchars($conv['user_prenom'] . ' ' . $conv['user_nom']) ?></strong>
                            <span class="status-badge status-<?= $conv['reservation_statut'] ?>">
                                <?= 
                                    $conv['reservation_statut'] === 'en_attente' ? 'En attente' : 
                                    ($conv['reservation_statut'] === 'approuve' ? 'Approuvé' : 'Refusé')
                                ?>
                            </span>
                        </div>
                        <div><?= htmlspecialchars($conv['livre_titre']) ?></div>
                        <?php if ($conv['last_message_date']): ?>
                            <small><?= date('d/m/Y H:i', strtotime($conv['last_message_date'])) ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Zone de conversation -->
        <div class="conversation">
            <?php if ($current_conversation && $conversation_info): ?>
                <h2>Conversation avec <?= htmlspecialchars($conversation_info['prenom'] . ' ' . $conversation_info['nom']) ?></h2>
                <h3>Livre: <?= htmlspecialchars($conversation_info['titre']) ?></h3>
                
                <?php if (isset($message)): ?>
                    <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <form method="post" onsubmit="return confirm('Supprimer définitivement cette conversation?')">
                        <input type="hidden" name="reservation_id" value="<?= $current_conversation ?>">
                        <button type="submit" name="supprimer_conversation">
                            <i class="fas fa-trash-alt"></i> Supprimer cette conversation
                        </button>
                    </form>
                </div>
                
                <div class="messages-list">
                    <?php if (empty($messages)): ?>
                        <p>Aucun message dans cette conversation</p>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <?php 
                                $is_admin = !empty($msg['admin_prenom']);
                                $expediteur_nom = $is_admin ? 
                                    $msg['admin_prenom'] . ' ' . $msg['admin_nom'] : 
                                    $msg['prenom'] . ' ' . $msg['nom'];
                            ?>
                            <div class="message <?= $is_admin ? 'message-admin' : 'message-user' ?>">
                                <div class="message-header">
                                    <span>
                                        <?= $is_admin ? 'Vous (' . htmlspecialchars($expediteur_nom) . ')' : htmlspecialchars($expediteur_nom) ?>
                                    </span>
                                    <span><?= date('d/m/Y H:i', strtotime($msg['date_envoi'])) ?></span>
                                </div>
                                <div class="message-content">
                                    <?= nl2br(htmlspecialchars($msg['contenu'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Formulaire de réponse -->
                <div class="message-form">
                    <h3>Répondre</h3>
                    <form method="post">
                        <input type="hidden" name="reservation_id" value="<?= $current_conversation ?>">
                        
                        <div>
                            <label><strong>Statut de la réservation:</strong></label><br>
                            <label>
                                <input type="radio" name="statut" value="en_attente" 
                                    <?= $conversation_info['statut'] === 'en_attente' ? 'checked' : '' ?>> En attente
                            </label>
                            <label>
                                <input type="radio" name="statut" value="approuve"
                                    <?= $conversation_info['statut'] === 'approuve' ? 'checked' : '' ?>> Approuver
                            </label>
                            <label>
                                <input type="radio" name="statut" value="refuse"
                                    <?= $conversation_info['statut'] === 'refuse' ? 'checked' : '' ?>> Refuser
                            </label>
                        </div>
                        
                        <textarea name="contenu" placeholder="Votre réponse..." required></textarea>
                        <button type="submit" name="repondre">Envoyer</button>
                    </form>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px;">
                    <h3>Sélectionnez une conversation pour voir les messages</h3>
                    <p>Choisissez une demande de réservation dans la liste de gauche pour commencer la conversation.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <a href="admin_dashboard.php" class="nav-item">
        <i class="fas fa-cog"></i>
        <span>Retour au dashboard</span>
    </a>
    
    <script>
        // Faire disparaître les messages flash après 5 secondes
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.querySelectorAll('.message-flash');
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