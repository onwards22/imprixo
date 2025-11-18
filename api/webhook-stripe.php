<?php
/**
 * Webhook Stripe - Imprixo
 * Traitement des événements Stripe
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Récupérer le payload
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    // Vérifier la signature
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        STRIPE_WEBHOOK_SECRET
    );

    $db = Database::getInstance();

    // Traiter l'événement
    switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;

            // Récupérer la commande via metadata
            $commandeId = $session->metadata->commande_id ?? null;

            if ($commandeId) {
                // Mettre à jour la commande
                $db->query(
                    "UPDATE commandes
                    SET statut_paiement = 'paye',
                        date_paiement = NOW(),
                        stripe_payment_intent_id = ?,
                        updated_at = NOW()
                    WHERE id = ?",
                    [$session->payment_intent, $commandeId]
                );

                // Récupérer la commande
                $commande = $db->fetchOne(
                    "SELECT * FROM commandes WHERE id = ?",
                    [$commandeId]
                );

                // Envoyer email confirmation paiement
                envoyerEmailPaiementConfirme($commandeId);

                // Logger
                error_log("Paiement confirmé pour commande #{$commande['numero_commande']}");
            }
            break;

        case 'payment_intent.payment_failed':
            $paymentIntent = $event->data->object;

            // Trouver la commande
            $commande = $db->fetchOne(
                "SELECT id FROM commandes WHERE stripe_payment_intent_id = ?",
                [$paymentIntent->id]
            );

            if ($commande) {
                // Marquer comme échoué
                $db->query(
                    "UPDATE commandes
                    SET statut_paiement = 'echoue',
                        updated_at = NOW()
                    WHERE id = ?",
                    [$commande['id']]
                );

                error_log("Paiement échoué pour commande #{$commande['id']}");
            }
            break;

        default:
            // Événement non géré
            error_log("Stripe webhook: événement non géré - " . $event->type);
    }

    http_response_code(200);
    echo json_encode(['success' => true]);

} catch (\UnexpectedValueException $e) {
    // Signature invalide
    http_response_code(400);
    error_log('Stripe webhook: signature invalide');
    exit();

} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Erreur vérification
    http_response_code(400);
    error_log('Stripe webhook: erreur vérification signature');
    exit();

} catch (Exception $e) {
    // Autre erreur
    http_response_code(500);
    error_log('Stripe webhook: ' . $e->getMessage());
    exit();
}
