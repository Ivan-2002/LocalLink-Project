<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

$productId = (int)($_POST['product_id'] ?? 0);
if (!$productId) jsonResponse(['error' => 'Product ID is required.'], 422);

try {
    $stmt = $pdo->prepare("SELECT seller_id, image, title FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        jsonResponse(['error' => 'Product not found.'], 404);
    }

    $isOwner = (int)$product['seller_id'] === (int)$_SESSION['user_id'];
    $isAdmin = isAdmin();

    if (!$isOwner && !$isAdmin) {
        jsonResponse(['error' => 'You do not have permission to delete this listing.'], 403);
    }

    // Check for active escrow before allowing delete
    $escStmt = $pdo->prepare("
        SELECT id FROM escrow_transactions 
        WHERE product_id = ? AND status IN ('pending','funded','in_progress','disputed')
        LIMIT 1
    ");
    $escStmt->execute([$productId]);
    if ($escStmt->fetch()) {
        jsonResponse(['error' => 'This product has an active escrow transaction and cannot be deleted.'], 409);
    }

    // Delete dependent rows first (no cascade exists yet)
    $pdo->prepare("DELETE FROM messages WHERE product_id = ?")->execute([$productId]);
    $pdo->prepare("DELETE FROM escrow_status_log WHERE transaction_id IN (SELECT id FROM escrow_transactions WHERE product_id = ?)")->execute([$productId]);
    $pdo->prepare("DELETE FROM escrow_disputes WHERE transaction_id IN (SELECT id FROM escrow_transactions WHERE product_id = ?)")->execute([$productId]);
    $pdo->prepare("DELETE FROM escrow_transactions WHERE product_id = ?")->execute([$productId]);

    // Delete image file from disk
    if ($product['image']) {
        $imagePath = UPLOAD_DIR . $product['image'];
        if (file_exists($imagePath)) unlink($imagePath);
    }

    // Now delete the product itself
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);

    jsonResponse(['success' => true, 'message' => 'Product deleted successfully.']);
} catch (PDOException $e) {
    error_log('Delete product error: ' . $e->getMessage());
    jsonResponse(['error' => 'Could not delete product. It may still be referenced by other records.'], 500);
}
