<?php
// classes/WhatsAppSender.php

require_once __DIR__ . '/../vendor/autoload.php';

// Chargement de la config si elle n'est pas déjà chargée
if (file_exists(__DIR__ . '/../config/notification_config.php')) {
    require_once __DIR__ . '/../config/notification_config.php';
}

use Twilio\Rest\Client;

class WhatsAppSender {
    
    private $sid;
    private $token;
    private $from;
    private $client;

    public function __construct() {
        // Lecture directe des constantes
        $this->sid   = defined('TWILIO_ACCOUNT_SID') ? TWILIO_ACCOUNT_SID : '';
        $this->token = defined('TWILIO_AUTH_TOKEN') ? TWILIO_AUTH_TOKEN : '';
        $this->from  = defined('TWILIO_WHATSAPP_FROM') ? TWILIO_WHATSAPP_FROM : '';
    }

    public function envoyer($to, $message) {
        if (empty($this->sid) || empty($this->token)) {
            return ['success' => false, 'message' => 'Erreur: Clés Twilio absentes dans la config.'];
        }

        // Nettoyage du numéro
        $to = $this->formaterNumero($to);

        try {
            if ($this->client === null) {
                $this->client = new Client($this->sid, $this->token);
            }

            // Vérification du "From"
            $fromNumber = $this->from;
            if (strpos($fromNumber, 'whatsapp:') !== 0) {
                $fromNumber = 'whatsapp:' . $fromNumber;
            }

            // Envoi
            $this->client->messages->create(
                $to,
                [
                    'from' => $fromNumber,
                    'body' => $message
                ]
            );

            return ['success' => true, 'message' => 'Message WhatsApp envoyé !'];

        } catch (Exception $e) {
            // L'erreur Twilio précise s'affichera ici (ex: "Not a valid mobile number")
            return ['success' => false, 'message' => 'Erreur Twilio : ' . $e->getMessage()];
        }
    }

    private function formaterNumero($numero) {
        // On garde uniquement les chiffres et le +
        $numero = preg_replace('/[^0-9+]/', '', $numero);
        
        // Si c'est un numéro marocain local (06/07...), on met +212
        if (preg_match('/^0[567]/', $numero)) {
            $numero = '+212' . substr($numero, 1);
        }

        // Ajout du préfixe whatsapp:
        if (strpos($numero, 'whatsapp:') !== 0) {
            $numero = 'whatsapp:' . $numero;
        }
        return $numero;
    }
}
?>