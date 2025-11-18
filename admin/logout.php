<?php
/**
 * Déconnexion Admin - VisuPrint Pro
 */

require_once __DIR__ . '/auth.php';

deconnecterAdmin();

header('Location: /admin/login.php');
exit;
