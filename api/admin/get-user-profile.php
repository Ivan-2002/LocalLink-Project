<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAdmin();

$userId = (int)($_GET['id'] ?? 0);
if (!$userId) jsonResponse(['error' => 'User ID required.'], 422);

// User info
$stmt = $pdo->prepare("SELECT id, name, email, role, status, location, avatar, created_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) jsonResponse(['error' => 'User not found.'], 404);

$user['avatar_url']   = $user['avatar'] ? BASE_URL . 'uploads/avatars/' . $user['avatar'] : null;
$user['initial']      = strtoupper(substr($user['name'], 0, 1));
$user['member_since'] = date('d M Y', strtotime($user['created_at']));

// Their listings
$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.price, p.status, p.image, p.created_at,
           c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.seller_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$userId]);
$listings = $stmt->fetchAll();

foreach ($listings as &$l) {
    $l['price_formatted'] = 'R ' . number_format($l['price'], 2);
    $l['image_url']       = $l['image'] ? BASE_URL . 'uploads/' . $l['image'] : null;
}

jsonResponse([
    'success'  => true,
    'user'     => $user,
    'listings' => $listings,
]);
