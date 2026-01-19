<?php
// classes/Logger.php
class Logger {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function log($action, $description) {
        $user = $_SESSION['user_name'] ?? 'Système';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

        try {
            $stmt = $this->db->getPDO()->prepare("INSERT INTO logs (action, description, utilisateur, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$action, $description, $user, $ip]);
        } catch (Exception $e) {
            // On ne veut pas bloquer le site si le log échoue
        }
    }
}
?>