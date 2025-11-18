<?php
/**
 * Page de connexion Admin - VisuPrint Pro
 */

require_once __DIR__ . '/auth.php';

// Si déjà connecté, rediriger
if (session_status() === PHP_SESSION_NONE) {
    session_name(ADMIN_SESSION_NAME);
    session_start();
}

if (isset($_SESSION['admin_id'])) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $db = Database::getInstance();

        $admin = $db->fetchOne(
            "SELECT * FROM admin_users WHERE username = ? AND actif = TRUE",
            [$username]
        );

        if ($admin && password_verify($password, $admin['password_hash'])) {
            connecterAdmin($admin['id'], $admin['username'], $admin['role']);
            header('Location: /admin/index.php');
            exit;
        } else {
            $error = 'Identifiants incorrects';
            // Logger la tentative échouée
            if ($admin) {
                logAdminAction($admin['id'], 'login_failed', 'Tentative de connexion échouée');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - VisuPrint Pro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }

        .info-box {
            margin-top: 20px;
            padding: 15px;
            background: #f0f0f0;
            border-radius: 6px;
            font-size: 13px;
            color: #666;
        }

        .info-box strong {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🎨 VisuPrint Pro</h1>
            <p>Administration</p>
        </div>

        <?php if ($error): ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-login">
                Se connecter
            </button>
        </form>

        <div class="info-box">
            <strong>📝 Premier démarrage ?</strong><br>
            Identifiants par défaut :<br>
            • Utilisateur : <code>admin</code><br>
            • Mot de passe : <code>Admin123!</code><br>
            <br>
            <strong>⚠️ Changez-le immédiatement !</strong>
        </div>
    </div>
</body>
</html>
