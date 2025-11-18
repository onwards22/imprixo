<?php
/**
 * Détail Commande - VisuPrint Pro Admin
 */

require_once __DIR__ . '/auth.php';

verifierAdminConnecte();
$admin = getAdminInfo();
$db = Database::getInstance();

$commandeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$commandeId) {
    header('Location: /admin/commandes.php');
    exit;
}

// Récupérer la commande
$commande = $db->fetchOne(
    "SELECT * FROM commandes WHERE id = ?",
    [$commandeId]
);

if (!$commande) {
    die('Commande non trouvée');
}

// Récupérer les lignes de commande
$lignes = $db->fetchAll(
    "SELECT * FROM lignes_commande WHERE commande_id = ?",
    [$commandeId]
);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_statut':
            $nouveauStatut = cleanInput($_POST['statut']);
            $db->query(
                "UPDATE commandes SET statut = ?, updated_at = NOW() WHERE id = ?",
                [$nouveauStatut, $commandeId]
            );
            logAdminAction($admin['id'], 'update_commande', "Statut commande {$commande['numero_commande']} -> $nouveauStatut");
            header("Location: /admin/commande.php?id=$commandeId&success=1");
            exit;
            break;

        case 'add_tracking':
            $transporteur = cleanInput($_POST['transporteur']);
            $numeroSuivi = cleanInput($_POST['numero_suivi']);
            $db->query(
                "UPDATE commandes
                SET transporteur = ?, numero_suivi = ?, date_expedition = NOW(), statut = 'expedie'
                WHERE id = ?",
                [$transporteur, $numeroSuivi, $commandeId]
            );
            logAdminAction($admin['id'], 'add_tracking', "Ajout suivi commande {$commande['numero_commande']}");
            // TODO: Envoyer email expédition
            header("Location: /admin/commande.php?id=$commandeId&success=2");
            exit;
            break;

        case 'add_note':
            $note = cleanInput($_POST['note']);
            $db->query(
                "UPDATE commandes SET notes_admin = ? WHERE id = ?",
                [$note, $commandeId]
            );
            logAdminAction($admin['id'], 'add_note', "Note ajoutée commande {$commande['numero_commande']}");
            header("Location: /admin/commande.php?id=$commandeId&success=3");
            exit;
            break;
    }
}

$success = isset($_GET['success']) ? (int)$_GET['success'] : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande <?php echo $commande['numero_commande']; ?> - Admin</title>
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
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px;
        }

        .back-link {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .section-title {
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .info-label {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
            border-bottom: 1px solid #ecf0f1;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.nouveau { background: #3498db; color: white; }
        .badge.confirme { background: #9b59b6; color: white; }
        .badge.en_production { background: #f39c12; color: white; }
        .badge.expedie { background: #27ae60; color: white; }
        .badge.paye { background: #27ae60; color: white; }
        .badge.en_attente { background: #95a5a6; color: white; }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        select, input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
            font-family: inherit;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .address-box {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <a href="/admin/index.php" class="back-link">← Retour au dashboard</a>
            <h1>Commande <?php echo $commande['numero_commande']; ?></h1>
        </div>
    </div>

    <div class="container">
        <?php if ($success === 1): ?>
            <div class="success-message">✓ Statut mis à jour avec succès</div>
        <?php elseif ($success === 2): ?>
            <div class="success-message">✓ Informations de suivi ajoutées</div>
        <?php elseif ($success === 3): ?>
            <div class="success-message">✓ Note enregistrée</div>
        <?php endif; ?>

        <!-- Informations principales -->
        <div class="section">
            <h2 class="section-title">📋 Informations générales</h2>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Client</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($commande['client_prenom'] . ' ' . $commande['client_nom']); ?>
                    </div>
                    <div style="margin-top: 5px; font-size: 14px; color: #666;">
                        <?php echo htmlspecialchars($commande['client_email']); ?>
                    </div>
                    <?php if ($commande['client_telephone']): ?>
                        <div style="margin-top: 5px; font-size: 14px; color: #666;">
                            📞 <?php echo htmlspecialchars($commande['client_telephone']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="info-item">
                    <div class="info-label">Montant total</div>
                    <div class="info-value" style="color: #27ae60; font-size: 24px;">
                        <?php echo number_format($commande['total_ttc'], 2, ',', ' '); ?> €
                    </div>
                    <div style="margin-top: 5px; font-size: 14px; color: #666;">
                        HT: <?php echo number_format($commande['total_ht'], 2, ',', ' '); ?> €
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Date commande</div>
                    <div class="info-value">
                        <?php echo date('d/m/Y H:i', strtotime($commande['created_at'])); ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Statut</div>
                    <div>
                        <span class="badge <?php echo $commande['statut']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $commande['statut'])); ?>
                        </span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Paiement</div>
                    <div>
                        <span class="badge <?php echo $commande['statut_paiement']; ?>">
                            <?php echo ucfirst($commande['statut_paiement']); ?>
                        </span>
                    </div>
                    <?php if ($commande['date_paiement']): ?>
                        <div style="margin-top: 5px; font-size: 12px; color: #666;">
                            Le <?php echo date('d/m/Y H:i', strtotime($commande['date_paiement'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
                        <th>Prix unitaire</th>
                        <th>Total TTC</th>
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
                                <br><small><?php echo ucfirst($ligne['impression']); ?> face</small>
                            </td>
                            <td><?php echo $ligne['quantite']; ?></td>
                            <td><?php echo number_format($ligne['prix_unitaire_m2'], 2, ',', ' '); ?> €/m²</td>
                            <td><strong><?php echo number_format($ligne['prix_ligne_ttc'], 2, ',', ' '); ?> €</strong></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background: #f8f9fa; font-weight: bold;">
                        <td colspan="4" style="text-align: right;">TOTAL TTC</td>
                        <td style="font-size: 18px; color: #27ae60;">
                            <?php echo number_format($commande['total_ttc'], 2, ',', ' '); ?> €
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Adresses -->
        <div class="section">
            <h2 class="section-title">📍 Adresses</h2>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h3 style="margin-bottom: 10px; font-size: 14px; color: #666;">Facturation</h3>
                    <div class="address-box">
                        <?php echo nl2br(htmlspecialchars($commande['adresse_facturation'])); ?><br>
                        <?php echo htmlspecialchars($commande['code_postal_facturation']); ?>
                        <?php echo htmlspecialchars($commande['ville_facturation']); ?><br>
                        <?php echo htmlspecialchars($commande['pays_facturation']); ?>
                    </div>
                </div>

                <div>
                    <h3 style="margin-bottom: 10px; font-size: 14px; color: #666;">Livraison</h3>
                    <div class="address-box">
                        <?php echo nl2br(htmlspecialchars($commande['adresse_livraison'])); ?><br>
                        <?php echo htmlspecialchars($commande['code_postal_livraison']); ?>
                        <?php echo htmlspecialchars($commande['ville_livraison']); ?><br>
                        <?php echo htmlspecialchars($commande['pays_livraison']); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Changer statut -->
            <div class="section">
                <h2 class="section-title">🔄 Changer le statut</h2>

                <form method="POST">
                    <input type="hidden" name="action" value="update_statut">

                    <div class="form-group">
                        <label>Nouveau statut</label>
                        <select name="statut">
                            <option value="nouveau" <?php echo $commande['statut'] === 'nouveau' ? 'selected' : ''; ?>>Nouveau</option>
                            <option value="confirme" <?php echo $commande['statut'] === 'confirme' ? 'selected' : ''; ?>>Confirmé</option>
                            <option value="en_production" <?php echo $commande['statut'] === 'en_production' ? 'selected' : ''; ?>>En production</option>
                            <option value="expedie" <?php echo $commande['statut'] === 'expedie' ? 'selected' : ''; ?>>Expédié</option>
                            <option value="livre" <?php echo $commande['statut'] === 'livre' ? 'selected' : ''; ?>>Livré</option>
                            <option value="annule" <?php echo $commande['statut'] === 'annule' ? 'selected' : ''; ?>>Annulé</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                </form>
            </div>

            <!-- Ajouter suivi -->
            <div class="section">
                <h2 class="section-title">📦 Informations d'expédition</h2>

                <?php if ($commande['numero_suivi']): ?>
                    <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <strong>Transporteur:</strong> <?php echo htmlspecialchars($commande['transporteur']); ?><br>
                        <strong>Numéro de suivi:</strong> <?php echo htmlspecialchars($commande['numero_suivi']); ?><br>
                        <strong>Date d'expédition:</strong> <?php echo date('d/m/Y', strtotime($commande['date_expedition'])); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="add_tracking">

                    <div class="form-group">
                        <label>Transporteur</label>
                        <input type="text" name="transporteur" value="<?php echo htmlspecialchars($commande['transporteur'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Numéro de suivi</label>
                        <input type="text" name="numero_suivi" value="<?php echo htmlspecialchars($commande['numero_suivi'] ?? ''); ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </form>
            </div>
        </div>

        <!-- Notes -->
        <div class="section">
            <h2 class="section-title">📝 Notes internes</h2>

            <form method="POST">
                <input type="hidden" name="action" value="add_note">

                <div class="form-group">
                    <textarea name="note" placeholder="Notes internes (non visibles par le client)"><?php echo htmlspecialchars($commande['notes_admin'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Enregistrer la note</button>
            </form>
        </div>
    </div>
</body>
</html>
