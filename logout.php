<?php
// logout.php
session_start();

require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/Logger.php';

// 1. On enregistre l'événement "Déconnexion" dans les Logs (Pour le PDF)
if (isset($_SESSION['user_id'])) {
    try {
        $db = new Database();
        $logger = new Logger($db);
        
        $nom = $_SESSION['user_name'] ?? 'Utilisateur inconnu';
        $email = $_SESSION['user_email'] ?? ''; // Si vous stockez l'email en session
        
        $logger->log('DECONNEXION', "L'utilisateur $nom s'est déconnecté.");
    } catch (Exception $e) {
        // Si la base de données ne répond pas, on continue quand même la déconnexion
    }
}

// 2. On vide toutes les variables de session
$_SESSION = array();

// 3. On efface le cookie de session (Nettoyage complet)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. On détruit la session côté serveur
session_destroy();

// 5. Redirection vers la page de connexion
header("Location: login.php");
exit;
?>