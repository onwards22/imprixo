<?php
/**
 * API Panier - VisuPrint Pro
 * Gestion complète du panier client
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance();

// ============================================
// GET /api/panier.php - Récupérer le panier
// ============================================

if ($method === 'GET') {
    $sessionId = getPanierSessionId();

    // Récupérer ou créer le panier
    $panier = $db->fetchOne(
        "SELECT * FROM paniers WHERE session_id = ?",
        [$sessionId]
    );

    if (!$panier) {
        // Créer un nouveau panier
        $db->query(
            "INSERT INTO paniers (session_id, expire_at) VALUES (?, DATE_ADD(NOW(), INTERVAL ? DAY))",
            [$sessionId, PANIER_EXPIRATION_DAYS]
        );
        $panierId = $db->lastInsertId();

        $panier = $db->fetchOne("SELECT * FROM paniers WHERE id = ?", [$panierId]);
    }

    // Récupérer les lignes du panier avec infos produits
    $lignes = $db->fetchAll(
        "SELECT
            lp.*,
            p.nom as produit_nom,
            p.code as produit_code,
            p.categorie,
            p.delai_jours,
            p.unite_vente
        FROM lignes_panier lp
        JOIN produits p ON lp.produit_id = p.id
        WHERE lp.panier_id = ?
        ORDER BY lp.created_at DESC",
        [$panier['id']]
    );

    jsonResponse([
        'success' => true,
        'panier' => $panier,
        'lignes' => $lignes,
        'nb_articles' => count($lignes),
        'total_ttc' => (float)$panier['total_ttc']
    ]);
}

// ============================================
// POST /api/panier.php - Ajouter au panier
// ============================================

else if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($data['produit_code'])) {
        jsonResponse(['success' => false, 'error' => 'Code produit requis'], 400);
    }

    if (empty($data['surface']) || $data['surface'] <= 0) {
        jsonResponse(['success' => false, 'error' => 'Surface invalide'], 400);
    }

    // Récupérer le produit
    $produit = $db->fetchOne(
        "SELECT * FROM produits WHERE code = ? AND stock_disponible = TRUE",
        [$data['produit_code']]
    );

    if (!$produit) {
        jsonResponse(['success' => false, 'error' => 'Produit non trouvé'], 404);
    }

    // Récupérer ou créer le panier
    $sessionId = getPanierSessionId();
    $panier = $db->fetchOne("SELECT * FROM paniers WHERE session_id = ?", [$sessionId]);

    if (!$panier) {
        $db->query(
            "INSERT INTO paniers (session_id, expire_at) VALUES (?, DATE_ADD(NOW(), INTERVAL ? DAY))",
            [$sessionId, PANIER_EXPIRATION_DAYS]
        );
        $panierId = $db->lastInsertId();
    } else {
        $panierId = $panier['id'];
    }

    // Calculer les valeurs
    $surface = (float)$data['surface'];
    $quantite = isset($data['quantite']) ? (int)$data['quantite'] : 1;
    $surfaceTotale = $surface * $quantite;

    // Calculer le prix dégressif
    $prixM2 = calculerPrixDegressif($produit, $surfaceTotale);

    // Type impression
    $impression = $data['impression'] ?? 'simple';
    if ($impression === 'double' && $produit['prix_double_face'] > 0) {
        $prixM2 = $produit['prix_double_face'];
    }

    // Calculer les prix des options
    $prixOptions = 0;
    $oeillets = isset($data['oeillets']) && $data['oeillets'] ? 1 : 0;
    $decoupe = isset($data['decoupe']) && $data['decoupe'] ? 1 : 0;
    $lamination = isset($data['lamination']) && $data['lamination'] ? 1 : 0;

    if ($oeillets) $prixOptions += 2 * $surface * $quantite; // 2€/m²
    if ($decoupe) $prixOptions += 1.5 * $surface * $quantite; // 1.5€/m²
    if ($lamination) $prixOptions += 5 * $surface * $quantite; // 5€/m²

    // Calculer les prix de la ligne
    $prixBase = $surface * $prixM2 * $quantite;
    $prixLigneHT = $prixBase + $prixOptions;
    $prixLigneTTC = $prixLigneHT * (1 + TVA_RATE);

    // Ajouter la ligne au panier
    try {
        $db->query(
            "INSERT INTO lignes_panier
            (panier_id, produit_id, surface, quantite, largeur, hauteur,
             impression, oeillets, decoupe, lamination,
             prix_unitaire_m2, prix_options, prix_ligne_ht, prix_ligne_ttc)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $panierId,
                $produit['id'],
                $surface,
                $quantite,
                $data['largeur'] ?? null,
                $data['hauteur'] ?? null,
                $impression,
                $oeillets,
                $decoupe,
                $lamination,
                $prixM2,
                $prixOptions,
                $prixLigneHT,
                $prixLigneTTC
            ]
        );

        $ligneId = $db->lastInsertId();

        // Recalculer les totaux du panier
        recalculerTotauxPanier($panierId);

        // Récupérer le panier mis à jour
        $panierMaj = $db->fetchOne("SELECT * FROM paniers WHERE id = ?", [$panierId]);

        jsonResponse([
            'success' => true,
            'message' => 'Produit ajouté au panier',
            'ligne_id' => $ligneId,
            'panier' => $panierMaj
        ]);

    } catch (PDOException $e) {
        jsonResponse([
            'success' => false,
            'error' => 'Erreur lors de l\'ajout au panier'
        ], 500);
    }
}

// ============================================
// PUT /api/panier.php?id=123 - Modifier quantité
// ============================================

else if ($method === 'PUT') {
    if (!isset($_GET['id'])) {
        jsonResponse(['success' => false, 'error' => 'ID ligne requis'], 400);
    }

    $ligneId = (int)$_GET['id'];
    $data = json_decode(file_get_contents('php://input'), true);

    // Récupérer la ligne
    $ligne = $db->fetchOne("SELECT * FROM lignes_panier WHERE id = ?", [$ligneId]);

    if (!$ligne) {
        jsonResponse(['success' => false, 'error' => 'Ligne non trouvée'], 404);
    }

    // Nouvelle quantité
    $nouvelleQuantite = isset($data['quantite']) ? (int)$data['quantite'] : $ligne['quantite'];

    if ($nouvelleQuantite <= 0) {
        jsonResponse(['success' => false, 'error' => 'Quantité invalide'], 400);
    }

    // Recalculer les prix
    $surface = $ligne['surface'];
    $surfaceTotale = $surface * $nouvelleQuantite;

    // Récupérer le produit pour recalculer le prix dégressif
    $produit = $db->fetchOne("SELECT * FROM produits WHERE id = ?", [$ligne['produit_id']]);
    $prixM2 = calculerPrixDegressif($produit, $surfaceTotale);

    if ($ligne['impression'] === 'double' && $produit['prix_double_face'] > 0) {
        $prixM2 = $produit['prix_double_face'];
    }

    // Recalculer options
    $prixOptions = 0;
    if ($ligne['oeillets']) $prixOptions += 2 * $surface * $nouvelleQuantite;
    if ($ligne['decoupe']) $prixOptions += 1.5 * $surface * $nouvelleQuantite;
    if ($ligne['lamination']) $prixOptions += 5 * $surface * $nouvelleQuantite;

    $prixLigneHT = ($surface * $prixM2 * $nouvelleQuantite) + $prixOptions;
    $prixLigneTTC = $prixLigneHT * (1 + TVA_RATE);

    // Mettre à jour
    $db->query(
        "UPDATE lignes_panier
        SET quantite = ?, prix_unitaire_m2 = ?, prix_options = ?,
            prix_ligne_ht = ?, prix_ligne_ttc = ?
        WHERE id = ?",
        [$nouvelleQuantite, $prixM2, $prixOptions, $prixLigneHT, $prixLigneTTC, $ligneId]
    );

    // Recalculer totaux panier
    recalculerTotauxPanier($ligne['panier_id']);

    jsonResponse([
        'success' => true,
        'message' => 'Quantité mise à jour'
    ]);
}

// ============================================
// DELETE /api/panier.php?id=123 - Supprimer ligne
// DELETE /api/panier.php - Vider le panier
// ============================================

else if ($method === 'DELETE') {

    // Supprimer une ligne spécifique
    if (isset($_GET['id'])) {
        $ligneId = (int)$_GET['id'];

        $ligne = $db->fetchOne("SELECT panier_id FROM lignes_panier WHERE id = ?", [$ligneId]);

        if (!$ligne) {
            jsonResponse(['success' => false, 'error' => 'Ligne non trouvée'], 404);
        }

        $db->query("DELETE FROM lignes_panier WHERE id = ?", [$ligneId]);

        // Recalculer totaux
        recalculerTotauxPanier($ligne['panier_id']);

        jsonResponse([
            'success' => true,
            'message' => 'Article supprimé du panier'
        ]);
    }

    // Vider tout le panier
    else {
        $sessionId = getPanierSessionId();
        $panier = $db->fetchOne("SELECT id FROM paniers WHERE session_id = ?", [$sessionId]);

        if ($panier) {
            $db->query("DELETE FROM lignes_panier WHERE panier_id = ?", [$panier['id']]);
            $db->query(
                "UPDATE paniers SET sous_total = 0, total_ht = 0, total_tva = 0, total_ttc = 0 WHERE id = ?",
                [$panier['id']]
            );
        }

        jsonResponse([
            'success' => true,
            'message' => 'Panier vidé'
        ]);
    }
}

else {
    jsonResponse(['success' => false, 'error' => 'Méthode non autorisée'], 405);
}

// ============================================
// FONCTION : Recalculer les totaux du panier
// ============================================

function recalculerTotauxPanier($panierId) {
    $db = Database::getInstance();

    $totaux = $db->fetchOne(
        "SELECT
            COALESCE(SUM(prix_ligne_ht), 0) as sous_total,
            COALESCE(SUM(prix_ligne_ttc), 0) as total_ttc
        FROM lignes_panier
        WHERE panier_id = ?",
        [$panierId]
    );

    $sousTotal = (float)$totaux['sous_total'];
    $totalTTC = (float)$totaux['total_ttc'];
    $totalTVA = $totalTTC - $sousTotal;

    // Frais de port (gratuit au-dessus du seuil)
    $fraisPort = $sousTotal >= FRAIS_PORT_GRATUIT_SEUIL ? 0 : FRAIS_PORT_STANDARD;

    $totalHT = $sousTotal + $fraisPort;
    $totalTTC = $totalHT * (1 + TVA_RATE);
    $totalTVA = $totalTTC - $totalHT;

    $db->query(
        "UPDATE paniers
        SET sous_total = ?, frais_port = ?, total_ht = ?, total_tva = ?, total_ttc = ?
        WHERE id = ?",
        [$sousTotal, $fraisPort, $totalHT, $totalTVA, $totalTTC, $panierId]
    );
}
