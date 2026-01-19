<?php
// Active les erreurs pour voir le problème
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Charge les fichiers nécessaires
require_once 'vendor/autoload.php';
require_once 'config/notification_config.php';
require_once 'classes/EmailSender.php';

echo "<h2>Test d'envoi d'Email</h2>";
echo "Configuration chargée : " . SMTP_HOST . " (Port: " . SMTP_PORT . ")<br>";

try {
    $sender = new EmailSender();
    
    // On force un test de connexion d'abord
    echo "Test de connexion SMTP... ";
    $connexion = $sender->testerConnexion();
    
    if ($connexion['success']) {
        echo "<span style='color:green'>OK !</span><br>";
        
        // Envoi réel
        echo "Tentative d'envoi d'email... ";
        $resultat = $sender->envoyer(
            SMTP_USERNAME, // On s'envoie l'email à soi-même pour tester
            "Test EduNotify", 
            "<h1>Ceci est un test</h1><p>Si vous lisez ça, le SMTP fonctionne !</p>"
        );
        
        if ($resultat['success']) {
            echo "<span style='color:green'>SUCCÈS ! Vérifiez votre boîte mail.</span>";
        } else {
            echo "<span style='color:red'>ERREUR D'ENVOI : " . $resultat['message'] . "</span>";
        }
    } else {
        echo "<span style='color:red'>ÉCHEC CONNEXION : " . $connexion['message'] . "</span>";
        echo "<br><em>Conseil : Vérifiez que votre pare-feu ou antivirus ne bloque pas le port 587.</em>";
    }

} catch (Exception $e) {
    echo "<span style='color:red'>EXCEPTION CRITIQUE : " . $e->getMessage() . "</span>";
}
?>