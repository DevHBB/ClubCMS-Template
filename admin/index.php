<?php
/**
 * ClubCMS — Pont admin pour XAMPP/Windows
 * Apache sur Windows cherche parfois index.php dans le sous-dossier.
 * Ce fichier redirige vers le front controller.
 */

// Chemin racine = dossier parent de /admin/
define('CC_ROOT', dirname(__DIR__));
define('CC_VERSION', '1.0.0');

if (!file_exists(CC_ROOT . '/config/config.php')) {
    header('Location: /install.php');
    exit;
}

// On simule la route /admin via le front controller
$_GET['route'] = 'admin/' . ltrim($_GET['route'] ?? '', '/');

require_once CC_ROOT . '/index.php';
