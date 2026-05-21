<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

$targetId = (int)($_POST['user_id'] ?? 0);
$action   = $_POST['action'] ?? '';

if (!$targetId) jsonResponse(['error' => 'User ID required.'], 422);

// Prevent admin from modifying themselves
if ($targetId === (int)$_SESSION['user_id']) {
    jsonResponse(['error' => 'You cannot modify your own account.'], 403);
}

// Check user exists
$stmt = $pdo->prepare("SELECT id, role, status FROM users WHERE id = ?");
$stmt->execute([$targetId]);
$user = $stmt->fetch();
if (!$user) jsonResponse(['error' => 'User not found.'], 404);

switch ($action) {
    case 'suspend':
        $pdo->prepare("UPDATE users SET status = 'blocked' WHERE id = ?")
            ->execute([$targetId]);
        jsonResponse(['success' => true, 'message' => 'User suspended.']);
        break;

    case 'unsuspend':
        $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")
            ->execute([$targetId]);
        jsonResponse(['success' => true, 'message' => 'User reactivated.']);
        break;

    case 'make_admin':
        // Prevent demoting the last admin
        $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")
            ->fetchColumn();
        $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")
            ->execute([$targetId]);
        jsonResponse(['success' => true, 'message' => 'User promoted to admin.']);
        break;

    case 'remove_admin':
        $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")
            ->fetchColumn();
        if ($adminCount <= 1) {
            jsonResponse(['error' => 'Cannot remove the last admin.'], 409);
        }
        $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?")
            ->execute([$targetId]);
        jsonResponse(['success' => true, 'message' => 'Admin rights removed.']);
        break;

    default:
        jsonResponse(['error' => 'Invalid action.'], 422);
}
