<?php
session_start();
$db = new PDO('mysql:host=localhost;dbname=gestion_bibliotheque;charset=utf8', 'root', '');

$etape = $_GET['etape'] ?? 'demande';
$user_type = $_GET['type'] ?? 'user'; // 'admin' ou 'user'
$message = '';
$erreur = '';

// Déterminer la table et la page de redirection selon le type d'utilisateur
$table = ($user_type === 'admin') ? 'administrateurs' : 'utilisateurs';
$login_page = ($user_type === 'admin') ? 'login_admin.php' : 'connexion.php';
$page_title = ($user_type === 'admin') ? 'Administrateur' : 'Utilisateur';

// Traitement du formulaire de vérification d'email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $etape === 'demande') {
    $email = trim($_POST['email']);
    
    $stmt = $db->prepare("SELECT * FROM $table WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Stocker l'ID de l'utilisateur en session pour la prochaine étape
        $_SESSION['reset_user_id'] = $user['id'];
        $_SESSION['reset_user_email'] = $email;
        $_SESSION['reset_user_type'] = $user_type;
        
        // Rediriger vers l'étape de réinitialisation
        header("Location: reset_password.php?etape=reinitialisation&type=$user_type");
        exit;
    } else {
        $erreur = "Aucun compte trouvé avec cette adresse email";
    }
}

// Traitement de la réinitialisation du mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $etape === 'reinitialisation') {
    // Vérifier que l'utilisateur a bien passé par l'étape de vérification d'email
    if (!isset($_SESSION['reset_user_id']) || $_SESSION['reset_user_type'] !== $user_type) {
        $erreur = "Session expirée. Veuillez recommencer la procédure.";
        $etape = 'demande';
    } else {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($password !== $confirm_password) {
            $erreur = "Les mots de passe ne correspondent pas";
        } elseif (strlen($password) < 8) {
            $erreur = "Le mot de passe doit contenir au moins 8 caractères";
        } else {
            // Mettre à jour le mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE $table SET mot_de_passe = ? WHERE id = ?");
            
            if ($stmt->execute([$hashed_password, $_SESSION['reset_user_id']])) {
                // Nettoyer la session
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_user_email']);
                unset($_SESSION['reset_user_type']);
                
                $message = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
                $etape = 'confirmation';
            } else {
                $erreur = "Erreur lors de la mise à jour du mot de passe. Veuillez réessayer.";
            }
        }
    }
}

// Vérification de la session pour l'étape de réinitialisation
if ($etape === 'reinitialisation') {
    if (!isset($_SESSION['reset_user_id']) || $_SESSION['reset_user_type'] !== $user_type) {
        $erreur = "Session expirée ou invalide. Veuillez recommencer la procédure.";
        $etape = 'demande';
        // Nettoyer la session au cas où
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['reset_user_email']);
        unset($_SESSION['reset_user_type']);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe - <?= $page_title ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .user-type-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            gap: 1rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .step.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        
        .step.inactive {
            background: #f8f9fa;
            color: #6c757d;
        }
        
        .step.completed {
            background: linear-gradient(45deg, #56ab2f, #a8e6cf);
            color: white;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.7rem;
            color: #2c3e50;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        input {
            width: 100%;
            padding: 0.9rem;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }
        
        button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .message {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
        }
        
        .success {
            background: linear-gradient(45deg, #56ab2f, #a8e6cf);
            color: white;
            border-left: 4px solid #27ae60;
        }
        
        .error {
            background: linear-gradient(45deg, #ff6b6b, #ffa8a8);
            color: white;
            border-left: 4px solid #e74c3c;
        }
        
        .info-text {
            color: #7f8c8d;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .links {
            text-align: center;
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem;
            border-radius: 8px;
        }
        
        .links a:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #764ba2;
        }
        
        .type-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            justify-content: center;
        }
        
        .type-btn {
            padding: 0.6rem 1.2rem;
            border: 2px solid #e1e8ed;
            background: white;
            border-radius: 20px;
            text-decoration: none;
            color: #7f8c8d;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .type-btn.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
        }
        
        .type-btn:hover:not(.active) {
            border-color: #667eea;
            color: #667eea;
        }
        
        .password-requirements {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .password-requirements ul {
            margin: 0.5rem 0 0 1rem;
        }
        
        .user-info {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #2196f3;
        }
        
        .user-info strong {
            color: #1565c0;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 1.5rem;
                margin: 10px;
            }
            
            .type-selector {
                flex-direction: column;
                align-items: center;
            }
            
            .type-btn {
                width: 100%;
                max-width: 200px;
                text-align: center;
            }
            
            .step-indicator {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Réinitialisation du mot de passe</h1>
            <span class="user-type-badge"><?= $page_title ?></span>
        </div>

        <!-- Indicateur d'étapes -->
        <div class="step-indicator">
            <div class="step <?= $etape === 'demande' ? 'active' : ($etape === 'reinitialisation' || $etape === 'confirmation' ? 'completed' : 'inactive') ?>">
                <span>1️⃣</span>
                <span>Vérification email</span>
            </div>
            <div class="step <?= $etape === 'reinitialisation' ? 'active' : ($etape === 'confirmation' ? 'completed' : 'inactive') ?>">
                <span>2️⃣</span>
                <span>Nouveau mot de passe</span>
            </div>
        </div>

        <?php if ($etape === 'demande'): ?>
            
            <!-- Sélecteur de type d'utilisateur -->
            <div class="type-selector">
                <a href="?type=user&etape=demande" class="type-btn <?= $user_type === 'user' ? 'active' : '' ?>">
                    👥 Utilisateur
                </a>
                <a href="?type=admin&etape=demande" class="type-btn <?= $user_type === 'admin' ? 'active' : '' ?>">
                    👨‍💼 Administrateur
                </a>
            </div>
            
            <?php if ($erreur): ?>
                <div class="error message">
                    <strong>⚠️ Erreur :</strong> <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>
            
            <p class="info-text">
                <strong>Étape 1 :</strong> Entrez votre adresse email pour vérifier votre identité. Vous pourrez ensuite créer un nouveau mot de passe.
            </p>
            
            <form method="post">
                <div class="form-group">
                    <label for="email">
                        📧 Adresse email
                    </label>
                    <input type="email" id="email" name="email" required 
                           placeholder="votre.email@exemple.com"
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
                <button type="submit">
                    ✅ Vérifier et continuer
                </button>
            </form>
            
            <div class="links">
                <a href="<?= $login_page ?>">← Retour à la connexion</a>
                <?php if ($user_type === 'user'): ?>
                    <a href="register.php">Pas encore de compte ? S'inscrire</a>
                <?php endif; ?>
            </div>
            
        <?php elseif ($etape === 'reinitialisation'): ?>
            
            <?php if (isset($_SESSION['reset_user_email'])): ?>
                <div class="user-info">
                    <strong>📧 Compte vérifié :</strong> <?= htmlspecialchars($_SESSION['reset_user_email']) ?>
                </div>
            <?php endif; ?>
            
            <h2 style="color: #2c3e50; margin-bottom: 1.5rem; text-align: center;">
                🔐 Créer un nouveau mot de passe
            </h2>
            
            <?php if ($erreur): ?>
                <div class="error message">
                    <strong>⚠️ Erreur :</strong> <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>
            
            <p class="info-text">
                <strong>Étape 2 :</strong> Choisissez un nouveau mot de passe sécurisé pour votre compte.
            </p>
            
            <form method="post">
                <div class="form-group">
                    <label for="password">
                        🔑 Nouveau mot de passe
                    </label>
                    <input type="password" id="password" name="password" required 
                           minlength="8" placeholder="Minimum 8 caractères">
                    <div class="password-requirements">
                        <strong>Exigences du mot de passe :</strong>
                        <ul>
                            <li>Au moins 8 caractères</li>
                            <li>Recommandé : mélange de lettres, chiffres et symboles</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        🔑 Confirmer le mot de passe
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           required minlength="8" placeholder="Retapez le mot de passe">
                </div>
                
                <button type="submit">
                    ✅ Créer le nouveau mot de passe
                </button>
            </form>
            
            <div class="links">
                <a href="?etape=demande&type=<?= $user_type ?>">← Recommencer avec un autre email</a>
            </div>
            
        <?php elseif ($etape === 'confirmation'): ?>
            
            <div style="text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🎉</div>
                <h2 style="color: #27ae60; margin-bottom: 1.5rem;">
                    Mot de passe mis à jour !
                </h2>
            </div>
            
            <div class="success message">
                <strong>✅ Succès :</strong> <?= htmlspecialchars($message) ?>
            </div>
            
            <div class="info-text">
                Parfait ! Votre mot de passe a été mis à jour avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.
            </div>
            
            <div class="links">
                <a href="<?= $login_page ?>" style="background: linear-gradient(45deg, #667eea, #764ba2); 
                   color: white; padding: 1rem 2rem; border-radius: 10px; font-weight: 600; text-decoration: none;">
                    🚀 Se connecter maintenant
                </a>
            </div>
            
        <?php endif; ?>
    </div>

    <script>
        // Validation en temps réel des mots de passe
        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirm_password');
            
            if (passwordField && confirmPasswordField) {
                function validatePasswords() {
                    const password = passwordField.value;
                    const confirmPassword = confirmPasswordField.value;
                    
                    // Vérifier la correspondance des mots de passe
                    if (confirmPassword && password !== confirmPassword) {
                        confirmPasswordField.setCustomValidity('Les mots de passe ne correspondent pas');
                        confirmPasswordField.style.borderColor = '#e74c3c';
                    } else {
                        confirmPasswordField.setCustomValidity('');
                        confirmPasswordField.style.borderColor = '#e1e8ed';
                    }
                    
                    // Vérifier la longueur du mot de passe
                    if (password && password.length < 8) {
                        passwordField.style.borderColor = '#e74c3c';
                    } else if (password && password.length >= 8) {
                        passwordField.style.borderColor = '#27ae60';
                    }
                }
                
                passwordField.addEventListener('input', validatePasswords);
                confirmPasswordField.addEventListener('input', validatePasswords);
            }
            
            // Animation d'entrée
            const container = document.querySelector('.container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.5s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>