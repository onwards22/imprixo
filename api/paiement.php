<?php
/**
 * API Paiement Stripe - VisuPrint Pro
 * Création session de paiement Stripe Checkout
 */

require_once 'config.php';

// Charger la bibliothèque Stripe
// Télécharger depuis : https://github.com/stripe/stripe-php
// Ou via Composer : composer require stripe/stripe-php
require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance();

// ============================================
// POST /api/paiement.php - Créer session Stripe
// ============================================

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Valider qu'on a un numéro de commande
    if (empty($data['numero_commande'])) {
        jsonResponse(['success' => false, 'error' => 'Numéro de commande requis'], 400);
    }

    $numeroCommande = cleanInput($data['numero_commande']);

    // Récupérer la commande
    $commande = $db->fetchOne(
        "SELECT * FROM commandes WHERE numero_commande = ?",
        [$numeroCommande]
    );

    if (!$commande) {
        jsonResponse(['success' => false, 'error' => 'Commande non trouvée'], 404);
    }

    // Vérifier que la commande n'est pas déjà payée
    if ($commande['statut_paiement'] === 'paye') {
        jsonResponse(['success' => false, 'error' => 'Commande déjà payée'], 400);
    }

    // Récupérer les lignes de commande
    $lignes = $db->fetchAll(
        "SELECT * FROM lignes_commande WHERE commande_id = ?",
        [$commande['id']]
    );

    try {
        // Construire les line_items pour Stripe
        $lineItems = [];

        foreach ($lignes as $ligne) {
            $description = "{$ligne['produit_nom']} - {$ligne['surface']}m² × {$ligne['quantite']}";

            if ($ligne['impression'] === 'double') {
                $description .= " (Double face)";
            }

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $ligne['produit_nom'],
                        'description' => $description,
                    ],
                    'unit_amount' => round($ligne['prix_ligne_ttc'] * 100), // En centimes
                ],
                'quantity' => 1,
            ];
        }

        // Ajouter les frais de port si applicable
        if ($commande['frais_port'] > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Frais de port',
                        'description' => 'Livraison standard',
                    ],
                    'unit_amount' => round($commande['frais_port'] * (1 + TVA_RATE) * 100),
                ],
                'quantity' => 1,
            ];
        }

        // Créer la session Stripe Checkout
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => SITE_URL . '/merci.html?session_id={CHECKOUT_SESSION_ID}&commande=' . $numeroCommande,
            'cancel_url' => SITE_URL . '/panier.html?canceled=1',
            'customer_email' => $commande['client_email'],
            'client_reference_id' => $numeroCommande,
            'metadata' => [
                'commande_id' => $commande['id'],
                'numero_commande' => $numeroCommande,
            ],
            'billing_address_collection' => 'required',
            'shipping_address_collection' => [
                'allowed_countries' => ['FR', 'BE', 'LU', 'CH'],
            ],
            'locale' => 'fr',
        ]);

        // Sauvegarder la session ID dans la commande
        $db->query(
            "UPDATE commandes SET stripe_payment_intent_id = ? WHERE id = ?",
            [$session->payment_intent, $commande['id']]
        );

        jsonResponse([
            'success' => true,
            'session_id' => $session->id,
            'url' => $session->url // URL de redirection vers Stripe
        ]);

    } catch (\Stripe\Exception\ApiErrorException $e) {
        jsonResponse([
            'success' => false,
            'error' => 'Erreur Stripe: ' . $e->getMessage()
        ], 500);
    }
}

// ============================================
// GET /api/paiement.php?session_id=xxx - Vérifier le paiement
// ============================================

else if ($method === 'GET' && isset($_GET['session_id'])) {
    $sessionId = cleanInput($_GET['session_id']);

    try {
        // Récupérer la session Stripe
        $session = \Stripe\Checkout\Session::retrieve($sessionId);

        // Vérifier le statut du paiement
        if ($session->payment_status === 'paid') {
            $numeroCommande = $session->client_reference_id;

            // Mettre à jour la commande
            $db->query(
                "UPDATE commandes
                SET statut_paiement = 'paye',
                    statut = 'confirme',
                    date_paiement = NOW(),
                    stripe_charge_id = ?
                WHERE numero_commande = ?",
                [$session->payment_intent, $numeroCommande]
            );

            // Envoyer email de confirmation de paiement
            $commande = $db->fetchOne(
                "SELECT * FROM commandes WHERE numero_commande = ?",
                [$numeroCommande]
            );

            if ($commande) {
                envoyerEmailPaiementConfirme($commande['id']);
            }

            jsonResponse([
                'success' => true,
                'paid' => true,
                'numero_commande' => $numeroCommande
            ]);
        } else {
            jsonResponse([
                'success' => true,
                'paid' => false,
                'status' => $session->payment_status
            ]);
        }

    } catch (\Stripe\Exception\ApiErrorException $e) {
        jsonResponse([
            'success' => false,
            'error' => 'Erreur Stripe: ' . $e->getMessage()
        ], 500);
    }
}

else {
    jsonResponse(['success' => false, 'error' => 'Méthode non autorisée'], 405);
}

// ============================================
// FONCTION : Email confirmation paiement
// ============================================

function envoyerEmailPaiementConfirme($commandeId) {
    $db = Database::getInstance();

    $commande = $db->fetchOne("SELECT * FROM commandes WHERE id = ?", [$commandeId]);
    $lignes = $db->fetchAll("SELECT * FROM lignes_commande WHERE commande_id = ?", [$commandeId]);

    $sujet = "Paiement confirmé - Commande {$commande['numero_commande']}";

    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>✅ Paiement confirmé !</h2>
        <p>Bonjour {$commande['client_prenom']},</p>

        <p style='background: #d1fae5; padding: 15px; border-left: 4px solid #059669;'>
            <strong>Votre paiement de " . number_format($commande['total_ttc'], 2) . " € a bien été encaissé.</strong>
        </p>

        <p>Votre commande <strong>{$commande['numero_commande']}</strong> est maintenant en production.</p>

        <h3>Récapitulatif de votre commande :</h3>
        <table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>
            <tr style='background: #f0f0f0;'>
                <th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Produit</th>
                <th style='padding: 10px; text-align: center; border: 1px solid #ddd;'>Quantité</th>
                <th style='padding: 10px; text-align: right; border: 1px solid #ddd;'>Prix TTC</th>
            </tr>";

    foreach ($lignes as $ligne) {
        $details = "";
        if ($ligne['largeur'] && $ligne['hauteur']) {
            $details = " ({$ligne['largeur']}×{$ligne['hauteur']}cm)";
        }

        $message .= "
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd;'>
                    {$ligne['produit_nom']}{$details}<br>
                    <small style='color: #666;'>{$ligne['surface']}m² - {$ligne['impression']}</small>
                </td>
                <td style='padding: 10px; text-align: center; border: 1px solid #ddd;'>{$ligne['quantite']}</td>
                <td style='padding: 10px; text-align: right; border: 1px solid #ddd;'>" . number_format($ligne['prix_ligne_ttc'], 2) . " €</td>
            </tr>";
    }

    if ($commande['frais_port'] > 0) {
        $message .= "
            <tr>
                <td colspan='2' style='padding: 10px; border: 1px solid #ddd;'>Frais de port</td>
                <td style='padding: 10px; text-align: right; border: 1px solid #ddd;'>" . number_format($commande['frais_port'] * (1 + TVA_RATE), 2) . " €</td>
            </tr>";
    }

    $message .= "
            <tr style='font-weight: bold; background: #f9f9f9;'>
                <td colspan='2' style='padding: 15px; border: 1px solid #ddd;'>TOTAL TTC</td>
                <td style='padding: 15px; text-align: right; border: 1px solid #ddd; font-size: 18px; color: #e63946;'>
                    " . number_format($commande['total_ttc'], 2) . " €
                </td>
            </tr>
        </table>

        <h3>Adresse de livraison :</h3>
        <p style='background: #f9f9f9; padding: 10px; border-left: 3px solid #2b2d42;'>
            {$commande['client_prenom']} {$commande['client_nom']}<br>";

    if ($commande['client_entreprise']) {
        $message .= "{$commande['client_entreprise']}<br>";
    }

    $message .= "
            {$commande['adresse_livraison']}<br>
            {$commande['code_postal_livraison']} {$commande['ville_livraison']}<br>
            {$commande['pays_livraison']}
        </p>

        <h3>Prochaines étapes :</h3>
        <ol>
            <li>Votre commande entre en production (délai : 3-5 jours)</li>
            <li>Vous recevrez un email dès l'expédition avec le numéro de suivi</li>
            <li>Livraison sous 48-72h après expédition</li>
        </ol>

        <p>
            <strong>Besoin d'aide ?</strong><br>
            Contactez-nous : " . EMAIL_FROM . "<br>
            Téléphone : 01 23 45 67 89
        </p>

        <p>
            Merci de votre confiance !<br>
            L'équipe VisuPrint Pro
        </p>
    </body>
    </html>
    ";

    // Envoyer au client
    envoyerEmail($commande['client_email'], $sujet, $message, true);

    // Notifier l'admin
    $sujetAdmin = "Nouvelle commande payée : {$commande['numero_commande']}";
    $messageAdmin = "
    <p>Nouvelle commande payée !</p>
    <p>
        <strong>Numéro :</strong> {$commande['numero_commande']}<br>
        <strong>Client :</strong> {$commande['client_prenom']} {$commande['client_nom']}<br>
        <strong>Email :</strong> {$commande['client_email']}<br>
        <strong>Montant :</strong> " . number_format($commande['total_ttc'], 2) . " € TTC
    </p>
    <p><a href='" . SITE_URL . "/admin/commandes.php?id={$commande['id']}'>Voir la commande</a></p>
    ";

    envoyerEmail(EMAIL_ADMIN, $sujetAdmin, $messageAdmin, true);
}
