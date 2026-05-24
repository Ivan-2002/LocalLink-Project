<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

$sellerId = (int)($_POST['seller_id'] ?? 0);
$rating   = (int)($_POST['rating']    ?? 0);
$comment  = sanitize($_POST['comment'] ?? '');
$userId   = (int)$_SESSION['user_id'];

// ── Validate ──────────────────────────────────────────────────
if (!$sellerId) jsonResponse(['error' => 'Seller ID is required.'], 422);
if ($rating < 1 || $rating > 5) {
    jsonResponse(['error' => 'Please select a star rating (1–5).'], 422);
}

// ── Cannot review yourself ────────────────────────────────────
if ($sellerId === $userId) {
    jsonResponse(['error' => 'You cannot review yourself.'], 403);
}

// ── Check seller exists ───────────────────────────────────────
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
$stmt->execute([$sellerId]);
$seller = $stmt->fetch();
if (!$seller) jsonResponse(['error' => 'Seller not found.'], 404);

// ── One review per buyer per seller ──────────────────────────
$stmt = $pdo->prepare("
    SELECT id FROM reviews WHERE seller_id = ? AND buyer_id = ?
");
$stmt->execute([$sellerId, $userId]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'You have already reviewed this seller.'], 409);
}

// ── Insert ────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    INSERT INTO reviews (seller_id, buyer_id, rating, comment)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$sellerId, $userId, $rating, $comment ?: null]);

// ── Return updated seller stats ───────────────────────────────
$stmt = $pdo->prepare("
    SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS review_count
    FROM reviews WHERE seller_id = ?
");
$stmt->execute([$sellerId]);
$stats = $stmt->fetch();

jsonResponse([
    'success'      => true,
    'message'      => 'Review submitted!',
    'avg_rating'   => (float)$stats['avg_rating'],
    'review_count' => (int)$stats['review_count'],
    'review'       => [
        'buyer_name' => $_SESSION['name'],
        'rating'     => $rating,
        'comment'    => $comment,
        'created_at' => date('Y-m-d H:i:s'),
    ],
]);
