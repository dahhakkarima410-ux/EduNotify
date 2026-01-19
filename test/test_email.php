<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/notification_config.php';
require_once __DIR__ . '/../classes/EmailSender.php';

$resultat = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailTest = trim($_POST['email_test'] ?? '');
    
    if (!empty($emailTest)) {
        try {
            $emailSender = new EmailSender();
            
            $sujet = " Test Email - Projet Absences";
            $contenu = "
            <html>
            <body style='font-family: Arial, sans-serif; padding: 20px;'>
                <div style='max-width: 500px; margin: 0 auto; background: #f0f7ff; padding: 30px; border-radius: 10px; border: 2px solid #667eea;'>
                    <h1 style='color: #667eea; text-align: center;'>Test Réussi !</h1>
                    <p style='text-align: center; font-size: 18px;'>Ton système d'envoi d'emails fonctionne !</p>
                    <hr style='border: 1px solid #ddd;'>
                    <p><strong> Serveur :</strong> " . SMTP_HOST . "</p>
                    <p><strong>Date :</strong> " . date('d/m/Y H:i:s') . "</p>
                </div>
            </body>
            </html>";
            
            $resultat = $emailSender->envoyer($emailTest, $sujet, $contenu);
            
        } catch (Exception $e) {
            $resultat = ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Email</title>
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea, #764ba2); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 500px; width: 90%; }
        h1 { color: #667eea; text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
        input[type="email"] { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; box-sizing: border-box; }
        input[type="email"]:focus { border-color: #667eea; outline: none; }
        button { width: 100%; padding: 15px; background: #667eea; color: white; border: none; border-radius: 8px; font-size: 18px; cursor: pointer; }
        button:hover { background: #5a6fd6; }
        .result { padding: 20px; border-radius: 8px; margin-top: 20px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .config { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .config p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Test d'Envoi Email</h1>
        
        <div class="config">
            <p><strong>Serveur :</strong> <?= SMTP_HOST ?></p>
            <p><strong>Email :</strong> <?= SMTP_USERNAME ?></p>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Envoyer un email de test à :</label>
                <input type="email" name="email_test" placeholder="ton.email@exemple.com" required>
            </div>
            <button type="submit">📨 Envoyer le Test</button>
        </form>
        
        <?php if ($resultat): ?>
        <div class="result <?= $resultat['success'] ? 'success' : 'error' ?>">
            <strong><?= $resultat['success'] ? 'Succès !' : ' Erreur' ?></strong><br>
            <?= htmlspecialchars($resultat['message']) ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>