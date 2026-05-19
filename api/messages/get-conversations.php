<?php
// Returns a list of unique conversations for the logged-in user
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

$userId = (int)$_SESSION['user_id'];

// Get latest message per conversation partner
$stmt = $pdo->prepare("
    SELECT
        m.id,
        m.body,
        m.created_at,
        m.is_read,
        m.product_id,
        -- The OTHER person in the conversation
        CASE
            WHEN m.sender_id = :uid THEN m.receiver_id
            ELSE m.sender_id
        END AS partner_id,
        CASE
            WHEN m.sender_id = :uid2 THEN ru.name
            ELSE su.name
        END AS partner_name,
        -- Unread count (messages sent TO me that I haven't read)
        (
            SELECT COUNT(*) FROM messages unread
            WHERE unread.receiver_id = :uid3
            AND unread.is_read = 0
            AND (
                unread.sender_id = CASE WHEN m.sender_id = :uid4 THEN m.receiver_id ELSE m.sender_id END
            )
        ) AS unread_count
    FROM messages m
    JOIN users su ON m.sender_id   = su.id
    JOIN users ru ON m.receiver_id = ru.id
    WHERE m.sender_id = :uid5 OR m.receiver_id = :uid6
    -- Get only the latest message per partner
    AND m.id = (
        SELECT MAX(m2.id) FROM messages m2
        WHERE (m2.sender_id = :uid7 OR m2.receiver_id = :uid8)
        AND (
            (m2.sender_id   = CASE WHEN m.sender_id = :uid9  THEN m.receiver_id ELSE m.sender_id END)
            OR
            (m2.receiver_id = CASE WHEN m.sender_id = :uid10 THEN m.receiver_id ELSE m.sender_id END)
        )
    )
    ORDER BY m.created_at DESC
");

// Simpler equivalent that avoids complex subqueries
$stmt = $pdo->prepare("
    SELECT
        partner_id,
        partner_name,
        last_message,
        last_time,
        SUM(is_unread) AS unread_count
    FROM (
        SELECT
            CASE WHEN sender_id = :me1 THEN receiver_id ELSE sender_id END AS partner_id,
            CASE WHEN sender_id = :me2 THEN ru.name     ELSE su.name   END AS partner_name,
            m.body       AS last_message,
            m.created_at AS last_time,
            CASE WHEN m.receiver_id = :me3 AND m.is_read = 0 THEN 1 ELSE 0 END AS is_unread,
            ROW_NUMBER() OVER (
                PARTITION BY CASE WHEN sender_id = :me4 THEN receiver_id ELSE sender_id END
                ORDER BY m.created_at DESC
            ) AS rn
        FROM messages m
        JOIN users su ON m.sender_id   = su.id
        JOIN users ru ON m.receiver_id = ru.id
        WHERE m.sender_id = :me5 OR m.receiver_id = :me6
    ) sub
    WHERE rn = 1
    GROUP BY partner_id, partner_name, last_message, last_time
    ORDER BY last_time DESC
");

$stmt->execute([
    ':me1' => $userId,
    ':me2' => $userId,
    ':me3' => $userId,
    ':me4' => $userId,
    ':me5' => $userId,
    ':me6' => $userId,
]);
$conversations = $stmt->fetchAll();

// Format times
foreach ($conversations as &$c) {
    $c['time_formatted'] = date('g:i A', strtotime($c['last_time']));
    $c['partner_initial'] = strtoupper(substr($c['partner_name'], 0, 1));
}

jsonResponse(['success' => true, 'conversations' => $conversations]);
