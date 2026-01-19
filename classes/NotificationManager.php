<?php
// classes/NotificationManager.php

// 👇 C'EST ICI LA CLÉ DU SUCCÈS : On charge la config Gmail 👇
require_once __DIR__ . '/../config/notification_config.php'; 

class NotificationManager {
    
    private $db;
    private $pdo;
    private $emailSender;
    private $whatsappSender;
    private $templateManager;
    private $config = []; 
    
    public function __construct($database) {
        $this->db = $database;
        // On récupère l'instance PDO (si votre classe Database a cette méthode getPDO)
        // Sinon on suppose que $database est déjà l'objet PDO ou gère les query
        if (method_exists($database, 'getPDO')) {
            $this->pdo = $database->getPDO();
        } else {
            // Fallback si Database n'a pas getPDO, on espère que c'est compatible
            $this->pdo = $database; 
        }
        
        $this->templateManager = new TemplateManager($database);
        $this->chargerConfiguration();
    }

    // Récupère les clés API et paramètres depuis la table 'configurations'
    private function chargerConfiguration() {
        try {
            // Vérification simple pour éviter les erreurs si la table n'existe pas encore
            $stmt = $this->db->query("SHOW TABLES LIKE 'configurations'");
            if (!empty($stmt)) {
                $req = $this->db->query("SELECT cle, valeur FROM configurations");
                // Adaptation selon le format de retour de votre classe Database
                foreach ($req as $row) {
                    $this->config[$row['cle']] = $row['valeur'];
                }
            }
        } catch (Exception $e) {
            // On ignore silencieusement
        }
    }
    
    public function initEmail() {
        if ($this->emailSender === null) {
            $this->emailSender = new EmailSender();
            // L'EmailSender va maintenant utiliser les constantes de notification_config.php (SMTP_HOST...)
            // car le fichier a été inclus tout en haut.
        }
        return $this->emailSender;
    }
    
    public function initWhatsApp() {
        if ($this->whatsappSender === null) {
            $this->whatsappSender = new WhatsAppSender();
        }
        return $this->whatsappSender;
    }
    
    public function envoyerNotification($absenceId, $nomTable, $type = 'email') {
        $donnees = $this->getAbsenceAvecEtudiant($absenceId, $nomTable);
        
        if (!$donnees) {
            return ['success' => false, 'message' => "Absence introuvable (ID: $absenceId)"];
        }
        
        $resultats = ['email' => null, 'whatsapp' => null];
        
        if ($type === 'email' || $type === 'both') {
            if (!empty($donnees['email_parent'])) {
                $resultats['email'] = $this->envoyerEmail($donnees);
            } else {
                $resultats['email'] = ['success' => false, 'message' => 'Pas d\'email parent'];
            }
        }
        
        if ($type === 'whatsapp' || $type === 'both') {
            if (!empty($donnees['telephone_parent'])) {
                $resultats['whatsapp'] = $this->envoyerWhatsApp($donnees);
            } else {
                $resultats['whatsapp'] = ['success' => false, 'message' => 'Pas de téléphone parent'];
            }
        }
        
        // Mise à jour du statut (coche verte)
        $succesGlobal = ($resultats['email']['success'] ?? false) || ($resultats['whatsapp']['success'] ?? false);
        if ($succesGlobal) {
            $this->mettreAJourStatutAbsence($absenceId, $nomTable, true);
        }
        
        return [
            'success' => $succesGlobal,
            'resultats' => $resultats
        ];
    }
    
    private function envoyerEmail($donnees) {
        $this->initEmail();
        // Récupération sécurisée du template
        try {
            $template = $this->templateManager->getTemplateEmailDefaut();
        } catch (Exception $e) {
            $template = ['sujet' => 'Absence', 'contenu' => 'Votre enfant est absent.'];
        }

        $variables = $this->preparerVariables($donnees);
        
        $sujet = $this->templateManager->remplacerVariables($template['sujet'], $variables);
        $contenu = $this->templateManager->remplacerVariables($template['contenu'], $variables);
        
        return $this->emailSender->envoyer($donnees['email_parent'], $sujet, $contenu);
    }
    
    private function envoyerWhatsApp($donnees) {
        $this->initWhatsApp();
        try {
            $template = $this->templateManager->getTemplateWhatsAppDefaut();
        } catch (Exception $e) {
             $template = ['contenu' => 'Votre enfant est absent.'];
        }

        $variables = $this->preparerVariables($donnees);
        $message = $this->templateManager->remplacerVariables($template['contenu'], $variables);
        
        return $this->whatsappSender->envoyer($donnees['telephone_parent'], $message);
    }
    
    private function getAbsenceAvecEtudiant($absenceId, $nomTable) {
        $nomTable = preg_replace('/[^a-zA-Z0-9_]/', '', $nomTable);
        
        $sql = "SELECT a.*, 
                       e.nom as nom_etudiant, 
                       e.prenom as prenom_etudiant,
                       e.classe,
                       e.email_parent,
                       e.telephone_parent,
                       e.nom_parent
                FROM `{$nomTable}` a
                JOIN etudiants e ON a.etudiant_id = e.id
                WHERE a.id = :id";
        
        $result = $this->db->query($sql, [':id' => $absenceId]);
        return !empty($result) ? $result[0] : null;
    }
    
    private function preparerVariables($donnees) {
        return [
            'nom_etudiant' => $donnees['nom_etudiant'] ?? '',
            'prenom_etudiant' => $donnees['prenom_etudiant'] ?? '',
            'classe' => $donnees['classe'] ?? '',
            'date_absence' => $donnees['date_absence'] ?? '',
            'heure_debut' => $donnees['heure_debut'] ?? 'Non précisé',
            'heure_fin' => $donnees['heure_fin'] ?? 'Non précisé',
            'matiere' => $donnees['matiere'] ?? 'Non précisée',
            'nom_parent' => $donnees['nom_parent'] ?? 'Madame, Monsieur',
            'nom_etablissement' => $this->config['app_name'] ?? 'EduNotify'
        ];
    }
    
    private function mettreAJourStatutAbsence($absenceId, $nomTable, $statut) {
        $nomTable = preg_replace('/[^a-zA-Z0-9_]/', '', $nomTable);
        try {
            $sql = "UPDATE `{$nomTable}` SET notifie = :notifie WHERE id = :id";
            $this->db->query($sql, [':notifie' => $statut ? 1 : 0, ':id' => $absenceId]);
        } catch (Exception $e) {
            // Erreur SQL silencieuse
        }
    }
}
?>