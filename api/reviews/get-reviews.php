<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$sellerId = (int)($_GET['seller_id'] ?? 0);
if (!$sellerId) jsonResponse(['error' => 'Seller ID required.'], 422);

// ── All reviews for this seller ───────────────────────────────
$stmt = $pdo->prepare("
    SELECT
        r.id, r.rating, r.comment, r.created_at,
        u.name   AS buyer_name,
        u.avatar AS buyer_avatar
    FROM reviews r
    JOIN users u ON r.buyer_id = u.id
    WHERE r.seller_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$sellerId]);
$reviews = $stmt->fetchAll();

foreach ($reviews as &$r) {
    $r['initial']        = strtoupper(substr($r['buyer_name'], 0, 1));
    $r['avatar_url']     = $r['buyer_avatar']
        ? BASE_URL . 'uploads/avatars/' . $r['buyer_avatar']
        : null;
    $r['date_formatted'] = date('d M Y', strtotime($r['created_at']));
}

// ── Aggregate stats ───────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT
        ROUND(AVG(rating), 1) AS avg_rating,
        COUNT(*)              AS review_count,
        SUM(rating = 5)       AS five_star,
        SUM(rating = 4)       AS four_star,
        SUM(rating = 3)       AS three_star,
        SUM(rating = 2)       AS two_star,
        SUM(rating = 1)       AS one_star
    FROM reviews WHERE seller_id = ?
");
$stmt->execute([$sellerId]);
$stats = $stmt->fetch();

// ── Has current user already reviewed this seller? ────────────
$userReviewed = false;
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT id FROM reviews WHERE seller_id = ? AND buyer_id = ?
    ");
    $stmt->execute([$sellerId, $_SESSION['user_id']]);
    $userReviewed = (bool)$stmt->fetch();
}

jsonResponse([
    'success'       => true,
    'reviews'       => $reviews,
    'stats'         => $stats,
    'user_reviewed' => $userReviewed,
]);
