<?php
// classes/EmailSender.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    
    private $mailer; // C'est le nom correct défini ici
    private $lastError = '';
    
    public function __construct() {
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            throw new Exception("PHPMailer n'est pas installé. Exécute : composer require phpmailer/phpmailer");
        }
        
        $this->mailer = new PHPMailer(true);
        $this->configurerSMTP();
    }

    /**
     * Configure le mailer dynamiquement avec les paramètres de la BDD
     */
    public function setConfiguration($config) {
        // CORRECTION ICI : On utilise $this->mailer (et pas $this->mail)
        if (isset($config['host'])) $this->mailer->Host = $config['host'];
        if (isset($config['username'])) $this->mailer->Username = $config['username'];
        if (isset($config['password'])) $this->mailer->Password = $config['password'];
        if (isset($config['port'])) $this->mailer->Port = $config['port'];
    }
    
    private function configurerSMTP() {
        // Configuration par défaut (constantes)
        $this->mailer->isSMTP();
        // On vérifie si les constantes existent avant de les utiliser pour éviter les erreurs
        $this->mailer->Host = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
        $this->mailer->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        $this->mailer->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->setFrom(
            defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@ecole.com', 
            defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'EduNotify'
        );
        $this->mailer->CharSet = 'UTF-8';
    }
    
    public function envoyer($destinataire, $sujet, $contenuHTML, $contenuTexte = '') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($destinataire);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $sujet;
            $this->mailer->Body = $contenuHTML;
            $this->mailer->AltBody = $contenuTexte ?: strip_tags($contenuHTML);
            $this->mailer->send();
            
            return [
                'success' => true,
                'message' => "Email envoyé avec succès à $destinataire"
            ];
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return [
                'success' => false,
                'message' => "Échec d'envoi: " . $this->mailer->ErrorInfo
            ];
        }
    }
    
    public function testerConnexion() {
        try {
            $smtp = new SMTP();
            // On utilise les valeurs actuelles de l'objet mailer
            $smtp->connect($this->mailer->Host, $this->mailer->Port);
            $smtp->hello('localhost');
            $smtp->quit();
            return ['success' => true, 'message' => "Connexion SMTP réussie !"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Échec connexion: " . $e->getMessage()];
        }
    }
    
    public function getLastError() {
        return $this->lastError;
    }
}
?>