<?php


ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/notification_config.php';
require_once __DIR__ . '/../classes/WhatsAppSender.php';

$resultat = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numeroTest = trim($_POST['numero_test'] ?? '');
    
    if (!empty($numeroTest)) {
        try {
            $whatsappSender = new WhatsAppSender();
            
            $message = " *TEST RÉUSSI !*\n\n";
            $message .= "Ton système WhatsApp fonctionne !\n\n";
            $message .= " Date : " . date('d/m/Y') . "\n";
            $message .= " Heure : " . date('H:i:s') . "\n\n";
            $message .= "_Message envoyé depuis le Projet Absences_";
            
            $resultat = $whatsappSender->envoyer($numeroTest, $message);
            
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
    <title>Test WhatsApp</title>
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #25D366, #128C7E); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 500px; width: 90%; }
        h1 { color: #25D366; text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
        input[type="tel"] { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; box-sizing: border-box; }
        input[type="tel"]:focus { border-color: #25D366; outline: none; }
        button { width: 100%; padding: 15px; background: #25D366; color: white; border: none; border-radius: 8px; font-size: 18px; cursor: pointer; }
        button:hover { background: #128C7E; }
        .result { padding: 20px; border-radius: 8px; margin-top: 20px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📱 Test WhatsApp</h1>
        
        <div class="info">
            <strong> Important :</strong> Tu peux seulement envoyer à ton numéro qui a envoyé "join" au Sandbox.
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Ton numéro WhatsApp :</label>
                <input type="tel" name="numero_test" placeholder="+212612345678" required>
            </div>
            <button type="submit"> Envoyer le Test</button>
        </form>
        
        <?php if ($resultat): ?>
        <div class="result <?= $resultat['success'] ? 'success' : 'error' ?>">
            <strong><?= $resultat['success'] ? ' Succès !' : ' Erreur' ?></strong><br>
            <?= htmlspecialchars($resultat['message']) ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>