<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

$productId = (int)($_POST['product_id'] ?? 0);
$action    = $_POST['action'] ?? '';

if (!$productId) jsonResponse(['error' => 'Product ID required.'], 422);

$stmt = $pdo->prepare("SELECT id, title, status FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product) jsonResponse(['error' => 'Product not found.'], 404);

switch ($action) {
    case 'approve':
        $pdo->prepare("UPDATE products SET status = 'active' WHERE id = ?")
            ->execute([$productId]);
        jsonResponse(['success' => true, 'message' => 'Product approved and now active.']);
        break;

    case 'remove':
        $pdo->prepare("UPDATE products SET status = 'removed' WHERE id = ?")
            ->execute([$productId]);
        jsonResponse(['success' => true, 'message' => 'Product removed from listings.']);
        break;

    case 'restore':
        $pdo->prepare("UPDATE products SET status = 'active' WHERE id = ?")
            ->execute([$productId]);
        jsonResponse(['success' => true, 'message' => 'Product restored to active.']);
        break;

    case 'delete':
        // Hard delete — removes from database permanently
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $img = $stmt->fetchColumn();
        if ($img && file_exists(UPLOAD_DIR . $img)) unlink(UPLOAD_DIR . $img);
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);
        jsonResponse(['success' => true, 'message' => 'Product permanently deleted.']);
        break;

    default:
        jsonResponse(['error' => 'Invalid action.'], 422);
}
