<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

$receiverId = (int)($_POST['receiver_id'] ?? 0);
$body       = trim($_POST['body']         ?? '');
$productId  = (int)($_POST['product_id']  ?? 0) ?: null;

if (!$receiverId) jsonResponse(['error' => 'Receiver is required.'], 422);
if (!$body)       jsonResponse(['error' => 'Message cannot be empty.'], 422);

// Prevent messaging yourself
if ($receiverId === (int)$_SESSION['user_id']) {
    jsonResponse(['error' => 'You cannot message yourself.'], 422);
}

// Check receiver exists
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
$stmt->execute([$receiverId]);
$receiver = $stmt->fetch();
if (!$receiver) jsonResponse(['error' => 'User not found.'], 404);

// Insert message
$stmt = $pdo->prepare("
    INSERT INTO messages (sender_id, receiver_id, product_id, body)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([
    $_SESSION['user_id'],
    $receiverId,
    $productId,
    $body,
]);

jsonResponse([
    'success'    => true,
    'message_id' => $pdo->lastInsertId(),
    'body'       => sanitize($body),
    'created_at' => date('Y-m-d H:i:s'),
]);
