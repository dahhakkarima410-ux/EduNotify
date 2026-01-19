<?php
// classes/MappingManager.php

class MappingManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Sauvegarder une configuration
    public function sauvegarder($nom, $mapping) {
        // Nettoyer le mapping pour ne garder que les associations valides
        $cleanMapping = array_filter($mapping, function($val) {
            return !empty($val);
        });

        $sql = "INSERT INTO configurations_import (nom, mapping_data) VALUES (:nom, :data)";
        return $this->db->query($sql, [
            'nom' => $nom,
            'data' => json_encode($cleanMapping)
        ]);
    }

    // Récupérer la liste des configurations
    public function getListe() {
        return $this->db->query("SELECT id, nom, date_creation FROM configurations_import ORDER BY date_creation DESC");
    }

    // Charger une configuration spécifique
    public function charger($id) {
        $sql = "SELECT mapping_data FROM configurations_import WHERE id = :id";
        $result = $this->db->query($sql, ['id' => $id]);
        
        if (!empty($result)) {
            return json_decode($result[0]['mapping_data'], true);
        }
        return null;
    }
}
?>