<?php
// Called every 10 seconds by the navbar to update the bell badge
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS unread
    FROM messages
    WHERE receiver_id = ? AND is_read = 0
");
$stmt->execute([$_SESSION['user_id']]);
$row = $stmt->fetch();

jsonResponse(['success' => true, 'unread' => (int)$row['unread']]);
