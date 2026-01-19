<?php

class TemplateManager {
    
    private $db;
    
    public function __construct($database = null) {
        $this->db = $database;
    }
    
    public function remplacerVariables($template, $donnees) {
        $donnees['date_envoi'] = date('d/m/Y à H:i');
        $donnees['nom_etablissement'] = $donnees['nom_etablissement'] ?? 'Notre Établissement';
        
        foreach ($donnees as $cle => $valeur) {
            $template = str_replace('{' . $cle . '}', $valeur ?? '', $template);
        }
        
        return $template;
    }
    
    public function getTemplateEmailDefaut() {
        return [
            'sujet' => 'Notification d\'absence - {prenom_etudiant} {nom_etudiant}',
            'contenu' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #667eea; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #667eea; }
        .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Notification d\'Absence</h1>
            <p>{nom_etablissement}</p>
        </div>
        <div class="content">
            <p>Bonjour <strong>{nom_parent}</strong>,</p>
            <p>Nous vous informons que votre enfant a été absent :</p>
            <div class="info-box">
                <p><strong> Élève :</strong> {prenom_etudiant} {nom_etudiant}</p>
                <p><strong> Classe :</strong> {classe}</p>
                <p><strong> Date :</strong> {date_absence}</p>
                <p><strong> Horaires :</strong> {heure_debut} - {heure_fin}</p>
                <p><strong> Matière :</strong> {matiere}</p>
            </div>
            <p>Si cette absence est justifiée, merci de nous transmettre un justificatif.</p>
            <p>Cordialement,<br><strong>L\'équipe pédagogique</strong></p>
        </div>
        <div class="footer">
            <p>Message envoyé le {date_envoi}</p>
        </div>
    </div>
</body>
</html>'
        ];
    }
    
    public function getTemplateWhatsAppDefaut() {
        return [
            'contenu' => ' *NOTIFICATION D\'ABSENCE*

Bonjour {nom_parent},

Votre enfant *{prenom_etudiant} {nom_etudiant}* ({classe}) a été absent(e).

 Date : {date_absence}
 Horaires : {heure_debut} - {heure_fin}
 Matière : {matiere}

Merci de transmettre un justificatif si nécessaire.

_Envoyé le {date_envoi}_'
        ];
    }
    
    public function previsualiser($templateContenu, $donneesExemple = []) {
        $exemplesDefaut = [
            'nom_etudiant' => 'DUPONT',
            'prenom_etudiant' => 'Jean',
            'classe' => '3ème A',
            'date_absence' => date('d/m/Y'),
            'heure_debut' => '08:00',
            'heure_fin' => '12:00',
            'matiere' => 'Mathématiques',
            'nom_parent' => 'M. DUPONT',
            'nom_etablissement' => 'Collège Victor Hugo'
        ];
        
        $donnees = array_merge($exemplesDefaut, $donneesExemple);
        return $this->remplacerVariables($templateContenu, $donnees);
    }
}
?>