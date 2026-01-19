<?php
// classes/CSVUploader.php

class CSVUploader {
    private $uploadDir;
    private $maxSize = 10485760; // 10 Mo en octets
    private $allowedExtensions = ['csv'];
    private $errors = [];

    public function __construct($uploadDir = '../uploads/csv/') {
        $this->uploadDir = $uploadDir;
        
        // Créer le dossier s'il n'existe pas
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload un fichier CSV
     * @param string $fileInputName - Nom du champ input file
     * @return array - Résultat de l'upload
     */
    public function upload($fileInputName) {
        // Vérifier que le fichier existe dans $_FILES
        if (!isset($_FILES[$fileInputName])) {
            return [
                'success' => false,
                'error' => 'Aucun fichier n\'a été uploadé'
            ];
        }

        $file = $_FILES[$fileInputName];

        // Vérifier les erreurs d'upload PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => $this->getUploadErrorMessage($file['error'])
            ];
        }

        // Vérifier la taille
        if ($file['size'] > $this->maxSize) {
            return [
                'success' => false,
                'error' => 'Le fichier est trop volumineux (max 10 Mo)'
            ];
        }

        // Vérifier que le fichier n'est pas vide
        if ($file['size'] === 0) {
            return [
                'success' => false,
                'error' => 'Le fichier est vide'
            ];
        }

        // Vérifier l'extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return [
                'success' => false,
                'error' => 'Extension non autorisée. Seuls les fichiers .csv sont acceptés'
            ];
        }

        // Vérification basique du contenu
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            return [
                'success' => false,
                'error' => 'Impossible de lire le fichier'
            ];
        }

        // Lire la première ligne pour vérifier que c'est du texte
        $firstLine = fgets($handle);
        fclose($handle);

        if ($firstLine === false) {
            return [
                'success' => false,
                'error' => 'Le fichier semble être vide ou corrompu'
            ];
        }

        // Vérifier que le fichier contient des séparateurs CSV
        $hasComma = strpos($firstLine, ',') !== false;
        $hasSemicolon = strpos($firstLine, ';') !== false;
        $hasTab = strpos($firstLine, "\t") !== false;

        if (!$hasComma && !$hasSemicolon && !$hasTab) {
            return [
                'success' => false,
                'error' => 'Le fichier ne semble pas être un CSV valide (aucun séparateur détecté)'
            ];
        }

        // Générer un nom de fichier unique
        $filename = $this->generateUniqueFilename($file['name']);
        $filepath = $this->uploadDir . $filename;

        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => false,
                'error' => 'Erreur lors du déplacement du fichier'
            ];
        }

        // Succès !
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => $file['size'],
            'original_name' => $file['name']
        ];
    }

    /**
     * Générer un nom de fichier unique
     */
    private function generateUniqueFilename($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Nettoyer le nom de fichier
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        
        // Limiter la longueur
        $basename = substr($basename, 0, 50);
        
        // Ajouter un timestamp
        $timestamp = date('YmdHis');
        $random = substr(md5(uniqid()), 0, 6);
        
        return $basename . '_' . $timestamp . '_' . $random . '.' . $extension;
    }

    /**
     * Obtenir le message d'erreur d'upload PHP
     */
    private function getUploadErrorMessage($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par le serveur',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale du formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement uploadé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été uploadé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté l\'upload'
        ];

        return $errors[$errorCode] ?? 'Erreur d\'upload inconnue';
    }

    /**
     * Supprimer un fichier uploadé
     */
    public function deleteFile($filename) {
        $filepath = $this->uploadDir . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }

    /**
     * Obtenir les erreurs
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Définir la taille max
     */
    public function setMaxSize($bytes) {
        $this->maxSize = $bytes;
    }

    /**
     * Obtenir la taille max
     */
    public function getMaxSize() {
        return $this->maxSize;
    }
}
?>
```

---

## 🧪 Tester maintenant

Après avoir remplacé le fichier, essayez à nouveau :
```
http://localhost/projet_absences/admin/import_csv.php