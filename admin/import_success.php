<?php
require_once 'auth.php';
// admin/import_success.php
session_start();

if (!isset($_SESSION['import_result'])) {
    header('Location: import_csv.php');
    exit;
}

$stats = $_SESSION['import_result'];
$nomTable = $_SESSION['import_table'] ?? 'N/A';

// Calculer le taux de succès
$tauxSucces = $stats['total'] > 0 
    ? round(($stats['importees'] / $stats['total']) * 100, 2) 
    : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Terminé ✓</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 800px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .success-icon {
            font-size: 4em;
            margin-bottom: 20px;
            animation: bounce 1s;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .content {
            padding: 40px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #667eea;
        }

        .stat-card.success {
            border-left-color: #28a745;
        }

        .stat-card.error {
            border-left-color: #dc3545;
        }

        .stat-card h3 {
            font-size: 2.5em;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-card.success h3 {
            color: #28a745;
        }

        .stat-card.error h3 {
            color: #dc3545;
        }

        .stat-card p {
            color: #666;
            font-size: 0.95em;
        }

        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            transition: width 1s ease;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }

        .info-box h4 {
            color: #1976d2;
            margin-bottom: 10px;
        }

        .error-list {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            max-height: 300px;
            overflow-y: auto;
        }

        .error-list h4 {
            color: #856404;
            margin-bottom: 15px;
        }

        .error-item {
            padding: 10px;
            margin: 5px 0;
            background: white;
            border-radius: 4px;
            font-size: 0.9em;
        }

        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        table th {
            background: #f8f9fa;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">✓</div>
            <h1>Import Terminé avec Succès !</h1>
            <p>Les données ont été importées dans la table : <strong><?= htmlspecialchars($nomTable) ?></strong></p>
        </div>

        <div class="content">
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $tauxSucces ?>%">
                    <?= $tauxSucces ?>% de succès
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?= $stats['total'] ?></h3>
                    <p>Lignes traitées</p>
                </div>
                <div class="stat-card success">
                    <h3><?= $stats['importees'] ?></h3>
                    <p>Importées avec succès</p>
                </div>
                <div class="stat-card error">
                    <h3><?= $stats['erreurs'] ?></h3>
                    <p>Erreurs</p>
                </div>
                <div class="stat-card">
                    <h3><?= $stats['absences_creees'] ?></h3>
                    <p>Absences créées</p>
                </div>
            </div>

            <?php if ($stats['erreurs'] > 0 && !empty($stats['rapportErreurs'])): ?>
            <div class="error-list">
                <h4>⚠️ Détail des erreurs (<?= count($stats['rapportErreurs']) ?> premières)</h4>
                <?php foreach (array_slice($stats['rapportErreurs'], 0, 10) as $erreur): ?>
                <div class="error-item">
                    <strong>Ligne <?= $erreur['ligne'] ?> :</strong> 
                    <?= htmlspecialchars($erreur['erreur']) ?>
                </div>
                <?php endforeach; ?>
                
                <?php if (count($stats['rapportErreurs']) > 10): ?>
                <p style="margin-top: 15px; color: #666; font-style: italic;">
                    ... et <?= count($stats['rapportErreurs']) - 10 ?> autres erreurs
                </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="info-box">
                <h4>ℹ️ Que faire maintenant ?</h4>
                <ul style="margin-left: 20px;">
                    <li>Consultez les absences importées dans le tableau de bord</li>
                    <li>Vérifiez que les données sont correctes</li>
                    <li>Vous pouvez maintenant envoyer des notifications aux parents</li>
                    <?php if ($stats['erreurs'] > 0): ?>
                    <li style="color: #856404;">Corrigez les lignes en erreur et réimportez-les si nécessaire</li>
                    <?php endif; ?>
                </ul>
            </div>

            <table>
                <tr>
                    <th>Information</th>
                    <th>Valeur</th>
                </tr>
                <tr>
                    <td>Table créée/utilisée</td>
                    <td><code><?= htmlspecialchars($nomTable) ?></code></td>
                </tr>
                <tr>
                    <td>Date d'import</td>
                    <td><?= date('d/m/Y à H:i:s') ?></td>
                </tr>
                <tr>
                    <td>Taux de réussite</td>
                    <td><strong><?= $tauxSucces ?>%</strong></td>
                </tr>
            </table>

            <div class="actions">
                <a href="import_csv.php" class="btn btn-primary">
                    📤 Nouvel Import
                </a>
                <a href="../index.php" class="btn btn-success">
                    📊 Voir le Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
        // Animation de la barre de progression
        setTimeout(() => {
            document.querySelector('.progress-fill').style.width = '<?= $tauxSucces ?>%';
        }, 100);

        // Nettoyer la session après affichage
        <?php unset($_SESSION['import_result'], $_SESSION['import_table']); ?>
    </script>
</body>
</html>