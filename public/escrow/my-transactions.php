<?php
/**
 * escrow/my-transactions.php
 * Display user's escrow transactions
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch user's transactions (as buyer or seller)
    $stmt = $db->prepare("
        SELECT et.*, 
               p.title as product_title,
               b.name as buyer_name,
               s.name as seller_name
        FROM escrow_transactions et
        JOIN products p ON et.product_id = p.id
        JOIN users b ON et.buyer_id = b.id
        JOIN users s ON et.seller_id = s.id
        WHERE et.buyer_id = ? OR et.seller_id = ?
        ORDER BY et.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    error_log('My transactions error: ' . $e->getMessage());
    die('An error occurred. Please try again.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Escrow Transactions — LocalLink</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/mobile.css">
    <style>
        .transaction-card {
            border-left: 4px solid #1a8a6f;
            transition: all 0.2s;
        }
        .transaction-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .role-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
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
            <div class="col-md-10">
                <div class="mb-4">
                    <h2>My Escrow Transactions</h2>
                    <p class="text-muted">View all your active and completed escrow purchases and sales</p>
                </div>

                <?php if (empty($transactions)): ?>
                    <div class="alert alert-info text-center">
                        <p class="mb-0">You don't have any escrow transactions yet.</p>
                        <a href="<?= BASE_URL ?>index.php" class="btn btn-sm btn-primary mt-3">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($transactions as $tx): ?>
                        <?php 
                            $is_buyer = $tx['buyer_id'] == $_SESSION['user_id'];
                            $party_name = $is_buyer ? $tx['seller_name'] : $tx['buyer_name'];
                            $party_role = $is_buyer ? 'Seller' : 'Buyer';
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="card transaction-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0">
                                            <?= htmlspecialchars(substr($tx['product_title'], 0, 30)) ?>
                                            <?php if (strlen($tx['product_title']) > 30): ?>...<?php endif; ?>
                                        </h5>
                                        <span class="badge <?= getStatusBadgeClass($tx['status']) ?>">
                                            <?= ucfirst($tx['status']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3 text-muted small">
                                        <div class="mb-2">
                                            <strong><?= $party_role ?>:</strong> <?= htmlspecialchars($party_name) ?>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Amount:</strong> R<?= number_format($tx['amount'], 2) ?>
                                        </div>
                                        <div>
                                            <strong>Created:</strong> <?= date('M d, Y', strtotime($tx['created_at'])) ?>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <a href="<?= BASE_URL ?>escrow/status.php?escrow_id=<?= $tx['id'] ?>" class="btn btn-sm btn-primary">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="mt-5">
                    <a href="<?= BASE_URL ?>index.php" class="btn btn-outline-secondary">← Back to Marketplace</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="text-muted">© 2026 LocalLink. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
