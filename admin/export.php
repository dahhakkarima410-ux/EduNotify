<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/UserManager.php';

$db = new Database();
$userMgr = new UserManager($db);

// 1. Sécurité : Vérifier si l'utilisateur est connecté
if (!$userMgr->isLogged()) {
    die("Accès refusé");
}

// 2. Récupérer le mois (par défaut janvier 2026)
$moisTable = $_GET['mois'] ?? 'absences_01_2026';

// 3. Vérifier si la table existe
if (!$db->tableExists($moisTable)) {
    die("Pas de données pour ce mois.");
}

// 4. Récupérer les données
$sql = "SELECT e.nom, e.prenom, e.classe, a.date_absence, a.matiere, a.justifie, a.notifie 
        FROM `$moisTable` a 
        JOIN etudiants e ON a.etudiant_id = e.id 
        ORDER BY a.date_absence DESC";
$data = $db->query($sql);

// 5. Générer le fichier CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Rapport_' . $moisTable . '.csv');

$output = fopen('php://output', 'w');

// En-têtes des colonnes (BOM pour Excel)
fputs($output, "\xEF\xBB\xBF"); 
fputcsv($output, ['Nom', 'Prénom', 'Classe', 'Date', 'Matière', 'Justifié', 'Notifié'], ';');

// Données
foreach ($data as $row) {
    fputcsv($output, [
        $row['nom'], 
        $row['prenom'], 
        $row['classe'], 
        $row['date_absence'], 
        $row['matiere'], 
        $row['justifie'] ? 'Oui' : 'Non',
        $row['notifie'] ? 'Oui' : 'Non'
    ], ';');
}
fclose($output);
exit;
?>