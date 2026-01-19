<?php
// classes/CSVParser.php

class CSVParser {
    private $filePath;
    private $delimiter;
    private $encoding;
    private $headers = [];
    private $data = [];
    private $errors = [];

    public function __construct($filePath = null) {
        if ($filePath !== null) {
            $this->filePath = $filePath;
        }
    }

    /**
     * Supprimer le BOM UTF-8
     */
    private function removeBOM($text) {
        $bom = pack('H*','EFBBBF');
        return preg_replace("/^$bom/", '', $text);
    }

    /**
     * Détecter le délimiteur
     */
    private function detectDelimiter($line) {
        $delimiters = [';' => 0, ',' => 0, "\t" => 0, '|' => 0];
        foreach ($delimiters as $delim => &$count) {
            $count = substr_count($line, $delim);
        }
        $best = array_search(max($delimiters), $delimiters);
        return ($delimiters[$best] > 0) ? $best : ',';
    }

    /**
     * Parser le fichier CSV
     */
    public function parse($filePath = null, $maxRows = 0) {
        if ($filePath !== null) $this->filePath = $filePath;

        if (!file_exists($this->filePath)) {
            $this->errors[] = "Fichier introuvable.";
            return false;
        }

        // Lire tout le contenu
        $content = file_get_contents($this->filePath);
        
        // 1. Nettoyage BOM et Encodage
        $content = $this->removeBOM($content);
        
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        $this->encoding = 'UTF-8';

        // 2. Découpage en lignes
        $lines = explode("\n", $content);
        
        // Détecter le délimiteur sur la première ligne non vide
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $this->delimiter = $this->detectDelimiter($line);
                break;
            }
        }

        $this->data = [];
        $this->headers = [];
        $rowNumber = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Enlever les guillemets qui entourent toute la ligne (cas Excel fréquent)
            if (substr($line, 0, 1) === '"' && substr($line, -1) === '"') {
                $line = substr($line, 1, -1);
            }

            // Parser la ligne
            $row = str_getcsv($line, $this->delimiter);

            // Nettoyer les valeurs
            $row = array_map(function($val) {
                return trim(trim($val), "\"'"); 
            }, $row);

            // GESTION DES EN-TÊTES
            if ($rowNumber === 0) {
                $row[0] = $this->removeBOM($row[0]);
                $this->headers = $row;
                $rowNumber++;
                continue;
            }

            // VERIFICATION ET RÉPARATION INTELLIGENTE
            if (count($row) !== count($this->headers)) {
                // Tentative de réparation : Si on utilise ';', peut-être qu'il y a des ',' par erreur ?
                if ($this->delimiter === ';') {
                    // On essaie de remplacer les virgules par des points-virgules
                    $lineReparee = str_replace(',', ';', $line);
                    $rowReparee = str_getcsv($lineReparee, ';');
                    $rowReparee = array_map(function($val) { return trim(trim($val), "\"'"); }, $rowReparee);

                    if (count($rowReparee) === count($this->headers)) {
                        $row = $rowReparee; // Ça a marché ! On garde la version réparée
                    }
                }
                // Tentative inverse (si on est en ',' et qu'il y a des ';')
                elseif ($this->delimiter === ',') {
                    $lineReparee = str_replace(';', ',', $line);
                    $rowReparee = str_getcsv($lineReparee, ',');
                    $rowReparee = array_map(function($val) { return trim(trim($val), "\"'"); }, $rowReparee);

                    if (count($rowReparee) === count($this->headers)) {
                        $row = $rowReparee;
                    }
                }
            }

            // Si le compte est bon (naturellement ou après réparation), on ajoute
            if (count($row) === count($this->headers)) {
                $item = [];
                foreach ($this->headers as $index => $header) {
                    $item[$header] = $row[$index];
                }
                $this->data[] = $item;
            } else {
                // Si toujours pas bon, on note une erreur mais on ne plante pas
                $this->errors[] = "Ligne ignorée (colonnes incorrectes) : " . htmlspecialchars($line);
            }

            $rowNumber++;
            if ($maxRows > 0 && $rowNumber > $maxRows) break;
        }

        return true;
    }

    // Getters
    public function getHeaders() { return $this->headers; }
    public function getData() { return $this->data; }
    public function getEncoding() { return $this->encoding; }
    public function getDelimiter() { return $this->delimiter; }
    public function getErrors() { return $this->errors; }
    public function getPreview($n = 10) { return array_slice($this->data, 0, $n); }
}
?>