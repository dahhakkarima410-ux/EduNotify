<?php
// classes/AbsenceManager.php

class AbsenceManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Créer une table mensuelle d'absences
     * Format : absences_01_2025
     */
    public function creerTableMensuelle($mois, $annee) {
        if ($mois < 1 || $mois > 12) {
            throw new Exception("Mois invalide");
        }
        
        $moisFormate = str_pad($mois, 2, '0', STR_PAD_LEFT);
        $nomTable = "absences_{$moisFormate}_{$annee}";
        
        // 1. Vérification préalable
        if ($this->tableExiste($nomTable)) {
            return [
                'existe' => true,
                'nom_table' => $nomTable
            ];
        }
        
        // 2. Création sécurisée (IF NOT EXISTS) pour éviter le crash SQL 1050
        try {
            // On utilise IF NOT EXISTS pour ne pas générer d'erreur si la table vient d'être créée
            $sql = "CREATE TABLE IF NOT EXISTS `{$nomTable}` LIKE absences_template";
            $this->db->query($sql);
        } catch (Exception $e) {
            // Si une erreur survient, on vérifie si la table existe quand même
            if ($this->tableExiste($nomTable)) {
                return [
                    'existe' => true,
                    'nom_table' => $nomTable
                ];
            }
            throw new Exception("Erreur lors de la création de la table : " . $e->getMessage());
        }
        
        return [
            'existe' => false,
            'nom_table' => $nomTable,
            'creee' => true
        ];
    }

    /**
     * Vérifier si une table existe (Méthode Infaillible)
     */
    private function tableExiste($nomTable) {
        try {
            // On essaie de lire une seule ligne. 
            // Si la table n'existe pas, MySQL renvoie une erreur qui est attrapée par le catch.
            // C'est beaucoup plus fiable que SHOW TABLES.
            $this->db->query("SELECT 1 FROM `{$nomTable}` LIMIT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtenir ou créer un étudiant
     */
    public function obtenirOuCreerEtudiant($data) {
        $nom = trim($data['nom'] ?? '');
        $prenom = trim($data['prenom'] ?? '');
        $classe = trim($data['classe'] ?? '');
        
        if (empty($nom) || empty($prenom) || empty($classe)) {
            throw new Exception("Nom, prénom et classe sont obligatoires");
        }
        
        // Chercher si l'étudiant existe déjà
        $sql = "SELECT id FROM etudiants 
                WHERE nom = :nom AND prenom = :prenom AND classe = :classe 
                LIMIT 1";
        
        $result = $this->db->query($sql, [
            'nom' => $nom,
            'prenom' => $prenom,
            'classe' => $classe
        ]);
        
        if (!empty($result)) {
            // Mise à jour des infos parent si fournies
            $this->mettreAJourInfosParent($result[0]['id'], $data);
            return $result[0]['id'];
        }
        
        // Création nouvel étudiant
        $sql = "INSERT INTO etudiants (
                    nom, prenom, classe, email_parent, 
                    telephone_parent, whatsapp_parent, nom_parent, adresse
                ) VALUES (
                    :nom, :prenom, :classe, :email_parent,
                    :telephone_parent, :whatsapp_parent, :nom_parent, :adresse
                )";
        
        $params = [
            'nom' => $nom,
            'prenom' => $prenom,
            'classe' => $classe,
            'email_parent' => $data['email_parent'] ?? null,
            'telephone_parent' => $data['telephone_parent'] ?? null,
            'whatsapp_parent' => $data['whatsapp_parent'] ?? $data['telephone_parent'] ?? null,
            'nom_parent' => $data['nom_parent'] ?? null,
            'adresse' => $data['adresse'] ?? null
        ];
        
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }

    /**
     * Mettre à jour les informations du parent
     */
    private function mettreAJourInfosParent($etudiantId, $data) {
        $updates = [];
        $params = ['id' => $etudiantId];
        
        if (!empty($data['email_parent'])) {
            $updates[] = "email_parent = :email_parent";
            $params['email_parent'] = $data['email_parent'];
        }
        
        if (!empty($data['telephone_parent'])) {
            $updates[] = "telephone_parent = :telephone_parent";
            $params['telephone_parent'] = $data['telephone_parent'];
            
            if (empty($data['whatsapp_parent'])) {
                $updates[] = "whatsapp_parent = :whatsapp_parent";
                $params['whatsapp_parent'] = $data['telephone_parent'];
            }
        }
        
        if (!empty($data['nom_parent'])) {
            $updates[] = "nom_parent = :nom_parent";
            $params['nom_parent'] = $data['nom_parent'];
        }
        
        if (!empty($updates)) {
            $sql = "UPDATE etudiants SET " . implode(", ", $updates) . " WHERE id = :id";
            $this->db->query($sql, $params);
        }
    }

    /**
     * Importer une absence dans la table mensuelle
     */
    public function importerAbsence($nomTable, $etudiantId, $data, $importId) {
        // 1. Conversion et Validation de la date (CRITIQUE)
        $dateBrute = $data['date_absence'] ?? '';
        $dateSQL = $this->convertirDatePourSQL($dateBrute);
        
        if (!$this->estDateValide($dateSQL)) {
            throw new Exception("Date d'absence invalide : " . $dateBrute);
        }
        
        // 2. Gestion intelligente du champ 'justifie'
        $justifie = 0;
        if (isset($data['justifie'])) {
            $val = strtolower(trim($data['justifie']));
            if (in_array($val, ['1', 'yes', 'oui', 'true', 'vrai', 'ok', 'justifié', 'justifie'])) {
                $justifie = 1;
            }
        }

        // 3. Préparation des paramètres
        $params = [
            'etudiant_id' => $etudiantId,
            'date_absence' => $dateSQL,
            'heure_debut' => !empty($data['heure_debut']) ? $data['heure_debut'] : null,
            'heure_fin' => !empty($data['heure_fin']) ? $data['heure_fin'] : null,
            'matiere' => !empty($data['matiere']) ? $data['matiere'] : null,
            'type_absence' => $data['type_absence'] ?? 'absence',
            'justifie' => $justifie,
            'motif_justification' => $data['motif'] ?? null,
            'document_justificatif' => $data['document_justificatif'] ?? null,
            'import_id' => $importId
        ];
        
        // 4. Insertion SQL (avec protection doublon)
        $sql = "INSERT INTO `{$nomTable}` (
                    etudiant_id, date_absence, heure_debut, heure_fin,
                    matiere, type_absence, justifie, motif_justification, 
                    document_justificatif, import_id  
                ) VALUES (
                    :etudiant_id, :date_absence, :heure_debut, :heure_fin,
                    :matiere, :type_absence, :justifie, :motif_justification, 
                    :document_justificatif, :import_id
                )
                ON DUPLICATE KEY UPDATE
                    heure_fin = VALUES(heure_fin),
                    justifie = VALUES(justifie),
                    motif_justification = VALUES(motif_justification),
                    document_justificatif = VALUES(document_justificatif)";
        
        try {
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            error_log("Erreur import absence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Convertit une date (JJ/MM/AAAA ou JJ-MM-AAAA) en format SQL (AAAA-MM-JJ)
     */
    private function convertirDatePourSQL($date) {
        if (empty($date)) return null;

        // Si déjà format SQL Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        
        // Format JJ/MM/AAAA
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $date, $matches)) {
            return $matches[3] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        }
        
        return $date; // Retourne tel quel si non reconnu
    }

    /**
     * Vérifie si une date SQL est valide (ex: pas 2026-02-30)
     */
    private function estDateValide($date) {
        if (!$date) return false;
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Obtenir toutes les tables mensuelles existantes
     */
    public function getMoisDisponibles() {
        // Sécurisé même si tableExiste plante
        try {
            // Utilisation directe de la requête sans passer par tableExiste
            $tables = $this->db->query("SHOW TABLES LIKE 'absences_%'");
        } catch (Exception $e) {
            return [];
        }
        
        $mois = [];
        foreach ($tables as $table) {
            // Récupère la première colonne (nom de la table)
            $nomTable = array_values($table)[0];
            
            if ($nomTable === 'absences_template') continue;

            if (preg_match('/absences_(\d{2})_(\d{4})/', $nomTable, $matches)) {
                $mois[] = [
                    'table' => $nomTable,
                    'mois' => $matches[1],
                    'annee' => $matches[2],
                    'mois_annee' => $matches[2] . '-' . $matches[1],
                    'label' => $this->getMoisLabel($matches[1], $matches[2])
                ];
            }
        }
        
        usort($mois, function($a, $b) {
            return strcmp($b['mois_annee'], $a['mois_annee']);
        });
        
        return $mois;
    }

    private function getMoisLabel($mois, $annee) {
        $moisNoms = [
            '01' => 'Janvier', '02' => 'Février', '03' => 'Mars',
            '04' => 'Avril', '05' => 'Mai', '06' => 'Juin',
            '07' => 'Juillet', '08' => 'Août', '09' => 'Septembre',
            '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
        ];
        
        return ($moisNoms[$mois] ?? 'Mois ' . $mois) . ' ' . $annee;
    }

    public function getAbsencesMensuelle($nomTable, $filtres = []) {
        if (!$this->tableExiste($nomTable)) {
            throw new Exception("Table mensuelle introuvable");
        }
        
        $sql = "SELECT a.*, 
                       e.nom, e.prenom, e.classe,
                       e.email_parent, e.telephone_parent
                FROM `{$nomTable}` a
                JOIN etudiants e ON a.etudiant_id = e.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtres['classe'])) {
            $sql .= " AND e.classe = :classe";
            $params['classe'] = $filtres['classe'];
        }
        
        if (!empty($filtres['date_debut'])) {
            $sql .= " AND a.date_absence >= :date_debut";
            $params['date_debut'] = $filtres['date_debut'];
        }
        
        if (!empty($filtres['date_fin'])) {
            $sql .= " AND a.date_absence <= :date_fin";
            $params['date_fin'] = $filtres['date_fin'];
        }
        
        $sql .= " ORDER BY a.date_absence DESC, e.nom ASC";
        
        return $this->db->query($sql, $params);
    }

    public function getStatistiquesMensuelle($nomTable) {
        if (!$this->tableExiste($nomTable)) {
            return null;
        }
        
        $stats = [];
        
        // Total absences
        $sql = "SELECT COUNT(*) as total FROM `{$nomTable}`";
        $result = $this->db->query($sql);
        $stats['total_absences'] = $result[0]['total'] ?? 0;
        
        // Absences notifiées
        $sql = "SELECT COUNT(*) as notifie FROM `{$nomTable}` WHERE notifie = 1";
        $result = $this->db->query($sql);
        $stats['absences_notifiees'] = $result[0]['notifie'] ?? 0;
        
        // Étudiants uniques
        $sql = "SELECT COUNT(DISTINCT etudiant_id) as etudiants FROM `{$nomTable}`";
        $result = $this->db->query($sql);
        $stats['etudiants_concernes'] = $result[0]['etudiants'] ?? 0;
        
        // Top 5
        $sql = "SELECT e.nom, e.prenom, e.classe, COUNT(*) as nb_absences
                FROM `{$nomTable}` a
                JOIN etudiants e ON a.etudiant_id = e.id
                GROUP BY a.etudiant_id
                ORDER BY nb_absences DESC
                LIMIT 5";
        $stats['top_absents'] = $this->db->query($sql);
        
        return $stats;
    }
}
?>