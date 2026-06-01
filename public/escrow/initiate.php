<?php

/**
 * escrow/initiate.php
 * Initiates an escrow transaction for a product purchase
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

// Verify user is a regular user (not admin)
if ($_SESSION['role'] !== 'user') {
    jsonResponse(['error' => 'Only regular users can initiate escrow transactions.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Invalid request method.'], 405);
}

$product_id = (int)($_POST['product_id'] ?? 0);
if (!$product_id) {
    jsonResponse(['error' => 'Product ID is required.'], 400);
}

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch product details
    $stmt = $db->prepare("SELECT id, seller_id, title, price, status FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        jsonResponse(['error' => 'Product not found.'], 404);
    }

    // Verify buyer is not the seller
    if ($product['seller_id'] == $_SESSION['user_id']) {
        jsonResponse(['error' => 'You cannot buy your own product.'], 403);
    }

    // Verify product is available
    if ($product['status'] !== 'active') {
        jsonResponse(['error' => 'This product is no longer available.'], 400);
    }

    $buyer_id = $_SESSION['user_id'];
    $seller_id = $product['seller_id'];
    $amount = (float)$product['price'];

    // Check if buyer already has an active escrow for this product
    $stmt = $db->prepare("
        SELECT id FROM escrow_transactions 
        WHERE product_id = ? AND buyer_id = ? 
        AND status NOT IN ('completed', 'cancelled', 'refunded', 'disputed')
    ");
    $stmt->execute([$product_id, $buyer_id]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'You already have an active purchase for this product.'], 400);
    }

    // Calculate platform fee
    if ($amount < 500) {
        $platform_fee = 5.00;
    } else {
        $platform_fee = round($amount * 0.02, 2); // 2%
    }

    $seller_receives = round($amount - $platform_fee, 2);
    $expires_at = date('Y-m-d H:i:s', strtotime('+5 days'));

    // Create escrow transaction
    $stmt = $db->prepare("
        INSERT INTO escrow_transactions 
        (product_id, buyer_id, seller_id, amount, platform_fee, seller_receives, status, expires_at, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
    ");
    $stmt->execute([
        $product_id,
        $buyer_id,
        $seller_id,
        $amount,
        $platform_fee,
        $seller_receives,
        $expires_at
    ]);

    $escrow_id = $db->lastInsertId();

    // Log status change
    $stmt = $db->prepare("
        INSERT INTO escrow_status_log 
        (transaction_id, old_status, new_status, changed_by, note, created_at)
        VALUES (?, NULL, 'pending', ?, 'Escrow transaction initiated', NOW())
    ");
    $stmt->execute([$escrow_id, $buyer_id]);

    // Redirect to mock payment page
    header('Location: ' . BASE_URL . 'escrow/mock_payment.php?escrow_id=' . $escrow_id);
    exit;
} catch (Exception $e) {
    error_log('Escrow initiate error: ' . $e->getMessage());
    jsonResponse(['error' => 'An error occurred. Please try again.'], 500);
}
