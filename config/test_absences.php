<?php


ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/notification_config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/EmailSender.php';
require_once __DIR__ . '/../classes/WhatsAppSender.php';
require_once __DIR__ . '/../classes/TemplateManager.php';
require_once __DIR__ . '/../classes/NotificationManager.php';

$db = new Database();
$resultat = null;
$message = '';


$sql = "SELECT a.*, e.nom, e.prenom, e.classe, e.email_parent, e.telephone_parent, e.nom_parent
        FROM absences_01_2026 a
        JOIN etudiants e ON a.etudiant_id = e.id
        WHERE a.notifie = 0
        ORDER BY a.date_absence DESC";
$absences = $db->query($sql);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $absenceId = $_POST['absence_id'] ?? 0;
    $typeNotif = $_POST['type_notif'] ?? 'email';
    
    if ($absenceId > 0) {
        try {
            $notificationManager = new NotificationManager($db);
            $resultat = $notificationManager->envoyerNotification($absenceId, 'absences_01_2026', $typeNotif);
            
            if ($resultat['success']) {
                $message = " Notification envoyée avec succès !";
            } else {
                $message = " Erreur : " . json_encode($resultat['resultats']);
            }
            
           
            $absences = $db->query($sql);
            
        } catch (Exception $e) {
            $message = " Erreur : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Notifications Absences</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea, #764ba2); min-height: 100vh; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .content { padding: 30px; }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        tr:hover { background: #f5f5f5; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin: 2px; }
        .btn-email { background: #667eea; color: white; }
        .btn-whatsapp { background: #25D366; color: white; }
        .btn-both { background: #ff9800; color: white; }
        .btn:hover { opacity: 0.8; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-sent { background: #d4edda; color: #155724; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-card h3 { font-size: 2em; color: #667eea; }
        .stat-card p { color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Gestion des Notifications d'Absences</h1>
            <p>Envoyer des notifications aux parents - Janvier 2026</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
            <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
                <?= $message ?>
            </div>
            <?php endif; ?>
            
            <div class="stats">
                <div class="stat-card">
                    <h3><?= count($absences) ?></h3>
                    <p>Absences non notifiées</p>
                </div>
                <div class="stat-card">
                    <h3>📧</h3>
                    <p>Email prêt</p>
                </div>
                <div class="stat-card">
                    <h3>📱</h3>
                    <p>WhatsApp prêt</p>
                </div>
            </div>
            
            <?php if (empty($absences)): ?>
            <p style="text-align: center; padding: 40px; color: #666;">
                 Toutes les absences ont été notifiées !
            </p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Classe</th>
                        <th>Date</th>
                        <th>Horaire</th>
                        <th>Matière</th>
                        <th>Parent</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($absences as $absence): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($absence['prenom'] . ' ' . $absence['nom']) ?></strong></td>
                        <td><?= htmlspecialchars($absence['classe']) ?></td>
                        <td><?= date('d/m/Y', strtotime($absence['date_absence'])) ?></td>
                        <td><?= substr($absence['heure_debut'], 0, 5) ?> - <?= substr($absence['heure_fin'], 0, 5) ?></td>
                        <td><?= htmlspecialchars($absence['matiere']) ?></td>
                        <td>
                            📧 <?= htmlspecialchars($absence['email_parent']) ?><br>
                            📱 <?= htmlspecialchars($absence['telephone_parent']) ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="absence_id" value="<?= $absence['id'] ?>">
                                <input type="hidden" name="type_notif" value="email">
                                <button type="submit" class="btn btn-email">📧 Email</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="absence_id" value="<?= $absence['id'] ?>">
                                <input type="hidden" name="type_notif" value="whatsapp">
                                <button type="submit" class="btn btn-whatsapp">📱 WhatsApp</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="absence_id" value="<?= $absence['id'] ?>">
                                <input type="hidden" name="type_notif" value="both">
                                <button type="submit" class="btn btn-both">📧📱 Les deux</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>