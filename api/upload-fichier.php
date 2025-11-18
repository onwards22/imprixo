<?php
/**
 * API Upload Fichiers Impression - Imprixo
 * Upload sécurisé de fichiers pour impression
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// Configuration upload
define('UPLOAD_BASE_DIR', __DIR__ . '/../uploads/impressions/');
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'tif', 'tiff', 'psd', 'ai', 'eps', 'svg', 'zip']);
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png',
    'image/tiff',
    'application/pdf',
    'application/postscript',
    'image/svg+xml',
    'application/zip',
    'image/vnd.adobe.photoshop',
]);

// ============================================
// POST - Upload nouveau fichier
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Vérifier qu'un fichier est uploadé
    if (!isset($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'Aucun fichier uploadé ou erreur upload'], 400);
    }

    $file = $_FILES['fichier'];

    // Récupérer les métadonnées
    $panierId = isset($_POST['panier_id']) ? (int)$_POST['panier_id'] : null;
    $lignePanierId = isset($_POST['ligne_panier_id']) ? (int)$_POST['ligne_panier_id'] : null;
    $commandeId = isset($_POST['commande_id']) ? (int)$_POST['commande_id'] : null;

    // Validation 1: Taille
    if ($file['size'] > MAX_FILE_SIZE) {
        jsonResponse([
            'error' => 'Fichier trop volumineux',
            'max_size_mb' => MAX_FILE_SIZE / (1024 * 1024),
            'file_size_mb' => round($file['size'] / (1024 * 1024), 2)
        ], 400);
    }

    // Validation 2: Extension
    $nomOriginal = $file['name'];
    $extension = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));

    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        jsonResponse([
            'error' => 'Extension de fichier non autorisée',
            'extension' => $extension,
            'allowed' => ALLOWED_EXTENSIONS
        ], 400);
    }

    // Validation 3: MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        jsonResponse([
            'error' => 'Type de fichier non autorisé',
            'mime_type' => $mimeType,
            'allowed' => ALLOWED_MIME_TYPES
        ], 400);
    }

    // Générer nom unique
    $date = date('Y/m');
    $uploadDir = UPLOAD_BASE_DIR . $date . '/';

    // Créer le dossier si nécessaire
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $nomStockage = uniqid(date('Ymd_His_'), true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $nomOriginal);
    $cheminComplet = $uploadDir . $nomStockage;

    // Calculer les hash
    $hashMd5 = md5_file($file['tmp_name']);
    $hashSha256 = hash_file('sha256', $file['tmp_name']);

    // Vérifier si fichier déjà uploadé (par hash)
    $db = Database::getInstance();
    $existing = $db->fetchOne(
        "SELECT * FROM fichiers_impression WHERE hash_sha256 = ? AND statut != 'supprime'",
        [$hashSha256]
    );

    if ($existing) {
        // Fichier déjà uploadé, retourner l'existant
        jsonResponse([
            'success' => true,
            'message' => 'Fichier déjà uploadé précédemment',
            'fichier' => [
                'id' => $existing['id'],
                'nom_original' => $existing['nom_original'],
                'taille_octets' => $existing['taille_octets'],
                'taille_mb' => round($existing['taille_octets'] / (1024 * 1024), 2),
                'extension' => $existing['extension'],
                'statut' => $existing['statut'],
                'created_at' => $existing['created_at']
            ]
        ]);
    }

    // Déplacer le fichier uploadé
    if (!move_uploaded_file($file['tmp_name'], $cheminComplet)) {
        jsonResponse(['error' => 'Erreur lors du déplacement du fichier'], 500);
    }

    // Extraire métadonnées selon le type
    $largeurMm = null;
    $hauteurMm = null;
    $resolutionDpi = null;
    $espaceCouleur = null;
    $nombrePages = 1;

    try {
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'tif', 'tiff'])) {
            // Image : extraire dimensions et résolution
            $imageInfo = getimagesize($cheminComplet);
            if ($imageInfo) {
                $largeurPx = $imageInfo[0];
                $hauteurPx = $imageInfo[1];

                // Essayer de récupérer le DPI
                if (function_exists('exif_read_data') && in_array($extension, ['jpg', 'jpeg', 'tif', 'tiff'])) {
                    $exif = @exif_read_data($cheminComplet);
                    if ($exif && isset($exif['XResolution'])) {
                        $resolutionDpi = (int)$exif['XResolution'];
                    }
                }

                // Si pas de DPI, assumer 72 par défaut
                if (!$resolutionDpi) {
                    $resolutionDpi = 72;
                }

                // Convertir pixels en mm (1 inch = 25.4 mm)
                $largeurMm = round(($largeurPx / $resolutionDpi) * 25.4, 2);
                $hauteurMm = round(($hauteurPx / $resolutionDpi) * 25.4, 2);
            }
        } elseif ($extension === 'pdf') {
            // PDF : compter les pages (nécessite pdfinfo ou similaire)
            // Simplification : on met 1 page par défaut
            $nombrePages = 1;
        }
    } catch (Exception $e) {
        // Continuer même si extraction métadonnées échoue
    }

    // Validation finale : résolution minimale pour impression
    $erreurValidation = null;
    $statut = 'valide';

    if ($resolutionDpi && $resolutionDpi < 150) {
        $erreurValidation = "Résolution trop faible ($resolutionDpi DPI). Minimum recommandé : 300 DPI pour une impression optimale.";
        $statut = 'en_attente'; // Nécessite validation admin
    }

    // Insérer en base de données
    try {
        $db->query(
            "INSERT INTO fichiers_impression (
                commande_id, panier_id, ligne_panier_id,
                nom_original, nom_stockage, chemin_complet,
                extension, taille_octets, mime_type,
                largeur_mm, hauteur_mm, resolution_dpi, espace_couleur, nombre_pages,
                statut, erreur_validation,
                hash_md5, hash_sha256, ip_upload
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $commandeId, $panierId, $lignePanierId,
                $nomOriginal, $nomStockage, $cheminComplet,
                $extension, $file['size'], $mimeType,
                $largeurMm, $hauteurMm, $resolutionDpi, $espaceCouleur, $nombrePages,
                $statut, $erreurValidation,
                $hashMd5, $hashSha256, $_SERVER['REMOTE_ADDR']
            ]
        );

        $fichierId = $db->lastInsertId();

        // Si ligne panier spécifiée, associer le fichier
        if ($lignePanierId) {
            $db->query(
                "UPDATE lignes_panier SET fichier_impression_id = ? WHERE id = ?",
                [$fichierId, $lignePanierId]
            );
        }

        jsonResponse([
            'success' => true,
            'message' => 'Fichier uploadé avec succès',
            'fichier' => [
                'id' => $fichierId,
                'nom_original' => $nomOriginal,
                'taille_octets' => $file['size'],
                'taille_mb' => round($file['size'] / (1024 * 1024), 2),
                'extension' => $extension,
                'dimensions_mm' => $largeurMm && $hauteurMm ? "{$largeurMm}×{$hauteurMm}" : null,
                'resolution_dpi' => $resolutionDpi,
                'nombre_pages' => $nombrePages,
                'statut' => $statut,
                'avertissement' => $erreurValidation,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);

    } catch (PDOException $e) {
        // Supprimer le fichier en cas d'erreur BDD
        @unlink($cheminComplet);
        jsonResponse(['error' => 'Erreur base de données : ' . $e->getMessage()], 500);
    }
}

// ============================================
// GET - Récupérer infos fichier
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $fichierId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!$fichierId) {
        jsonResponse(['error' => 'ID fichier requis'], 400);
    }

    $db = Database::getInstance();
    $fichier = $db->fetchOne(
        "SELECT * FROM fichiers_impression WHERE id = ?",
        [$fichierId]
    );

    if (!$fichier) {
        jsonResponse(['error' => 'Fichier non trouvé'], 404);
    }

    jsonResponse([
        'success' => true,
        'fichier' => [
            'id' => $fichier['id'],
            'nom_original' => $fichier['nom_original'],
            'taille_octets' => $fichier['taille_octets'],
            'taille_mb' => round($fichier['taille_octets'] / (1024 * 1024), 2),
            'extension' => $fichier['extension'],
            'mime_type' => $fichier['mime_type'],
            'dimensions_mm' => $fichier['largeur_mm'] && $fichier['hauteur_mm']
                ? "{$fichier['largeur_mm']}×{$fichier['hauteur_mm']}"
                : null,
            'resolution_dpi' => $fichier['resolution_dpi'],
            'nombre_pages' => $fichier['nombre_pages'],
            'statut' => $fichier['statut'],
            'erreur_validation' => $fichier['erreur_validation'],
            'valide_par_admin' => (bool)$fichier['valide_par_admin'],
            'created_at' => $fichier['created_at']
        ]
    ]);
}

// ============================================
// DELETE - Supprimer fichier
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    parse_str(file_get_contents("php://input"), $data);
    $fichierId = isset($data['id']) ? (int)$data['id'] : 0;

    if (!$fichierId) {
        jsonResponse(['error' => 'ID fichier requis'], 400);
    }

    $db = Database::getInstance();
    $fichier = $db->fetchOne(
        "SELECT * FROM fichiers_impression WHERE id = ?",
        [$fichierId]
    );

    if (!$fichier) {
        jsonResponse(['error' => 'Fichier non trouvé'], 404);
    }

    // Ne pas supprimer physiquement si associé à une commande validée
    if ($fichier['commande_id']) {
        $commande = $db->fetchOne(
            "SELECT statut FROM commandes WHERE id = ?",
            [$fichier['commande_id']]
        );

        if ($commande && !in_array($commande['statut'], ['nouveau', 'annule'])) {
            jsonResponse([
                'error' => 'Impossible de supprimer : fichier lié à une commande en cours',
                'commande_statut' => $commande['statut']
            ], 400);
        }
    }

    // Marquer comme supprimé
    $db->query(
        "UPDATE fichiers_impression SET statut = 'supprime', updated_at = NOW() WHERE id = ?",
        [$fichierId]
    );

    // Optionnel : supprimer le fichier physique après 30 jours
    // Pour l'instant on garde juste marqué comme supprimé

    jsonResponse([
        'success' => true,
        'message' => 'Fichier marqué comme supprimé'
    ]);
}

jsonResponse(['error' => 'Méthode non autorisée'], 405);
