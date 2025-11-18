<?php
/**
 * Déconnexion Client - Imprixo
 */

session_start();
session_destroy();

header('Location: /');
exit;
