<?php
/**
 * Gestion Produits - Imprixo Admin
 */

require_once __DIR__ . '/auth.php';

verifierAdminConnecte();
$admin = getAdminInfo();
$db = Database::getInstance();

// Récupérer tous les produits
$produits = $db->fetchAll(
    "SELECT * FROM produits ORDER BY categorie, nom"
);

// Stats
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM produits")['count'],
    'stock' => $db->fetchOne("SELECT COUNT(*) as count FROM produits WHERE stock_disponible = TRUE")['count'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Produits - Imprixo Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="/admin/"><h1>🎨 <span class="brand">Imprixo</span> - Admin</h1></a>
                </div>
                <nav class="nav">
                    <a href="index.php">Dashboard</a>
                    <a href="commandes.php">Commandes</a>
                    <a href="produits.php" class="active">Produits</a>
                    <a href="clients.php">Clients</a>
                </nav>
                <div class="header-actions">
                    <a href="logout.php" class="btn-logout">Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <section style="padding: 60px 0;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h1 style="font-size: 36px;">Gestion Produits</h1>
                <div style="display: flex; gap: 15px;">
                    <div style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <div style="font-size: 12px; color: #666;">Total</div>
                        <div style="font-size: 24px; font-weight: 700;"><?php echo $stats['total']; ?></div>
                    </div>
                    <div style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <div style="font-size: 12px; color: #666;">En stock</div>
                        <div style="font-size: 24px; font-weight: 700; color: #27ae60;"><?php echo $stats['stock']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Liste produits -->
            <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 15px; text-align: left; font-weight: 600; color: #666; font-size: 13px;">Code</th>
                            <th style="padding: 15px; text-align: left; font-weight: 600; color: #666; font-size: 13px;">Produit</th>
                            <th style="padding: 15px; text-align: left; font-weight: 600; color: #666; font-size: 13px;">Catégorie</th>
                            <th style="padding: 15px; text-align: left; font-weight: 600; color: #666; font-size: 13px;">Prix (300+m²)</th>
                            <th style="padding: 15px; text-align: left; font-weight: 600; color: #666; font-size: 13px;">Stock</th>
                            <th style="padding: 15px; text-align: left; font-weight: 600; color: #666; font-size: 13px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $currentCategory = '';
                        foreach ($produits as $produit):
                            if ($currentCategory !== $produit['categorie']):
                                $currentCategory = $produit['categorie'];
                        ?>
                                <tr style="background: #667eea; color: white;">
                                    <td colspan="6" style="padding: 10px 15px; font-weight: 600;">
                                        <?php echo htmlspecialchars($currentCategory); ?>
                                    </td>
                                </tr>
                        <?php endif; ?>
                        <tr style="border-bottom: 1px solid #e0e0e0;">
                            <td style="padding: 15px;">
                                <code><?php echo htmlspecialchars($produit['code']); ?></code>
                            </td>
                            <td style="padding: 15px;">
                                <strong><?php echo htmlspecialchars($produit['nom']); ?></strong>
                            </td>
                            <td style="padding: 15px; color: #666; font-size: 14px;">
                                <?php echo htmlspecialchars($produit['categorie']); ?>
                            </td>
                            <td style="padding: 15px;">
                                <strong style="color: #667eea;"><?php echo number_format($produit['prix_300_plus'], 2, ',', ' '); ?> €/m²</strong>
                            </td>
                            <td style="padding: 15px;">
                                <?php if ($produit['stock_disponible']): ?>
                                    <span style="display: inline-block; padding: 4px 12px; background: #27ae60; color: white; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                        En stock
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-block; padding: 4px 12px; background: #e74c3c; color: white; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                        Rupture
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px;">
                                <a href="/produit.html?code=<?php echo $produit['code']; ?>" target="_blank" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">
                                    Voir
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</body>
</html>
