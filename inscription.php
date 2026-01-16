<?php
session_start();
$db = new PDO('mysql:host=localhost;dbname=gestion_bibliotheque;charset=utf8', 'root', '');

$erreurs = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation des données
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
    if (empty($mot_de_passe)) {
        $erreurs[] = "Le mot de passe est obligatoire";
    } elseif (strlen($mot_de_passe) < 6) {
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

    // Vérification email unique
    if (empty($erreurs)) {
        $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erreurs[] = "Cet email est déjà utilisé";
        }
    }

    // Insertion en base si pas d'erreurs
    if (empty($erreurs)) {
        $donnees = [
            'type' => $type,
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'telephone' => $telephone,
            'mot_de_passe' => password_hash($mot_de_passe, PASSWORD_DEFAULT),
            'specialite' => $type === 'etudiant' ? $specialite : null,
            'niveau' => $type === 'etudiant' ? $niveau : null,
            'matiere' => $type === 'professeur' ? $matiere : null
        ];

        $sql = "INSERT INTO utilisateurs (type, nom, prenom, email, telephone, mot_de_passe, specialite, niveau, matiere) 
                VALUES (:type, :nom, :prenom, :email, :telephone, :mot_de_passe, :specialite, :niveau, :matiere)";
        
        try {
            $stmt = $db->prepare($sql);
            if ($stmt->execute($donnees)) {
                $success = "Inscription réussie! Vous pouvez maintenant vous connecter.";
                $_POST = []; // Vider le formulaire
            } else {
                $erreurs[] = "Erreur lors de l'inscription";
            }
        } catch (PDOException $e) {
            $erreurs[] = "Erreur de base de données: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .registration-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 520px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2rem;
            font-weight: 300;
            position: relative;
        }

        h1::after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 15px auto 0;
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        /* Styles pour les radio buttons */
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .radio-option {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 12px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            background: #f8f9fa;
            transition: all 0.3s ease;
            position: relative;
            min-width: 120px;
            justify-content: center;
        }

        .radio-option:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .radio-option input[type="radio"] {
            margin-right: 8px;
            width: auto;
            padding: 0;
            position: absolute;
            opacity: 0;
        }

        .radio-option input[type="radio"]:checked + .radio-label {
            color: #667eea;
            font-weight: 600;
        }

        .radio-option input[type="radio"]:checked {
            opacity: 1;
        }

        .radio-option.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .radio-label {
            color: #555;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        /* Champs conditionnels */
        .type-fields {
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.4s ease;
            transform: translateY(-10px);
        }

        .type-fields.active {
            opacity: 1;
            max-height: 500px;
            transform: translateY(0);
        }

        select {
            cursor: pointer;
        }

        button[type="submit"] {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        button[type="submit"]::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        button[type="submit"]:hover::before {
            left: 100%;
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #fcc;
            font-size: 0.9rem;
            animation: shake 0.5s ease-in-out;
        }

        .success {
            background: #efe;
            color: #3a3;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #cfc;
            font-size: 0.95rem;
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links p {
            margin-bottom: 10px;
            color: #666;
            font-size: 0.95rem;
        }

        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 2px 0;
            border-bottom: 1px solid transparent;
        }

        .links a:hover {
            color: #764ba2;
            border-bottom-color: #764ba2;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .registration-container {
                padding: 30px 20px;
                margin: 10px;
                max-width: 100%;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 10px;
            }
            
            .radio-option {
                min-width: 100%;
            }
            
            input[type="text"],
            input[type="email"],
            input[type="password"],
            select {
                padding: 12px 15px;
            }
            
            button[type="submit"] {
                padding: 12px;
                font-size: 1rem;
            }
        }

        /* État de chargement pour le bouton */
        .loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid transparent;
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Style pour le succès */
        .success-container {
            text-align: center;
            padding: 40px 20px;
        }

        .success-container h1 {
            color: #28a745;
            margin-bottom: 20px;
        }

        .success-container .success {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .success-container a {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .success-container a:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <h1>Inscription</h1>
        
        <?php if ($success): ?>
            <div class="success-container">
                <div class="success"><?= htmlspecialchars($success) ?></div>
                <a href="connexion.php">Se connecter</a>
            </div>
        <?php else: ?>
            <?php foreach ($erreurs as $erreur): ?>
                <div class="error"><?= htmlspecialchars($erreur) ?></div>
            <?php endforeach; ?>
            
            <form method="post" id="registrationForm">
                <div class="form-group">
                    <label>Vous êtes :</label>
                    <div class="radio-group">
                        <div class="radio-option <?= ($_POST['type'] ?? 'etudiant') === 'etudiant' ? 'selected' : '' ?>" data-type="etudiant">
                            <input type="radio" name="type" value="etudiant" id="etudiant" <?= ($_POST['type'] ?? 'etudiant') === 'etudiant' ? 'checked' : '' ?>>
                            <label for="etudiant" class="radio-label">Étudiant</label>
                        </div>
                        <div class="radio-option <?= ($_POST['type'] ?? '') === 'professeur' ? 'selected' : '' ?>" data-type="professeur">
                            <input type="radio" name="type" value="professeur" id="professeur" <?= ($_POST['type'] ?? '') === 'professeur' ? 'checked' : '' ?>>
                            <label for="professeur" class="radio-label">Professeur</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="nom">Nom *</label>
                    <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="prenom">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="text" id="telephone" name="telephone" value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>" placeholder="Ex: +225 01 02 03 04 05">
                </div>
                
                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe * (min. 6 caractères)</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="6">
                </div>
                
                <!-- Champs étudiants -->
                <div id="etudiant-fields" class="type-fields <?= ($_POST['type'] ?? 'etudiant') === 'etudiant' ? 'active' : '' ?>">
                    <div class="form-group">
                        <label for="specialite">Spécialité *</label>
                        <input type="text" id="specialite" name="specialite" value="<?= htmlspecialchars($_POST['specialite'] ?? '') ?>" placeholder="Ex: Informatique, Mathématiques, etc.">
                    </div>
                    
                    <div class="form-group">
                        <label for="niveau">Niveau *</label>
                        <select id="niveau" name="niveau">
                            <option value="">Sélectionner un niveau</option>
                            <option value="L1" <?= (($_POST['niveau'] ?? '') === 'L1') ? 'selected' : '' ?>>Licence 1</option>
                            <option value="L2" <?= (($_POST['niveau'] ?? '') === 'L2') ? 'selected' : '' ?>>Licence 2</option>
                            <option value="L3" <?= (($_POST['niveau'] ?? '') === 'L3') ? 'selected' : '' ?>>Licence 3</option>
                            <option value="M1" <?= (($_POST['niveau'] ?? '') === 'M1') ? 'selected' : '' ?>>Master 1</option>
                            <option value="M2" <?= (($_POST['niveau'] ?? '') === 'M2') ? 'selected' : '' ?>>Master 2</option>
                        </select>
                    </div>
                </div>
                
                <!-- Champs professeurs -->
                <div id="professeur-fields" class="type-fields <?= ($_POST['type'] ?? '') === 'professeur' ? 'active' : '' ?>">
                    <div class="form-group">
                        <label for="matiere">Matière principale *</label>
                        <input type="text" id="matiere" name="matiere" value="<?= htmlspecialchars($_POST['matiere'] ?? '') ?>" placeholder="Ex: Mathématiques, Histoire, etc.">
                    </div>
                </div>
                
                <button type="submit" id="submitBtn">S'inscrire</button>
                
                <div class="links">
                    <p>Déjà inscrit? <a href="connexion.php">Connectez-vous</a></p>
                </div>
            </form>
        <?php endif; ?>
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

        // Animation de chargement lors de la soumission
        document.getElementById('registrationForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
            btn.textContent = '';
        });

        // Animation des champs au focus
        document.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
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
        document.getElementById('mot_de_passe').addEventListener('input', function() {
            if (this.value.length < 6 && this.value.length > 0) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '';
            }
        });
    </script>
</body>
</html>