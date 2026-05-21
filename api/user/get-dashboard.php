<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

$userId = (int)$_SESSION['user_id'];

// ── Profile ───────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT id, name, email, location, role, avatar, created_at
    FROM users WHERE id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Build full avatar URL if one exists
$user['avatar_url'] = $user['avatar']
    ? BASE_URL . 'uploads/avatars/' . $user['avatar']
    : null;

// ── My Listings ───────────────────────────────────────────────
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

// ── My Orders ─────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT o.id, o.total_amount, o.payment_method, o.status, o.created_at,
           GROUP_CONCAT(p.title SEPARATOR ', ') AS items,
           COUNT(oi.id) AS item_count
    FROM orders o
    JOIN order_items oi ON oi.order_id  = o.id
    JOIN products   p  ON oi.product_id = p.id
    WHERE o.buyer_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

foreach ($orders as &$o) {
    $o['total_formatted'] = 'R ' . number_format($o['total_amount'], 2);
}

// ── Stats ─────────────────────────────────────────────────────
$stats = [
    'active_listings' => count(array_filter($listings, fn($l) => $l['status'] === 'active')),
    'total_listings'  => count($listings),
    'total_orders'    => count($orders),
    'member_since'    => date('M Y', strtotime($user['created_at'])),
];

jsonResponse([
    'success'  => true,
    'user'     => $user,
    'listings' => $listings,
    'orders'   => $orders,
    'stats'    => $stats,
]);
