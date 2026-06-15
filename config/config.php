
<?php
// config/config.php
// ============================================================
// Change file when live hosting IMPORTANT!!!!
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
// Live example:   'https://locallink.xo.je/public/'
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
