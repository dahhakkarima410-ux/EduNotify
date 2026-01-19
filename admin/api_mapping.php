<?php
// admin/api_mapping.php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/MappingManager.php';

try {
    $db = new Database();
    $manager = new MappingManager($db);
    $action = $_POST['action'] ?? '';

    if ($action === 'sauvegarder') {
        $nom = trim($_POST['nom'] ?? '');
        $mapping = $_POST['mapping'] ?? [];

        if (empty($nom) || empty($mapping)) {
            throw new Exception("Nom ou données manquants");
        }

        $manager->sauvegarder($nom, $mapping);
        echo json_encode(['success' => true, 'message' => 'Configuration sauvegardée !']);
    }
    
    elseif ($action === 'charger') {
        $id = $_POST['id'] ?? 0;
        $data = $manager->charger($id);
        
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            throw new Exception("Configuration introuvable");
        }
    }
    
    else {
        throw new Exception("Action inconnue");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>