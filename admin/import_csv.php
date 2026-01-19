<?php
require_once 'auth.php';
// admin/import_csv.php (CORRIGÉ AVEC SÉLECTEUR DE MOIS)
session_start();
$error = isset($_SESSION['upload_error']) ? $_SESSION['upload_error'] : '';
unset($_SESSION['upload_error']);
$success = isset($_SESSION['upload_success']) ? $_SESSION['upload_success'] : '';
unset($_SESSION['upload_success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Import CSV - EduNotify</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        // Petite fonction pour valider avant l'envoi
        function validerEtEnvoyer() {
            var dateInput = document.getElementById('moisCible');
            if (!dateInput.value) {
                alert("⚠️ Veuillez choisir le mois et l'année avant d'importer le fichier !");
                return false;
            }
            document.getElementById('formUpload').submit();
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
                <a href="../index.php" class="nav-item">
                    <i class="fas fa-home"></i> <span>Dashboard</span>
                </a>
                <a href="import_csv.php" class="nav-item active">
                    <i class="fas fa-cloud-upload-alt"></i> <span>Import CSV</span>
                </a>
                <a href="dashboard_notifications.php" class="nav-item">
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
                    <h1>📂 Importation des Données</h1>
                    <span>Charger les nouveaux fichiers d'absences</span>
                </div>
                <div class="user-info">
                    <div class="user-avatar">A</div>
                </div>
            </div>

            <div class="content-scroll">
                
                <?php if($error): ?>
                    <div class="card" style="background:#fee2e2; border-left:4px solid #ef4444; color:#991b1b; padding:15px; margin-bottom:20px;">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <h3 style="margin-bottom: 20px;">Nouvel Import</h3>
                    
                    <form id="formUpload" action="process_upload.php" method="POST" enctype="multipart/form-data">
                        
                        <div style="margin-bottom: 25px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600; color:#475569;">Pour quel mois importez-vous ces données ?</label>
                            <input type="month" id="moisCible" name="mois_cible" required 
                                   value="<?= date('Y-m') ?>"
                                   style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; max-width: 300px; font-family: inherit;">
                            <p style="font-size: 0.9em; color: #94a3b8; margin-top: 5px;">Cela créera ou mettra à jour la table correspondante (ex: absences_1_2026).</p>
                        </div>

                        <div class="upload-zone" onclick="document.getElementById('fileInput').click()" 
                             style="border: 2px dashed #cbd5e1; border-radius: 10px; padding: 40px; text-align: center; cursor: pointer; transition: 0.3s; background: #f8fafc;">
                            
                            <i class="fas fa-file-csv" style="font-size: 3em; color: #667eea; margin-bottom: 15px;"></i>
                            <h3 style="color: #475569;">Cliquez pour choisir le fichier CSV</h3>
                            <input type="file" id="fileInput" name="csv_file" style="display: none;" onchange="validerEtEnvoyer()">
                        </div>

                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>