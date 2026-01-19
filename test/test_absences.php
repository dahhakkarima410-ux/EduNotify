<?php
/**
 * UEMF - Gestion des Notifications d'Absences
 * Université Euromed de Fès
 */

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
$message = '';
$messageType = '';

// Statistiques
$totalAbsences = $db->query("SELECT COUNT(*) as total FROM absences_01_2026")[0]['total'];
$nonNotifiees = $db->query("SELECT COUNT(*) as total FROM absences_01_2026 WHERE notifie = 0")[0]['total'];
$notifiees = $db->query("SELECT COUNT(*) as total FROM absences_01_2026 WHERE notifie = 1")[0]['total'];
$totalEtudiants = $db->query("SELECT COUNT(*) as total FROM etudiants")[0]['total'];

// Récupérer les absences
$sql = "SELECT a.*, e.nom, e.prenom, e.classe, e.email_parent, e.telephone_parent, e.nom_parent
        FROM absences_01_2026 a
        JOIN etudiants e ON a.etudiant_id = e.id
        WHERE a.notifie = 0
        ORDER BY a.date_absence DESC";
$absences = $db->query($sql);

// Traitement envoi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $absenceId = $_POST['absence_id'] ?? 0;
    $typeNotif = $_POST['type_notif'] ?? 'email';
    
    if ($absenceId > 0) {
        try {
            $notificationManager = new NotificationManager($db);
            $resultat = $notificationManager->envoyerNotification($absenceId, 'absences_01_2026', $typeNotif);
            
            if ($resultat['success']) {
                $message = "Notification envoyée avec succès !";
                $messageType = 'success';
            } else {
                $message = "Erreur lors de l'envoi";
                $messageType = 'error';
            }
            
            $absences = $db->query($sql);
            $nonNotifiees = $db->query("SELECT COUNT(*) as total FROM absences_01_2026 WHERE notifie = 0")[0]['total'];
            $notifiees = $db->query("SELECT COUNT(*) as total FROM absences_01_2026 WHERE notifie = 1")[0]['total'];
            
        } catch (Exception $e) {
            $message = "Erreur : " . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UEMF - Gestion des Absences</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --uemf-blue: #1a4d8c;
            --uemf-blue-dark: #0d3a6e;
            --uemf-green: #4cb050;
            --uemf-green-light: #6abf6e;
            --white: #ffffff;
            --gray-light: #f5f7fa;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--uemf-blue-dark) 0%, var(--uemf-blue) 100%);
            min-height: 100vh;
            color: var(--text-dark);
        }
        
        /* Header */
        .header {
            background: var(--white);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-icon {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .logo-icon .leaf {
            font-size: 2rem;
        }
        
        .logo-icon .leaf-green { color: var(--uemf-green); }
        .logo-icon .leaf-blue { color: var(--uemf-blue); }
        
        .logo-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--uemf-blue);
            letter-spacing: 2px;
        }
        
        .logo-text p {
            font-size: 0.65rem;
            color: var(--uemf-blue);
            letter-spacing: 1px;
        }
        
        .header-nav {
            display: flex;
            gap: 15px;
        }
        
        .nav-btn {
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-btn-green {
            background: var(--uemf-green);
            color: var(--white);
            border: none;
        }
        
        .nav-btn-outline {
            background: transparent;
            color: var(--uemf-blue);
            border: 2px solid var(--uemf-blue);
        }
        
        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        /* Main Content */
        .main-content {
            padding: 40px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Page Title */
        .page-title {
            text-align: center;
            margin-bottom: 40px;
            color: var(--white);
        }
        
        .page-title h2 {
            font-size: 2.2rem;
            font-weight: 300;
            margin-bottom: 5px;
        }
        
        .page-title h2 span {
            color: var(--uemf-green);
            font-weight: 600;
        }
        
        .page-title p {
            opacity: 0.8;
            font-size: 1rem;
        }
        
        /* Alert */
        .alert {
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(76, 176, 80, 0.2);
            border: 1px solid var(--uemf-green);
            color: var(--white);
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: var(--white);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.2);
        }
        
        .stat-card .icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.5rem;
        }
        
        .stat-card .value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
        }
        
        /* Table Container */
        .table-container {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .table-header {
            background: var(--gray-light);
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .table-header h3 {
            font-size: 1.2rem;
            color: var(--uemf-blue);
            font-weight: 600;
        }
        
        .table-actions {
            display: flex;
            gap: 10px;
        }
        
        .filter-btn {
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid var(--uemf-blue);
            background: transparent;
            color: var(--uemf-blue);
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: var(--uemf-blue);
            color: var(--white);
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 18px 25px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-light);
            background: var(--gray-light);
            border-bottom: 2px solid #e0e0e0;
        }
        
        td {
            padding: 20px 25px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.95rem;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        /* Student Info */
        .student-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .student-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--uemf-blue), var(--uemf-green));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
            font-size: 1rem;
        }
        
        .student-name {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .student-class {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        /* Module Badge */
        .module-badge {
            display: inline-block;
            padding: 8px 15px;
            background: rgba(26, 77, 140, 0.1);
            color: var(--uemf-blue);
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        /* Contact Info */
        .contact-info {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        .contact-info div {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn {
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-email {
            background: rgba(26, 77, 140, 0.1);
            color: var(--uemf-blue);
        }
        
        .btn-email:hover {
            background: var(--uemf-blue);
            color: var(--white);
        }
        
        .btn-whatsapp {
            background: rgba(76, 176, 80, 0.1);
            color: var(--uemf-green);
        }
        
        .btn-whatsapp:hover {
            background: var(--uemf-green);
            color: var(--white);
        }
        
        .btn-both {
            background: rgba(241, 196, 15, 0.2);
            color: #d4a106;
        }
        
        .btn-both:hover {
            background: #f1c40f;
            color: var(--text-dark);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }
        
        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: var(--uemf-green);
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 30px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                padding: 15px 20px;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">
            <div class="logo-icon">
                <span class="leaf leaf-green">🌿</span>
            </div>
            <div class="logo-text">
                <h1>UEMF</h1>
                <p>UNIVERSITÉ EUROMED DE FÈS</p>
            </div>
        </div>
        <nav class="header-nav">
            <a href="#" class="nav-btn nav-btn-green">
                📊 Dashboard
            </a>
            <a href="#" class="nav-btn nav-btn-outline">
                ⚙️ Paramètres
            </a>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Title -->
        <div class="page-title">
            <h2>Gestion des <span>Absences</span></h2>
            <p>Système de notification automatique aux parents • Janvier 2026</p>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $messageType === 'success' ? '✅' : '❌' ?>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">👥</div>
                <div class="value"><?= $totalEtudiants ?></div>
                <div class="label">Étudiants</div>
            </div>
            <div class="stat-card">
                <div class="icon">📋</div>
                <div class="value"><?= $totalAbsences ?></div>
                <div class="label">Total Absences</div>
            </div>
            <div class="stat-card">
                <div class="icon">⏳</div>
                <div class="value"><?= $nonNotifiees ?></div>
                <div class="label">Non Notifiées</div>
            </div>
            <div class="stat-card">
                <div class="icon">✅</div>
                <div class="value"><?= $notifiees ?></div>
                <div class="label">Notifiées</div>
            </div>
        </div>
        
        <!-- Table -->
        <div class="table-container">
            <div class="table-header">
                <h3>📚 Absences en attente de notification</h3>
                <div class="table-actions">
                    <button class="filter-btn active">Toutes</button>
                    <button class="filter-btn">Aujourd'hui</button>
                    <button class="filter-btn">Cette semaine</button>
                </div>
            </div>
            
            <?php if (empty($absences)): ?>
            <div class="empty-state">
                <div class="icon">🎉</div>
                <h3>Toutes les absences ont été notifiées !</h3>
                <p>Aucune notification en attente pour le moment.</p>
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Date</th>
                        <th>Horaire</th>
                        <th>Module</th>
                        <th>Contact Parent</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($absences as $absence): ?>
                    <tr>
                        <td>
                            <div class="student-info">
                                <div class="student-avatar">
                                    <?= strtoupper(substr($absence['prenom'], 0, 1) . substr($absence['nom'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="student-name"><?= htmlspecialchars($absence['prenom'] . ' ' . $absence['nom']) ?></div>
                                    <div class="student-class"><?= htmlspecialchars($absence['classe']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong><?= date('d/m/Y', strtotime($absence['date_absence'])) ?></strong>
                        </td>
                        <td>
                            <?= substr($absence['heure_debut'], 0, 5) ?> - <?= substr($absence['heure_fin'], 0, 5) ?>
                        </td>
                        <td>
                            <span class="module-badge"><?= htmlspecialchars($absence['matiere']) ?></span>
                        </td>
                        <td>
                            <div class="contact-info">
                                <div>📧 <?= htmlspecialchars($absence['email_parent']) ?></div>
                                <div>📱 <?= htmlspecialchars($absence['telephone_parent']) ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
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
                                    <button type="submit" class="btn btn-both">📨 Les deux</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <p>© 2026 UEMF - Université Euromed de Fès • Système de Gestion des Absences</p>
    </footer>
</body>
</html>


