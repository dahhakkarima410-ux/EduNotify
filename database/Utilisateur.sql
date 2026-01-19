-- 1. Table pour gérer les comptes (Admin et Opérateur)
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operateur') DEFAULT 'operateur',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Création d'un compte ADMIN par défaut
-- Email : admin@ecole.com
-- Mot de passe : admin123
INSERT INTO utilisateurs (nom, email, mot_de_passe, role) 
VALUES ('Administrateur', 'admin@ecole.com', '$2y$10$8K1p/a.F7.P.K1p/a.F7.O.6N.8K1p/a.F7.P.K1p/a.F7.O.6N', 'admin')
ON DUPLICATE KEY UPDATE id=id;

-- 3. Table pour l'historique de sécurité (Qui a fait quoi ?)
CREATE TABLE IF NOT EXISTS logs_activite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_adresse VARCHAR(45),
    date_log TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;