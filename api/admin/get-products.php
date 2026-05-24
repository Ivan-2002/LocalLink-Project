<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAdmin();

$search   = trim($_GET['search']    ?? '');
$status   = $_GET['status']         ?? '';
$category = (int)($_GET['category'] ?? 0);

$sql    = "
    SELECT p.id, p.title, p.price, p.status, p.image, p.created_at,
           u.id   AS seller_id,   u.name AS seller_name,
           c.id   AS category_id, c.name AS category_name
    FROM products p
    JOIN  users      u ON p.seller_id   = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE 1=1
";
$params = [];

if ($search) {
    $sql .= " AND (p.title LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status) {
    $sql .= " AND p.status = ?";
    $params[] = $status;
}
if ($category) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

foreach ($products as &$p) {
    $p['price_formatted'] = 'R ' . number_format($p['price'], 2);
    $p['image_url']       = $p['image'] ? BASE_URL . 'uploads/' . $p['image'] : null;
    $p['date_listed']     = date('d M Y', strtotime($p['created_at']));
    $p['seller_initial']  = strtoupper(substr($p['seller_name'], 0, 1));
}

jsonResponse(['success' => true, 'products' => $products]);
