<?php
require_once 'auth.php';
// admin/preview_csv.php
session_start();

require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/ColumnDetector.php';
require_once '../classes/MappingManager.php';

// Vérifier qu'on a bien des données en session
if (!isset($_SESSION['csv_import'])) {
    $_SESSION['upload_error'] = "Aucun fichier en cours d'import";
    header('Location: import_csv.php');
    exit;
}

$csvData = $_SESSION['csv_import'];
$db = new Database();
$detector = new ColumnDetector();
$mappingManager = new MappingManager($db);

// Détecter automatiquement les colonnes
$mapping = $detector->detecter($csvData['headers']);
$validation = $detector->validerMapping($mapping);
$champsDisponibles = $detector->getChampsDisponibles();

// Récupérer les configs existantes pour la liste déroulante
$configsExistantes = $mappingManager->getListe();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapping des Colonnes - Import CSV</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
        }
        .content { padding: 40px; }
        .section-title {
            font-size: 1.5em; color: #333; margin: 30px 0 20px 0;
            padding-bottom: 10px; border-bottom: 2px solid #667eea;
        }
        .mapping-table {
            width: 100%; border-collapse: collapse; margin: 20px 0;
            background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .mapping-table th, .mapping-table td { padding: 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        .mapping-table th { background: #667eea; color: white; }
        
        /* Styles pour la barre de configuration */
        .config-bar {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid #28a745;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .config-group { display: flex; gap: 10px; align-items: center; }
        .btn-small {
            padding: 8px 15px; border: none; border-radius: 4px;
            cursor: pointer; background: #28a745; color: white;
            font-size: 0.9em;
        }
        .btn-small:hover { background: #218838; }
        input, select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }

        /* Styles Prévisualisation */
        .preview-table {
            width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 0.9em;
        }
        .preview-table th, .preview-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .preview-table th { background: #f8f9fa; font-weight: 600; }
        
        .btn { padding: 15px 40px; border: none; border-radius: 6px; cursor: pointer; color: white; text-decoration: none; font-size: 1.1em;}
        .btn-primary { background: #667eea; }
        .btn-secondary { background: #6c757d; }
        .confidence-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.85em; font-weight: bold; }
        .confidence-high { background: #d4edda; color: #155724; }
        .confidence-medium { background: #fff3cd; color: #856404; }
        .confidence-low { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔗 Mapping des Colonnes</h1>
            <p>Fichier : <?= htmlspecialchars($csvData['filename']) ?></p>
        </div>

        <div class="content">
            
            <div class="config-bar">
                <div class="config-group">
                    <strong>📂 Charger un modèle :</strong>
                    <select id="selectConfig">
                        <option value="">-- Choisir un modèle sauvegardé --</option>
                        <?php foreach ($configsExistantes as $conf): ?>
                            <option value="<?= $conf['id'] ?>">
                                <?= htmlspecialchars($conf['nom']) ?> (<?= date('d/m/Y', strtotime($conf['date_creation'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn-small" onclick="chargerConfig()">Charger</button>
                </div>

                <div class="config-group">
                    <strong>💾 Sauvegarder ce mapping :</strong>
                    <input type="text" id="nomConfig" placeholder="Nom (ex: Modèle Standard)">
                    <button type="button" class="btn-small" onclick="sauvegarderConfig()">Sauvegarder</button>
                </div>
            </div>

            <form id="mappingForm" action="process_import.php" method="POST">
                <h2 class="section-title">1️⃣ Correspondance des Colonnes</h2>
                <table class="mapping-table">
                    <thead>
                        <tr>
                            <th>Colonne CSV</th>
                            <th>Correspondance Système</th>
                            <th>Confiance Détection</th>
                            <th>Exemple</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mapping as $index => $map): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($map['colonne_csv']) ?></strong></td>
                            <td>
                                <select name="mapping[<?= $index ?>]" class="mapping-select" data-colonne-csv="<?= htmlspecialchars($map['colonne_csv_normalized']) ?>">
                                    <option value="">-- Ignorer --</option>
                                    <?php foreach ($champsDisponibles as $champ): ?>
                                        <?php $selected = ($map['champ_detecte'] === $champ) ? 'selected' : ''; ?>
                                        <option value="<?= $champ ?>" <?= $selected ?>>
                                            <?= ucfirst(str_replace('_', ' ', $champ)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <?php
                                $score = $map['confiance'];
                                $badge = $score >= 90 ? 'high' : ($score >= 70 ? 'medium' : 'low');
                                ?>
                                <span class="confidence-badge confidence-<?= $badge ?>">
                                    <?= $score ?>%
                                </span>
                            </td>
                            <td><?= htmlspecialchars(substr($csvData['preview_data'][0][$map['colonne_csv']] ?? '', 0, 30)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h2 class="section-title">2️⃣ Prévisualisation (10 premières lignes)</h2>
                <div style="overflow-x: auto;">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <?php foreach ($csvData['headers'] as $header): ?>
                                <th><?= htmlspecialchars($header) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($csvData['preview_data'] as $row): ?>
                            <tr>
                                <?php foreach ($csvData['headers'] as $header): ?>
                                <td><?= htmlspecialchars($row[$header] ?? '') ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="actions" style="text-align: center; margin-top: 30px;">
                    <a href="import_csv.php" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Valider et Importer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function sauvegarderConfig() {
            const nom = document.getElementById('nomConfig').value;
            if (!nom) { alert("Veuillez donner un nom au modèle !"); return; }

            let mapping = {};
            document.querySelectorAll('.mapping-select').forEach(select => {
                let colCSV = select.getAttribute('data-colonne-csv');
                mapping[colCSV] = select.value;
            });

            const formData = new FormData();
            formData.append('action', 'sauvegarder');
            formData.append('nom', nom);
            for (let key in mapping) {
                formData.append('mapping[' + key + ']', mapping[key]);
            }

            fetch('api_mapping.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("✅ " + data.message);
                        location.reload();
                    } else {
                        alert("❌ Erreur : " + data.error);
                    }
                });
        }

        function chargerConfig() {
            const id = document.getElementById('selectConfig').value;
            if (!id) return;

            const formData = new FormData();
            formData.append('action', 'charger');
            formData.append('id', id);

            fetch('api_mapping.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        const savedMapping = res.data;
                        let count = 0;
                        document.querySelectorAll('.mapping-select').forEach(select => {
                            let colCSV = select.getAttribute('data-colonne-csv');
                            if (savedMapping[colCSV]) {
                                select.value = savedMapping[colCSV];
                                count++;
                            }
                        });
                        alert("✅ Configuration chargée ! (" + count + " colonnes associées)");
                    } else {
                        alert("❌ Erreur : " + res.error);
                    }
                });
        }
    </script>
</body>
</html>