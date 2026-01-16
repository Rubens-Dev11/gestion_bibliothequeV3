<?php
session_start();

// Vérification simple de l'authentification (comme dans votre code original)
if (!isset($_SESSION['utilisateur'])) {
    header('Location: connexion.php');
    exit();
}

try {
    $db = new PDO('mysql:host=localhost;dbname=gestion_bibliotheque;charset=utf8', 'root', '');
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données");
}

$user_id = (int)$_SESSION['utilisateur']['id'];

// Traitement de la suppression de message (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'delete_message' && isset($_POST['message_id'])) {
        $message_id = (int)$_POST['message_id'];
        
        // Vérifier que le message appartient bien à l'utilisateur connecté
        $stmt = $db->prepare("SELECT id FROM messages WHERE id = ? AND destinataire_id = ?");
        $stmt->execute([$message_id, $user_id]);
        
        if ($stmt->fetch()) {
            // Supprimer le message
            $delete_stmt = $db->prepare("DELETE FROM messages WHERE id = ? AND destinataire_id = ?");
            $success = $delete_stmt->execute([$message_id, $user_id]);
            
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Message non trouvé']);
        }
        exit();
    }
    
    if ($_POST['action'] === 'mark_read' && isset($_POST['message_id'])) {
        $message_id = (int)$_POST['message_id'];
        
        // Vérifier que le message appartient bien à l'utilisateur connecté
        $stmt = $db->prepare("SELECT id FROM messages WHERE id = ? AND destinataire_id = ?");
        $stmt->execute([$message_id, $user_id]);
        
        if ($stmt->fetch()) {
            $update_stmt = $db->prepare("UPDATE messages SET lu = TRUE WHERE id = ? AND destinataire_id = ?");
            $success = $update_stmt->execute([$message_id, $user_id]);
            
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Message non trouvé']);
        }
        exit();
    }
}

// Marquer automatiquement tous les messages comme lus lors de la consultation
$db->prepare("UPDATE messages SET lu = TRUE WHERE destinataire_id = ? AND lu = FALSE")
   ->execute([$user_id]);

// Récupérer les messages (expéditeur par défaut : Bibliothèque)
$stmt = $db->prepare("
    SELECT 
        m.id,
        m.contenu,
        m.date_envoi,
        m.lu,
        'Bibliothèque' as expediteur_nom,
        l.titre AS livre_titre
    FROM messages m
    LEFT JOIN reservations r ON m.reservation_id = r.id
    LEFT JOIN livres l ON r.livre_id = l.id
    WHERE m.destinataire_id = ?
    ORDER BY m.date_envoi DESC
");
$stmt->execute([$user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(bin2hex(random_bytes(32))) ?>">
    <title>Messagerie - IUGET Bibliothèque</title>
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

/* Container */
.container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 30px;
}

/* Messages Container */
.messages-container {
    background: var(--bg-white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.messages-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 30px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.messages-header h2 {
    font-size: 1.3rem;
    font-weight: 600;
}

/* État vide */
.empty-state {
    text-align: center;
    padding: 60px 30px;
    color: var(--text-gray);
}

.empty-state i {
    font-size: 4rem;
    color: var(--border-light);
    margin-bottom: 20px;
    display: block;
}

.empty-state h3 {
    font-size: 1.2rem;
    margin-bottom: 10px;
    color: var(--text-dark);
}

/* Messages List */
.messages-list {
    padding: 0;
}

/* Message Item */
.message {
    border-bottom: 1px solid var(--border-light);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.message:last-child {
    border-bottom: none;
}

.message:hover {
    background: rgba(93, 173, 226, 0.02);
}

.message.non-lu {
    background: linear-gradient(90deg, rgba(93, 173, 226, 0.08) 0%, rgba(93, 173, 226, 0.02) 100%);
    border-left: 4px solid var(--primary);
}

.message.non-lu::before {
    content: '';
    position: absolute;
    top: 20px;
    right: 20px;
    width: 10px;
    height: 10px;
    background: var(--primary);
    border-radius: 50%;
    box-shadow: 0 0 0 3px rgba(93, 173, 226, 0.3);
}

.message-content {
    padding: 25px 30px;
}

/* Message Header */
.message-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    gap: 20px;
}

.message-sender {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.sender-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    border: 2px solid rgba(25, 118, 210, 0.3);
}

.sender-info strong {
    color: var(--text-dark);
    font-weight: 600;
    font-size: 1rem;
}

.admin-badge {
    background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
    margin-left: 8px;
}

.message-date {
    color: var(--text-gray);
    font-size: 0.85rem;
    white-space: nowrap;
}

/* Message Book Reference */
.message-livre {
    background: linear-gradient(135deg, rgba(82, 196, 26, 0.1) 0%, rgba(82, 196, 26, 0.05) 100%);
    border: 1px solid rgba(82, 196, 26, 0.2);
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #389e0d;
    font-weight: 500;
    font-size: 0.9rem;
}

.message-livre i {
    color: var(--success);
}

/* Message Content */
.message-contenu {
    color: var(--text-dark);
    line-height: 1.7;
    font-size: 0.95rem;
    padding: 20px;
    background: var(--bg-light);
    border-radius: 8px;
    border-left: 3px solid var(--primary);
    position: relative;
}

.message-contenu::before {
    content: '"';
    position: absolute;
    top: -5px;
    left: 10px;
    font-size: 2rem;
    color: var(--primary);
    opacity: 0.3;
}

/* Message Actions */
.message-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-action {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}

.btn-delete {
    background: var(--danger);
    color: white;
}

.btn-delete:hover {
    background: #d32f2f;
    transform: translateY(-1px);
}

.btn-mark-read {
    background: var(--success);
    color: white;
}

.btn-mark-read:hover {
    background: #389e0d;
    transform: translateY(-1px);
}

/* Stats Bar */
.stats-bar {
    background: var(--bg-white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: 20px 30px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-item {
    text-align: center;
    flex: 1;
}

.stat-item:not(:last-child) {
    border-right: 1px solid var(--border-light);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    display: block;
}

.stat-label {
    font-size: 0.85rem;
    color: var(--text-gray);
    margin-top: 5px;
}

/* Notification */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 1000;
    transform: translateX(400px);
    transition: transform 0.3s ease;
}

.notification.success {
    background: var(--success);
}

.notification.error {
    background: var(--danger);
}

.notification.show {
    transform: translateX(0);
}

/* Loading state */
.message.loading {
    opacity: 0.6;
    pointer-events: none;
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

@media (max-width: 480px) {
    .stats-bar {
        flex-direction: column;
        gap: 15px;
    }
    
    .stat-item:not(:last-child) {
        border-right: none;
        border-bottom: 1px solid var(--border-light);
        padding-bottom: 15px;
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
            <a href="accueil.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Retour à l'accueil
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Stats Bar -->
        <?php 
        $total_messages = count($messages);
        $messages_non_lus = count(array_filter($messages, function($msg) { return !$msg['lu']; }));
        $messages_lus = $total_messages - $messages_non_lus;
        ?>
        
        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-number"><?= $total_messages ?></span>
                <div class="stat-label">Total messages</div>
            </div>
            <div class="stat-item">
                <span class="stat-number" style="color: var(--primary);"><?= $messages_non_lus ?></span>
                <div class="stat-label">Non lus</div>
            </div>
            <div class="stat-item">
                <span class="stat-number" style="color: var(--success);"><?= $messages_lus ?></span>
                <div class="stat-label">Lus</div>
            </div>
        </div>

        <!-- Messages Container -->
        <div class="messages-container">
            <div class="messages-header">
                <i class="fas fa-inbox"></i>
                <h2>Boîte de réception</h2>
                <?php if ($messages_non_lus > 0): ?>
                    <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 15px; font-size: 0.8rem; margin-left: auto;">
                        <?= $messages_non_lus ?> nouveau<?= $messages_non_lus > 1 ? 'x' : '' ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="messages-list">
                <?php if (empty($messages)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Aucun message</h3>
                        <p>Vous n'avez reçu aucun message pour le moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?= !$message['lu'] ? 'non-lu' : '' ?>" id="message-<?= $message['id'] ?>">
                            <div class="message-content">
                                <div class="message-header">
                                    <div class="message-sender">
                                        <div class="sender-avatar">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <div class="sender-info">
                                            <strong>
                                                <?= htmlspecialchars($message['expediteur_nom']) ?>
                                            </strong>
                                            <span class="admin-badge">
                                                <i class="fas fa-shield-alt"></i> Administration
                                            </span>
                                            <?php if (!$message['lu']): ?>
                                                <span style="color: var(--primary); font-size: 0.8rem; font-weight: 500; margin-left: 8px;">
                                                    <i class="fas fa-circle" style="font-size: 0.5rem;"></i> Nouveau
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="message-date">
                                        <i class="fas fa-clock" style="margin-right: 5px; opacity: 0.7;"></i>
                                        <?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?>
                                    </div>
                                </div>

                                <?php if (!empty($message['livre_titre'])): ?>
                                    <div class="message-livre">
                                        <i class="fas fa-book"></i>
                                        À propos du livre: <strong><?= htmlspecialchars($message['livre_titre']) ?></strong>
                                    </div>
                                <?php endif; ?>

                                <div class="message-contenu">
                                    <?= nl2br(htmlspecialchars($message['contenu'])) ?>
                                </div>

                                <!-- Actions -->
                                <div class="message-actions">
                                    <?php if (!$message['lu']): ?>
                                        <button class="btn-action btn-mark-read" onclick="markAsRead(<?= $message['id'] ?>)">
                                            <i class="fas fa-check"></i>
                                            Marquer comme lu
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn-action btn-delete" onclick="deleteMessage(<?= $message['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                        Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Notification -->
    <div id="notification" class="notification"></div>

    <script>
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }
        
        function markAsRead(messageId) {
            const messageElement = document.getElementById('message-' + messageId);
            messageElement.classList.add('loading');
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_read&message_id=${messageId}`
            })
            .then(response => response.json())
            .then(data => {
                messageElement.classList.remove('loading');
                
                if (data.success) {
                    messageElement.classList.remove('non-lu');
                    updateStats();
                    showNotification('Message marqué comme lu');
                    
                    // Supprimer le bouton "Marquer comme lu"
                    const button = messageElement.querySelector('.btn-mark-read');
                    if (button) {
                        button.remove();
                    }
                } else {
                    showNotification('Erreur lors du marquage du message', 'error');
                }
            })
            .catch(error => {
                messageElement.classList.remove('loading');
                showNotification('Erreur de connexion', 'error');
            });
        }
        
        function deleteMessage(messageId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible.')) {
                return;
            }
            
            const messageElement = document.getElementById('message-' + messageId);
            messageElement.classList.add('loading');
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_message&message_id=${messageId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animation de suppression
                    messageElement.style.transform = 'translateX(-100%)';
                    messageElement.style.opacity = '0';
                    
                    setTimeout(() => {
                        messageElement.remove();
                        updateStats();
                        showNotification('Message supprimé avec succès');
                        
                        // Vérifier s'il reste des messages
                        if (document.querySelectorAll('.message').length === 0) {
                            location.reload();
                        }
                    }, 300);
                } else {
                    messageElement.classList.remove('loading');
                    showNotification('Erreur lors de la suppression du message', 'error');
                }
            })
            .catch(error => {
                messageElement.classList.remove('loading');
                showNotification('Erreur de connexion', 'error');
            });
        }
        
        function updateStats() {
            const nonLusCount = document.querySelectorAll('.message.non-lu').length;
            const totalCount = document.querySelectorAll('.message').length;
            const lusCount = totalCount - nonLusCount;
            
            // Mettre à jour les statistiques dans l'interface
            const statsNumbers = document.querySelectorAll('.stat-number');
            if (statsNumbers[0]) statsNumbers[0].textContent = totalCount;
            if (statsNumbers[1]) statsNumbers[1].textContent = nonLusCount;
            if (statsNumbers[2]) statsNumbers[2].textContent = lusCount;
            
            // Mettre à jour le badge dans le header
            const badge = document.querySelector('.messages-header span');
            if (badge) {
                if (nonLusCount === 0) {
                    badge.style.display = 'none';
                } else {
                    badge.textContent = nonLusCount + ' nouveau' + (nonLusCount > 1 ? 'x' : '');
                }
            }
        }
        
        // Animation d'apparition des messages
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -20px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, index * 100);
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.message').forEach(message => {
            message.style.opacity = '0';
            message.style.transform = 'translateY(20px)';
            message.style.transition = 'all 0.6s ease';
            observer.observe(message);
        });
        
        // Animation pour les stats
        document.querySelectorAll('.stat-number').forEach((stat, index) => {
            const finalValue = parseInt(stat.textContent);
            stat.textContent = '0';
            
            setTimeout(() => {
                let current = 0;
                const increment = finalValue / 20;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= finalValue) {
                        current = finalValue;
                        clearInterval(timer);
                    }
                    stat.textContent = Math.floor(current);
                }, 50);
            }, index * 200);
        });
    </script>
</body>
</html>