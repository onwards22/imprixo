<?php
/**
 * Gestion Clients - Imprixo Admin
 */

require_once __DIR__ . '/auth.php';

verifierAdminConnecte();
$admin = getAdminInfo();
$db = Database::getInstance();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Filtres
$filtreRecherche = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

// Construire la requête
$where = [];
$params = [];

if ($filtreRecherche) {
    $where[] = '(email LIKE ? OR nom LIKE ? OR prenom LIKE ? OR telephone LIKE ?)';
    $search = "%$filtreRecherche%";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

$whereClause = empty($where) ? '1=1' : implode(' AND ', $where);

// Compter le total
$total = $db->fetchOne(
    "SELECT COUNT(*) as count FROM clients WHERE $whereClause",
    $params
)['count'];

$totalPages = ceil($total / $perPage);

// Récupérer les clients
$clients = $db->fetchAll(
    "SELECT * FROM clients
    WHERE $whereClause
    ORDER BY created_at DESC
    LIMIT $perPage OFFSET $offset",
    $params
);

// Stats
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM clients")['count'],
    'avec_commandes' => $db->fetchOne("SELECT COUNT(DISTINCT client_id) as count FROM commandes")['count'],
    'ca_total' => $db->fetchOne("SELECT COALESCE(SUM(total_ttc), 0) as total FROM commandes WHERE statut_paiement = 'paye'")['total'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Clients - Imprixo Admin</title>
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
            max-width: 1600px;
            margin: 0 auto;
            padding: 40px;
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stat-label {
            font-size: 13px;
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

        .search-bar {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .search-bar form {
            display: flex;
            gap: 15px;
        }

        .search-bar input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .section-header {
            padding: 20px 25px;
            border-bottom: 2px solid #f0f0f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px 25px;
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            border-bottom: 2px solid #e0e0e0;
        }

        td {
            padding: 18px 25px;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .client-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .client-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }

        .client-details strong {
            display: block;
            margin-bottom: 3px;
        }

        .client-details small {
            color: #666;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            color: #666;
            background: white;
            border: 2px solid #e0e0e0;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination .active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
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

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎨 Imprixo - Administration</h1>
        <div class="user-info">
            👤 <?php echo htmlspecialchars($admin['prenom'] ?? $admin['username']); ?>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </div>

    <nav class="nav">
        <ul>
            <li><a href="index.php">📊 Dashboard</a></li>
            <li><a href="commandes.php">📦 Commandes</a></li>
            <li><a href="produits.php">🏷️ Produits</a></li>
            <li><a href="clients.php" class="active">👥 Clients</a></li>
            <li><a href="parametres.php">⚙️ Paramètres</a></li>
        </ul>
    </nav>

    <div class="container">
        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total clients</div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Clients avec commandes</div>
                <div class="stat-value" style="color: #27ae60;"><?php echo $stats['avec_commandes']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">CA total</div>
                <div class="stat-value price" style="color: #667eea;"><?php echo number_format($stats['ca_total'], 2, ',', ' '); ?></div>
            </div>
        </div>

        <!-- Recherche -->
        <div class="search-bar">
            <form method="GET">
                <input type="text" name="search" placeholder="🔍 Rechercher par email, nom, prénom ou téléphone..." value="<?php echo htmlspecialchars($filtreRecherche); ?>">
                <button type="submit" class="btn btn-primary">Rechercher</button>
                <?php if ($filtreRecherche): ?>
                    <a href="clients.php" class="btn" style="background: #95a5a6; color: white;">Réinitialiser</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Liste des clients -->
        <div class="section">
            <div class="section-header">
                <h2>👥 Liste des clients (<?php echo $total; ?>)</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Téléphone</th>
                        <th>Ville</th>
                        <th>Commandes</th>
                        <th>CA Total</th>
                        <th>Inscription</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clients)): ?>
                        <tr>
                            <td colspan="6" class="no-results">
                                Aucun client trouvé
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clients as $client): ?>
                            <?php
                            // Récupérer les stats du client
                            $clientStats = $db->fetchOne(
                                "SELECT
                                    COUNT(*) as nb_commandes,
                                    COALESCE(SUM(total_ttc), 0) as ca_total
                                FROM commandes
                                WHERE client_id = ? AND statut_paiement = 'paye'",
                                [$client['id']]
                            );
                            ?>
                            <tr>
                                <td>
                                    <div class="client-info">
                                        <div class="client-avatar">
                                            <?php echo strtoupper(substr($client['prenom'], 0, 1) . substr($client['nom'], 0, 1)); ?>
                                        </div>
                                        <div class="client-details">
                                            <strong><?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?></strong>
                                            <small><?php echo htmlspecialchars($client['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($client['telephone'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($client['ville_facturation'] ?? '-'); ?></td>
                                <td><strong><?php echo $clientStats['nb_commandes']; ?></strong> commande<?php echo $clientStats['nb_commandes'] > 1 ? 's' : ''; ?></td>
                                <td><strong><?php echo number_format($clientStats['ca_total'], 2, ',', ' '); ?> €</strong></td>
                                <td><?php echo date('d/m/Y', strtotime($client['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $filtreRecherche ? '&search=' . urlencode($filtreRecherche) : ''; ?>">← Précédent</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo $filtreRecherche ? '&search=' . urlencode($filtreRecherche) : ''; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $filtreRecherche ? '&search=' . urlencode($filtreRecherche) : ''; ?>">Suivant →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
