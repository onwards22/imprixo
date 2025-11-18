<?php
/**
 * Script pour créer l'utilisateur admin
 * À exécuter UNE SEULE FOIS via navigateur
 * URL: https://imprixo.fr/creer-admin.php
 */

// ⚠️ IMPORTANT : Supprimer ce fichier après utilisation !

// Configuration (à modifier avec vos identifiants)
define('DB_HOST', 'localhost');
define('DB_NAME', 'ispy2055_imprixo_ecommerce');  // ⚠️ Remplacer par votre base
define('DB_USER', 'ispy2055_imprixo_user');       // ⚠️ Remplacer par votre user
define('DB_PASS', 'VOTRE_MOT_DE_PASSE_MYSQL');    // ⚠️ Remplacer par votre password

// Mot de passe admin à créer
$adminUsername = 'admin';
$adminPassword = 'Admin123!';  // ⚠️ Changez-le après première connexion !
$adminEmail = 'admin@imprixo.fr';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Création Admin - Imprixo</title>
    <style>
        body {
            font-family: monospace;
            background: #1a1a1a;
            color: #00ff00;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffaa00; }
        .info { color: #00aaff; }
        pre { background: #000; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body>
<h1>🔐 Création Utilisateur Admin - Imprixo</h1>
<pre>
<?php

try {
    // Connexion à la base de données
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "<span class='success'>✓ Connexion à la base de données réussie</span>\n\n";

    // Générer le hash du mot de passe
    $passwordHash = password_hash($adminPassword, PASSWORD_BCRYPT);

    echo "<span class='info'>📝 Informations admin :</span>\n";
    echo "   Username : <strong>$adminUsername</strong>\n";
    echo "   Password : <strong>$adminPassword</strong>\n";
    echo "   Email    : $adminEmail\n\n";

    // Vérifier si l'admin existe déjà
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
    $stmt->execute([$adminUsername]);
    $existing = $stmt->fetch();

    if ($existing) {
        echo "<span class='warning'>⚠️  Utilisateur '$adminUsername' existe déjà</span>\n";
        echo "<span class='warning'>   Mise à jour du mot de passe...</span>\n\n";

        // Mettre à jour le mot de passe
        $stmt = $pdo->prepare("
            UPDATE admin_users
            SET password_hash = ?, email = ?, actif = TRUE, updated_at = NOW()
            WHERE username = ?
        ");
        $stmt->execute([$passwordHash, $adminEmail, $adminUsername]);

        echo "<span class='success'>✓ Mot de passe mis à jour avec succès !</span>\n";
    } else {
        echo "<span class='info'>Création du nouvel utilisateur admin...</span>\n\n";

        // Créer le nouvel admin
        $stmt = $pdo->prepare("
            INSERT INTO admin_users
            (username, email, password_hash, nom, prenom, role, actif)
            VALUES (?, ?, ?, 'Admin', 'Imprixo', 'admin', TRUE)
        ");
        $stmt->execute([$adminUsername, $adminEmail, $passwordHash]);

        echo "<span class='success'>✓ Utilisateur admin créé avec succès !</span>\n";
    }

    echo "\n";
    echo "<span class='info'>═══════════════════════════════════════</span>\n";
    echo "<span class='success'>🎉 TERMINÉ !</span>\n";
    echo "<span class='info'>═══════════════════════════════════════</span>\n\n";

    echo "<span class='info'>🔗 Connexion Admin :</span>\n";
    echo "   URL      : <a href='/admin/login.php' style='color: #00aaff;'>https://imprixo.fr/admin/login.php</a>\n";
    echo "   Username : <strong style='color: white;'>$adminUsername</strong>\n";
    echo "   Password : <strong style='color: white;'>$adminPassword</strong>\n\n";

    echo "<span class='warning'>⚠️  IMPORTANT :</span>\n";
    echo "<span class='warning'>   1. Changez le mot de passe après première connexion</span>\n";
    echo "<span class='warning'>   2. SUPPRIMEZ ce fichier creer-admin.php du serveur !</span>\n";

} catch (PDOException $e) {
    echo "<span class='error'>❌ ERREUR : " . htmlspecialchars($e->getMessage()) . "</span>\n\n";

    echo "<span class='warning'>Vérifiez que :</span>\n";
    echo "   • Les identifiants DB sont corrects dans ce fichier\n";
    echo "   • La table admin_users existe\n";
    echo "   • L'utilisateur MySQL a les droits INSERT/UPDATE\n";
}

?>
</pre>
</body>
</html>
