<?php
// Updates the product and checks ownership of the product
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

$productId   = (int)($_POST['product_id']   ?? 0);
$title       = sanitize($_POST['title']       ?? '');
$price       = (float)($_POST['price']        ?? 0);
$description = sanitize($_POST['description'] ?? '');
$location    = sanitize($_POST['location']    ?? '');
$category_id = (int)($_POST['category_id']   ?? 0);

// ── Validate inputs ──────────────────────────────────────────
if (!$productId) jsonResponse(['error' => 'Product ID is required.'], 422);
if (!$title)     jsonResponse(['error' => 'Item name is required.'], 422);
if ($price <= 0) jsonResponse(['error' => 'Please enter a valid price.'], 422);
if (!$category_id) jsonResponse(['error' => 'Please select a category.'], 422);

// ── Fetch product to check ownership ────────────────────────
$stmt = $pdo->prepare("SELECT seller_id, image FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    jsonResponse(['error' => 'Product not found.'], 404);
}

// ── Ownership check ──────────────────────────────────────────
// Only the seller who listed it OR an admin can edit
$isOwner = (int)$product['seller_id'] === (int)$_SESSION['user_id'];
$isAdmin = isAdmin();

if (!$isOwner && !$isAdmin) {
    jsonResponse(['error' => 'You do not have permission to edit this listing.'], 403);
}

// ── Handle new image upload (optional) ───────────────────────
$imageName = $product['image']; // keep existing image by default

if (!empty($_FILES['image']['name'])) {
    $newImage = uploadImage($_FILES['image']);
    if ($newImage === false) {
        jsonResponse(['error' => 'Invalid image. Use JPG, PNG or WebP under 2MB.'], 422);
    }

    // Delete old image from disk if it exists
    if ($product['image']) {
        $oldPath = UPLOAD_DIR . $product['image'];
        if (file_exists($oldPath)) unlink($oldPath);
    }

    $imageName = $newImage;
}

// ── Update product ────────────────────────────────────────────
$stmt = $pdo->prepare("
    UPDATE products
    SET title       = ?,
        price       = ?,
        description = ?,
        location    = ?,
        category_id = ?,
        image       = ?
    WHERE id = ?
");
$stmt->execute([
    $title,
    $price,
    $description ?: null,
    $location    ?: null,
    $category_id,
    $imageName,
    $productId,
]);

jsonResponse(['success' => true, 'message' => 'Product updated successfully.']);
