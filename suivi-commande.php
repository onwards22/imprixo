<?php
/**
 * Suivi Commande Client - Imprixo
 * Tracking détaillé de commande avec timeline
 */

session_start();
require_once __DIR__ . '/api/config.php';

$commandeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$commandeId) {
    header('Location: /mon-compte.php');
    exit;
}

$db = Database::getInstance();

// Récupérer la commande
$commande = $db->fetchOne(
    "SELECT * FROM commandes WHERE id = ?",
    [$commandeId]
);

if (!$commande) {
    die('Commande non trouvée');
}

// Vérifier que c'est bien la commande du client connecté
$clientId = $_SESSION['client_id'] ?? null;
if ($commande['client_id'] != $clientId) {
    die('Accès non autorisé');
}

// Récupérer les lignes de commande
$lignes = $db->fetchAll(
    "SELECT * FROM lignes_commande WHERE commande_id = ?",
    [$commandeId]
);

// Récupérer les fichiers
$fichiers = $db->fetchAll(
    "SELECT * FROM fichiers_impression WHERE commande_id = ?",
    [$commandeId]
);

// Timeline statuts
$timeline = [
    'nouveau' => ['label' => 'Commande reçue', 'icon' => '✅', 'active' => false],
    'confirme' => ['label' => 'Commande confirmée', 'icon' => '✓', 'active' => false],
    'en_production' => ['label' => 'En production', 'icon' => '🖨️', 'active' => false],
    'expedie' => ['label' => 'Expédiée', 'icon' => '📦', 'active' => false],
    'livre' => ['label' => 'Livrée', 'icon' => '🎉', 'active' => false],
];

// Activer les statuts jusqu'au statut actuel
$statutActuel = $commande['statut'];
$found = false;
foreach ($timeline as $key => &$step) {
    if (!$found) {
        $step['active'] = true;
    }
    if ($key === $statutActuel) {
        $found = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi Commande <?php echo $commande['numero_commande']; ?> - Imprixo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 40px;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header p {
            opacity: 0.9;
        }

        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .back-link:hover {
            opacity: 1;
        }

        .container {
            max-width: 1200px;
            margin: -40px auto 40px;
            padding: 0 40px;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 20px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 40px 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 4px;
            background: #e0e0e0;
            z-index: 0;
        }

        .timeline-step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .timeline-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #95a5a6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 15px;
            border: 4px solid white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .timeline-step.active .timeline-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .timeline-label {
            font-size: 14px;
            color: #95a5a6;
            font-weight: 500;
        }

        .timeline-step.active .timeline-label {
            color: #2c3e50;
            font-weight: 600;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .info-label {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 18px;
            color: #2c3e50;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px;
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
            font-size: 13px;
        }

        td {
            padding: 15px 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.paye { background: #27ae60; color: white; }
        .badge.en_attente { background: #f39c12; color: white; }
        .badge.valide { background: #27ae60; color: white; }

        .tracking-info {
            background: #e8f5e9;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #27ae60;
        }

        .tracking-info h4 {
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-block;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <a href="/mon-compte.php" class="back-link">← Retour à mon compte</a>
            <h1>Suivi de commande</h1>
            <p>N° <?php echo htmlspecialchars($commande['numero_commande']); ?></p>
        </div>
    </div>

    <div class="container">
        <!-- Timeline -->
        <div class="section">
            <div class="timeline">
                <?php foreach ($timeline as $key => $step): ?>
                    <div class="timeline-step <?php echo $step['active'] ? 'active' : ''; ?>">
                        <div class="timeline-icon"><?php echo $step['icon']; ?></div>
                        <div class="timeline-label"><?php echo $step['label']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Informations principales -->
        <div class="section">
            <h2 class="section-title">📋 Informations de la commande</h2>

            <div class="info-grid">
                <div class="info-card">
                    <div class="info-label">Date de commande</div>
                    <div class="info-value"><?php echo date('d/m/Y à H:i', strtotime($commande['created_at'])); ?></div>
                </div>

                <div class="info-card">
                    <div class="info-label">Montant total</div>
                    <div class="info-value" style="color: #27ae60;"><?php echo number_format($commande['total_ttc'], 2, ',', ' '); ?> €</div>
                </div>

                <div class="info-card">
                    <div class="info-label">Paiement</div>
                    <div class="info-value">
                        <span class="badge <?php echo $commande['statut_paiement']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $commande['statut_paiement'])); ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if ($commande['numero_suivi']): ?>
                <div class="tracking-info">
                    <h4>📦 Informations de livraison</h4>
                    <p><strong>Transporteur :</strong> <?php echo htmlspecialchars($commande['transporteur']); ?></p>
                    <p><strong>Numéro de suivi :</strong> <?php echo htmlspecialchars($commande['numero_suivi']); ?></p>
                    <p><strong>Date d'expédition :</strong> <?php echo date('d/m/Y', strtotime($commande['date_expedition'])); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Produits commandés -->
        <div class="section">
            <h2 class="section-title">🛒 Produits commandés</h2>

            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Configuration</th>
                        <th>Quantité</th>
                        <th>Prix TTC</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lignes as $ligne): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($ligne['produit_nom']); ?></strong><br>
                                <small style="color: #666;">Réf: <?php echo htmlspecialchars($ligne['produit_code']); ?></small>
                            </td>
                            <td>
                                <?php echo $ligne['surface']; ?> m²
                                <?php if ($ligne['largeur'] && $ligne['hauteur']): ?>
                                    <br><small>(<?php echo $ligne['largeur']; ?>×<?php echo $ligne['hauteur']; ?>cm)</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $ligne['quantite']; ?></td>
                            <td><strong><?php echo number_format($ligne['prix_ligne_ttc'], 2, ',', ' '); ?> €</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Fichiers uploadés -->
        <?php if (!empty($fichiers)): ?>
            <div class="section">
                <h2 class="section-title">📁 Fichiers d'impression</h2>

                <table>
                    <thead>
                        <tr>
                            <th>Nom du fichier</th>
                            <th>Taille</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fichiers as $fichier): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($fichier['nom_original']); ?></strong></td>
                                <td><?php echo round($fichier['taille_octets'] / (1024 * 1024), 2); ?> MB</td>
                                <td><span class="badge <?php echo $fichier['statut']; ?>"><?php echo ucfirst($fichier['statut']); ?></span></td>
                                <td>
                                    <a href="/telecharger-fichier.php?id=<?php echo $fichier['id']; ?>" class="btn btn-primary">Télécharger</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Adresses -->
        <div class="section">
            <h2 class="section-title">📍 Adresses</h2>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <div>
                    <h4 style="margin-bottom: 10px; color: #666;">Facturation</h4>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <?php echo nl2br(htmlspecialchars($commande['adresse_facturation'])); ?><br>
                        <?php echo htmlspecialchars($commande['code_postal_facturation']); ?>
                        <?php echo htmlspecialchars($commande['ville_facturation']); ?><br>
                        <?php echo htmlspecialchars($commande['pays_facturation']); ?>
                    </div>
                </div>

                <div>
                    <h4 style="margin-bottom: 10px; color: #666;">Livraison</h4>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <?php echo nl2br(htmlspecialchars($commande['adresse_livraison'])); ?><br>
                        <?php echo htmlspecialchars($commande['code_postal_livraison']); ?>
                        <?php echo htmlspecialchars($commande['ville_livraison']); ?><br>
                        <?php echo htmlspecialchars($commande['pays_livraison']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
