<?php
// admin/process_import.php
session_start();

require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/CSVParser.php';
require_once '../classes/AbsenceManager.php';

// Vérifier qu'on a bien des données en session
if (!isset($_SESSION['csv_import'])) {
    $_SESSION['upload_error'] = "Session expirée. Veuillez recommencer l'import.";
    header('Location: import_csv.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: import_csv.php');
    exit;
}

$db = new Database();
$absenceManager = new AbsenceManager($db);
$csvData = $_SESSION['csv_import'];

// Récupérer le mapping depuis le formulaire
$mapping = $_POST['mapping'] ?? [];

// Créer un mapping inversé (champ_système => index_colonne_csv)
$mappingInverse = [];
foreach ($mapping as $indexColonne => $champSysteme) {
    if (!empty($champSysteme)) {
        $mappingInverse[$champSysteme] = $indexColonne;
    }
}

// Vérifier que les champs obligatoires sont présents
$champsRequis = ['nom_etudiant', 'prenom_etudiant', 'date_absence'];
$champsManquants = array_diff($champsRequis, array_keys($mappingInverse));

if (!empty($champsManquants)) {
    $_SESSION['upload_error'] = "Champs obligatoires manquants : " . implode(', ', $champsManquants);
    header('Location: preview_csv.php');
    exit;
}

try {
    // Parser complètement le fichier CSV
    $parser = new CSVParser();
    if (!$parser->parse($csvData['filepath'])) {
        throw new Exception("Erreur lors de la lecture du fichier CSV");
    }

    $allData = $parser->getData();
    $headers = $parser->getHeaders();

    // Extraire le mois et l'année de la cible
    list($annee, $mois) = explode('-', $csvData['mois_cible']);
    
    // Créer la table mensuelle si elle n'existe pas
    $tableInfo = $absenceManager->creerTableMensuelle((int)$mois, (int)$annee);
    $nomTable = $tableInfo['nom_table'];

    // Commencer une transaction
    $db->beginTransaction();

    // Créer un enregistrement d'import dans la table imports
    $sqlImport = "INSERT INTO imports (
                    nom_fichier, taille_fichier, mois_cible,
                    lignes_total, utilisateur_id
                  ) VALUES (
                    :nom_fichier, :taille, :mois_cible,
                    :lignes_total, :user_id
                  )";
    
    $db->query($sqlImport, [
        'nom_fichier' => $csvData['filename'],
        'taille' => filesize($csvData['filepath']),
        'mois_cible' => $csvData['mois_cible'],
        'lignes_total' => count($allData),
        'user_id' => $_SESSION['user_id']
    ]);

    $importId = $db->lastInsertId();

    // Compteurs pour le rapport
    $stats = [
        'total' => count($allData),
        'importees' => 0,
        'erreurs' => 0,
        'etudiants_crees' => 0,
        'absences_creees' => 0,
        'rapportErreurs' => []
    ];

    // Traiter chaque ligne
    foreach ($allData as $numeroLigne => $ligne) {
        try {
            // Extraire les données selon le mapping
            $dataEtudiant = [
                'nom' => trim($ligne[$headers[$mappingInverse['nom_etudiant']]] ?? ''),
                'prenom' => trim($ligne[$headers[$mappingInverse['prenom_etudiant']]] ?? ''),
                'classe' => trim($ligne[$headers[$mappingInverse['classe']]] ?? '') ?: 'Non spécifiée',
                'email_parent' => isset($mappingInverse['email_parent']) 
                    ? trim($ligne[$headers[$mappingInverse['email_parent']]] ?? '') 
                    : null,
                'telephone_parent' => isset($mappingInverse['telephone_parent']) 
                    ? trim($ligne[$headers[$mappingInverse['telephone_parent']]] ?? '') 
                    : null,
                'nom_parent' => isset($mappingInverse['nom_parent']) 
                    ? trim($ligne[$headers[$mappingInverse['nom_parent']]] ?? '') 
                    : null,
            ];

            $dataAbsence = [
                'date_absence' => trim($ligne[$headers[$mappingInverse['date_absence']]] ?? ''),
                'heure_debut' => isset($mappingInverse['heure_debut']) 
                    ? trim($ligne[$headers[$mappingInverse['heure_debut']]] ?? '') 
                    : null,
                'heure_fin' => isset($mappingInverse['heure_fin']) 
                    ? trim($ligne[$headers[$mappingInverse['heure_fin']]] ?? '') 
                    : null,
                'matiere' => isset($mappingInverse['matiere']) 
                    ? trim($ligne[$headers[$mappingInverse['matiere']]] ?? '') 
                    : null,
                'motif' => isset($mappingInverse['motif']) 
                    ? trim($ligne[$headers[$mappingInverse['motif']]] ?? '') 
                    : null,
                'justifie' => isset($mappingInverse['justifie']) 
                    ? trim($ligne[$headers[$mappingInverse['justifie']]] ?? '') 
                    : null,   
                'document_justificatif' => isset($mappingInverse['document_justificatif']) 
                    ? trim($ligne[$headers[$mappingInverse['document_justificatif']]] ?? '') 
                    : null,     
            ];

            // Validation basique
            if (empty($dataEtudiant['nom']) || empty($dataEtudiant['prenom'])) {
                throw new Exception("Nom ou prénom manquant");
            }

            if (empty($dataAbsence['date_absence'])) {
                throw new Exception("Date d'absence manquante");
            }

            // Obtenir ou créer l'étudiant
            $etudiantId = $absenceManager->obtenirOuCreerEtudiant($dataEtudiant);
            
            if ($etudiantId) {
                $stats['etudiants_crees']++;
            }

            // Importer l'absence
            $resultat = $absenceManager->importerAbsence(
                $nomTable,
                $etudiantId,
                $dataAbsence,
                $importId
            );

            if ($resultat) {
                $stats['importees']++;
                $stats['absences_creees']++;
            } else {
                $stats['erreurs']++;
                $stats['rapportErreurs'][] = [
                    'ligne' => $numeroLigne + 2, // +2 car ligne 1 = headers
                    'erreur' => 'Échec insertion absence'
                ];
            }

        } catch (Exception $e) {
            $stats['erreurs']++;
            $stats['rapportErreurs'][] = [
                'ligne' => $numeroLigne + 2,
                'erreur' => $e->getMessage()
            ];
        }
    }

    // Mettre à jour l'enregistrement d'import
    $sqlUpdate = "UPDATE imports SET
                    lignes_importees = :importees,
                    lignes_erreur = :erreurs,
                    statut = 'termine',
                    rapport_erreurs = :rapport
                  WHERE id = :import_id";
    
    $db->query($sqlUpdate, [
        'importees' => $stats['importees'],
        'erreurs' => $stats['erreurs'],
        'rapport' => json_encode($stats['rapportErreurs']),
        'import_id' => $importId
    ]);

    // Valider la transaction
    $db->commit();

    // Sauvegarder les stats pour la page de résultat
    $_SESSION['import_result'] = $stats;
    $_SESSION['import_table'] = $nomTable;

    // Nettoyer la session
    unset($_SESSION['csv_import']);

    // Rediriger vers la page de résultat
    header('Location: import_success.php');
    exit;

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur (seulement si une transaction est active)
    try {
        $db->rollback();
    } catch (Exception $rollbackError) {
        // Ignorer l'erreur de rollback si pas de transaction active
    }
    
    $_SESSION['upload_error'] = "Erreur lors de l'import : " . $e->getMessage();
    header('Location: preview_csv.php');
    exit;
}
?>