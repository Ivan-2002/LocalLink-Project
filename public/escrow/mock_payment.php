<?php

/**
 * escrow/mock_payment.php
 * Simulated payment gateway for escrow transactions
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

$escrow_id = (int)($_GET['escrow_id'] ?? 0);
if (!$escrow_id) {
    jsonResponse(['error' => 'Escrow ID is required.'], 400);
}

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch escrow transaction
    $stmt = $db->prepare("
        SELECT et.*, p.title 
        FROM escrow_transactions et
        JOIN products p ON et.product_id = p.id
        WHERE et.id = ?
    ");
    $stmt->execute([$escrow_id]);
    $escrow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$escrow) {
        die('Escrow transaction not found.');
    }

    // Verify user is the buyer
    if ($escrow['buyer_id'] != $_SESSION['user_id']) {
        die('Unauthorized access.');
    }

    // Handle payment simulation success
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'pay_success') {
            $payment_reference = 'MOCK-' . strtoupper(bin2hex(random_bytes(8)));
            $release_pin = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Update escrow to funded status
            $stmt = $db->prepare("
                UPDATE escrow_transactions 
                SET status = 'funded', 
                    funded_at = NOW(), 
                    payment_reference = ?, 
                    release_pin = ?
                WHERE id = ?
            ");
            $stmt->execute([$payment_reference, $release_pin, $escrow_id]);

            // Log status change
            $stmt = $db->prepare("
                INSERT INTO escrow_status_log 
                (transaction_id, old_status, new_status, changed_by, note, created_at)
                VALUES (?, 'pending', 'funded', ?, 'Payment simulated successfully', NOW())
            ");
            $stmt->execute([$escrow_id, $_SESSION['user_id']]);

            // Send automated message to seller with PIN
            try {
                // Get seller info
                $stmt = $db->prepare("
                    SELECT et.seller_id, u.name as seller_name, p.title as product_title
                    FROM escrow_transactions et
                    JOIN users u ON et.seller_id = u.id
                    JOIN products p ON et.product_id = p.id
                    WHERE et.id = ?
                ");
                $stmt->execute([$escrow_id]);
                $seller_info = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($seller_info) {
                    // Find or create a system bot user
                    $stmt = $db->prepare("SELECT id FROM users WHERE email = 'system@locallink.local' LIMIT 1");
                    $stmt->execute();
                    $bot_user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$bot_user) {
                        // Create system bot if it doesn't exist
                        $stmt = $db->prepare("
                            INSERT INTO users (name, email, password, role, status, location, created_at)
                            VALUES ('LocalLink System', 'system@locallink.local', ?, 'admin', 'active', 'System', NOW())
                        ");
                        $stmt->execute([password_hash('system_bot_secure_key', PASSWORD_DEFAULT)]);
                        $bot_user_id = $db->lastInsertId();
                    } else {
                        $bot_user_id = $bot_user['id'];
                    }

                    // Create the message
                    $message_body = "🔔 Payment Received!\n\n" .
                        "A buyer has completed payment for your listing: " . htmlspecialchars($seller_info['product_title']) . "\n\n" .
                        "Transaction ID: #" . $escrow_id . "\n" .
                        "Amount Received: R" . number_format($escrow['amount'], 2) . "\n" .
                        "Platform Fee: R" . number_format($escrow['platform_fee'], 2) . "\n" .
                        "You Receive: R" . number_format($escrow['seller_receives'], 2) . "\n\n" .
                        "🔐 RELEASE PIN: " . $release_pin . "\n\n" .
                        "Once the buyer confirms they have received the item in good condition, share this PIN with them to complete the transaction and release the funds to your account.\n\n" .
                        "View full details: Go to your messages or check the escrow status page.";

                    // Send message
                    $stmt = $db->prepare("
                        INSERT INTO messages (sender_id, receiver_id, body, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$bot_user_id, $seller_info['seller_id'], $message_body]);
                }
            } catch (Exception $e) {
                // Log error but don't fail the payment - message is informational only
                error_log('Failed to send PIN message to seller: ' . $e->getMessage());
            }

            header('Location: ' . BASE_URL . 'escrow/status.php?escrow_id=' . $escrow_id);
            exit;
        } elseif ($_POST['action'] === 'cancel') {
            // Update escrow to cancelled status
            $stmt = $db->prepare("
                UPDATE escrow_transactions 
                SET status = 'cancelled'
                WHERE id = ?
            ");
            $stmt->execute([$escrow_id]);

            // Log status change
            $stmt = $db->prepare("
                INSERT INTO escrow_status_log 
                (transaction_id, old_status, new_status, changed_by, note, created_at)
                VALUES (?, 'pending', 'cancelled', ?, 'Payment cancelled by buyer', NOW())
            ");
            $stmt->execute([$escrow_id, $_SESSION['user_id']]);

            header('Location: ' . BASE_URL . 'product.php?id=' . $escrow['product_id']);
            exit;
        }
    }
} catch (Exception $e) {
    error_log('Mock payment error: ' . $e->getMessage());
    die('An error occurred. Please try again.');
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mock Payment — LocalLink</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/mobile.css">
</head>

<body class="home-page">
    <header class="top-navbar">
        <div class="nav-left">
            <a href="<?= BASE_URL ?>index.php" class="brand-logo">
                <strong>LocalLink</strong>
            </a>
        </div>
    </header>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Order Summary</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Product:</strong></td>
                                <td><?= htmlspecialchars($escrow['title']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Amount:</strong></td>
                                <td>R<?= number_format($escrow['amount'], 2) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Platform Fee:</strong></td>
                                <td>R<?= number_format($escrow['platform_fee'], 2) ?></td>
                            </tr>
                            <tr class="table-active">
                                <td><strong>Seller Receives:</strong></td>
                                <td><strong>R<?= number_format($escrow['seller_receives'], 2) ?></strong></td>
                            </tr>
                        </table>

                        <div class="alert alert-info mt-4">
                            <strong>Simulated Payment:</strong> This is a mock payment gateway for testing.
                            Click "Pay Now" to simulate a successful PayFast payment.
                        </div>

                        <form method="POST" class="mt-4">
                            <div class="d-grid gap-2">
                                <button type="submit" name="action" value="pay_success" class="btn btn-success btn-lg">
                                    Simulate Payment Success
                                </button>
                                <button type="submit" name="action" value="cancel" class="btn btn-secondary">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="text-muted">© 2024 LocalLink. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>