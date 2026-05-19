<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

$partnerId = (int)($_GET['partner_id'] ?? 0);
if (!$partnerId) jsonResponse(['error' => 'Partner ID required.'], 422);

$userId = (int)$_SESSION['user_id'];

// Fetch all messages between the two users
$stmt = $pdo->prepare("
    SELECT
        m.id, m.body, m.created_at, m.is_read,
        m.sender_id,
        u.name AS sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (m.sender_id = :me AND m.receiver_id = :them)
       OR (m.sender_id = :them2 AND m.receiver_id = :me2)
    ORDER BY m.created_at ASC
");
$stmt->execute([
    ':me'    => $userId,
    ':them'  => $partnerId,
    ':them2' => $partnerId,
    ':me2'  => $userId,
]);
$messages = $stmt->fetchAll();

// Mark messages from partner as read
$pdo->prepare("
    UPDATE messages
    SET is_read = 1
    WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
")->execute([$partnerId, $userId]);

// Format
foreach ($messages as &$m) {
    $m['is_mine']       = (int)$m['sender_id'] === $userId;
    $m['time_formatted'] = date('g:i A', strtotime($m['created_at']));
}

// Get partner info
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
$stmt->execute([$partnerId]);
$partner = $stmt->fetch();
$partner['initial'] = strtoupper(substr($partner['name'], 0, 1));

jsonResponse([
    'success'  => true,
    'messages' => $messages,
    'partner'  => $partner,
]);
