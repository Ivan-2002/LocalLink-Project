<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$id = (int)($_GET['id'] ?? 0);

// ==========================================
// 1. SINGLE PRODUCT VIEW CODE (For product.php)
// ==========================================
if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT
            p.id, p.title, p.description, p.price, p.location,
            p.image, p.status, p.created_at,
            u.id          AS seller_id,
            u.name        AS seller_name,
            u.location    AS seller_location,
            c.name        AS category_name,
            ROUND(AVG(r.rating), 1) AS avg_rating,
            COUNT(r.id)             AS review_count
    FROM products p
    JOIN  users      u ON p.seller_id   = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews    r ON r.product_id  = p.id
    WHERE p.id = ? AND p.status = 'active'
    GROUP BY p.id
    ");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        jsonResponse(['error' => 'Product not found.'], 404);
    }

    $product['price_formatted'] = 'ZAR ' . number_format($product['price'], 0, '.', ' ');
    $product['image_url']       = $product['image'] ? BASE_URL . 'uploads/' . $product['image'] : null;
    $product['seller_initial']  = strtoupper(substr($product['seller_name'], 0, 1));
    $product['avg_rating']      = (float)($product['avg_rating'] ?? 0);

    // Fetch Reviews
    $rStmt = $pdo->prepare("
        SELECT r.rating, r.comment, r.created_at, u.name AS buyer_name
        FROM reviews r
        JOIN users u ON r.buyer_id = u.id
        WHERE r.product_id = ?
        ORDER BY r.created_at DESC LIMIT 5
    ");
    $rStmt->execute([$id]);
    $product['reviews'] = $rStmt->fetchAll();

    jsonResponse(['success' => true, 'product' => $product]);
    exit; // Stop execution here
}

// ==========================================
// 2. ALL PRODUCTS GRID CODE (For index.php)
// ==========================================
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search   = trim($_GET['search']   ?? '');
$sort     = $_GET['sort']           ?? 'newest';
$date     = $_GET['date']           ?? '';
$location = trim($_GET['location']  ?? '');

$sql = "
    SELECT
        p.id, p.title, p.description, p.price, p.location,
        p.image, p.created_at, u.id AS seller_id, u.name AS seller_name,
        c.id AS category_id, c.name AS category_name
    FROM products p
    JOIN users u ON p.seller_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active'
";

$params = [];

if ($category) {
    $sql     .= " AND (p.category_id = ? OR c.parent_id = ?)";
    $params[] = $category;
    $params[] = $category;
}
if ($search) {
    $sql     .= " AND (p.title LIKE ? OR p.description LIKE ? OR u.name LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($location) {
    $sql     .= " AND p.location LIKE ?";
    $params[] = "%$location%";
}
if ($date === 'today') {
    $sql .= " AND DATE(p.created_at) = CURDATE()";
} elseif ($date === 'week') {
    $sql .= " AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($date === 'month') {
    $sql .= " AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    default:
        $sql .= " ORDER BY p.created_at DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

foreach ($products as &$p) {
    $p['price_formatted'] = 'R ' . number_format($p['price'], 2);
    $p['image_url'] = $p['image'] ? BASE_URL . 'uploads/' . $p['image'] : null;
    $p['seller_initial'] = strtoupper(substr($p['seller_name'], 0, 1));
}

jsonResponse([
    'success'  => true,
    'count'    => count($products),
    'products' => $products,
]);

// // ── Inputs ──────────────────────────────────────────────────
// $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
// $search   = trim($_GET['search']   ?? '');
// $sort     = $_GET['sort']           ?? 'newest';
// $date     = $_GET['date']           ?? '';
// $location = trim($_GET['location']  ?? '');
// $forYou   = isset($_GET['for_you']); // personalised tab

// // ── Base query ───────────────────────────────────────────────
// // Join users for seller name, categories for category name
// $sql = "
//     SELECT
//         p.id,
//         p.title,
//         p.description,
//         p.price,
//         p.location,
//         p.image,
//         p.created_at,
//         u.id   AS seller_id,
//         u.name AS seller_name,
//         c.id   AS category_id,
//         c.name AS category_name
//     FROM products p
//     JOIN users      u ON p.seller_id   = u.id
//     LEFT JOIN categories c ON p.category_id = c.id
//     WHERE p.status = 'active'
// ";

// $params = [];

// // ── Filters ──────────────────────────────────────────────────

// // Category filter — also include subcategories of selected parent
// if ($category) {
//     $sql     .= " AND (p.category_id = ? OR c.parent_id = ?)";
//     $params[] = $category;
//     $params[] = $category;
// }

// // Search filter
// if ($search) {
//     $sql     .= " AND (p.title LIKE ? OR p.description LIKE ? OR u.name LIKE ?)";
//     $like     = "%$search%";
//     $params[] = $like;
//     $params[] = $like;
//     $params[] = $like;
// }

// // Location filter
// if ($location) {
//     $sql     .= " AND p.location LIKE ?";
//     $params[] = "%$location%";
// }

// // Date filter
// if ($date === 'today') {
//     $sql .= " AND DATE(p.created_at) = CURDATE()";
// } elseif ($date === 'week') {
//     $sql .= " AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
// } elseif ($date === 'month') {
//     $sql .= " AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
// }

// // ── Sorting ──────────────────────────────────────────────────
// switch ($sort) {
//     case 'price_asc':
//         $sql .= " ORDER BY p.price ASC";
//         break;
//     case 'price_desc':
//         $sql .= " ORDER BY p.price DESC";
//         break;
//     default:
//         $sql .= " ORDER BY p.created_at DESC"; // newest
// }

// // ── Execute ──────────────────────────────────────────────────
// $stmt = $pdo->prepare($sql);
// $stmt->execute($params);
// $products = $stmt->fetchAll();

// // Format price and image URL for frontend
// foreach ($products as &$p) {
//     $p['price_formatted'] = 'R ' . number_format($p['price'], 2);
//     $p['image_url'] = $p['image']
//         ? BASE_URL . 'uploads/' . $p['image']
//         : null;
//     $p['seller_initial'] = strtoupper(substr($p['seller_name'], 0, 1));
// }

// jsonResponse([
//     'success'  => true,
//     'count'    => count($products),
//     'products' => $products,
// ]);

// $id = (int)($_GET['id'] ?? 0);
// if (!$id) jsonResponse(['error' => 'Product ID is required.'], 422);

// $stmt = $pdo->prepare("
//     SELECT
//         p.id, p.title, p.description, p.price, p.location,
//         p.image, p.status, p.created_at,
//         u.id          AS seller_id,
//         u.name        AS seller_name,
//         u.location    AS seller_location,
//         c.name        AS category_name,
//         ROUND(AVG(r.rating), 1) AS avg_rating,
//         COUNT(r.id)             AS review_count
//     FROM products p
//     JOIN  users      u ON p.seller_id   = u.id
//     LEFT JOIN categories c ON p.category_id = c.id
//     LEFT JOIN reviews    r ON r.product_id  = p.id
//     WHERE p.id = ? AND p.status = 'active'
//     GROUP BY p.id
// ");
// $stmt->execute([$id]);
// $product = $stmt->fetch();

// if (!$product) jsonResponse(['error' => 'Product not found.'], 404);

// $product['price_formatted'] = 'ZAR ' . number_format($product['price'], 0, '.', ' ');
// $product['image_url']       = $product['image'] ? BASE_URL . 'uploads/' . $product['image'] : null;
// $product['seller_initial']  = strtoupper(substr($product['seller_name'], 0, 1));
// $product['avg_rating']      = (float)($product['avg_rating'] ?? 0);

// // Reviews
// $rStmt = $pdo->prepare("
//     SELECT r.rating, r.comment, r.created_at, u.name AS buyer_name
//     FROM reviews r
//     JOIN users u ON r.buyer_id = u.id
//     WHERE r.product_id = ?
//     ORDER BY r.created_at DESC LIMIT 5
// ");
// $rStmt->execute([$id]);
// $product['reviews'] = $rStmt->fetchAll();

// jsonResponse(['success' => true, 'product' => $product]);
