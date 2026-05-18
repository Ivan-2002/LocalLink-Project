<?php
// Deletes product and check ownership of product
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

$productId = (int)($_POST['product_id'] ?? 0);
if (!$productId) jsonResponse(['error' => 'Product ID is required.'], 422);

// ── Fetch product ────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT seller_id, image, title FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    jsonResponse(['error' => 'Product not found.'], 404);
}

// ── Ownership check ──────────────────────────────────────────
$isOwner = (int)$product['seller_id'] === (int)$_SESSION['user_id'];
$isAdmin = isAdmin();

if (!$isOwner && !$isAdmin) {
    jsonResponse(['error' => 'You do not have permission to delete this listing.'], 403);
}

// ── Delete image file from disk ───────────────────────────────
if ($product['image']) {
    $imagePath = UPLOAD_DIR . $product['image'];
    if (file_exists($imagePath)) unlink($imagePath);
}

// ── Delete product from database ──────────────────────────────
// Order_items, messages, reviews linked to this product
// are handled by ON DELETE CASCADE in the schema
$stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([$productId]);

jsonResponse(['success' => true, 'message' => 'Product deleted successfully.']);
