<?php
// classes/StatistiquesManager.php

class StatistiquesManager {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Récupère toutes les statistiques en un seul appel pour le Dashboard
     */
    public function getStatistiquesGlobales($nomTable) {
        
        // 1. Vérification sécurisée si la table existe
        if (!$this->tableExiste($nomTable)) {
            return [
                'total_absences' => 0,
                'absences_notifiees' => 0,
                'etudiants_concernes' => 0,
                'top_absents' => []
            ];
        }

        $stats = [];

        // 2. Total des absences
        // On utilise try/catch au cas où
        try {
            $sql = "SELECT COUNT(*) as total FROM `{$nomTable}`";
            $res = $this->db->query($sql);
            // Gestion selon si query renvoie un tableau ou objet
            $stats['total_absences'] = $res[0]['total'] ?? 0;
        } catch (Exception $e) {
            $stats['total_absences'] = 0;
        }

        // 3. Absences notifiées
        try {
            $sql = "SELECT COUNT(*) as notifie FROM `{$nomTable}` WHERE notifie = 1";
            $res = $this->db->query($sql);
            $stats['absences_notifiees'] = $res[0]['notifie'] ?? 0;
        } catch (Exception $e) {
            $stats['absences_notifiees'] = 0;
        }

        // 4. Nombre d'étudiants uniques concernés
        try {
            $sql = "SELECT COUNT(DISTINCT etudiant_id) as etudiants FROM `{$nomTable}`";
            $res = $this->db->query($sql);
            $stats['etudiants_concernes'] = $res[0]['etudiants'] ?? 0;
        } catch (Exception $e) {
            $stats['etudiants_concernes'] = 0;
        }

        // 5. Top 5 des étudiants les plus absents
        try {
            $sql = "SELECT e.nom, e.prenom, e.classe, COUNT(*) as nb_absences
                    FROM `{$nomTable}` a
                    JOIN etudiants e ON a.etudiant_id = e.id
                    GROUP BY a.etudiant_id
                    ORDER BY nb_absences DESC
                    LIMIT 5";
            $stats['top_absents'] = $this->db->query($sql);
        } catch (Exception $e) {
            $stats['top_absents'] = [];
        }

        return $stats;
    }

    /**
     * Méthode robuste pour vérifier l'existence d'une table
     * (Ne dépend pas d'une méthode tableExists dans Database)
     */
    private function tableExiste($nomTable) {
        try {
            // On essaie de sélectionner 1 ligne. Si la table n'existe pas, ça plante et va dans le catch.
            $sql = "SELECT 1 FROM `{$nomTable}` LIMIT 1";
            $this->db->query($sql);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>