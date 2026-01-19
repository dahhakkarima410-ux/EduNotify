<?php
// classes/Database.php

class Database {
    private $host = DB_HOST;
    private $dbname = DB_NAME;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $charset = DB_CHARSET;
    private $pdo;
    private $error;

    /**
     * Constructeur - Connexion automatique
     */
    public function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            throw new Exception("Database connection failed: " . $this->error);
        }
    }

    /**
     * Méthode query() - Exécute une requête SQL avec paramètres
     * @param string $sql - Requête SQL
     * @param array $params - Paramètres de la requête
     * @return array|bool - Résultats ou true/false
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            // Si c'est un SELECT, retourner les résultats
            if (stripos(trim($sql), 'SELECT') === 0 || stripos(trim($sql), 'SHOW') === 0) {
                return $stmt->fetchAll();
            }

            // Sinon retourner true
            return true;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            throw new Exception("Query failed: " . $this->error);
        }
    }

    /**
     * Obtenir le dernier ID inséré
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * Commencer une transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Valider une transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Annuler une transaction
     */
    public function rollback() {
    // Vérifier qu'une transaction est active avant de faire rollback
    if ($this->pdo->inTransaction()) {
        return $this->pdo->rollBack();
    }
    return false;
}

    /**
     * Obtenir l'objet PDO (si besoin d'accès direct)
     */
    public function getPDO() {
        return $this->pdo;
    }

    /**
     * Obtenir la dernière erreur
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Fermer la connexion
     */
    public function close() {
        $this->pdo = null;
    }

    /**
     * Vérifier si une table existe
     */
    public function tableExists($tableName) {
        try {
            $result = $this->pdo->query("SHOW TABLES LIKE '{$tableName}'");
            return $result->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Exécuter une requête simple sans retour de résultats
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            throw new Exception("Execute failed: " . $this->error);
        }
    }

    /**
     * Récupérer une seule ligne
     */
    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            throw new Exception("Fetch failed: " . $this->error);
        }
    }

    /**
     * Compter le nombre de lignes
     */
    public function count($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            throw new Exception("Count failed: " . $this->error);
        }
    }
}
?>