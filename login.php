<?php
// login.php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/UserManager.php';
require_once __DIR__ . '/classes/Logger.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $userMgr = new UserManager($db);
    $logger = new Logger($db);
    
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($userMgr->login($email, $password)) {
        $logger->log('CONNEXION', "Connexion réussie : " . $email);
        header('Location: admin/index.php'); // Redirection vers le dossier admin
        exit;
    } else {
        $message = "Identifiants incorrects";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - EduNotify</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex; align-items: center; justify-content: center; margin: 0;
            font-family: 'Outfit', sans-serif;
        }
        .login-card {
            background: white; padding: 40px; border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; max-width: 400px; text-align: center;
        }
        .login-logo {
            width: 80px; height: 80px; background: #f3f4f6; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px; font-size: 30px; color: #667eea;
        }
        .form-control {
            width: 100%; padding: 12px 15px; margin-bottom: 20px;
            border: 2px solid #e5e7eb; border-radius: 10px; font-size: 16px;
            box-sizing: border-box; transition: 0.3s;
        }
        .form-control:focus { border-color: #667eea; outline: none; }
        .btn-login {
            width: 100%; padding: 12px; background: #667eea; color: white;
            border: none; border-radius: 10px; font-size: 16px; font-weight: 600;
            cursor: pointer; transition: 0.3s;
        }
        .btn-login:hover { background: #5a67d8; transform: translateY(-2px); }
        .error-msg { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9em; }
        
        /* Lien S'inscrire */
        .register-link { margin-top: 20px; font-size: 0.9em; color: #6b7280; }
        .register-link a { color: #667eea; text-decoration: none; font-weight: 600; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo"><i class="fas fa-graduation-cap"></i></div>
        <h2 style="margin-bottom: 10px; color: #1f2937;">Bienvenue</h2>
        <p style="color: #6b7280; margin-bottom: 30px;">Connectez-vous à EduNotify</p>
        
        <?php if($message): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?= $message ?></div>
        <?php endif; ?>

        <form method="POST">
            <div style="text-align: left; margin-bottom: 5px; color: #4b5563; font-size: 0.9em; font-weight: 500;">Email</div>
            <input type="email" name="email" class="form-control" placeholder="admin@ecole.com" required>
            
            <div style="text-align: left; margin-bottom: 5px; color: #4b5563; font-size: 0.9em; font-weight: 500;">Mot de passe</div>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            
            <button type="submit" class="btn-login">Se connecter <i class="fas fa-arrow-right" style="margin-left: 5px;"></i></button>
        </form>
        
        <div class="register-link">
            Pas encore de compte ? <a href="register.php">S'inscrire</a>
        </div>
        
        <p style="margin-top: 25px; font-size: 0.85em; color: #9ca3af;">Projet EIDIA 2025</p>
    </div>
</body>
</html>