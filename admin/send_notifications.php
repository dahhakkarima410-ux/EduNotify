<?php
require_once 'auth.php';
// admin/send_notifications.php (VERSION AVEC VÉRIFICATION VISUELLE)
session_start();
require_once '../config/database.php';
require_once '../classes/Database.php';

$db = new Database();

// 1. Récupération des tables
$tables = $db->query("SHOW TABLES LIKE 'absences_%'");
$toutesLesTables = [];
if (!empty($tables)) {
    foreach ($tables as $t) {
        $nom = array_values($t)[0];
        if ($nom !== 'absences_template') $toutesLesTables[] = $nom;
    }
}
$tableActive = isset($_GET['mois']) ? $_GET['mois'] : (end($toutesLesTables) ?: '');

// 2. LOGIQUE : Récupérer les données
$aNotifierIndividuel = [];
$aNotifierSeuil = [];
$seuilDefini = isset($_GET['seuil']) ? (int)$_GET['seuil'] : 3;

if ($tableActive) {
    // A. Individuel : On récupère MAINTENANT l'email et le téléphone
    try {
        $aNotifierIndividuel = $db->query("SELECT a.id, a.date_absence, e.nom, e.prenom, e.classe, e.email_parent, e.telephone_parent 
                                 FROM `$tableActive` a 
                                 JOIN etudiants e ON a.etudiant_id = e.id 
                                 WHERE a.notifie = 0 ORDER BY a.date_absence DESC");
    } catch(Exception $e) {}

    // B. Seuil
    try {
        $sqlSeuil = "SELECT e.id as etudiant_id, e.nom, e.prenom, e.classe, e.email_parent, e.telephone_parent, 
                     COUNT(a.id) as nb_absences, 
                     GROUP_CONCAT(a.date_absence SEPARATOR ', ') as dates
                     FROM `$tableActive` a
                     JOIN etudiants e ON a.etudiant_id = e.id
                     WHERE a.notifie = 0
                     GROUP BY a.etudiant_id
                     HAVING nb_absences >= $seuilDefini";
        $aNotifierSeuil = $db->query($sqlSeuil);
    } catch(Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Envois - EduNotify</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .tabs { display: flex; gap: 20px; border-bottom: 2px solid #eee; margin-bottom: 20px; }
        .tab-btn { padding: 10px 20px; border: none; background: none; font-size: 16px; font-weight: 600; color: #7f8c8d; cursor: pointer; border-bottom: 3px solid transparent; transition: 0.3s; }
        .tab-btn:hover { color: #667eea; }
        .tab-btn.active { color: #667eea; border-bottom-color: #667eea; }
        .tab-content { display: none; animation: fadeIn 0.3s; }
        .tab-content.active { display: block; }
        
        /* Style pour les infos de contact */
        .contact-info { font-size: 0.9em; color: #555; }
        .contact-icon { width: 20px; text-align: center; margin-right: 5px; }
        
        @keyframes fadeIn { from { opacity:0; transform:translateY(5px); } to { opacity:1; transform:translateY(0); } }
    </style>
    <script>
        function openTab(tabName, btn) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            btn.classList.add('active');
        }
    </script>
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="logo-area">
                <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
                <span>EduNotify</span>
            </div>
            <nav class="nav-links">
                <a href="../index.php" class="nav-item"><i class="fas fa-home"></i> <span>Dashboard</span></a>
                <a href="import_csv.php" class="nav-item"><i class="fas fa-cloud-upload-alt"></i> <span>Import CSV</span></a>
                <a href="dashboard_notifications.php" class="nav-item"><i class="fas fa-list"></i> <span>Liste Absences</span></a>
                <a href="send_notifications.php" class="nav-item active"><i class="fas fa-paper-plane"></i> <span>Envois</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="page-title">
                    <h1>🚀 Centre de Notification</h1>
                    <span>Vérification et Envoi</span>
                </div>
                
                <form method="GET" id="monthForm">
                    <?php if(isset($_GET['seuil'])): ?>
                        <input type="hidden" name="seuil" value="<?= $_GET['seuil'] ?>">
                    <?php endif; ?>
                    <select name="mois" onchange="document.getElementById('monthForm').submit()" style="padding: 8px; border-radius: 6px;">
                        <?php foreach ($toutesLesTables as $t): ?>
                            <option value="<?= $t ?>" <?= $t == $tableActive ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="content-scroll">
                
                <div class="tabs">
                    <button class="tab-btn active" onclick="openTab('tab-individuel', this)">✉️ Envoi Individuel</button>
                    <button class="tab-btn" onclick="openTab('tab-seuil', this)">⚠️ Alertes Cumulées (Seuil)</button>
                </div>

                <div id="tab-individuel" class="tab-content active">
                    <div class="card">
                        <h3 style="margin-bottom: 20px;">Vérifier les coordonnées avant envoi</h3>
                        
                        <?php if (empty($aNotifierIndividuel)): ?>
                            <div style="background:#dcfce7; color:#166534; padding:20px; border-radius:8px; text-align:center;">
                                <i class="fas fa-check-circle"></i> Aucune notification en attente.
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Étudiant</th>
                                            <th>Date Absence</th>
                                            <th>Coordonnées Parent (Vérification)</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($aNotifierIndividuel as $n): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($n['prenom'] . ' ' . $n['nom']) ?></strong><br>
                                                <small class="badge badge-info"><?= htmlspecialchars($n['classe']) ?></small>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($n['date_absence'])) ?></td>
                                            
                                            <td>
                                                <div class="contact-info">
                                                    <div><i class="fas fa-envelope contact-icon" style="color:#EA4335;"></i> <?= htmlspecialchars($n['email_parent'] ?? 'Aucun email') ?></div>
                                                    <div style="margin-top:4px;"><i class="fab fa-whatsapp contact-icon" style="color:#25D366;"></i> <?= htmlspecialchars($n['telephone_parent'] ?? 'Aucun tél') ?></div>
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <form action="send_process_simple.php" method="POST">
                                                    <input type="hidden" name="table_source" value="<?= $tableActive ?>">
                                                    <input type="hidden" name="absence_id" value="<?= $n['id'] ?>">
                                                    
                                                    <button type="submit" class="btn btn-primary" 
                                                            style="padding: 6px 12px; font-size: 0.9em;"
                                                            onclick="return confirm('Confirmez-vous l\'envoi à : <?= htmlspecialchars($n['telephone_parent']) ?> ?')">
                                                        <i class="fas fa-paper-plane"></i> Envoyer
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="tab-seuil" class="tab-content">
                    <div class="card">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                            <h3>Étudiants avec beaucoup d'absences</h3>
                            <form method="GET" style="display:flex; align-items:center; gap:10px; background:#f1f5f9; padding:10px; border-radius:8px;">
                                <input type="hidden" name="mois" value="<?= $tableActive ?>">
                                <label style="font-weight:600;">Seuil d'alerte :</label>
                                <input type="number" name="seuil" value="<?= $seuilDefini ?>" min="2" max="20" style="width:60px; padding:5px; border-radius:4px; border:1px solid #ccc;">
                                <button type="submit" class="btn btn-primary" style="padding:5px 10px; font-size:0.9em;">Appliquer</button>
                            </form>
                        </div>

                        <?php if (empty($aNotifierSeuil)): ?>
                            <div style="text-align:center; padding:30px; color:#7f8c8d;">
                                <i class="fas fa-shield-alt" style="font-size:3em; color:#2ecc71; margin-bottom:15px;"></i>
                                <p>Aucun étudiant n'a atteint <?= $seuilDefini ?> absences.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Étudiant</th>
                                            <th>Total</th>
                                            <th>Vérification Contact</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($aNotifierSeuil as $etud): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($etud['prenom'].' '.$etud['nom']) ?></strong><br>
                                                <small><?= htmlspecialchars($etud['classe']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-danger" style="font-size:1.1em;">
                                                    <?= $etud['nb_absences'] ?> absences
                                                </span>
                                            </td>
                                            
                                            <td>
                                                <div class="contact-info">
                                                    <div><i class="fas fa-envelope contact-icon" style="color:#EA4335;"></i> <?= htmlspecialchars($etud['email_parent']) ?></div>
                                                    <div style="margin-top:4px;"><i class="fab fa-whatsapp contact-icon" style="color:#25D366;"></i> <?= htmlspecialchars($etud['telephone_parent']) ?></div>
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <form action="send_process_group.php" method="POST">
                                                    <input type="hidden" name="table_source" value="<?= $tableActive ?>">
                                                    <input type="hidden" name="etudiant_id" value="<?= $etud['etudiant_id'] ?>">
                                                    <input type="hidden" name="nb_absences" value="<?= $etud['nb_absences'] ?>">
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Alerter ce parent sur le numéro <?= htmlspecialchars($etud['telephone_parent']) ?> ?')">
                                                        🚨 Alerter
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>