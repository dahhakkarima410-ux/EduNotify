<?php
// classes/ColumnDetector.php

class ColumnDetector {
    // Dictionnaire enrichi pour éviter les conflits nom/prénom
    private $dictionnaire = [
        'nom_etudiant' => [
            'nom', 'name', 'lastname', 'family_name', 'nom_famille', 'nom_eleve', 
            'student_name', 'last_name', 'surname', 'eleve_nom', 'nom_etudiant'
        ],
        'prenom_etudiant' => [
            'prenom', 'prénom', 'firstname', 'first_name', 'prenom_eleve', 
            'given_name', 'forename', 'eleve_prenom', 'prenom_etudiant' // ✅ AJOUT CRUCIAL
        ],
        'email_parent' => [
            'email', 'mail', 'courriel', 'email_parent', 'parent_email', 
            'parent_mail', 'email_tuteur', 'adresse_email', 'e_mail'
        ],
        'telephone_parent' => [
            'tel', 'telephone', 'téléphone', 'phone', 'mobile', 'gsm', 
            'tel_parent', 'telephone_parent', 'numero', 'contact', 'cellulaire', 'parent_tel', 'tel_parent'
        ],
        'date_absence' => [
            'date', 'date_absence', 'absence_date', 'jour', 'day', 
            'date_abs', 'absent_le', 'date_jour', 'le'
        ],
        'heure_debut' => [
            'heure_debut', 'debut', 'start', 'start_time', 'time_start', 
            'heure_depart', 'h_debut', 'from', 'de'
        ],
        'heure_fin' => [
            'heure_fin', 'fin', 'end', 'end_time', 'time_end', 
            'heure_arrivee', 'h_fin', 'to', 'a', 'jusqua'
        ],
        'classe' => [
            'classe', 'class', 'niveau', 'groupe', 'group', 'filiere', 
            'section', 'promotion', 'division'
        ],
        'matiere' => [
            'matiere', 'matière', 'subject', 'cours', 'course', 
            'module', 'discipline', 'enseignement'
        ],
        'motif' => [
            'motif', 'raison', 'reason', 'justification', 'commentaire', 
            'observation', 'remarque', 'cause'
        ],
        'document_justificatif' => [
            'document', 'fichier', 'piece_jointe', 'justificatif', 'file', 
            'attachment', 'certificat', 'preuve', 'doc', 'pj','document_justificatif'
        ],
        'justifie' => [
            'justifie', 'justifié', 'est_justifie', 'statut', 'etat', 
            'validation', 'is_justified','status'
        ]
    ];

    // Normaliser un texte (minuscules, sans accents, sans caractères spéciaux)
    private function normaliser($texte) {
        $texte = mb_strtolower($texte, 'UTF-8');
        $texte = $this->supprimerAccents($texte);
        // On supprime tout ce qui n'est pas lettre ou chiffre pour coller les mots
        // Ex: "prenom_etudiant" devient "prenometudiant"
        $texte = preg_replace('/[^a-z0-9]/', '', $texte); 
        return $texte;
    }

    // Supprimer les accents
    private function supprimerAccents($texte) {
        $accents = [
            'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A',
            'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a',
            'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e',
            'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I',
            'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i',
            'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O',
            'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o',
            'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U',
            'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u',
            'Ç'=>'C', 'ç'=>'c', 'Ñ'=>'N', 'ñ'=>'n'
        ];
        return strtr($texte, $accents);
    }
    
    // Trouver la correspondance pour une colonne
    private function trouverCorrespondance($nomNormalise) {
        $meilleurMatch = ['champ' => null, 'score' => 0];

        foreach ($this->dictionnaire as $champ => $variantes) {
            foreach ($variantes as $variante) {
                // On normalise la variante pour comparer des pommes avec des pommes
                $varianteNorm = $this->normaliser($variante);
                
                // 1. MATCH EXACT (Priorité absolue 100%)
                if ($nomNormalise === $varianteNorm) {
                    return ['champ' => $champ, 'score' => 100];
                }
                
                // 2. CONTIENT (Score plus faible pour éviter les faux positifs comme preNOM)
                if (strpos($nomNormalise, $varianteNorm) !== false) {
                     // Si on trouve "nom" dans "prenom", on donne un score moyen
                     // Mais comme "prenom" matchera EXACTEMENT "prenom" plus tard (100%),
                     // le 100% écrasera ce score moyen.
                     $score = 80;
                     
                     // Pénalité spécifique : Si on cherche "nom" et que le mot est "prenom..."
                     if ($champ === 'nom_etudiant' && strpos($nomNormalise, 'prenom') !== false) {
                         $score = 10; // On ignore presque
                     }

                     if ($score > $meilleurMatch['score']) {
                        $meilleurMatch = ['champ' => $champ, 'score' => $score];
                    }
                }
                
                // 3. LEVENSHTEIN (Tolérance fautes de frappe)
                if (strlen($nomNormalise) > 3) {
                    $lev = levenshtein($nomNormalise, $varianteNorm);
                    if ($lev <= 2) { // 2 fautes max
                         if (70 > $meilleurMatch['score']) {
                            $meilleurMatch = ['champ' => $champ, 'score' => 70];
                        }
                    }
                }
            }
        }
        return $meilleurMatch;
    }

    // Détecter automatiquement toutes les colonnes
    public function detecter($colonnesCSV) {
        $mapping = [];
        foreach ($colonnesCSV as $index => $nomColonne) {
            $normalise = $this->normaliser($nomColonne);
            $resultat = $this->trouverCorrespondance($normalise);
            
            $mapping[$index] = [
                'colonne_csv' => $nomColonne,
                'colonne_csv_normalized' => $normalise,
                'champ_detecte' => $resultat['champ'],
                'confiance' => $resultat['score'],
                'statut' => $resultat['score'] >= 70 ? 'auto' : 'manuel'
            ];
        }
        return $mapping;
    }

    public function getChampsDisponibles() {
        return array_keys($this->dictionnaire);
    }

    public function validerMapping($mapping) {
        $champsRequis = ['nom_etudiant', 'prenom_etudiant', 'date_absence'];
        $champsPresents = [];
        foreach ($mapping as $item) {
            if ($item['champ_detecte']) {
                $champsPresents[] = $item['champ_detecte'];
            }
        }
        $champManquants = array_diff($champsRequis, $champsPresents);
        return [
            'valide' => empty($champManquants),
            'champs_manquants' => $champManquants
        ];
    }
}
?>