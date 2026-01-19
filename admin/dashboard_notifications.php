<?php
require_once 'auth.php';
// admin/dashboard_notifications.php
session_start();
require_once '../config/database.php';
require_once '../classes/Database.php';

$db = new Database();

// Récupération des tables
$tables = $db->query("SHOW TABLES LIKE 'absences_%'");
$toutesLesTables = [];
if (!empty($tables)) foreach ($tables as $t) $toutesLesTables[] = array_values($t)[0];

// Mois sélectionné
$tableActive = isset($_GET['mois']) ? $_GET['mois'] : (end($toutesLesTables) ?: '');

// Récupération des absences
$absences = [];
if ($tableActive) {
    try {
        $absences = $db->query("SELECT a.*, e.nom, e.prenom, e.classe, e.telephone_parent 
                                FROM `$tableActive` a 
                                JOIN etudiants e ON a.etudiant_id = e.id 
                                ORDER BY a.id DESC");
    } catch(Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste Absences - EduNotify</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="logo-area">
                <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
                <span>EduNotify</span>
            </div>
            <nav class="nav-links">
                <a href="../index.php" class="nav-item">
                    <i class="fas fa-home"></i> <span>Dashboard</span>
                </a>
                <a href="import_csv.php" class="nav-item">
                    <i class="fas fa-cloud-upload-alt"></i> <span>Import CSV</span>
                </a>
                <a href="dashboard_notifications.php" class="nav-item active">
                    <i class="fas fa-list"></i> <span>Liste Absences</span>
                </a>
                <a href="send_notifications.php" class="nav-item">
                    <i class="fas fa-paper-plane"></i> <span>Envois</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="page-title">
                    <h1>📋 Liste Complète</h1>
                    <span>Gestion détaillée des absences</span>
                </div>
                
                <form method="GET">
                    <select name="mois" onchange="this.form.submit()" style="padding: 8px; border-radius: 6px; border: 1px solid #ddd;">
                        <?php foreach ($toutesLesTables as $t): ?>
                            <option value="<?= $t ?>" <?= $t == $tableActive ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="content-scroll">
                <?php if (empty($absences)): ?>
                    <div class="card" style="text-align:center; padding: 40px;">
                        <p>Aucune absence trouvée pour ce mois.</p>
                    </div>
                <?php else: ?>
                    <div class="card" style="padding:0; overflow:hidden;">
                        <div class="table-container">
                            <table>
                                <thead style="background: #f8f9fa;">
                                    <tr>
                                        <th>Étudiant</th>
                                        <th>Classe</th>
                                        <th>Date</th>
                                        <th>Contact</th>
                                        <th>Statut Notification</th>
                                    </tr>
                                </thead>
                                <tbody>
    <?php foreach ($absences as $row): ?>
    <tr>
        <td><strong><?= htmlspecialchars(($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? '')) ?></strong></td>
        
        <td>
            <span style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:4px; font-size:0.8em;">
                <?= htmlspecialchars($row['classe'] ?? '') ?>
            </span>
        </td>
        
        <td><?= htmlspecialchars($row['date_absence'] ?? '') ?></td>
        
        <td><?= htmlspecialchars($row['telephone_parent'] ?? 'Non renseigné') ?></td>
        
        <td>
            <?php if (!empty($row['notifie']) && $row['notifie'] == 1): ?>
                <span style="color:#166534; font-weight:bold;"><i class="fas fa-check"></i> Envoyé</span>
            <?php else: ?>
                <span style="color:#991b1b;"><i class="fas fa-clock"></i> En attente</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>