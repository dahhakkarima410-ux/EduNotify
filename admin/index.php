<?php
// admin/index.php - Dashboard Principal

// 1. SÉCURITÉ : On remplace la vérification manuelle par le gardien
require_once 'auth.php'; 

// 2. INCLUSIONS DES CLASSES
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/AbsenceManager.php';
require_once '../classes/StatistiquesManager.php';

// 3. INITIALISATION & LOGIQUE
try {
    $db = new Database();
    $absenceManager = new AbsenceManager($db);
    $statsManager = new StatistiquesManager($db);
    
    // --- A. RÉCUPÉRATION DES MOIS ---
    // On utilise le Manager qui trie déjà correctement
    $moisDisponiblesData = $absenceManager->getMoisDisponibles();
    // On simplifie pour ton selecteur existant
    $moisDisponibles = [];
    foreach($moisDisponiblesData as $m) {
        $moisDisponibles[] = $m['table'];
    }
    
    // --- B. CHOIX DU MOIS ACTIF ---
    if (isset($_GET['mois']) && in_array($_GET['mois'], $moisDisponibles)) {
        $moisActif = $_GET['mois'];
    } else {
        $moisActif = !empty($moisDisponibles) ? $moisDisponibles[0] : null;
    }
    
    // --- C. STATISTIQUES ---
    $stats = [
        'total_etudiants' => 0,
        'total_absences' => 0,
        'absences_non_notifiees' => 0,
        'absences_justifiees' => 0,
        'top_absents' => []
    ];
    
    // Total global étudiants
    $res = $db->query("SELECT COUNT(*) as total FROM etudiants");
    $stats['total_etudiants'] = $res[0]['total'];
    
    $dernieresAbsences = [];

    if ($moisActif) {
        // On utilise le Manager pour récupérer les stats solides
        $globalStats = $statsManager->getStatistiquesGlobales($moisActif);
        
        $stats['total_absences'] = $globalStats['total_absences'];
        $stats['absences_notifiees'] = $globalStats['absences_notifiees']; // Notifiées
        $stats['absences_non_notifiees'] = $globalStats['total_absences'] - $globalStats['absences_notifiees'];
        $stats['top_absents'] = $globalStats['top_absents'];

        // Justifiées (requête spécifique)
        $resJ = $db->query("SELECT COUNT(*) as total FROM `$moisActif` WHERE justifie = 1");
        $stats['absences_justifiees'] = $resJ[0]['total'];
        
        // Liste des 5 dernières absences
        $sql = "SELECT a.*, e.nom, e.prenom, e.classe 
                FROM `$moisActif` a
                JOIN etudiants e ON a.etudiant_id = e.id
                ORDER BY a.date_absence DESC, a.id DESC LIMIT 5";
        $dernieresAbsences = $db->query($sql);
    }

    // --- D. LOGS D'AUDIT (NOUVEAU) ---
    // Récupérer les 5 dernières actions pour l'affichage
    $logsRecents = [];
    try {
        $logsRecents = $db->query("SELECT * FROM logs ORDER BY date_creation DESC LIMIT 5");
    } catch (Exception $e) {}

    // État des services
    $servicesStatus = [
        'database' => true,
        'email' => file_exists('../vendor/autoload.php'),
        'whatsapp' => file_exists('../vendor/autoload.php') // On suppose Twilio installé via Composer
    ];

} catch (Exception $e) {
    $error = "Erreur BDD : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EduNotify</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #667eea; --secondary: #764ba2; --success: #2ecc71;
            --danger: #e74c3c; --warning: #f39c12; --info: #3498db;
            --dark: #2c3e50; --light: #ecf0f1; --text-primary: #2c3e50;
            --text-muted: #7f8c8d; --bg-body: #f8f9fa;
            --shadow: 0 2px 10px rgba(0,0,0,0.1); --shadow-lg: 0 10px 40px rgba(0,0,0,0.15);
        }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); color: var(--text-primary); }
        .app-container { display: flex; min-height: 100vh; }
        
        .sidebar {
            width: 280px; background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white; padding: 30px 20px; position: fixed; height: 100vh;
            overflow-y: auto; box-shadow: var(--shadow-lg); z-index: 1000;
        }
        .logo-area { display: flex; align-items: center; gap: 15px; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .logo-icon { width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .logo-area span { font-size: 24px; font-weight: 700; }
        
        .nav-links { display: flex; flex-direction: column; gap: 10px; }
        .nav-item {
            display: flex; align-items: center; gap: 15px; padding: 15px 20px;
            color: rgba(255,255,255,0.8); text-decoration: none; border-radius: 10px;
            transition: all 0.3s; font-weight: 500;
        }
        .nav-item:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(5px); }
        .nav-item.active { background: rgba(255,255,255,0.2); color: white; }
        .nav-item i { width: 24px; text-align: center; font-size: 18px; }

        .main-content { margin-left: 280px; flex: 1; display: flex; flex-direction: column; }
        .topbar { background: white; padding: 20px 30px; box-shadow: var(--shadow); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        
        .page-title { display: flex; align-items: center; gap: 15px; }
        .month-selector { padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Outfit', sans-serif; font-size: 14px; color: var(--text-primary); cursor: pointer; outline: none; background: #f8f9fa; }
        .month-selector:hover { border-color: var(--primary); }

        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }

        .content-scroll { padding: 30px; overflow-y: auto; flex: 1; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: var(--shadow); display: flex; align-items: center; gap: 20px; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 28px; color: white; }
        .stat-icon.primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); }
        .stat-icon.success { background: linear-gradient(135deg, #2ecc71, #27ae60); }
        .stat-icon.danger { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-icon.warning { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-icon.info { background: linear-gradient(135deg, #3498db, #2980b9); }
        .stat-info h3 { font-size: 32px; font-weight: 700; color: var(--text-primary); margin-bottom: 5px; }
        .stat-info p { color: var(--text-muted); font-size: 14px; }

        .card { background: white; padding: 25px; border-radius: 15px; box-shadow: var(--shadow); margin-bottom: 25px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid var(--light); }
        .card-header h2 { font-size: 22px; color: var(--text-primary); }

        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }

        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: var(--light); padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary); }
        table td { padding: 15px; border-bottom: 1px solid #eee; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }

        .service-status { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .service-item { padding: 15px; background: var(--light); border-radius: 10px; display: flex; align-items: center; gap: 12px; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; }
        .status-dot.online { background: var(--success); box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2); }
        .status-dot.offline { background: var(--danger); box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2); }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
        }
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
                <a href="index.php" class="nav-item active"><i class="fas fa-home"></i> <span>Dashboard</span></a>
                <a href="import_csv.php" class="nav-item"><i class="fas fa-cloud-upload-alt"></i> <span>Import CSV</span></a>
                <a href="dashboard_notifications.php" class="nav-item"><i class="fas fa-list"></i> <span>Liste Absences</span></a>
                <a href="send_notifications.php" class="nav-item"><i class="fas fa-paper-plane"></i> <span>Envois</span></a>
                <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> <span>Configuration</span></a>
                <a href="../logout.php" class="nav-item" style="margin-top: auto; color: #ffcccc;"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <div class="page-title">
                    <h1>📊 Tableau de Bord</h1>
                    
                    <form method="GET" id="dashboardForm" style="margin-left: 15px;">
                        <select name="mois" class="month-selector" onchange="document.getElementById('dashboardForm').submit()">
                            <?php if(empty($moisDisponibles)): ?>
                                <option>Aucun mois</option>
                            <?php else: ?>
                                <?php foreach ($moisDisponibles as $m): ?>
                                    <option value="<?= $m ?>" <?= $m == $moisActif ? 'selected' : '' ?>>
                                        <?= ucfirst(str_replace('absences_', ' ', str_replace('_', '/', $m))) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </form>
                </div>

                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></strong>
                        <div style="font-size: 12px; color: var(--text-muted);">Administrateur</div>
                    </div>
                </div>
            </div>

            <div class="content-scroll">
                <?php if (isset($error)): ?>
                <div class="alert alert-danger" style="background:#f8d7da; padding:15px; border-radius:10px; margin-bottom:20px; color:#721c24;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Erreur :</strong> <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <?php if (!$moisActif): ?>
                <div class="card" style="text-align:center; padding:40px;">
                    <i class="fas fa-cloud-upload-alt" style="font-size:4em; color:#3498db; margin-bottom:20px;"></i>
                    <h3>Bienvenue sur EduNotify !</h3>
                    <p style="color:#7f8c8d; margin-bottom:20px;">Aucune donnée d'absence trouvée. Commencez par importer un fichier.</p>
                    <a href="import_csv.php" class="btn btn-primary">Importer un CSV</a>
                </div>
                <?php else: ?>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon primary"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <h3><?= number_format($stats['total_etudiants']) ?></h3>
                            <p>Étudiants inscrits</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon warning"><i class="fas fa-user-times"></i></div>
                        <div class="stat-info">
                            <h3><?= number_format($stats['total_absences']) ?></h3>
                            <p>Total Absences</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon danger"><i class="fas fa-bell"></i></div>
                        <div class="stat-info">
                            <h3><?= number_format($stats['absences_non_notifiees']) ?></h3>
                            <p>Non notifiées</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-info">
                            <h3><?= number_format($stats['absences_justifiees']) ?></h3>
                            <p>Justifiées</p>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>📋 Dernières Absences</h2>
                            <a href="dashboard_notifications.php?mois=<?= $moisActif ?>" class="btn btn-primary"><i class="fas fa-list"></i> Voir tout</a>
                        </div>
                        <?php if (!empty($dernieresAbsences)): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Étudiant</th>
                                        <th>Classe</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dernieresAbsences as $abs): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($abs['prenom'] . ' ' . $abs['nom']) ?></strong></td>
                                        <td><span class="badge badge-info"><?= htmlspecialchars($abs['classe']) ?></span></td>
                                        <td><?= date('d/m', strtotime($abs['date_absence'])) ?></td>
                                        <td>
                                            <?php if ($abs['notifie'] == 1): ?>
                                                <span class="badge badge-success">✓ Notifié</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">⏳ Attente</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <p style="text-align:center; color:#7f8c8d; padding:10px;">Aucune absence récente.</p>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <div class="card-header"><h2><i class="fas fa-chart-line"></i> Top Absents</h2></div>
                        <div class="table-container">
                            <table>
                                <tbody>
                                    <?php if(!empty($stats['top_absents'])): ?>
                                        <?php foreach ($stats['top_absents'] as $top): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($top['nom'].' '.$top['prenom']) ?></td>
                                            <td style="text-align:right;"><span class="badge badge-danger"><?= $top['nb_absences'] ?> abs.</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td style="color:#888;">Aucune donnée</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> Activité Récente (Audit)</h2>
                        <a href="settings.php" class="btn btn-primary" style="font-size:0.8em; padding:5px 10px;">Voir tout</a>
                    </div>
                    <?php if (!empty($logsRecents)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Heure</th>
                                    <th>Action</th>
                                    <th>Utilisateur</th>
                                    <th>Détail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logsRecents as $log): ?>
                                <tr>
                                    <td><?= date('d/m H:i', strtotime($log['date_creation'])) ?></td>
                                    <td><span class="badge badge-info"><?= htmlspecialchars($log['action']) ?></span></td>
                                    <td><?= htmlspecialchars($log['utilisateur']) ?></td>
                                    <td style="color:#7f8c8d; font-size:0.9em;"><?= htmlspecialchars(substr($log['description'], 0, 50)) ?>...</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <p style="padding:10px; color:#888;">Aucun historique pour le moment.</p>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-header"><h2>🔧 État des Services</h2></div>
                    <div class="service-status">
                        <div class="service-item">
                            <div class="status-dot <?= $servicesStatus['database'] ? 'online' : 'offline' ?>"></div>
                            <span>Base de données</span>
                        </div>
                        <div class="service-item">
                            <div class="status-dot <?= $servicesStatus['email'] ? 'online' : 'offline' ?>"></div>
                            <span>PHPMailer (Email)</span>
                        </div>
                        <div class="service-item">
                            <div class="status-dot <?= $servicesStatus['whatsapp'] ? 'online' : 'offline' ?>"></div>
                            <span>Twilio (WhatsApp)</span>
                        </div>
                    </div>
                </div>

                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>