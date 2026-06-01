<?php
/**
 * escrow/dispute.php
 * Buyer raises a dispute on an escrow transaction
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

$escrow_id = (int)($_POST['escrow_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if (!$escrow_id || empty($reason)) {
    die('Escrow ID and dispute reason are required.');
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
        die('Only the buyer can raise a dispute.');
    }

    // Verify transaction is disputable
    if (!in_array($escrow['status'], ['funded', 'in_progress'])) {
        die('This transaction cannot be disputed in its current state.');
    }

    // Start transaction
    $db->beginTransaction();

    // Create dispute record
    $stmt = $db->prepare("
        INSERT INTO escrow_disputes 
        (transaction_id, raised_by, reason, evidence_note, status, created_at)
        VALUES (?, ?, ?, NULL, 'open', NOW())
    ");
    $stmt->execute([$escrow_id, $_SESSION['user_id'], $reason]);

    // Update escrow status to disputed
    $stmt = $db->prepare("
        UPDATE escrow_transactions 
        SET status = 'disputed'
        WHERE id = ?
    ");
    $stmt->execute([$escrow_id]);

    // Log status change
    $stmt = $db->prepare("
        INSERT INTO escrow_status_log 
        (transaction_id, old_status, new_status, changed_by, note, created_at)
        VALUES (?, ?, 'disputed', ?, ?, NOW())
    ");
    $stmt->execute([$escrow_id, $escrow['status'], $_SESSION['user_id'], 'Dispute raised: ' . substr($reason, 0, 100)]);

    $db->commit();

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    error_log('Escrow dispute error: ' . $e->getMessage());
    die('An error occurred. Please try again.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dispute Raised — LocalLink</title>
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
                            <i class="bi bi-exclamation-circle text-warning" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="card-title text-warning mb-3">Dispute Raised</h2>
                        <p class="card-text text-muted mb-4">
                            Your dispute has been submitted and logged. Our admin team will review it shortly.
                        </p>
                        <div class="alert alert-warning">
                            <strong>Status:</strong> Your transaction has been marked as disputed and is under review.
                        </div>
                        <p class="text-muted small mb-4">
                            You will receive an update once the admin team has reviewed your case.
                        </p>
                        <a href="<?= BASE_URL ?>escrow/status.php?escrow_id=<?= $escrow_id ?>" class="btn btn-primary btn-lg">
                            View Transaction Status
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
