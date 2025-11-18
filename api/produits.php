<?php
/**
 * API Produits - VisuPrint Pro
 * Endpoint: /api/produits.php
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance();

// ============================================
// GET /api/produits.php - Liste des produits
// GET /api/produits.php?code=FX-3MM - Détail produit
// ============================================

if ($method === 'GET') {

    // Détail d'un produit spécifique
    if (isset($_GET['code'])) {
        $code = cleanInput($_GET['code']);

        $produit = $db->fetchOne(
            "SELECT * FROM produits WHERE code = ? AND stock_disponible = TRUE",
            [$code]
        );

        if ($produit) {
            jsonResponse([
                'success' => true,
                'produit' => $produit
            ]);
        } else {
            jsonResponse([
                'success' => false,
                'error' => 'Produit non trouvé'
            ], 404);
        }
    }

    // Liste des produits (avec filtres optionnels)
    else {
        $where = ['stock_disponible = TRUE'];
        $params = [];

        // Filtre par catégorie
        if (isset($_GET['categorie'])) {
            $where[] = 'categorie = ?';
            $params[] = cleanInput($_GET['categorie']);
        }

        // Recherche
        if (isset($_GET['search'])) {
            $search = '%' . cleanInput($_GET['search']) . '%';
            $where[] = '(nom LIKE ? OR description_courte LIKE ?)';
            $params[] = $search;
            $params[] = $search;
        }

        // Prix min/max
        if (isset($_GET['prix_min'])) {
            $where[] = 'prix_300_plus >= ?';
            $params[] = (float)$_GET['prix_min'];
        }

        if (isset($_GET['prix_max'])) {
            $where[] = 'prix_0_10 <= ?';
            $params[] = (float)$_GET['prix_max'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT * FROM produits WHERE $whereClause ORDER BY nom ASC";

        $produits = $db->fetchAll($sql, $params);

        // Statistiques
        $stats = [
            'total' => count($produits),
            'categories' => array_unique(array_column($produits, 'categorie'))
        ];

        jsonResponse([
            'success' => true,
            'produits' => $produits,
            'stats' => $stats
        ]);
    }
}

// ============================================
// POST /api/produits.php - Créer un produit (admin)
// ============================================

else if ($method === 'POST') {
    // TODO: Vérifier authentification admin

    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    $required = ['code', 'nom', 'categorie'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonResponse(['success' => false, 'error' => "Champ requis: $field"], 400);
        }
    }

    try {
        $db->query(
            "INSERT INTO produits (code, nom, categorie, description_courte, description_longue,
             poids_m2, epaisseur, usage, prix_simple_face, prix_0_10, prix_11_50, prix_51_100, prix_101_300, prix_300_plus)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['code'],
                $data['nom'],
                $data['categorie'],
                $data['description_courte'] ?? null,
                $data['description_longue'] ?? null,
                $data['poids_m2'] ?? null,
                $data['epaisseur'] ?? null,
                $data['usage'] ?? null,
                $data['prix_simple_face'] ?? 0,
                $data['prix_0_10'] ?? 0,
                $data['prix_11_50'] ?? 0,
                $data['prix_51_100'] ?? 0,
                $data['prix_101_300'] ?? 0,
                $data['prix_300_plus'] ?? 0
            ]
        );

        jsonResponse([
            'success' => true,
            'id' => $db->lastInsertId(),
            'message' => 'Produit créé avec succès'
        ]);

    } catch (PDOException $e) {
        jsonResponse([
            'success' => false,
            'error' => 'Erreur lors de la création du produit'
        ], 500);
    }
}

else {
    jsonResponse(['success' => false, 'error' => 'Méthode non autorisée'], 405);
}
