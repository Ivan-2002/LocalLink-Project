<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAdmin();

// ── Stats ─────────────────────────────────────────────────────
$stats = [
    'total_users'    => $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn(),
    'total_products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'total_orders'   => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'reported_items' => $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'removed'")->fetchColumn(),
];

// ── Recent listings ───────────────────────────────────────────
$listings = $pdo->query("
    SELECT p.title, p.created_at, u.name AS seller_name
    FROM products p
    JOIN users u ON p.seller_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 5
")->fetchAll();

// ── Recent users ──────────────────────────────────────────────
$users = $pdo->query("
    SELECT name, created_at FROM users
    ORDER BY created_at DESC LIMIT 5
")->fetchAll();

// ── Time ago helper ───────────────────────────────────────────
function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return $diff . ' secs ago';
    if ($diff < 3600)  return round($diff / 60)   . ' mins ago';
    if ($diff < 86400) return round($diff / 3600)  . ' hrs ago';
    return round($diff / 86400) . ' days ago';
}

foreach ($listings as &$l) $l['time_ago'] = timeAgo($l['created_at']);
foreach ($users    as &$u) $u['time_ago'] = timeAgo($u['created_at']);

jsonResponse([
    'success'  => true,
    'stats'    => $stats,
    'listings' => $listings,
    'users'    => $users,
]);
