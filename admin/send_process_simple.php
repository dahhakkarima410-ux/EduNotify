<?php
// admin/send_process_simple.php
session_start();

// 1. Charger toutes les dépendances
require_once '../config/database.php';
require_once '../config/notification_config.php';
require_once '../vendor/autoload.php';
require_once '../classes/Database.php';
require_once '../classes/NotificationManager.php';
require_once '../classes/EmailSender.php';
require_once '../classes/WhatsAppSender.php';
require_once '../classes/TemplateManager.php';

// 2. Initialiser le Gestionnaire
$db = new Database();
$manager = new NotificationManager($db);

// 3. Vérifier si on a reçu les données du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $table = $_POST['table_source'] ?? '';
    $absenceId = $_POST['absence_id'] ?? '';

    if (!empty($table) && !empty($absenceId)) {
        
        // --- ACTION : Envoyer la notification ---
        // 'both' signifie qu'on essaie d'envoyer par WhatsApp ET Email
        $resultat = $manager->envoyerNotification($absenceId, $table, 'both');

        // --- RÉSULTAT ---
        if ($resultat['success']) {
            $_SESSION['upload_success'] = "✅ Notification envoyée avec succès !";
            require_once '../classes/Logger.php';
            $logger = new Logger($db);
    // On peut récupérer le nom de l'étudiant via une petite requête si on veut être précis, 
    // ou juste logger l'ID de l'absence.
            $logger->log('ENVOI', "Notification individuelle envoyée (Absence ID: $absenceId) via canaux : WhatsApp/Email");
        } else {
            $_SESSION['upload_error'] = "❌ Erreur lors de l'envoi : " . ($resultat['message'] ?? 'Erreur inconnue');
        }

    } else {
        $_SESSION['upload_error'] = "❌ Erreur : Données manquantes (ID absence ou Table).";
    }

    // 4. Redirection vers la page d'envoi
    // On garde le mois sélectionné dans l'URL pour ne pas perdre le fil
    header("Location: send_notifications.php?mois=" . urlencode($table));
    exit;

} else {
    // Si quelqu'un essaie d'ouvrir ce fichier directement sans formulaire
    header("Location: send_notifications.php");
    exit;
}
?>