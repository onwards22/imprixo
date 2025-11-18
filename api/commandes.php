<?php
/**
 * API Commandes - VisuPrint Pro
 * Gestion des commandes clients
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance();

// ============================================
// POST /api/commandes.php - Créer une commande
// ============================================

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation des données requises
    $required = ['email', 'prenom', 'nom', 'telephone', 'adresse_facturation',
                 'code_postal_facturation', 'ville_facturation'];

    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonResponse(['success' => false, 'error' => "Champ requis: $field"], 400);
        }
    }

    // Valider l'email
    if (!isValidEmail($data['email'])) {
        jsonResponse(['success' => false, 'error' => 'Email invalide'], 400);
    }

    // Récupérer le panier
    $sessionId = getPanierSessionId();
    $panier = $db->fetchOne("SELECT * FROM paniers WHERE session_id = ?", [$sessionId]);

    if (!$panier || $panier['total_ttc'] == 0) {
        jsonResponse(['success' => false, 'error' => 'Panier vide'], 400);
    }

    // Vérifier montant minimum
    if ($panier['total_ht'] < COMMANDE_MIN) {
        jsonResponse([
            'success' => false,
            'error' => 'Montant minimum de commande: ' . COMMANDE_MIN . '€'
        ], 400);
    }

    // Récupérer les lignes du panier
    $lignes = $db->fetchAll(
        "SELECT lp.*, p.code as produit_code, p.nom as produit_nom
        FROM lignes_panier lp
        JOIN produits p ON lp.produit_id = p.id
        WHERE lp.panier_id = ?",
        [$panier['id']]
    );

    if (empty($lignes)) {
        jsonResponse(['success' => false, 'error' => 'Panier vide'], 400);
    }

    try {
        // Créer ou récupérer le client
        $client = $db->fetchOne("SELECT * FROM clients WHERE email = ?", [cleanInput($data['email'])]);

        if (!$client) {
            // Créer le client
            $db->query(
                "INSERT INTO clients (email, prenom, nom, entreprise, telephone,
                 adresse_facturation, code_postal_facturation, ville_facturation, pays_facturation,
                 type_client, newsletter)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    cleanInput($data['email']),
                    cleanInput($data['prenom']),
                    cleanInput($data['nom']),
                    cleanInput($data['entreprise'] ?? ''),
                    cleanInput($data['telephone']),
                    cleanInput($data['adresse_facturation']),
                    cleanInput($data['code_postal_facturation']),
                    cleanInput($data['ville_facturation']),
                    cleanInput($data['pays_facturation'] ?? 'France'),
                    cleanInput($data['type_client'] ?? 'particulier'),
                    isset($data['newsletter']) ? 1 : 0
                ]
            );

            $clientId = $db->lastInsertId();
        } else {
            $clientId = $client['id'];

            // Mettre à jour les infos client
            $db->query(
                "UPDATE clients SET
                prenom = ?, nom = ?, entreprise = ?, telephone = ?,
                adresse_facturation = ?, code_postal_facturation = ?, ville_facturation = ?,
                pays_facturation = ?
                WHERE id = ?",
                [
                    cleanInput($data['prenom']),
                    cleanInput($data['nom']),
                    cleanInput($data['entreprise'] ?? ''),
                    cleanInput($data['telephone']),
                    cleanInput($data['adresse_facturation']),
                    cleanInput($data['code_postal_facturation']),
                    cleanInput($data['ville_facturation']),
                    cleanInput($data['pays_facturation'] ?? 'France'),
                    $clientId
                ]
            );
        }

        // Générer numéro de commande unique
        $numeroCommande = genererNumeroCommande();

        // Adresse de livraison (si différente)
        $adresseLivraison = $data['adresse_livraison'] ?? $data['adresse_facturation'];
        $cpLivraison = $data['code_postal_livraison'] ?? $data['code_postal_facturation'];
        $villeLivraison = $data['ville_livraison'] ?? $data['ville_facturation'];
        $paysLivraison = $data['pays_livraison'] ?? $data['pays_facturation'] ?? 'France';

        // Créer la commande
        $db->query(
            "INSERT INTO commandes (
                numero_commande, client_id,
                client_email, client_nom, client_prenom, client_entreprise, client_telephone,
                adresse_facturation, code_postal_facturation, ville_facturation, pays_facturation,
                adresse_livraison, code_postal_livraison, ville_livraison, pays_livraison,
                sous_total, frais_port, reduction, total_ht, total_tva, total_ttc,
                code_promo, notes_client, statut, statut_paiement
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $numeroCommande,
                $clientId,
                cleanInput($data['email']),
                cleanInput($data['nom']),
                cleanInput($data['prenom']),
                cleanInput($data['entreprise'] ?? ''),
                cleanInput($data['telephone']),
                cleanInput($data['adresse_facturation']),
                cleanInput($data['code_postal_facturation']),
                cleanInput($data['ville_facturation']),
                cleanInput($data['pays_facturation'] ?? 'France'),
                cleanInput($adresseLivraison),
                cleanInput($cpLivraison),
                cleanInput($villeLivraison),
                cleanInput($paysLivraison),
                $panier['sous_total'],
                $panier['frais_port'],
                $panier['reduction'],
                $panier['total_ht'],
                $panier['total_tva'],
                $panier['total_ttc'],
                $panier['code_promo'],
                cleanInput($data['notes'] ?? ''),
                'nouveau',
                'en_attente'
            ]
        );

        $commandeId = $db->lastInsertId();

        // Créer les lignes de commande (snapshot du panier)
        foreach ($lignes as $ligne) {
            $db->query(
                "INSERT INTO lignes_commande (
                    commande_id, produit_id, produit_code, produit_nom,
                    surface, quantite, largeur, hauteur,
                    impression, oeillets, decoupe, lamination,
                    prix_unitaire_m2, prix_options, prix_ligne_ht, prix_ligne_ttc,
                    fichier_nom, fichier_path
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $commandeId,
                    $ligne['produit_id'],
                    $ligne['produit_code'],
                    $ligne['produit_nom'],
                    $ligne['surface'],
                    $ligne['quantite'],
                    $ligne['largeur'],
                    $ligne['hauteur'],
                    $ligne['impression'],
                    $ligne['oeillets'],
                    $ligne['decoupe'],
                    $ligne['lamination'],
                    $ligne['prix_unitaire_m2'],
                    $ligne['prix_options'],
                    $ligne['prix_ligne_ht'],
                    $ligne['prix_ligne_ttc'],
                    $ligne['fichier_nom'],
                    $ligne['fichier_path']
                ]
            );
        }

        // Envoyer email de confirmation (en attente de paiement)
        envoyerEmailConfirmationCommande($commandeId);

        jsonResponse([
            'success' => true,
            'message' => 'Commande créée',
            'commande_id' => $commandeId,
            'numero_commande' => $numeroCommande,
            'total_ttc' => $panier['total_ttc']
        ]);

    } catch (PDOException $e) {
        jsonResponse([
            'success' => false,
            'error' => 'Erreur lors de la création de la commande'
        ], 500);
    }
}

// ============================================
// GET /api/commandes.php - Liste commandes client
// GET /api/commandes.php?numero=VP-xxx - Détail
// ============================================

else if ($method === 'GET') {

    // Détail d'une commande par numéro
    if (isset($_GET['numero'])) {
        $numero = cleanInput($_GET['numero']);

        $commande = $db->fetchOne(
            "SELECT * FROM commandes WHERE numero_commande = ?",
            [$numero]
        );

        if (!$commande) {
            jsonResponse(['success' => false, 'error' => 'Commande non trouvée'], 404);
        }

        // Récupérer les lignes
        $lignes = $db->fetchAll(
            "SELECT * FROM lignes_commande WHERE commande_id = ?",
            [$commande['id']]
        );

        jsonResponse([
            'success' => true,
            'commande' => $commande,
            'lignes' => $lignes
        ]);
    }

    // Liste des commandes d'un client (par email)
    else if (isset($_GET['email'])) {
        $email = cleanInput($_GET['email']);

        $commandes = $db->fetchAll(
            "SELECT * FROM commandes
            WHERE client_email = ?
            ORDER BY created_at DESC",
            [$email]
        );

        jsonResponse([
            'success' => true,
            'commandes' => $commandes,
            'total' => count($commandes)
        ]);
    }

    else {
        jsonResponse(['success' => false, 'error' => 'Paramètre requis'], 400);
    }
}

// ============================================
// PUT /api/commandes.php?id=123 - Mettre à jour (admin)
// ============================================

else if ($method === 'PUT') {
    // TODO: Vérifier authentification admin

    if (!isset($_GET['id'])) {
        jsonResponse(['success' => false, 'error' => 'ID commande requis'], 400);
    }

    $commandeId = (int)$_GET['id'];
    $data = json_decode(file_get_contents('php://input'), true);

    $commande = $db->fetchOne("SELECT * FROM commandes WHERE id = ?", [$commandeId]);

    if (!$commande) {
        jsonResponse(['success' => false, 'error' => 'Commande non trouvée'], 404);
    }

    // Mettre à jour le statut
    if (isset($data['statut'])) {
        $statutsValides = ['nouveau', 'confirme', 'en_production', 'expedie', 'livre', 'annule'];

        if (!in_array($data['statut'], $statutsValides)) {
            jsonResponse(['success' => false, 'error' => 'Statut invalide'], 400);
        }

        $db->query("UPDATE commandes SET statut = ? WHERE id = ?", [$data['statut'], $commandeId]);

        // Si expédié, envoyer email
        if ($data['statut'] === 'expedie') {
            envoyerEmailExpedition($commandeId);
        }
    }

    // Mettre à jour le numéro de suivi
    if (isset($data['numero_suivi'])) {
        $db->query(
            "UPDATE commandes SET numero_suivi = ?, date_expedition = NOW() WHERE id = ?",
            [cleanInput($data['numero_suivi']), $commandeId]
        );
    }

    jsonResponse([
        'success' => true,
        'message' => 'Commande mise à jour'
    ]);
}

else {
    jsonResponse(['success' => false, 'error' => 'Méthode non autorisée'], 405);
}

// ============================================
// FONCTIONS EMAIL
// ============================================

function envoyerEmailConfirmationCommande($commandeId) {
    $db = Database::getInstance();

    $commande = $db->fetchOne("SELECT * FROM commandes WHERE id = ?", [$commandeId]);
    $lignes = $db->fetchAll("SELECT * FROM lignes_commande WHERE commande_id = ?", [$commandeId]);

    $sujet = "Commande {$commande['numero_commande']} - En attente de paiement";

    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Merci pour votre commande !</h2>
        <p>Bonjour {$commande['client_prenom']},</p>
        <p>Nous avons bien reçu votre commande <strong>{$commande['numero_commande']}</strong>.</p>

        <h3>Récapitulatif :</h3>
        <table style='border-collapse: collapse; width: 100%;'>
            <tr style='background: #f0f0f0;'>
                <th style='padding: 10px; text-align: left;'>Produit</th>
                <th style='padding: 10px; text-align: right;'>Quantité</th>
                <th style='padding: 10px; text-align: right;'>Prix</th>
            </tr>";

    foreach ($lignes as $ligne) {
        $message .= "
            <tr>
                <td style='padding: 10px;'>{$ligne['produit_nom']}</td>
                <td style='padding: 10px; text-align: right;'>{$ligne['quantite']}</td>
                <td style='padding: 10px; text-align: right;'>" . number_format($ligne['prix_ligne_ttc'], 2) . " €</td>
            </tr>";
    }

    $message .= "
            <tr style='font-weight: bold; background: #f9f9f9;'>
                <td colspan='2' style='padding: 10px;'>Total TTC</td>
                <td style='padding: 10px; text-align: right;'>" . number_format($commande['total_ttc'], 2) . " €</td>
            </tr>
        </table>

        <p style='margin-top: 20px;'>
            <strong>Statut :</strong> En attente de paiement<br>
            <strong>Délai estimé :</strong> 3-5 jours après paiement
        </p>

        <p>
            Vous recevrez un email dès que votre paiement sera confirmé.
        </p>

        <p>
            Cordialement,<br>
            L'équipe VisuPrint Pro
        </p>
    </body>
    </html>
    ";

    return envoyerEmail($commande['client_email'], $sujet, $message, true);
}

function envoyerEmailExpedition($commandeId) {
    $db = Database::getInstance();

    $commande = $db->fetchOne("SELECT * FROM commandes WHERE id = ?", [$commandeId]);

    $sujet = "Commande {$commande['numero_commande']} - Expédiée !";

    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Votre commande a été expédiée ! 📦</h2>
        <p>Bonjour {$commande['client_prenom']},</p>
        <p>Bonne nouvelle ! Votre commande <strong>{$commande['numero_commande']}</strong> a été expédiée.</p>";

    if ($commande['numero_suivi']) {
        $message .= "
        <p>
            <strong>Numéro de suivi :</strong> {$commande['numero_suivi']}<br>
            <strong>Transporteur :</strong> {$commande['transporteur']}
        </p>";
    }

    $message .= "
        <p>
            Vous devriez recevoir votre colis sous 48-72h.
        </p>

        <p>
            Cordialement,<br>
            L'équipe VisuPrint Pro
        </p>
    </body>
    </html>
    ";

    return envoyerEmail($commande['client_email'], $sujet, $message, true);
}
