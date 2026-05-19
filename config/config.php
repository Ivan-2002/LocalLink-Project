
<?php
// config/config.php
// ============================================================
// This is the ONLY file you need to change when you deploy
// to live hosting. Everything else reads from these constants.
// ============================================================

// ── Database credentials ─────────────────────────────────────
// These match what you set up in XAMPP / phpMyAdmin
define('DB_HOST', 'localhost');       // almost always localhost on XAMPP
define('DB_NAME', 'c2c_platform');    // the database name you created
define('DB_USER', 'root');            // XAMPP default username
define('DB_PASS', '');                // XAMPP default password is empty

// ── Base URL ─────────────────────────────────────────────────
// The URL to your PUBLIC folder.
// Change this when you deploy to live hosting.
//
// XAMPP example:  'http://localhost/c2c-platform/public/'
// Live example:   'https://yoursite.com/public/'
//
define('BASE_URL', 'http://localhost/ITECA-PROJECT/LocalLink-Project-Root/public/');

// ── File uploads ─────────────────────────────────────────────
// UPLOAD_DIR  = the actual folder path on disk where images are saved
// MAX_FILE_SIZE = biggest image allowed (in bytes)
//
// 2 * 1024 * 1024 = 2MB
// If you want 5MB change it to: 5 * 1024 * 1024
//
define('UPLOAD_DIR',     __DIR__ . '/../public/uploads/');
define('MAX_FILE_SIZE',  2 * 1024 * 1024);

// ── App settings ─────────────────────────────────────────────
define('APP_NAME',    'LocalLink');
define('APP_VERSION', '1.0.0');

// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// // 1. Determine the active language (Default to English)
// $allowedLanguages = ['en', 'xh', 'zu'];
// $currentLang = $_SESSION['lang'] ?? 'en';

// // 2. Handle a user switching languages via a URL parameter (?lang=xh)
// if (isset($_GET['lang']) && in_array($_GET['lang'], $allowedLanguages)) {
//     $currentLang = $_GET['lang'];
//     $_SESSION['lang'] = $currentLang;
// }

// // 3. Load the corresponding translation array matrix
// $langPath = __DIR__ . "/../includes/lang/{$currentLang}.php";
// if (file_exists($langPath)) {
//     $lang = require $langPath;
// } else {
//     $lang = require __DIR__ . "/../includes/lang/en.php"; // Fallback safety
// }

// // 4. Create a quick global helper function to print translated keys safely
// function __($key) {
//     global $lang;
//     return htmlspecialchars($lang[$key] ?? $key);
// }