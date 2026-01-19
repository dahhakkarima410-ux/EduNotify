USE projet_absences-2025;


CREATE TABLE IF NOT EXISTS templates_notification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    type ENUM('email', 'whatsapp') NOT NULL DEFAULT 'email',
    sujet VARCHAR(255) NULL COMMENT 'Sujet pour les emails',
    contenu TEXT NOT NULL COMMENT 'Contenu avec variables {nom_etudiant}, etc.',
    actif TINYINT(1) DEFAULT 1,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type (type),
    INDEX idx_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS historique_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    absence_id INT NULL COMMENT 'ID de l''absence concernée',
    etudiant_id INT NULL COMMENT 'ID de l''étudiant',
    type_notification ENUM('email', 'whatsapp') NOT NULL,
    destinataire VARCHAR(255) NOT NULL COMMENT 'Email ou numéro de téléphone',
    statut ENUM('en_attente', 'envoye', 'delivre', 'lu', 'echec') DEFAULT 'en_attente',
    message_sid VARCHAR(100) NULL COMMENT 'SID Twilio pour WhatsApp',
    message_erreur TEXT NULL COMMENT 'Message d''erreur si échec',
    tentatives INT DEFAULT 1 COMMENT 'Nombre de tentatives d''envoi',
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_mise_a_jour TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_absence (absence_id),
    INDEX idx_etudiant (etudiant_id),
    INDEX idx_type (type_notification),
    INDEX idx_statut (statut),
    INDEX idx_date (date_envoi),
    
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE IF NOT EXISTS file_attente_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    absence_id INT NOT NULL,
    table_absence VARCHAR(50) NOT NULL COMMENT 'Nom de la table mensuelle',
    type_notification ENUM('email', 'whatsapp', 'both') NOT NULL DEFAULT 'email',
    template_id INT NULL,
    priorite TINYINT DEFAULT 5 COMMENT '1=haute, 10=basse',
    statut ENUM('en_attente', 'en_cours', 'termine', 'echec') DEFAULT 'en_attente',
    date_programmee TIMESTAMP NULL COMMENT 'Date d''envoi programmé',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_traitement TIMESTAMP NULL,
    
    INDEX idx_statut (statut),
    INDEX idx_date_prog (date_programmee),
    INDEX idx_priorite (priorite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS config_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cle VARCHAR(50) NOT NULL UNIQUE,
    valeur TEXT NOT NULL,
    description VARCHAR(255) NULL,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_cle (cle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET @table_name = 'absences_template';
SET @column_name = 'notifie';
SET @column_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = @table_name 
    AND COLUMN_NAME = @column_name
);


INSERT INTO templates_notification (nom, type, sujet, contenu) VALUES
(
    'Email Standard Absence',
    'email',
    'Notification d''absence - {prenom_etudiant} {nom_etudiant}',
    '<!DOCTYPE html>
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
            <h1> Notification d''Absence</h1>
        </div>
        <div class="content">
            <p>Bonjour {nom_parent},</p>
            <p>Nous vous informons que votre enfant a été absent :</p>
            <div class="info-box">
                <p><strong> Élève :</strong> {prenom_etudiant} {nom_etudiant}</p>
                <p><strong> Classe :</strong> {classe}</p>
                <p><strong> Date :</strong> {date_absence}</p>
                <p><strong> Horaires :</strong> {heure_debut} - {heure_fin}</p>
                <p><strong> Matière :</strong> {matiere}</p>
            </div>
            <p>Si cette absence est justifiée, merci de nous transmettre un justificatif.</p>
            <p>Cordialement,<br><strong>L''équipe pédagogique</strong></p>
        </div>
        <div class="footer">
            <p>Message envoyé le {date_envoi}</p>
        </div>
    </div>
</body>
</html>'
),
(
    'WhatsApp Standard Absence',
    'whatsapp',
    NULL,
    ' *NOTIFICATION D''ABSENCE*

Bonjour {nom_parent},

Votre enfant *{prenom_etudiant} {nom_etudiant}* ({classe}) a été absent(e).

 Date : {date_absence}
 Horaires : {heure_debut} - {heure_fin}
 Matière : {matiere}

Merci de transmettre un justificatif si nécessaire.

_Envoyé le {date_envoi}_'
),
(
    'Email Rappel Justificatif',
    'email',
    'Rappel : Justificatif d''absence attendu - {prenom_etudiant} {nom_etudiant}',
    '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif;">
    <h2> Rappel - Justificatif attendu</h2>
    <p>Bonjour {nom_parent},</p>
    <p>Nous n''avons pas encore reçu de justificatif pour l''absence de votre enfant 
    <strong>{prenom_etudiant} {nom_etudiant}</strong> ({classe}) du <strong>{date_absence}</strong>.</p>
    <p>Merci de nous le transmettre dans les plus brefs délais.</p>
    <p>Cordialement,<br>L''équipe pédagogique</p>
</body>
</html>'
);


INSERT INTO config_notifications (cle, valeur, description) VALUES
('nom_etablissement', 'Mon Établissement', 'Nom de l''établissement affiché dans les messages'),
('email_expediteur', 'noreply@ecole.fr', 'Email affiché comme expéditeur'),
('delai_rappel_jours', '3', 'Nombre de jours avant envoi d''un rappel'),
('max_tentatives', '3', 'Nombre maximum de tentatives d''envoi')
ON DUPLICATE KEY UPDATE valeur = VALUES(valeur);

