<?php
/**
 * Configuration File
 * Manage environment-specific settings for paths and database connections.
 * Toggle comments to switch between Local and FastPanel environments.
 */

// ------------------------------
// DATABASE CONFIGURATION
// ------------------------------

// Local Environment
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');
define('DB_NAME', 'movie');

// FastPanel Environment (Uncomment and update when deploying)
// define('DB_SERVER', 'localhost');
// define('DB_USERNAME', 'st2025_xgrab_user'); // Update with actual user
// define('DB_PASSWORD', 'your_fastpanel_password'); // Update with actual password
// define('DB_NAME', 'st2025_xgrab_db'); // Update with actual db name


// ------------------------------
// PATH CONFIGURATION
// ------------------------------

// Local Environment Base URL
// Auto-detect protocol, host, and path for flexible local development
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST']; // e.g., localhost:8888

// Calculate project path relative to document root
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../'));
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$path = '/xGRAB/'; // Fallback

if (strpos($projectRoot, $docRoot) === 0) {
    $folder = substr($projectRoot, strlen($docRoot));
    $folder = trim($folder, '/');
    $path = !empty($folder) ? '/' . $folder . '/' : '/';
}

define('BASE_URL', $protocol . "://" . $host . $path);

// FastPanel Environment Base URL (Uncomment when deploying)
// define('BASE_URL', 'http://10.1.7.100:7777/st2025-024.com/xGRAB/');


// ------------------------------
// URI ROOT (Calculated automatically)
// ------------------------------
// Used for asset paths like images/css/js
// Result: /xGRAB or /st2025-024.com/xGRAB
$url_path = parse_url(BASE_URL, PHP_URL_PATH);
define('PROJECT_URI_ROOT', rtrim($url_path, '/'));


// ------------------------------
// FILESYSTEM PATHS
// ------------------------------
// Absolute path to the project root directory
define('ROOT_PATH', realpath(__DIR__ . '/../'));

?>