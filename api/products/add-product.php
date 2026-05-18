<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

// ── Inputs ───────────────────────────────────────────────────
$title       = sanitize($_POST['title']       ?? '');
$price       = (float)($_POST['price']        ?? 0);
$description = sanitize($_POST['description'] ?? '');
$location    = sanitize($_POST['location']    ?? '');
$category_id = (int)($_POST['category_id']   ?? 0);
$status      = $_POST['status'] === 'pending' ? 'pending' : 'active';

// ── Validation ───────────────────────────────────────────────
if (!$title) {
    jsonResponse(['error' => 'Item name is required.'], 422);
}
if ($price <= 0) {
    jsonResponse(['error' => 'Please enter a valid price.'], 422);
}
if (!$category_id) {
    jsonResponse(['error' => 'Please select a category.'], 422);
}

// ── Image upload ──────────────────────────────────────────────
$imageName = null;
if (!empty($_FILES['image']['name'])) {
    $imageName = uploadImage($_FILES['image']);
    if ($imageName === false) {
        jsonResponse(['error' => 'Invalid image. Use JPG, PNG or WebP under 2MB.'], 422);
    }
}

// ── Insert product ────────────────────────────────────────────
$stmt = $pdo->prepare("
    INSERT INTO products
        (seller_id, category_id, title, description, price, location, image, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $_SESSION['user_id'],
    $category_id,
    $title,
    $description ?: null,
    $price,
    $location   ?: null,
    $imageName,
    $status,
]);

jsonResponse([
    'success'    => true,
    'message'    => $status === 'active' ? 'Item listed successfully!' : 'Draft saved.',
    'product_id' => $pdo->lastInsertId(),
]);
