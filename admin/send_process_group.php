<?php
// admin/send_process_group.php (CORRIGÉ & AMÉLIORÉ)
session_start();
require_once '../config/database.php';
// On inclut la config pour être sûr d'avoir les constantes SMTP et Twilio
require_once '../config/notification_config.php'; 
require_once '../vendor/autoload.php';
require_once '../classes/Database.php';
require_once '../classes/NotificationManager.php';
require_once '../classes/EmailSender.php';
require_once '../classes/WhatsAppSender.php';
require_once '../classes/Logger.php';

$db = new Database();
$pdo = $db->getPDO();
$logger = new Logger($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table = $_POST['table_source'];
    $etudiantId = $_POST['etudiant_id'];
    $nbAbsences = $_POST['nb_absences'];

    // 1. Récupérer les infos de l'étudiant
    $etudiant = $db->fetchOne("SELECT * FROM etudiants WHERE id = ?", [$etudiantId]);

    if ($etudiant) {
        $rapport = [];
        
        // --- Construction du Message ---
        $sujet = "⚠️ ALERTE URGENTE : Cumul d'absences";
        $message = "Bonjour M/Mme {$etudiant['nom_parent']}.\n\n";
        $message .= "Nous attirons votre attention sur le fait que votre enfant {$etudiant['prenom']} {$etudiant['nom']} ";
        $message .= "a accumulé $nbAbsences absences non justifiées.\n";
        $message .= "Merci de contacter l'administration de toute urgence.";

        // --- 2. Envoi EMAIL (Nouveau) ---
        $emailSender = new EmailSender(); // Il va lire les constantes SMTP automatiquement
        if (!empty($etudiant['email_parent'])) {
            $resEmail = $emailSender->envoyer(
                $etudiant['email_parent'], 
                $sujet, 
                nl2br($message), // Convertit les sauts de ligne pour l'HTML
                $message
            );
            $rapport[] = $resEmail['success'] ? "Email OK" : "Email Échec";
        } else {
            $rapport[] = "Pas d'email";
        }

        // --- 3. Envoi WHATSAPP ---
        $whatsappSender = new WhatsAppSender(); // Il va lire les constantes Twilio
        if (!empty($etudiant['telephone_parent'])) {
            $resWA = $whatsappSender->envoyer($etudiant['telephone_parent'], $message);
            $rapport[] = $resWA['success'] ? "WhatsApp OK" : "WhatsApp Échec";
        } else {
            $rapport[] = "Pas de tél";
        }

        // --- 4. Conclusion et Mise à jour ---
        // On considère que c'est un succès si au moins l'un des deux a marché
        $succes = strpos(implode(',', $rapport), 'OK') !== false;

        if ($succes) {
            // Marquer TOUTES les absences de cet étudiant comme "Notifiées"
            $sqlUpdate = "UPDATE `$table` SET notifie = 1 WHERE etudiant_id = :eid AND notifie = 0";
            $stmt = $pdo->prepare($sqlUpdate);
            $stmt->execute([':eid' => $etudiantId]);
            
            $_SESSION['upload_success'] = "Alerte envoyée (" . implode(', ', $rapport) . ")";
            $logger->log('ALERTE', "Alerte cumul ($nbAbsences) pour {$etudiant['nom']} : " . implode(', ', $rapport));
        } else {
            $_SESSION['upload_error'] = "Échec de l'envoi : " . implode(', ', $rapport);
        }
    } else {
        $_SESSION['upload_error'] = "Étudiant introuvable.";
    }
}

// Retour à la page
header('Location: send_notifications.php?mois=' . $table . '&seuil=3');
exit;
?>