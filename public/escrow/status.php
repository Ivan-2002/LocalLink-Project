<?php
/**
 * escrow/status.php
 * Display escrow transaction status with action options
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

$escrow_id = (int)($_GET['escrow_id'] ?? 0);
if (!$escrow_id) {
    die('Escrow ID is required.');
}

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch escrow transaction with buyer and seller details
    $stmt = $db->prepare("
        SELECT et.*, 
               p.title, 
               b.name as buyer_name, 
               s.name as seller_name
        FROM escrow_transactions et
        JOIN products p ON et.product_id = p.id
        JOIN users b ON et.buyer_id = b.id
        JOIN users s ON et.seller_id = s.id
        WHERE et.id = ?
    ");
    $stmt->execute([$escrow_id]);
    $escrow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$escrow) {
        die('Escrow transaction not found.');
    }

    // Verify user is either buyer or seller
    if ($escrow['buyer_id'] != $_SESSION['user_id'] && $escrow['seller_id'] != $_SESSION['user_id']) {
        die('Unauthorized access.');
    }

    $is_buyer = $escrow['buyer_id'] == $_SESSION['user_id'];
    $is_seller = $escrow['seller_id'] == $_SESSION['user_id'];

    // Fetch status history
    $stmt = $db->prepare("
        SELECT * FROM escrow_status_log
        WHERE transaction_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$escrow_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Helper function for status badge color
    function getStatusBadgeClass($status) {
        $badges = [
            'pending' => 'bg-secondary',
            'funded' => 'bg-info',
            'in_progress' => 'bg-info',
            'completed' => 'bg-success',
            'disputed' => 'bg-danger',
            'resolved' => 'bg-warning',
            'cancelled' => 'bg-warning',
            'refunded' => 'bg-warning'
        ];
        return $badges[$status] ?? 'bg-secondary';
    }

} catch (Exception $e) {
    error_log('Escrow status error: ' . $e->getMessage());
    die('An error occurred. Please try again.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Escrow Status — LocalLink</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/mobile.css">
</head>
<body class="home-page">
    <header class="top-navbar">
        <div class="nav-left">
            <a href="<?= BASE_URL ?>escrow/my-transactions.php" class="brand-logo" style="text-decoration: none; font-size: 1.5rem;">
                ← Back
            </a>
        </div>
    </header>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Transaction Overview -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Transaction Details</h4>
                        <span class="badge <?= getStatusBadgeClass($escrow['status']) ?> fs-6">
                            <?= ucfirst($escrow['status']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Product:</strong></td>
                                <td><?= htmlspecialchars($escrow['title']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Buyer:</strong></td>
                                <td><?= htmlspecialchars($escrow['buyer_name']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Seller:</strong></td>
                                <td><?= htmlspecialchars($escrow['seller_name']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Amount:</strong></td>
                                <td>R<?= number_format($escrow['amount'], 2) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Platform Fee:</strong></td>
                                <td>R<?= number_format($escrow['platform_fee'], 2) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Seller Receives:</strong></td>
                                <td>R<?= number_format($escrow['seller_receives'], 2) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Created:</strong></td>
                                <td><?= date('M d, Y H:i', strtotime($escrow['created_at'])) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Expires:</strong></td>
                                <td><?= date('M d, Y H:i', strtotime($escrow['expires_at'])) ?></td>
                            </tr>
                            <?php if ($is_seller && $escrow['release_pin']): ?>
                            <tr class="table-warning">
                                <td><strong>Release PIN (Seller Only):</strong></td>
                                <td><code class="fs-5"><?= htmlspecialchars($escrow['release_pin']) ?></code></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <!-- Buyer Actions -->
                <?php if ($is_buyer && in_array($escrow['status'], ['funded', 'in_progress'])): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Confirm Trade Complete</h5>
                    </div>
                    <div class="card-body">
                        <p>Enter the 6-digit PIN provided by the seller to confirm the trade is complete and release funds.</p>
                        <form method="POST" action="<?= BASE_URL ?>escrow/confirm.php">
                            <input type="hidden" name="escrow_id" value="<?= $escrow_id ?>">
                            <div class="mb-3">
                                <label for="pin" class="form-label">Release PIN</label>
                                <input type="text" class="form-control form-control-lg" id="pin" name="release_pin" 
                                       maxlength="6" pattern="[0-9]{6}" required placeholder="000000">
                            </div>
                            <button type="submit" class="btn btn-success">Confirm & Release Funds</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Dispute Button -->
                <?php if ($is_buyer && in_array($escrow['status'], ['funded', 'in_progress'])): ?>
                <div class="card shadow-sm mb-4 border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Raise a Dispute</h5>
                    </div>
                    <div class="card-body">
                        <p>If there's an issue with this transaction, you can raise a dispute for admin review.</p>
                        <form method="POST" action="<?= BASE_URL ?>escrow/dispute.php">
                            <input type="hidden" name="escrow_id" value="<?= $escrow_id ?>">
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for Dispute</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" required 
                                          placeholder="Describe the issue..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger">Raise Dispute</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Status History -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">Transaction History</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($history as $log): ?>
                            <div class="timeline-item mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between">
                                    <strong>
                                        <?= htmlspecialchars($log['old_status'] ?? 'New') ?> → 
                                        <?= htmlspecialchars($log['new_status']) ?>
                                    </strong>
                                    <small class="text-muted">
                                        <?= date('M d, Y H:i', strtotime($log['created_at'])) ?>
                                    </small>
                                </div>
                                <?php if ($log['note']): ?>
                                <small class="text-muted d-block mt-1">
                                    <?= htmlspecialchars($log['note']) ?>
                                </small>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
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
