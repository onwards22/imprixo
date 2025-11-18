<?php
/**
 * Script d'import des produits depuis CSV
 * À exécuter UNE SEULE FOIS après installation
 * URL: https://visuprintpro.fr/scripts/import-produits.php
 */

// Sécurité : Désactiver après première utilisation
$IMPORT_ENABLED = true; // ⚠️ Passer à FALSE après import !

if (!$IMPORT_ENABLED) {
    die('❌ Import désactivé. Modifier $IMPORT_ENABLED dans le script.');
}

require_once __DIR__ . '/../api/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Import Produits CSV - VisuPrint Pro</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px; }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .info { color: #00aaff; }
        .warning { color: #ffaa00; }
    </style>
</head>
<body>
<h1>🚀 Import Produits VisuPrint Pro</h1>
<pre>
<?php

$db = Database::getInstance();
$csvFile = __DIR__ . '/../CATALOGUE_COMPLET_VISUPRINT.csv';

// Vérifier que le fichier CSV existe
if (!file_exists($csvFile)) {
    echo "<span class='error'>❌ ERREUR: Fichier CSV non trouvé: $csvFile</span>\n";
    exit;
}

echo "<span class='info'>📂 Lecture du fichier CSV...</span>\n";

// Vérifier si des produits existent déjà
$existing = $db->fetchOne("SELECT COUNT(*) as count FROM produits");
if ($existing['count'] > 0) {
    echo "<span class='warning'>⚠️  ATTENTION: {$existing['count']} produits déjà en base !</span>\n";
    echo "<span class='warning'>   Voulez-vous continuer ? (les doublons seront ignorés)</span>\n\n";
}

// Ouvrir le CSV
$handle = fopen($csvFile, 'r');
if (!$handle) {
    echo "<span class='error'>❌ Impossible d'ouvrir le fichier CSV</span>\n";
    exit;
}

// Lire l'en-tête
$headers = fgetcsv($handle);
echo "<span class='info'>✓ En-têtes CSV détectés: " . count($headers) . " colonnes</span>\n\n";

$imported = 0;
$skipped = 0;
$errors = 0;

// Lire chaque ligne
while (($row = fgetcsv($handle)) !== false) {
    // Créer un tableau associatif
    $data = array_combine($headers, $row);

    $code = $data['ID_PRODUIT'];

    // Vérifier si le produit existe déjà
    $exists = $db->fetchOne(
        "SELECT id FROM produits WHERE code = ?",
        [$code]
    );

    if ($exists) {
        echo "<span class='warning'>⊘ SKIP: $code (déjà existant)</span>\n";
        $skipped++;
        continue;
    }

    try {
        // Générer le slug SEO
        $slug = genererSlug($data['NOM_PRODUIT']);

        // Nettoyer les prix (remplacer - par NULL)
        $prixSimpleFace = $data['PRIX_SIMPLE_FACE_M2'] === '-' ? null : (float)$data['PRIX_SIMPLE_FACE_M2'];
        $prixDoubleFace = $data['PRIX_DOUBLE_FACE_M2'] === '-' ? null : (float)$data['PRIX_DOUBLE_FACE_M2'];

        // Générer meta title et description SEO
        $metaTitle = $data['NOM_PRODUIT'] . ' - Impression Grand Format | Prix Dégressifs';
        $metaDescription = "Impression " . $data['NOM_PRODUIT'] . " ✓ Prix dégressifs dès " . $data['PRIX_300_PLUS_M2'] . "€/m² ✓ Livraison 48-72h ✓ Qualité professionnelle garantie";

        // Insérer le produit
        $db->query(
            "INSERT INTO produits (
                code, nom, categorie, sous_titre,
                description_courte, description_longue,
                poids_m2, epaisseur, format_max, `usage`, duree_vie,
                certification, finition, impression_faces,
                prix_simple_face, prix_double_face,
                prix_0_10, prix_11_50, prix_51_100, prix_101_300, prix_300_plus,
                commande_min, delai_jours, unite_vente,
                url_slug, meta_title, meta_description,
                stock_disponible
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, TRUE
            )",
            [
                $code,
                $data['NOM_PRODUIT'],
                $data['CATEGORIE'],
                $data['SOUS_TITRE'] ?? null,
                $data['DESCRIPTION_COURTE'] ?? null,
                $data['DESCRIPTION_LONGUE'] ?? null,
                $data['POIDS_M2'] !== '-' ? (float)$data['POIDS_M2'] : null,
                $data['EPAISSEUR'] !== '-' ? $data['EPAISSEUR'] : null,
                $data['FORMAT_MAX_CM'] ?? null,
                $data['USAGE'] ?? null,
                $data['DUREE_VIE'] ?? null,
                $data['CERTIFICATION'] ?? null,
                $data['FINITION'] ?? null,
                $data['IMPRESSION_FACES'] ?? null,
                $prixSimpleFace,
                $prixDoubleFace,
                (float)$data['PRIX_0_10_M2'],
                (float)$data['PRIX_11_50_M2'],
                (float)$data['PRIX_51_100_M2'],
                (float)$data['PRIX_101_300_M2'],
                (float)$data['PRIX_300_PLUS_M2'],
                (float)$data['COMMANDE_MIN_EURO'],
                (int)$data['DELAI_STANDARD_JOURS'],
                $data['UNITE_VENTE'] ?? 'm²',
                $slug,
                $metaTitle,
                $metaDescription
            ]
        );

        echo "<span class='success'>✓ IMPORT: $code - {$data['NOM_PRODUIT']}</span>\n";
        $imported++;

    } catch (PDOException $e) {
        echo "<span class='error'>✗ ERREUR: $code - " . $e->getMessage() . "</span>\n";
        $errors++;
    }
}

fclose($handle);

// Résumé
echo "\n";
echo "<span class='info'>═══════════════════════════════════════</span>\n";
echo "<span class='info'>📊 RÉSUMÉ DE L'IMPORT</span>\n";
echo "<span class='info'>═══════════════════════════════════════</span>\n";
echo "<span class='success'>✓ Importés : $imported produits</span>\n";
if ($skipped > 0) {
    echo "<span class='warning'>⊘ Ignorés  : $skipped produits (déjà existants)</span>\n";
}
if ($errors > 0) {
    echo "<span class='error'>✗ Erreurs  : $errors produits</span>\n";
}

// Statistiques finales
$stats = $db->fetchOne("
    SELECT
        COUNT(*) as total,
        COUNT(DISTINCT categorie) as categories,
        MIN(prix_300_plus) as prix_min,
        MAX(prix_0_10) as prix_max
    FROM produits
");

echo "\n<span class='info'>📦 TOTAL EN BASE DE DONNÉES</span>\n";
echo "<span class='info'>   • Produits : {$stats['total']}</span>\n";
echo "<span class='info'>   • Catégories : {$stats['categories']}</span>\n";
echo "<span class='info'>   • Prix min : {$stats['prix_min']}€/m²</span>\n";
echo "<span class='info'>   • Prix max : {$stats['prix_max']}€/m²</span>\n";

if ($imported > 0) {
    echo "\n<span class='success'>🎉 IMPORT TERMINÉ AVEC SUCCÈS !</span>\n";
    echo "\n<span class='warning'>⚠️  IMPORTANT: Désactivez ce script en passant \$IMPORT_ENABLED = false;</span>\n";
}

echo "<span class='info'>═══════════════════════════════════════</span>\n";

?>
</pre>
</body>
</html>
<?php

/**
 * Générer un slug SEO-friendly
 */
function genererSlug($text) {
    // Convertir en minuscules
    $text = mb_strtolower($text, 'UTF-8');

    // Remplacer les caractères accentués
    $accents = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n'
    ];
    $text = strtr($text, $accents);

    // Remplacer les caractères spéciaux par des tirets
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);

    // Supprimer les tirets multiples
    $text = preg_replace('/-+/', '-', $text);

    // Supprimer les tirets en début/fin
    $text = trim($text, '-');

    return $text;
}
