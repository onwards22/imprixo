<?php
/**
 * Configuration E-commerce VisuPrint Pro
 * À placer dans /api/config.php sur votre serveur O2Switch
 */

// ============================================
// CONFIGURATION BASE DE DONNÉES
// ============================================

// À MODIFIER avec vos identifiants O2Switch
define('DB_HOST', 'localhost'); // Généralement localhost sur O2Switch
define('DB_NAME', 'visuprint_ecommerce'); // Nom de votre base
define('DB_USER', 'votre_user'); // Votre utilisateur MySQL
define('DB_PASS', 'votre_password'); // Votre mot de passe MySQL
define('DB_CHARSET', 'utf8mb4');

// ============================================
// CONFIGURATION STRIPE
// ============================================

// Clés Stripe (à récupérer sur dashboard.stripe.com)
define('STRIPE_PUBLIC_KEY', 'pk_test_xxxxxxxxxxxxx'); // Clé publique
define('STRIPE_SECRET_KEY', 'sk_test_xxxxxxxxxxxxx'); // Clé secrète
define('STRIPE_WEBHOOK_SECRET', 'whsec_xxxxxxxxxxxxx'); // Secret webhook

// ============================================
// CONFIGURATION EMAIL
// ============================================

// Email expéditeur
define('EMAIL_FROM', 'contact@visuprintpro.fr');
define('EMAIL_FROM_NAME', 'VisuPrint Pro');

// Email admin (pour recevoir les notifications)
define('EMAIL_ADMIN', 'admin@visuprintpro.fr');

// ============================================
// CONFIGURATION SITE
// ============================================

define('SITE_URL', 'https://visuprintpro.fr'); // Votre nom de domaine
define('API_URL', SITE_URL . '/api');
define('UPLOAD_DIR', __DIR__ . '/../uploads/'); // Dossier uploads

// ============================================
// CONFIGURATION PANIER
// ============================================

define('PANIER_EXPIRATION_DAYS', 30); // Expiration panier (jours)
define('COMMANDE_MIN', 25); // Montant minimum commande (€)

// ============================================
// CONFIGURATION FRAIS DE PORT
// ============================================

define('FRAIS_PORT_GRATUIT_SEUIL', 200); // Gratuit au-dessus de X€
define('FRAIS_PORT_STANDARD', 15); // Frais standards (€)
define('FRAIS_PORT_EXPRESS', 30); // Frais express (€)

// ============================================
// CONFIGURATION TVA
// ============================================

define('TVA_RATE', 0.20); // 20%

// ============================================
// SÉCURITÉ
// ============================================

define('SESSION_NAME', 'VISUPRINT_SESSION');
define('ADMIN_SESSION_NAME', 'VISUPRINT_ADMIN');

// Protection CSRF
define('CSRF_TOKEN_NAME', 'csrf_token');

// ============================================
// CONNEXION BASE DE DONNÉES
// ============================================

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode([
                'success' => false,
                'error' => 'Erreur de connexion à la base de données'
            ]));
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

// ============================================
// FONCTIONS UTILITAIRES
// ============================================

/**
 * Envoyer une réponse JSON
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Obtenir l'ID de session du panier
 */
function getPanierSessionId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['panier_id'])) {
        $_SESSION['panier_id'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['panier_id'];
}

/**
 * Calculer le prix dégressif
 */
function calculerPrixDegressif($produit, $surfaceTotale) {
    if ($surfaceTotale <= 10) {
        return $produit['prix_0_10'];
    } elseif ($surfaceTotale <= 50) {
        return $produit['prix_11_50'];
    } elseif ($surfaceTotale <= 100) {
        return $produit['prix_51_100'];
    } elseif ($surfaceTotale <= 300) {
        return $produit['prix_101_300'];
    } else {
        return $produit['prix_300_plus'];
    }
}

/**
 * Générer un numéro de commande unique
 */
function genererNumeroCommande() {
    return 'VP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Envoyer un email
 */
function envoyerEmail($destinataire, $sujet, $message, $html = true) {
    $headers = [
        'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM . '>',
        'Reply-To: ' . EMAIL_FROM,
        'X-Mailer: PHP/' . phpversion()
    ];

    if ($html) {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=utf-8';
    }

    $success = mail($destinataire, $sujet, $message, implode("\r\n", $headers));

    // Logger l'email
    $db = Database::getInstance();
    $db->query(
        "INSERT INTO historique_emails (type_email, destinataire, sujet, statut, envoye_at)
         VALUES (?, ?, ?, ?, NOW())",
        ['general', $destinataire, $sujet, $success ? 'envoye' : 'echoue']
    );

    return $success;
}

/**
 * Nettoyer les entrées
 */
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Valider un email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Générer un token CSRF
 */
function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }

    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Vérifier le token CSRF
 */
function verifyCsrfToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Logger une action admin
 */
function logAdminAction($adminId, $action, $description = null) {
    $db = Database::getInstance();
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $db->query(
        "INSERT INTO logs_admin (admin_user_id, action, description, ip_address)
         VALUES (?, ?, ?, ?)",
        [$adminId, $action, $description, $ip]
    );
}

// ============================================
// INITIALISATION
// ============================================

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Timezone
date_default_timezone_set('Europe/Paris');

// Erreurs en développement (à désactiver en production)
if ($_SERVER['SERVER_NAME'] === 'localhost') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
