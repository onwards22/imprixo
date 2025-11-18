<?php
/**
 * Dashboard Admin - VisuPrint Pro
 */

require_once __DIR__ . '/auth.php';

verifierAdminConnecte();
$admin = getAdminInfo();
$db = Database::getInstance();

// Statistiques
$stats = [
    'commandes_jour' => $db->fetchOne("SELECT COUNT(*) as count FROM commandes WHERE DATE(created_at) = CURDATE()")['count'],
    'commandes_mois' => $db->fetchOne("SELECT COUNT(*) as count FROM commandes WHERE MONTH(created_at) = MONTH(CURDATE())")['count'],
    'ca_jour' => $db->fetchOne("SELECT COALESCE(SUM(total_ttc), 0) as total FROM commandes WHERE DATE(created_at) = CURDATE() AND statut_paiement = 'paye'")['total'],
    'ca_mois' => $db->fetchOne("SELECT COALESCE(SUM(total_ttc), 0) as total FROM commandes WHERE MONTH(created_at) = MONTH(CURDATE()) AND statut_paiement = 'paye'")['total'],
    'commandes_attente' => $db->fetchOne("SELECT COUNT(*) as count FROM commandes WHERE statut = 'nouveau'")['count'],
    'commandes_production' => $db->fetchOne("SELECT COUNT(*) as count FROM commandes WHERE statut = 'en_production'")['count'],
    'total_produits' => $db->fetchOne("SELECT COUNT(*) as count FROM produits")['count'],
    'total_clients' => $db->fetchOne("SELECT COUNT(*) as count FROM clients")['count'],
];

// Dernières commandes
$dernieresCommandes = $db->fetchAll(
    "SELECT * FROM commandes ORDER BY created_at DESC LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VisuPrint Pro Admin</title>
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 24px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav {
            background: white;
            padding: 0 40px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .nav ul {
            list-style: none;
            display: flex;
            gap: 30px;
        }

        .nav a {
            display: block;
            padding: 15px 0;
            color: #666;
            text-decoration: none;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .nav a:hover,
        .nav a.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
        }

        .stat-card.orange { border-left-color: #f39c12; }
        .stat-card.green { border-left-color: #27ae60; }
        .stat-card.red { border-left-color: #e74c3c; }

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

        .stat-value.price::before {
            content: '';
        }

        .stat-value.price::after {
            content: ' €';
            font-size: 20px;
            color: #7f8c8d;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .section-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: #2c3e50;
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
            text-transform: uppercase;
        }

        td {
            padding: 15px 12px;
            border-bottom: 1px solid #ecf0f1;
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
        .badge.paye { background: #27ae60; color: white; }
        .badge.en_attente { background: #95a5a6; color: white; }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎨 VisuPrint Pro - Administration</h1>
        <div class="header-right">
            <div class="user-info">
                👤 <?php echo htmlspecialchars($admin['prenom'] ?? $admin['username']); ?>
                <span style="opacity: 0.7">(<?php echo $admin['role']; ?>)</span>
            </div>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </div>

    <nav class="nav">
        <ul>
            <li><a href="index.php" class="active">📊 Dashboard</a></li>
            <li><a href="commandes.php">📦 Commandes</a></li>
            <li><a href="produits.php">🏷️ Produits</a></li>
            <li><a href="clients.php">👥 Clients</a></li>
            <li><a href="parametres.php">⚙️ Paramètres</a></li>
        </ul>
    </nav>

    <div class="container">
        <!-- Statistiques principales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Commandes aujourd'hui</div>
                <div class="stat-value"><?php echo $stats['commandes_jour']; ?></div>
            </div>

            <div class="stat-card orange">
                <div class="stat-label">Commandes ce mois</div>
                <div class="stat-value"><?php echo $stats['commandes_mois']; ?></div>
            </div>

            <div class="stat-card green">
                <div class="stat-label">CA aujourd'hui</div>
                <div class="stat-value price"><?php echo number_format($stats['ca_jour'], 2, ',', ' '); ?></div>
            </div>

            <div class="stat-card green">
                <div class="stat-label">CA ce mois</div>
                <div class="stat-value price"><?php echo number_format($stats['ca_mois'], 2, ',', ' '); ?></div>
            </div>

            <div class="stat-card red">
                <div class="stat-label">En attente</div>
                <div class="stat-value"><?php echo $stats['commandes_attente']; ?></div>
            </div>

            <div class="stat-card orange">
                <div class="stat-label">En production</div>
                <div class="stat-value"><?php echo $stats['commandes_production']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Produits</div>
                <div class="stat-value"><?php echo $stats['total_produits']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Clients</div>
                <div class="stat-value"><?php echo $stats['total_clients']; ?></div>
            </div>
        </div>

        <!-- Dernières commandes -->
        <div class="section">
            <h2 class="section-title">📋 Dernières commandes</h2>

            <table>
                <thead>
                    <tr>
                        <th>N° Commande</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Paiement</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dernieresCommandes)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #95a5a6;">
                                Aucune commande pour le moment
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dernieresCommandes as $cmd): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cmd['numero_commande']); ?></strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($cmd['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($cmd['client_prenom'] . ' ' . $cmd['client_nom']); ?></td>
                                <td><strong><?php echo number_format($cmd['total_ttc'], 2, ',', ' '); ?> €</strong></td>
                                <td><span class="badge <?php echo $cmd['statut']; ?>"><?php echo ucfirst($cmd['statut']); ?></span></td>
                                <td><span class="badge <?php echo $cmd['statut_paiement']; ?>"><?php echo ucfirst($cmd['statut_paiement']); ?></span></td>
                                <td>
                                    <a href="commande.php?id=<?php echo $cmd['id']; ?>" class="btn btn-primary">Voir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
