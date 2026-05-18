<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$id = (int)($_POST['id'] ?? 0);

if (!$id) {
    jsonResponse(['error' => 'Category ID is required.'], 422);
}

// Check if any products use this category
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
$stmt->execute([$id]);
if ($stmt->fetchColumn() > 0) {
    jsonResponse(['error' => 'Cannot delete — products are using this category.'], 409);
}

// Reassign child categories to no parent before deleting
$stmt = $pdo->prepare("UPDATE categories SET parent_id = NULL WHERE parent_id = ?");
$stmt->execute([$id]);

$stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
$stmt->execute([$id]);

jsonResponse(['success' => true, 'message' => 'Category deleted successfully.']);
