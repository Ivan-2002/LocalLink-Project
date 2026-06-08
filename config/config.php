
<?php
// config/config.php
// ============================================================
// Change file before live hosting
// ============================================================

// ── Database credentials ─────────────────────────────────────
// These match what you set up in XAMPP / phpMyAdmin
define('DB_HOST', 'sql300.infinityfree.com');
define('DB_NAME', 'if0_42133430_c2c_platform');
define('DB_USER', 'if0_42133430');
define('DB_PASS', 'pKRrwAuGCF8NvvO');

// ── Base URL ─────────────────────────────────────────────────
// The URL to your PUBLIC folder.
// Change this when you deploy to live hosting.
//
// XAMPP example:  'http://localhost/ITECA-PROJECT/LocalLink-Project-Root/public/'
// Live example:   'https://yoursite.com/public/'
//
define('BASE_URL', 'https://locallink.xo.je/public/');
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
