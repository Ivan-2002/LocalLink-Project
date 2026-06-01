<?php
/**
 * escrow/confirm.php
 * Buyer confirms trade completion with PIN and releases funds
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

$escrow_id = (int)($_POST['escrow_id'] ?? 0);
$release_pin = (string)trim($_POST['release_pin'] ?? '');

if (!$escrow_id || !$release_pin) {
    die('Escrow ID and PIN are required.');
}

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch escrow transaction
    $stmt = $db->prepare("SELECT * FROM escrow_transactions WHERE id = ?");
    $stmt->execute([$escrow_id]);
    $escrow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$escrow) {
        die('Escrow transaction not found.');
    }

    // Verify user is the buyer
    if ($escrow['buyer_id'] != $_SESSION['user_id']) {
        die('Only the buyer can confirm this transaction.');
    }

    // Verify transaction is in fundable state
    if (!in_array($escrow['status'], ['funded', 'in_progress'])) {
        die('This transaction cannot be confirmed in its current state. Status: ' . $escrow['status']);
    }

    // Verify PIN matches
    $stored_pin = (string)trim($escrow['release_pin']);
    $entered_pin = (string)trim($release_pin);
    
    error_log("PIN comparison for escrow_id=$escrow_id: entered=[$entered_pin] vs stored=[$stored_pin]");
    
    if ($entered_pin !== $stored_pin) {
        error_log("PIN MISMATCH: Entered PIN '$entered_pin' does not match stored PIN '$stored_pin'");
        die("Invalid PIN. You entered: $entered_pin | Expected: $stored_pin");
    }

    // Start transaction
    $db->beginTransaction();

    // Update escrow to completed
    $stmt = $db->prepare("
        UPDATE escrow_transactions 
        SET status = 'completed', 
            buyer_confirmed = 1, 
            completed_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$escrow_id]);

    // Update product status to sold
    $stmt = $db->prepare("
        UPDATE products 
        SET status = 'sold'
        WHERE id = ?
    ");
    $stmt->execute([$escrow['product_id']]);

    // Log status change
    $stmt = $db->prepare("
        INSERT INTO escrow_status_log 
        (transaction_id, old_status, new_status, changed_by, note, created_at)
        VALUES (?, ?, 'completed', ?, 'Trade confirmed by buyer with correct PIN', NOW())
    ");
    $stmt->execute([$escrow_id, $escrow['status'], $_SESSION['user_id']]);

    $db->commit();

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    error_log('Escrow confirm error: ' . $e->getMessage());
    die('An error occurred: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trade Complete — LocalLink</title>
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
                <div class="card shadow-sm text-center">
                    <div class="card-body py-5">
                        <div class="mb-4">
                            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="card-title text-success mb-3">Trade Complete!</h2>
                        <p class="card-text text-muted mb-4">
                            Funds have been released to the seller. The product has been marked as sold.
                        </p>
                        <div class="alert alert-success">
                            <strong>R<?= number_format($escrow['seller_receives'], 2) ?></strong> has been transferred to the seller.
                        </div>
                        <a href="<?= BASE_URL ?>index.php" class="btn btn-primary btn-lg">
                            Back to Marketplace
                        </a>
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
