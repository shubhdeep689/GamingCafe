<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Core Configuration
 */

// Strict error reporting for development, but hidden from end users in production
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Set Indian Standard Time (Asia/Kolkata)
date_default_timezone_set('Asia/Kolkata');

// Secure Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    
    session_set_cookie_params([
        'lifetime' => 86400 * 7, // 7 days
        'path'     => '/',
        'domain'   => '',
        'secure'   => $is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Database Credentials
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'ironclad_gaming');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

// Brand Identity
define('APP_NAME', 'IRONCLAD GAMING SANCTUARY');
define('APP_TAGLINE', 'Gaming. Competition. Community.');
define('APP_VERSION', '1.0.0');
define('APP_ADDRESS', 'Behind Redtape Showroom, Near PSPCL Office, Guru Angad Dev Nagar, Sri Muktsar Sahib, Punjab – 152026');
define('APP_CITY', 'Sri Muktsar Sahib');
define('APP_STATE', 'Punjab');
define('APP_PIN', '152026');
define('APP_COUNTRY', 'India');

// Dynamic Base URL Detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$project_root_name = 'GamingCafe';

if (php_sapi_name() === 'cli' || empty($_SERVER['HTTP_HOST'])) {
    $base_path = '/' . $project_root_name;
} else {
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $pos = strpos($script_dir, '/' . $project_root_name);
    if ($pos !== false) {
        $base_path = substr($script_dir, 0, $pos + strlen('/' . $project_root_name));
    } else {
        $base_path = '/' . $project_root_name;
    }
}
define('BASE_URL', rtrim($protocol . $host . $base_path, '/') . '/');

// Asset Paths
define('ASSETS_URL', BASE_URL . 'assets/');
define('IMAGES_URL', BASE_URL . 'images/');
define('UPLOADS_URL', BASE_URL . 'uploads/');
define('UPLOAD_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);

// Include Database connection and helper functions
require_once __DIR__ . '/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
