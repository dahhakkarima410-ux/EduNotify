<?php
require_once 'auth.php';
// admin/debug_import.php
session_start();

// Activer TOUS les messages d'erreur
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Debug Import</h1>";
echo "<hr>";

// Vérifier la session
echo "<h2>1. Données en session</h2>";
if (isset($_SESSION['csv_import'])) {
    echo "<pre>";
    print_r($_SESSION['csv_import']);
    echo "</pre>";
} else {
    echo "<p style='color:red;'>❌ Aucune donnée CSV en session</p>";
}

// Vérifier le POST
echo "<h2>2. Données POST</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
} else {
    echo "<p>Aucune donnée POST (normal si vous ouvrez cette page directement)</p>";
}

// Vérifier la connexion DB
echo "<h2>3. Test connexion base de données</h2>";
try {
    require_once '../config/database.php';
    require_once '../classes/Database.php';
    
    $db = new Database();
    echo "<p style='color:green;'>✅ Connexion DB réussie</p>";
    
    // Tester une requête simple
    $result = $db->query("SELECT COUNT(*) as total FROM etudiants");
    echo "<p>Nombre d'étudiants actuels : " . $result[0]['total'] . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Erreur DB : " . $e->getMessage() . "</p>";
}

// Vérifier les fichiers uploadés
echo "<h2>4. Fichiers uploadés</h2>";
$csvDir = '../uploads/csv/';
if (is_dir($csvDir)) {
    $files = scandir($csvDir);
    $csvFiles = array_filter($files, function($file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'csv';
    });
    
    if (!empty($csvFiles)) {
        echo "<ul>";
        foreach ($csvFiles as $file) {
            echo "<li>" . $file . " (" . filesize($csvDir . $file) . " octets)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Aucun fichier CSV trouvé</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Le dossier uploads/csv/ n'existe pas</p>";
}

// Vérifier les erreurs en session
echo "<h2>5. Erreurs en session</h2>";
if (isset($_SESSION['upload_error'])) {
    echo "<p style='color:red;'>❌ " . htmlspecialchars($_SESSION['upload_error']) . "</p>";
    unset($_SESSION['upload_error']);
} else {
    echo "<p style='color:green;'>✅ Aucune erreur</p>";
}

if (isset($_SESSION['upload_success'])) {
    echo "<p style='color:green;'>✅ " . htmlspecialchars($_SESSION['upload_success']) . "</p>";
    unset($_SESSION['upload_success']);
}
?>