<?php
// PDO database connection — gives you the $pdo variable

require_once __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            // Show errors as exceptions (easier to debug)
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // Return rows as associative arrays e.g. $row['name']
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // Use real prepared statements (more secure)
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Don't expose real error to browser — just return JSON error
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed. Check config.php.']);
    exit;
}
