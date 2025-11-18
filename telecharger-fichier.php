<?php
/**
 * Téléchargement Fichier - Imprixo
 * Télécharger les fichiers uploadés (sécurisé)
 */

session_start();
require_once __DIR__ . '/api/config.php';

$fichierId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$fichierId) {
    die('ID fichier requis');
}

$db = Database::getInstance();

// Récupérer le fichier
$fichier = $db->fetchOne(
    "SELECT * FROM fichiers_impression WHERE id = ?",
    [$fichierId]
);

if (!$fichier) {
    die('Fichier non trouvé');
}

// Vérifier les droits d'accès
$clientId = $_SESSION['client_id'] ?? null;

if ($fichier['commande_id']) {
    // Si lié à une commande, vérifier que c'est le bon client
    $commande = $db->fetchOne(
        "SELECT client_id FROM commandes WHERE id = ?",
        [$fichier['commande_id']]
    );

    if (!$commande || $commande['client_id'] != $clientId) {
        die('Accès non autorisé');
    }
}

// Vérifier que le fichier existe physiquement
if (!file_exists($fichier['chemin_complet'])) {
    die('Fichier introuvable sur le serveur');
}

// Forcer le téléchargement
header('Content-Type: ' . $fichier['mime_type']);
header('Content-Disposition: attachment; filename="' . $fichier['nom_original'] . '"');
header('Content-Length: ' . $fichier['taille_octets']);
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Lire et envoyer le fichier
readfile($fichier['chemin_complet']);
exit;
