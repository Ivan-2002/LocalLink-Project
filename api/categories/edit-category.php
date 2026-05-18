<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$id        = (int)($_POST['id']        ?? 0);
$name      = sanitize($_POST['name']   ?? '');
$parent_id = $_POST['parent_id']       ?? null;

if (!$id || !$name) {
    jsonResponse(['error' => 'ID and name are required.'], 422);
}

if ($parent_id === '' || $parent_id === '0') {
    $parent_id = null;
}

// Prevent a category from being its own parent
if ((int)$parent_id === $id) {
    jsonResponse(['error' => 'A category cannot be its own parent.'], 422);
}

// Check it exists
$stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    jsonResponse(['error' => 'Category not found.'], 404);
}

$stmt = $pdo->prepare("UPDATE categories SET name = ?, parent_id = ? WHERE id = ?");
$stmt->execute([$name, $parent_id, $id]);

jsonResponse(['success' => true, 'message' => 'Category updated successfully.']);
