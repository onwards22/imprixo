<?php
/**
 * Liste Commandes - VisuPrint Pro Admin
 * Page complète de gestion des commandes avec filtres et actions
 */

require_once __DIR__ . '/auth.php';

verifierAdminConnecte();
$admin = getAdminInfo();
$db = Database::getInstance();

// Paramètres de pagination et filtres
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Filtres
$filtreStatut = isset($_GET['statut']) ? cleanInput($_GET['statut']) : '';
$filtrePaiement = isset($_GET['paiement']) ? cleanInput($_GET['paiement']) : '';
$filtreRecherche = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$filtreDateDebut = isset($_GET['date_debut']) ? cleanInput($_GET['date_debut']) : '';
$filtreDateFin = isset($_GET['date_fin']) ? cleanInput($_GET['date_fin']) : '';

// Construire la requête
$where = ['1=1'];
$params = [];

if ($filtreStatut) {
    $where[] = 'statut = ?';
    $params[] = $filtreStatut;
}

if ($filtrePaiement) {
    $where[] = 'statut_paiement = ?';
    $params[] = $filtrePaiement;
}

if ($filtreRecherche) {
    $where[] = '(numero_commande LIKE ? OR client_email LIKE ? OR client_nom LIKE ? OR client_prenom LIKE ?)';
    $search = "%$filtreRecherche%";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

if ($filtreDateDebut) {
    $where[] = 'DATE(created_at) >= ?';
    $params[] = $filtreDateDebut;
}

if ($filtreDateFin) {
    $where[] = 'DATE(created_at) <= ?';
    $params[] = $filtreDateFin;
}

$whereClause = implode(' AND ', $where);

// Compter le total
$total = $db->fetchOne(
    "SELECT COUNT(*) as count FROM commandes WHERE $whereClause",
    $params
)['count'];

$totalPages = ceil($total / $perPage);

// Récupérer les commandes
$commandes = $db->fetchAll(
    "SELECT * FROM commandes
    WHERE $whereClause
    ORDER BY created_at DESC
    LIMIT $perPage OFFSET $offset",
    $params
);

// Statistiques rapides
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM commandes")['count'],
    'nouveau' => $db->fetchOne("SELECT COUNT(*) as count FROM commandes WHERE statut = 'nouveau'")['count'],
    'en_production' => $db->fetchOne("SELECT COUNT(*) as count FROM commandes WHERE statut = 'en_production'")['count'],
    'expedie' => $db->fetchOne("SELECT COUNT(*) as count FROM commandes WHERE statut = 'expedie'")['count'],
    'attente_paiement' => $db->fetchOne("SELECT COUNT(*) as count FROM commandes WHERE statut_paiement = 'en_attente'")['count'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Commandes - Imprixo Admin</title>
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .stat-label {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
        }

        .filters {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
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

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        .badge.annule { background: #e74c3c; color: white; }
        .badge.paye { background: #27ae60; color: white; }
        .badge.en_attente { background: #95a5a6; color: white; }
        .badge.echoue { background: #e74c3c; color: white; }
        .badge.rembourse { background: #34495e; color: white; }

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

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
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
            <li><a href="commandes.php" class="active">📦 Commandes</a></li>
            <li><a href="produits.php">🏷️ Produits</a></li>
            <li><a href="clients.php">👥 Clients</a></li>
            <li><a href="parametres.php">⚙️ Paramètres</a></li>
        </ul>
    </nav>

    <div class="container">
        <!-- Statistiques rapides -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total commandes</div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Nouvelles</div>
                <div class="stat-value" style="color: #3498db;"><?php echo $stats['nouveau']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">En production</div>
                <div class="stat-value" style="color: #f39c12;"><?php echo $stats['en_production']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Expédiées</div>
                <div class="stat-value" style="color: #27ae60;"><?php echo $stats['expedie']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Attente paiement</div>
                <div class="stat-value" style="color: #e74c3c;"><?php echo $stats['attente_paiement']; ?></div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filters">
            <form method="GET">
                <div class="filters-grid">
                    <div class="form-group">
                        <label>Recherche</label>
                        <input type="text" name="search" placeholder="N° commande, email, nom..." value="<?php echo htmlspecialchars($filtreRecherche); ?>">
                    </div>

                    <div class="form-group">
                        <label>Statut</label>
                        <select name="statut">
                            <option value="">Tous</option>
                            <option value="nouveau" <?php echo $filtreStatut === 'nouveau' ? 'selected' : ''; ?>>Nouveau</option>
                            <option value="confirme" <?php echo $filtreStatut === 'confirme' ? 'selected' : ''; ?>>Confirmé</option>
                            <option value="en_production" <?php echo $filtreStatut === 'en_production' ? 'selected' : ''; ?>>En production</option>
                            <option value="expedie" <?php echo $filtreStatut === 'expedie' ? 'selected' : ''; ?>>Expédié</option>
                            <option value="livre" <?php echo $filtreStatut === 'livre' ? 'selected' : ''; ?>>Livré</option>
                            <option value="annule" <?php echo $filtreStatut === 'annule' ? 'selected' : ''; ?>>Annulé</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Paiement</label>
                        <select name="paiement">
                            <option value="">Tous</option>
                            <option value="paye" <?php echo $filtrePaiement === 'paye' ? 'selected' : ''; ?>>Payé</option>
                            <option value="en_attente" <?php echo $filtrePaiement === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                            <option value="echoue" <?php echo $filtrePaiement === 'echoue' ? 'selected' : ''; ?>>Échoué</option>
                            <option value="rembourse" <?php echo $filtrePaiement === 'rembourse' ? 'selected' : ''; ?>>Remboursé</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Date début</label>
                        <input type="date" name="date_debut" value="<?php echo htmlspecialchars($filtreDateDebut); ?>">
                    </div>

                    <div class="form-group">
                        <label>Date fin</label>
                        <input type="date" name="date_fin" value="<?php echo htmlspecialchars($filtreDateFin); ?>">
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">🔍 Filtrer</button>
                    <a href="commandes.php" class="btn btn-secondary">↻ Réinitialiser</a>
                </div>
            </form>
        </div>

        <!-- Liste des commandes -->
        <div class="section">
            <div class="section-header">
                <h2>📋 Liste des commandes (<?php echo $total; ?>)</h2>
            </div>

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
                    <?php if (empty($commandes)): ?>
                        <tr>
                            <td colspan="7" class="no-results">
                                Aucune commande trouvée
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($commandes as $cmd): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cmd['numero_commande']); ?></strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($cmd['created_at'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($cmd['client_prenom'] . ' ' . $cmd['client_nom']); ?><br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($cmd['client_email']); ?></small>
                                </td>
                                <td><strong><?php echo number_format($cmd['total_ttc'], 2, ',', ' '); ?> €</strong></td>
                                <td><span class="badge <?php echo $cmd['statut']; ?>"><?php echo ucfirst(str_replace('_', ' ', $cmd['statut'])); ?></span></td>
                                <td><span class="badge <?php echo $cmd['statut_paiement']; ?>"><?php echo ucfirst($cmd['statut_paiement']); ?></span></td>
                                <td>
                                    <div class="actions">
                                        <a href="commande.php?id=<?php echo $cmd['id']; ?>" class="btn btn-primary btn-small">Voir</a>
                                    </div>
                                </td>
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
                    <a href="?page=<?php echo $page - 1; ?><?php echo $filtreStatut ? '&statut=' . $filtreStatut : ''; ?><?php echo $filtrePaiement ? '&paiement=' . $filtrePaiement : ''; ?>">← Précédent</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo $filtreStatut ? '&statut=' . $filtreStatut : ''; ?><?php echo $filtrePaiement ? '&paiement=' . $filtrePaiement : ''; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $filtreStatut ? '&statut=' . $filtreStatut : ''; ?><?php echo $filtrePaiement ? '&paiement=' . $filtrePaiement : ''; ?>">Suivant →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
