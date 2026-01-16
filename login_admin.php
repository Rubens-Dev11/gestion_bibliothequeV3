<?php
session_start();

// Configuration de la base de données
$db_host = 'localhost';
$db_name = 'gestion_bibliotheque';
$db_user = 'root';
$db_pass = '';

// Connexion à la base de données
try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Vérifier si l'admin est déjà connecté
if (isset($_SESSION['admin'])) {
    header('Location: admin_dashboard.php');
    exit();
}

$error = '';
$email = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validation des entrées
    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs";
    } else {
        try {
            // Recherche de l'administrateur
            $stmt = $db->prepare("SELECT * FROM administrateurs WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vérification du mot de passe
            if ($admin && password_verify($password, $admin['mot_de_passe'])) {
    $_SESSION['admin'] = [
        'id' => $admin['id'],
        'nom' => $admin['nom'],
        'prenom' => $admin['prenom'],
        'email' => $admin['email']
    ];
                
                // Régénération de l'ID de session pour prévenir les attaques de fixation
                session_regenerate_id(true);
                
                // Enregistrement du login
                $db->prepare("UPDATE administrateurs SET derniere_connexion = NOW() WHERE id = ?")
                   ->execute([$admin['id']]);
                
                // Redirection vers le tableau de bord
                header('Location: admin_dashboard.php');
                exit();
            } else {
                $error = "Identifiants incorrects";
                // Délai anti-bruteforce
                sleep(1);
            }
        } catch(PDOException $e) {
            $error = "Une erreur est survenue. Veuillez réessayer plus tard.";
            error_log("Login admin error: " . $e->getMessage());
        }
    }
}

// Structure HTML
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administrateur</title>
    <style>
        body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    margin: 0;
    padding: 20px;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    overflow-x: hidden;
}

body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 80%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
    pointer-events: none;
}

.login-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 3rem;
    border-radius: 20px;
    box-shadow: 
        0 25px 50px rgba(0, 0, 0, 0.15),
        0 0 0 1px rgba(255, 255, 255, 0.05);
    width: 100%;
    max-width: 450px;
    position: relative;
    transform: translateY(0);
    transition: all 0.3s ease;
    animation: slideUp 0.6s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.login-container:hover {
    transform: translateY(-5px);
    box-shadow: 
        0 35px 70px rgba(0, 0, 0, 0.2),
        0 0 0 1px rgba(255, 255, 255, 0.1);
}

.login-header {
    text-align: center;
    margin-bottom: 2.5rem;
    position: relative;
}

.login-header::after {
    content: '';
    position: absolute;
    bottom: -15px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, #3498db, #2ecc71);
    border-radius: 2px;
}

.login-header h1 {
    color: #2c3e50;
    margin-bottom: 0.5rem;
    font-size: 2rem;
    font-weight: 600;
    background: linear-gradient(135deg, #2c3e50, #3498db);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: none;
}

.login-header p {
    color: #7f8c8d;
    font-size: 1rem;
    margin: 0;
    opacity: 0.8;
}

.login-header img {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, #3498db, #2ecc71);
    padding: 15px;
    box-shadow: 0 10px 30px rgba(52, 152, 219, 0.3);
    transition: all 0.3s ease;
}

.login-header img:hover {
    transform: scale(1.05);
    box-shadow: 0 15px 40px rgba(52, 152, 219, 0.4);
}

.form-group {
    margin-bottom: 1.8rem;
    position: relative;
}

.form-group label {
    display: block;
    margin-bottom: 0.7rem;
    color: #2c3e50;
    font-weight: 600;
    font-size: 0.95rem;
    transition: color 0.3s ease;
}

.form-group input {
    width: 100%;
    padding: 1rem 1.2rem;
    border: 2px solid rgba(52, 152, 219, 0.2);
    border-radius: 12px;
    font-size: 1rem;
    box-sizing: border-box;
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    position: relative;
}

.form-group input:focus {
    outline: none;
    border-color: #3498db;
    background: rgba(255, 255, 255, 0.95);
    transform: translateY(-2px);
    box-shadow: 
        0 10px 25px rgba(52, 152, 219, 0.15),
        0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-group input:focus + label,
.form-group:focus-within label {
    color: #3498db;
}

.error-message {
    color: #e74c3c;
    background: rgba(231, 76, 60, 0.1);
    border: 1px solid rgba(231, 76, 60, 0.2);
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    text-align: center;
    font-weight: 500;
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.btn-login {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #3498db, #2ecc71);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-login::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn-login:hover::before {
    left: 100%;
}

.btn-login:hover {
    background: linear-gradient(135deg, #2980b9, #27ae60);
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(52, 152, 219, 0.3);
}

.btn-login:active {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(52, 152, 219, 0.3);
}

.password-toggle {
    display: flex;
    align-items: center;
    margin-top: 0.8rem;
    gap: 0.5rem;
}

.password-toggle input[type="checkbox"] {
    width: auto;
    margin: 0;
    transform: scale(1.2);
    accent-color: #3498db;
    cursor: pointer;
}

.password-toggle label {
    margin: 0;
    font-size: 0.9rem;
    color: #7f8c8d;
    cursor: pointer;
    transition: color 0.3s ease;
}

.password-toggle label:hover {
    color: #3498db;
}

.forgot-password {
    text-align: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(52, 152, 219, 0.1);
}

.forgot-password a {
    color: #3498db;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
}

.forgot-password a::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, #3498db, #2ecc71);
    transition: width 0.3s ease;
}

.forgot-password a:hover::after {
    width: 100%;
}

.forgot-password a:hover {
    color: #2980b9;
    transform: translateY(-1px);
}

/* Animations des éléments */
.form-group {
    opacity: 0;
    animation: fadeInUp 0.6s ease-out forwards;
}

.form-group:nth-child(1) { animation-delay: 0.1s; }
.form-group:nth-child(2) { animation-delay: 0.2s; }
.btn-login { 
    opacity: 0;
    animation: fadeInUp 0.6s ease-out 0.3s forwards;
}
.forgot-password {
    opacity: 0;
    animation: fadeInUp 0.6s ease-out 0.4s forwards;
}

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

/* Responsive design */
@media (max-width: 480px) {
    body {
        padding: 15px;
    }
    
    .login-container {
        margin: 0;
        padding: 2rem 1.5rem;
        border-radius: 15px;
    }
    
    .login-header h1 {
        font-size: 1.7rem;
    }
    
    .login-header img {
        width: 75px;
        height: 75px;
    }
    
    .form-group input {
        padding: 0.9rem 1rem;
        font-size: 0.95rem;
    }
    
    .btn-login {
        padding: 0.9rem;
        font-size: 1rem;
    }
}

@media (max-width: 320px) {
    .login-container {
        padding: 1.5rem 1rem;
    }
    
    .login-header h1 {
        font-size: 1.5rem;
    }
}
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="assets/admin-icon.png" alt="Admin Icon">
            <h1>Connexion Admin</h1>
            <p>Accès réservé aux administrateurs</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
                <div class="password-toggle">
                    <input type="checkbox" id="show-password" onclick="
                        const password = document.getElementById('password');
                        password.type = this.checked ? 'text' : 'password';
                    ">
                    <label for="show-password">Afficher le mot de passe</label>
                </div>
            </div>
            
            <button type="submit" class="btn-login">Se connecter</button>
            
            <div class="forgot-password">
                <a href="reset_password.php">Mot de passe oublié ?</a>
            </div>
        </form>
    </div>

    <script>
        // Focus sur le champ email si vide, sinon sur le mot de passe
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email');
            if (emailField.value === '') {
                emailField.focus();
            } else {
                document.getElementById('password').focus();
            }
        });
    </script>
</body>
</html>