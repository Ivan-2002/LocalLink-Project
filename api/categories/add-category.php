<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Only admins can add categories
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$name      = sanitize($_POST['name']      ?? '');
$parent_id = $_POST['parent_id'] ?? null;

if (!$name) {
    jsonResponse(['error' => 'Category name is required.'], 422);
}

// Clean up parent_id — treat empty string as NULL
if ($parent_id === '' || $parent_id === '0') {
    $parent_id = null;
}

// Check for duplicate name under same parent
$stmt = $pdo->prepare("
    SELECT id FROM categories
    WHERE name = ? AND (parent_id = ? OR (parent_id IS NULL AND ? IS NULL))
    LIMIT 1
");
$stmt->execute([$name, $parent_id, $parent_id]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'A category with this name already exists.'], 409);
}

$stmt = $pdo->prepare("INSERT INTO categories (name, parent_id) VALUES (?, ?)");
$stmt->execute([$name, $parent_id]);

jsonResponse([
    'success' => true,
    'message' => 'Category added successfully.',
    'id'      => $pdo->lastInsertId(),
]);
