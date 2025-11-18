<?php
/**
 * Espace Client - Imprixo
 * Mon compte avec historique commandes et fichiers
 */

session_start();
require_once __DIR__ . '/api/config.php';

// Vérifier si client connecté (par email dans session)
$clientEmail = $_SESSION['client_email'] ?? null;
$clientId = $_SESSION['client_id'] ?? null;

if (!$clientEmail) {
    // Rediriger vers formulaire connexion
    header('Location: /connexion.php');
    exit;
}

$db = Database::getInstance();

// Récupérer infos client
$client = $db->fetchOne(
    "SELECT * FROM clients WHERE id = ?",
    [$clientId]
);

// Récupérer commandes
$commandes = $db->fetchAll(
    "SELECT * FROM commandes WHERE client_id = ? ORDER BY created_at DESC",
    [$clientId]
);

// Statistiques
$stats = $db->fetchOne(
    "SELECT
        COUNT(*) as total_commandes,
        COALESCE(SUM(CASE WHEN statut_paiement = 'paye' THEN total_ttc ELSE 0 END), 0) as ca_total
    FROM commandes
    WHERE client_id = ?",
    [$clientId]
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte - Imprixo</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-welcome h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .user-welcome p {
            opacity: 0.9;
        }

        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: -40px auto 40px;
            padding: 0 40px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
        }

        .stat-value.price::after {
            content: ' €';
            font-size: 20px;
            color: #7f8c8d;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            padding: 25px 30px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h2 {
            font-size: 22px;
        }

        .section-content {
            padding: 30px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .info-label {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 500;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px 30px;
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            padding: 20px 30px;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f8f9fa;
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
        .badge.livre { background: #16a085; color: white; }
        .badge.paye { background: #27ae60; color: white; }
        .badge.en_attente { background: #95a5a6; color: white; }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e0e0e0;
            padding: 0 30px;
        }

        .tab {
            padding: 15px 25px;
            cursor: pointer;
            color: #666;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.3s;
        }

        .tab:hover,
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
            padding: 30px;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="user-welcome">
                <h1>👋 Bonjour <?php echo htmlspecialchars($client['prenom']); ?> !</h1>
                <p><?php echo htmlspecialchars($client['email']); ?></p>
            </div>
            <a href="/deconnexion.php" class="btn-logout">Déconnexion</a>
        </div>
    </div>

    <div class="container">
        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Commandes totales</div>
                <div class="stat-value"><?php echo $stats['total_commandes']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Montant total dépensé</div>
                <div class="stat-value price"><?php echo number_format($stats['ca_total'], 2, ',', ' '); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Client depuis</div>
                <div class="stat-value" style="font-size: 24px;">
                    <?php echo date('d/m/Y', strtotime($client['created_at'])); ?>
                </div>
            </div>
        </div>

        <!-- Onglets -->
        <div class="section">
            <div class="tabs">
                <div class="tab active" onclick="switchTab('commandes')">📦 Mes Commandes</div>
                <div class="tab" onclick="switchTab('infos')">👤 Mes Informations</div>
                <div class="tab" onclick="switchTab('fichiers')">📁 Mes Fichiers</div>
            </div>

            <!-- Onglet Commandes -->
            <div class="tab-content active" id="tab-commandes">
                <?php if (empty($commandes)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <h3>Aucune commande pour le moment</h3>
                        <p style="margin-top: 10px;">Commencez dès maintenant votre première impression !</p>
                        <a href="/" class="btn btn-primary" style="margin-top: 20px;">Découvrir nos produits</a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Paiement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commandes as $cmd): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($cmd['numero_commande']); ?></strong></td>
                                    <td><?php echo date('d/m/Y', strtotime($cmd['created_at'])); ?></td>
                                    <td><strong><?php echo number_format($cmd['total_ttc'], 2, ',', ' '); ?> €</strong></td>
                                    <td><span class="badge <?php echo $cmd['statut']; ?>"><?php echo ucfirst(str_replace('_', ' ', $cmd['statut'])); ?></span></td>
                                    <td><span class="badge <?php echo $cmd['statut_paiement']; ?>"><?php echo ucfirst($cmd['statut_paiement']); ?></span></td>
                                    <td>
                                        <a href="/suivi-commande.php?id=<?php echo $cmd['id']; ?>" class="btn btn-primary">Voir détails</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Onglet Informations -->
            <div class="tab-content" id="tab-infos">
                <h3 style="margin-bottom: 20px;">Informations personnelles</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nom complet</div>
                        <div class="info-value"><?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($client['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Téléphone</div>
                        <div class="info-value"><?php echo htmlspecialchars($client['telephone'] ?? 'Non renseigné'); ?></div>
                    </div>
                </div>

                <h3 style="margin: 30px 0 20px;">Adresse de facturation</h3>
                <div class="info-item" style="max-width: 500px;">
                    <div class="info-value">
                        <?php echo nl2br(htmlspecialchars($client['adresse_facturation'] ?? 'Non renseignée')); ?><br>
                        <?php if ($client['code_postal_facturation']): ?>
                            <?php echo htmlspecialchars($client['code_postal_facturation']); ?>
                            <?php echo htmlspecialchars($client['ville_facturation']); ?><br>
                            <?php echo htmlspecialchars($client['pays_facturation']); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Onglet Fichiers -->
            <div class="tab-content" id="tab-fichiers">
                <?php
                $fichiers = $db->fetchAll(
                    "SELECT fi.*, c.numero_commande
                    FROM fichiers_impression fi
                    LEFT JOIN commandes c ON fi.commande_id = c.id
                    WHERE c.client_id = ?
                    ORDER BY fi.created_at DESC",
                    [$clientId]
                );
                ?>

                <?php if (empty($fichiers)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📁</div>
                        <h3>Aucun fichier uploadé</h3>
                        <p style="margin-top: 10px;">Vos fichiers d'impression apparaîtront ici</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom du fichier</th>
                                <th>Commande</th>
                                <th>Taille</th>
                                <th>Statut</th>
                                <th>Date upload</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fichiers as $fichier): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($fichier['nom_original']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($fichier['numero_commande'] ?? '-'); ?></td>
                                    <td><?php echo round($fichier['taille_octets'] / (1024 * 1024), 2); ?> MB</td>
                                    <td><span class="badge <?php echo $fichier['statut']; ?>"><?php echo ucfirst($fichier['statut']); ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($fichier['created_at'])); ?></td>
                                    <td>
                                        <a href="/telecharger-fichier.php?id=<?php echo $fichier['id']; ?>" class="btn btn-primary">Télécharger</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Masquer tous les contenus
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Désactiver tous les onglets
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Activer l'onglet cliqué
            event.target.classList.add('active');

            // Afficher le contenu correspondant
            document.getElementById('tab-' + tabName).classList.add('active');
        }
    </script>
</body>
</html>
