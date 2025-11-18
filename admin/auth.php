<?php
/**
 * Authentification Admin - VisuPrint Pro
 * Gestion login/logout administrateurs
 */

require_once __DIR__ . '/../api/config.php';

/**
 * Vérifier si l'utilisateur est connecté en tant qu'admin
 */
function verifierAdminConnecte() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(ADMIN_SESSION_NAME);
        session_start();
    }

    if (!isset($_SESSION['admin_id'])) {
        header('Location: /admin/login.php');
        exit;
    }

    return $_SESSION['admin_id'];
}

/**
 * Connecter un admin
 */
function connecterAdmin($adminId, $username, $role) {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(ADMIN_SESSION_NAME);
        session_start();
    }

    $_SESSION['admin_id'] = $adminId;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_role'] = $role;

    // Logger la connexion
    $db = Database::getInstance();
    $db->query(
        "UPDATE admin_users SET last_login = NOW() WHERE id = ?",
        [$adminId]
    );

    logAdminAction($adminId, 'login', "Connexion depuis " . $_SERVER['REMOTE_ADDR']);
}

/**
 * Déconnecter l'admin
 */
function deconnecterAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(ADMIN_SESSION_NAME);
        session_start();
    }

    if (isset($_SESSION['admin_id'])) {
        logAdminAction($_SESSION['admin_id'], 'logout', 'Déconnexion');
    }

    session_destroy();
}

/**
 * Obtenir les infos de l'admin connecté
 */
function getAdminInfo() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(ADMIN_SESSION_NAME);
        session_start();
    }

    if (!isset($_SESSION['admin_id'])) {
        return null;
    }

    $db = Database::getInstance();
    return $db->fetchOne(
        "SELECT id, username, email, nom, prenom, role FROM admin_users WHERE id = ?",
        [$_SESSION['admin_id']]
    );
}

/**
 * Vérifier les permissions par rôle
 */
function verifierPermission($roleRequis = 'gestionnaire') {
    $admin = getAdminInfo();

    if (!$admin) {
        return false;
    }

    $hierarchy = ['admin' => 3, 'gestionnaire' => 2, 'lecture_seule' => 1];

    return $hierarchy[$admin['role']] >= $hierarchy[$roleRequis];
}
