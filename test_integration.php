<?php
// test_integration.php
require_once 'config/database.php';
require_once 'config/notification_config.php';
require_once 'vendor/autoload.php'; // Charge PHPMailer
require_once 'classes/Database.php';
require_once 'classes/EmailSender.php';
require_once 'classes/WhatsAppSender.php';
require_once 'classes/TemplateManager.php';
require_once 'classes/NotificationManager.php';

echo "<h1>🚀 Test d'Intégration : CSV vers Notification</h1>";

// 1. Connexion
$db = new Database();
$notifManager = new NotificationManager($db);

// 2. On cherche la dernière absence importée (Adapte le mois selon ton CSV !)
// ATTENTION : Change 'absences_1_2025' par le nom de la table créée par ton import (regarde dans PhpMyAdmin)
$nom_table_mois = 'absences_01_2026'; // <--- VERIFIE CE NOM DANS PHPMYADMIN

try {
    // On prend une absence qui n'a pas encore été notifiée
    $sql = "SELECT id FROM $nom_table_mois WHERE notifie = 0 LIMIT 1";
    $result = $db->query($sql);

    if (empty($result)) {
        die("❌ Aucune absence non notifiée trouvée dans la table '$nom_table_mois'. Faites un import CSV d'abord !");
    }

    $id_absence = $result[0]['id'];
    echo "<p>✅ Absence trouvée (ID: $id_absence). Tentative d'envoi...</p>";

    // 3. Envoi de la notification (Email + WhatsApp)
    // Cela va utiliser les infos (Email/Tel) importées par TON CSV dans la base
    $resultat = $notifManager->envoyerNotification($id_absence, $nom_table_mois, 'both');

    echo "<pre>";
    print_r($resultat);
    echo "</pre>";

    if ($resultat['success']) {
        echo "<h2 style='color:green'>SUCCÈS TOTAL ! 🎉</h2>";
        echo "<p>Le système a lu l'absence importée et a envoyé l'alerte.</p>";
    } else {
        echo "<h2 style='color:red'>Échec de l'envoi</h2>";
    }

} catch (Exception $e) {
    echo "Erreur SQL (La table existe-t-elle ?) : " . $e->getMessage();
}
?>