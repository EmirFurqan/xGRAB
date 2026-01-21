<?php
/**
 * Configuration File Template
 * 
 * Instructions:
 * 1. Copy this file to 'config.php'
 * 2. Update the database credentials below to match your environment
 */

// Prevent multiple inclusions
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);

// ------------------------------
// DATABASE CONFIGURATION
// ------------------------------

// Local Environment - Update these matching your local DB setup
if (!defined('DB_SERVER')) {
    define('DB_SERVER', 'localhost');
}
if (!defined('DB_USERNAME')) {
    define('DB_USERNAME', 'root');
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', 'root');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'movie');
}

// ------------------------------
// PATH CONFIGURATION
// ------------------------------

// Auto-detect protocol, host, and path for flexible local development
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];

// Calculate project path relative to document root
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../'));
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$path = '/xGRAB/'; // Fallback default

if (strpos($projectRoot, $docRoot) === 0) {
    $folder = substr($projectRoot, strlen($docRoot));
    $folder = trim($folder, '/');
    $path = !empty($folder) ? '/' . $folder . '/' : '/';
}

if (!defined('BASE_URL')) {
    define('BASE_URL', $protocol . "://" . $host . $path);
}

// ------------------------------
// URI ROOT (Calculated automatically)
// ------------------------------
if (!defined('PROJECT_URI_ROOT')) {
    $url_path = parse_url(BASE_URL, PHP_URL_PATH);
    define('PROJECT_URI_ROOT', rtrim($url_path, '/'));
}

// ------------------------------
// FILESYSTEM PATHS
// ------------------------------
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(__DIR__ . '/../'));
}
?>