<?php
// Afficher les erreurs PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'classes/WhatsAppSender.php';

echo "<h1>Test WhatsApp</h1>";

$sender = new WhatsAppSender();

// REMPLACEZ PAR VOTRE VRAI NUMÉRO (celui qui a envoyé "join ..." à Twilio)
$monNumero = "+212771493177"; // <--- Mettez votre numéro ici !

echo "Tentative d'envoi à $monNumero ...<br>";

$resultat = $sender->envoyer($monNumero, "Ceci est un test depuis EduNotify ! 🚀");

if ($resultat['success']) {
    echo "<h2 style='color:green'>SUCCÈS ! ✅</h2>";
    echo "Le message a été envoyé.";
} else {
    echo "<h2 style='color:red'>ÉCHEC ❌</h2>";
    echo "Raison : " . $resultat['message'];
}
?>