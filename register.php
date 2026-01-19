<?php
// register.php - Inscription d'un nouvel administrateur
session_start();
require_once 'config/database.php';
require_once 'classes/Database.php';

$message = "";
$messageType = ""; // success ou error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $secret = $_POST['code_secret']; // Petite sécurité anti-élèves

    // Code secret pour empêcher n'importe qui de s'inscrire (ex: le prof ou toi)
    // Tu peux dire au prof : "Le code secret est 'admin2026'"
    if ($secret !== "admin2026") {
        $message = "Code secret incorrect !";
        $messageType = "error";
    } elseif ($password !== $confirm) {
        $message = "Les mots de passe ne correspondent pas.";
        $messageType = "error";
    } else {
        try {
            $db = new Database();
            $pdo = $db->getPDO();

            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $message = "Cet email est déjà utilisé.";
                $messageType = "error";
            } else {
                // Création du compte
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO utilisateurs (nom, email, password, role) VALUES (?, ?, ?, 'admin')";
                $stmtInsert = $pdo->prepare($sql);
                $stmtInsert->execute([$nom, $email, $hash]);

                $message = "Compte créé avec succès ! <a href='login.php'>Connectez-vous ici</a>";
                $messageType = "success";
            }
        } catch (Exception $e) {
            $message = "Erreur : " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - EduNotify</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0; font-family: 'Outfit', sans-serif;
        }
        .login-card {
            background: white; padding: 40px; border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; max-width: 450px;
        }
        .form-control {
            width: 100%; padding: 12px; margin-bottom: 15px;
            border: 2px solid #e5e7eb; border-radius: 8px; box-sizing: border-box;
        }
        .btn-primary {
            width: 100%; padding: 12px; background: #667eea; color: white;
            border: none; border-radius: 8px; font-weight: 600; cursor: pointer;
        }
        .btn-primary:hover { background: #5a67d8; }
        .alert-error { color: #991b1b; background: #fee2e2; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
        .alert-success { color: #166534; background: #dcfce7; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
        .alert-success a { font-weight: bold; color: #166534; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 style="text-align: center; margin-bottom: 20px; color: #333;">Créer un compte Admin</h2>
        
        <?php if($message): ?>
            <div class="<?= $messageType == 'success' ? 'alert-success' : 'alert-error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if($messageType !== 'success'): ?>
        <form method="POST">
            <input type="text" name="nom" class="form-control" placeholder="Nom complet" required>
            <input type="email" name="email" class="form-control" placeholder="Email" required>
            <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
            <input type="password" name="confirm_password" class="form-control" placeholder="Confirmer mot de passe" required>
            
            <div style="margin-bottom: 15px;">
                <input type="password" name="code_secret" class="form-control" placeholder="Code secret école (admin2026)" required>
                <small style="color: #666; font-size: 0.8em;">Demandez le code au responsable.</small>
            </div>

            <button type="submit" class="btn-primary">S'inscrire</button>
        </form>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 20px;">
            Déjà un compte ? <a href="login.php" style="color: #667eea; text-decoration: none; font-weight: 600;">Se connecter</a>
        </p>
    </div>
</body>
</html>