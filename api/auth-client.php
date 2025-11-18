<?php
/**
 * API Authentification Client - Imprixo
 */

session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$db = Database::getInstance();

// ============================================
// LOGIN
// ============================================

if ($action === 'login') {
    $email = cleanInput($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        jsonResponse(['error' => 'Email et mot de passe requis'], 400);
    }

    // Chercher le client
    $client = $db->fetchOne(
        "SELECT * FROM clients WHERE email = ?",
        [$email]
    );

    if (!$client) {
        jsonResponse(['error' => 'Email ou mot de passe incorrect'], 401);
    }

    // Vérifier mot de passe
    if (!password_verify($password, $client['password_hash'])) {
        jsonResponse(['error' => 'Email ou mot de passe incorrect'], 401);
    }

    // Connecter le client
    $_SESSION['client_id'] = $client['id'];
    $_SESSION['client_email'] = $client['email'];
    $_SESSION['client_nom'] = $client['nom'];
    $_SESSION['client_prenom'] = $client['prenom'];

    jsonResponse([
        'success' => true,
        'message' => 'Connexion réussie',
        'client' => [
            'id' => $client['id'],
            'email' => $client['email'],
            'nom' => $client['nom'],
            'prenom' => $client['prenom']
        ]
    ]);
}

// ============================================
// REGISTER
// ============================================

if ($action === 'register') {
    $email = cleanInput($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $prenom = cleanInput($input['prenom'] ?? '');
    $nom = cleanInput($input['nom'] ?? '');
    $telephone = cleanInput($input['telephone'] ?? '');

    if (empty($email) || empty($password) || empty($prenom) || empty($nom)) {
        jsonResponse(['error' => 'Tous les champs sont requis'], 400);
    }

    // Vérifier si email existe déjà
    $existing = $db->fetchOne(
        "SELECT id FROM clients WHERE email = ?",
        [$email]
    );

    if ($existing) {
        jsonResponse(['error' => 'Cet email est déjà utilisé'], 400);
    }

    // Créer le compte
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $db->query(
            "INSERT INTO clients (email, password_hash, prenom, nom, telephone)
            VALUES (?, ?, ?, ?, ?)",
            [$email, $passwordHash, $prenom, $nom, $telephone]
        );

        $clientId = $db->lastInsertId();

        // Connecter automatiquement
        $_SESSION['client_id'] = $clientId;
        $_SESSION['client_email'] = $email;
        $_SESSION['client_nom'] = $nom;
        $_SESSION['client_prenom'] = $prenom;

        jsonResponse([
            'success' => true,
            'message' => 'Compte créé avec succès',
            'client' => [
                'id' => $clientId,
                'email' => $email,
                'nom' => $nom,
                'prenom' => $prenom
            ]
        ]);

    } catch (PDOException $e) {
        jsonResponse(['error' => 'Erreur création compte'], 500);
    }
}

// ============================================
// LOGOUT
// ============================================

if ($action === 'logout') {
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Déconnexion réussie']);
}

// ============================================
// CHECK SESSION
// ============================================

if ($action === 'check') {
    if (isset($_SESSION['client_id'])) {
        jsonResponse([
            'authenticated' => true,
            'client' => [
                'id' => $_SESSION['client_id'],
                'email' => $_SESSION['client_email'],
                'nom' => $_SESSION['client_nom'],
                'prenom' => $_SESSION['client_prenom']
            ]
        ]);
    } else {
        jsonResponse(['authenticated' => false]);
    }
}

jsonResponse(['error' => 'Action invalide'], 400);
