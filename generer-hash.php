<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Générateur Hash Mot de Passe</title>
    <style>
        body {
            font-family: -apple-system, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #5568d3;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            background: #f0f0f0;
            border-radius: 6px;
            word-break: break-all;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Générateur Hash Mot de Passe</h1>
        <p>Entre ton nouveau mot de passe admin :</p>

        <form method="POST">
            <input type="text" name="password" placeholder="Nouveau mot de passe" required>
            <button type="submit">Générer Hash</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
            $password = $_POST['password'];
            $hash = password_hash($password, PASSWORD_BCRYPT);

            echo '<div class="result">';
            echo '<strong>✅ Hash généré :</strong><br><br>';
            echo '<code>' . htmlspecialchars($hash) . '</code>';
            echo '<br><br>';
            echo '<strong>Instructions :</strong><br>';
            echo '1. Copie le hash ci-dessus<br>';
            echo '2. Va dans phpMyAdmin > table admin_users<br>';
            echo '3. Édite la ligne "admin"<br>';
            echo '4. Remplace password_hash par ce hash<br>';
            echo '5. Supprime ce fichier du serveur !';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
