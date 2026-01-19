<?php
// admin/settings.php

// 1. SÉCURITÉ : On appelle le gardien (qui gère session_start et la redirection)
require_once 'auth.php'; 

// 2. Inclusions
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/Logger.php';


$db = new Database();
$pdo = $db->getPDO();
$logger = new Logger($db);
$message = "";

// --- TRAITEMENT DES FORMULAIRES ---

// 1. Changement de Config (Seuil)
if (isset($_POST['update_config'])) {
    $seuil = $_POST['seuil_alerte'];
    $stmt = $pdo->prepare("UPDATE configurations SET valeur = ? WHERE cle = 'seuil_alerte'");
    $stmt->execute([$seuil]);
    
    $logger->log('CONFIG', "Modification du seuil d'alerte à : $seuil");
    $message = "<div class='alert-success'>✅ Configuration mise à jour !</div>";
}

// 2. Changement Mot de Passe
if (isset($_POST['update_password'])) {
    $new_pass = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if ($new_pass === $confirm) {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE utilisateurs SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $_SESSION['user_id']]);
        
        $logger->log('SECURITE', "Changement du mot de passe administrateur");
        $message = "<div class='alert-success'>✅ Mot de passe modifié avec succès !</div>";
    } else {
        $message = "<div class='alert-danger'>❌ Les mots de passe ne correspondent pas.</div>";
    }
}

// --- RÉCUPÉRATION DES DONNÉES ---
// Config actuelle
$config = [];
try {
    // On gère le cas où la table n'existerait pas encore (pour éviter une erreur fatale)
    $stmt = $pdo->query("SHOW TABLES LIKE 'configurations'");
    if ($stmt->rowCount() > 0) {
        $req = $pdo->query("SELECT * FROM configurations");
        while ($row = $req->fetch()) { $config[$row['cle']] = $row['valeur']; }
    }
} catch (Exception $e) {
    // Rien à faire, config restera vide
}

// Logs (Les 50 derniers)
$logs = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'logs'");
    if ($stmt->rowCount() > 0) {
        $logs = $pdo->query("SELECT * FROM logs ORDER BY date_creation DESC LIMIT 50")->fetchAll();
    }
} catch (Exception $e) {
    // Rien à faire
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Configuration - EduNotify</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .log-table { font-size: 0.85em; width: 100%; border-collapse: collapse; }
        .log-table th { background: #f1f5f9; text-align: left; padding: 10px; }
        .log-table td { padding: 8px; border-bottom: 1px solid #eee; }
        .badge-log { padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 0.8em; }
        .log-IMPORT { background: #dbeafe; color: #1e40af; }
        .log-ENVOI { background: #dcfce7; color: #166534; }
        .log-SECURITE { background: #fee2e2; color: #991b1b; }
        .log-CONNEXION { background: #e0e7ff; color: #3730a3; }
        .alert-success { background:#dcfce7; color:#166534; padding:15px; border-radius:8px; margin-bottom:20px; }
        .alert-danger { background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; margin-bottom:20px; }
    </style>
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="logo-area">
                <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
                <span>EduNotify</span>
            </div>
            <nav class="nav-links">
                <a href="index.php" class="nav-item"><i class="fas fa-home"></i> <span>Dashboard</span></a>
                <a href="import_csv.php" class="nav-item"><i class="fas fa-cloud-upload-alt"></i> <span>Import CSV</span></a>
                <a href="dashboard_notifications.php" class="nav-item"><i class="fas fa-list"></i> <span>Liste Absences</span></a>
                <a href="send_notifications.php" class="nav-item"><i class="fas fa-paper-plane"></i> <span>Envois</span></a>
                <a href="settings.php" class="nav-item active"><i class="fas fa-cog"></i> <span>Configuration</span></a>
                <a href="../logout.php" class="nav-item" style="margin-top: auto; color: #ef4444;"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="page-title">
                    <h1>⚙️ Paramètres & Audit</h1>
                    <span>Gestion du système et traçabilité</span>
                </div>
                <div class="user-info">
                    <span>Admin</span>
                </div>
            </div>

            <div class="content-scroll">
                <?= $message ?>

                <div class="settings-grid">
                    <div class="card">
                        <h3><i class="fas fa-sliders-h"></i> Paramètres Généraux</h3>
                        <form method="POST" style="margin-top: 15px;">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label>Seuil d'alerte (Absences cumulées)</label>
                                <input type="number" name="seuil_alerte" class="form-control" value="<?= htmlspecialchars($config['seuil_alerte'] ?? 3) ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                <small style="color: #666;">Nombre d'absences déclenchant l'alerte rouge.</small>
                            </div>
                            <button type="submit" name="update_config" class="btn btn-primary" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Enregistrer</button>
                        </form>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-lock"></i> Sécurité Admin</h3>
                        <form method="POST" style="margin-top: 15px;">
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label>Nouveau mot de passe</label>
                                <input type="password" name="new_password" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label>Confirmer</label>
                                <input type="password" name="confirm_password" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <button type="submit" name="update_password" class="btn btn-danger" style="background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Changer le mot de passe</button>
                        </form>
                    </div>
                </div>

                <div class="card" style="margin-top: 20px;">
                    <h3><i class="fas fa-history"></i> Journal d'Audit (Logs)</h3>
                    <?php if (empty($logs)): ?>
                        <p style="color: #666; font-style: italic; padding: 20px;">Aucun historique disponible pour le moment.</p>
                    <?php else: ?>
                    <div class="table-container" style="max-height: 400px; overflow-y: auto;">
                        <table class="log-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Action</th>
                                    <th>Utilisateur</th>
                                    <th>Détails</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= $log['date_creation'] ?></td>
                                    <td><span class="badge-log log-<?= $log['action'] ?>"><?= $log['action'] ?></span></td>
                                    <td><strong><?= htmlspecialchars($log['utilisateur']) ?></strong></td>
                                    <td><?= htmlspecialchars($log['description']) ?></td>
                                    <td style="color:#888;"><?= $log['ip_address'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>
</body>
</html>