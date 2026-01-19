<?php
// admin/auth.php
// Ce fichier agit comme un "videur" de boîte de nuit.

// 1. On démarre la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Vérification : Est-ce que l'utilisateur est connecté ?
if (!isset($_SESSION['user_id'])) {
    // NON -> Dehors ! On redirige vers la page de login
    header("Location: ../login.php");
    exit(); // Important : on arrête l'exécution du script immédiatement
}

// 3. (Optionnel) Vérification du rôle : Est-ce un admin ?
// Si vous avez géré les rôles dans UserManager, décommentez ceci :
/*
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin') {
    die("Accès interdit : Vous n'êtes pas administrateur.");
}
*/
?>