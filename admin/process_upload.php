<?php
// admin/process_upload.php
session_start();

require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/CSVUploader.php';
require_once '../classes/CSVParser.php';
// AJOUT : On inclut le Logger
require_once '../classes/Logger.php';

// Vérifier si l'utilisateur est connecté (à adapter)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Pour les tests
    $_SESSION['user_name'] = 'Admin (Test)'; // Utile pour le Logger
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: import_csv.php');
    exit;
}

try {
    // Récupérer le mois cible
    $moisCible = $_POST['mois_cible'] ?? '';
    if (empty($moisCible)) {
        throw new Exception("Mois cible non spécifié");
    }

    // Créer le dossier uploads/csv s'il n'existe pas
    $uploadDir = '../uploads/csv/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Upload du fichier
    $uploader = new CSVUploader($uploadDir);
    $uploadResult = $uploader->upload('csv_file');

    if (!$uploadResult['success']) {
        throw new Exception($uploadResult['error']);
    }

    $filePath = $uploadResult['filepath'];

    // Parser le fichier CSV
    $parser = new CSVParser();
    if (!$parser->parse($filePath, 100)) { // Limiter à 100 lignes pour la prévisualisation
        $errors = $parser->getErrors();
        throw new Exception("Erreur lors de la lecture du CSV : " . implode(", ", $errors));
    }

    // Sauvegarder les infos en session pour l'étape suivante
    $_SESSION['csv_import'] = [
        'filepath' => $filePath,
        'filename' => $uploadResult['filename'],
        'headers' => $parser->getHeaders(),
        'preview_data' => $parser->getPreview(10),
        'total_rows' => count($parser->getData()),
        'encoding' => $parser->getEncoding(),
        'delimiter' => $parser->getDelimiter(),
        'mois_cible' => $moisCible
    ];

    // --- MODIFICATION ICI : LOGGER L'ACTION ---
    // On instancie la base de données et le logger
    $db = new Database();
    $logger = new Logger($db);
    
    // On enregistre l'action dans la table logs
    $nomFichier = $uploadResult['filename'];
    $logger->log('IMPORT', "Fichier '$nomFichier' uploadé pour le mois : $moisCible");
    // ------------------------------------------

    // Rediriger vers la page de mapping
    header('Location: preview_csv.php');
    exit;

} catch (Exception $e) {
    $_SESSION['upload_error'] = $e->getMessage();
    header('Location: import_csv.php');
    exit;
}
?>