<?php
// classes/UserManager.php
class UserManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function login($email, $password) {
        $pdo = $this->db->getPDO();
        
        // 1. On récupère l'utilisateur par son email
        $sql = "SELECT * FROM utilisateurs WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Vérification du mot de passe
        // C'EST ICI LA CORRECTION : on utilise $user['password'] et non $user['mot_de_passe']
        if ($user && password_verify($password, $user['password'])) {
            
            // Démarrer la session si ce n'est pas déjà fait
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Enregistrer les infos en session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nom'];
            $_SESSION['user_role'] = $user['role'] ?? 'admin';

            return true;
        }

        return false;
    }

    public function isAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}
?>