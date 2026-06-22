<?php

/**
 * admin/escrow_panel.php
 * Admin panel for managing escrow transactions and disputes
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

// Verify user is admin
if ($_SESSION['role'] !== 'admin') {
    die('Access denied. Admins only.');
}

// Get filter parameter
$status_filter = $_GET['status'] ?? '';
$allowed_statuses = ['pending', 'funded', 'in_progress', 'completed', 'disputed', 'resolved', 'cancelled', 'refunded'];

if ($status_filter && !in_array($status_filter, $allowed_statuses)) {
    $status_filter = '';
}

// Handle dispute resolution actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $escrow_id = (int)($_POST['escrow_id'] ?? 0);
    $action = $_POST['action'];

    if ($escrow_id && in_array($action, ['release_to_seller', 'refund_to_buyer'])) {
        try {
            $db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS
            );
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Fetch dispute
            $stmt = $db->prepare("
                SELECT ed.* FROM escrow_disputes ed
                JOIN escrow_transactions et ON ed.transaction_id = et.id
                WHERE et.id = ? AND ed.status = 'open'
            ");
            $stmt->execute([$escrow_id]);
            $dispute = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dispute) {
                die('Dispute not found or already resolved.');
            }

            $db->beginTransaction();

            // Determine resolution and new transaction status
            if ($action === 'release_to_seller') {
                $resolution = 'released_to_seller';
                $new_status = 'resolved';
            } else {
                $resolution = 'refunded_to_buyer';
                $new_status = 'refunded';
            }

            // Update dispute
            $stmt = $db->prepare("
                UPDATE escrow_disputes 
                SET status = 'resolved', 
                    resolution = ?, 
                    resolved_at = NOW(),
                    admin_note = ?
                WHERE id = ?
            ");
            $stmt->execute([$resolution, $_POST['admin_note'] ?? '', $dispute['id']]);

            // Update escrow transaction
            $stmt = $db->prepare("
                UPDATE escrow_transactions 
                SET status = ?
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $escrow_id]);

            // Get old status for logging
            $stmt = $db->prepare("SELECT status FROM escrow_transactions WHERE id = ?");
            $stmt->execute([$escrow_id]);
            $old_status = $stmt->fetch(PDO::FETCH_ASSOC)['status'];

            // Log status change
            $stmt = $db->prepare("
                INSERT INTO escrow_status_log 
                (transaction_id, old_status, new_status, changed_by, note, created_at)
                VALUES (?, 'disputed', ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $escrow_id,
                $new_status,
                $_SESSION['user_id'],
                'Dispute resolved by admin: ' . $resolution
            ]);

            $db->commit();

            // Redirect to refresh
            header('Location: ' . $_SERVER['PHP_SELF'] . '?status=disputed');
            exit;
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            error_log('Dispute resolution error: ' . $e->getMessage());
            $error_message = 'An error occurred while processing the dispute.';
        }
    }
}

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Build query with optional status filter
    $query = "
        SELECT et.*, 
               p.title as product_title,
               b.name as buyer_name,
               s.name as seller_name,
               COUNT(ed.id) as dispute_count
        FROM escrow_transactions et
        JOIN products p ON et.product_id = p.id
        JOIN users b ON et.buyer_id = b.id
        JOIN users s ON et.seller_id = s.id
        LEFT JOIN escrow_disputes ed ON et.id = ed.transaction_id AND ed.status = 'open'
    ";

    if ($status_filter) {
        $query .= " WHERE et.status = ?";
    }

    $query .= " GROUP BY et.id ORDER BY et.created_at DESC";

    $stmt = $db->prepare($query);

    if ($status_filter) {
        $stmt->execute([$status_filter]);
    } else {
        $stmt->execute();
    }

    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch disputes for disputed transactions
    $stmt = $db->prepare("
        SELECT ed.*, et.id as transaction_id
        FROM escrow_disputes ed
        JOIN escrow_transactions et ON ed.transaction_id = et.id
        WHERE ed.status = 'open'
    ");
    $stmt->execute();
    $open_disputes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $disputes_by_transaction = [];
    foreach ($open_disputes as $d) {
        $disputes_by_transaction[$d['transaction_id']] = $d;
    }
} catch (Exception $e) {
    error_log('Escrow panel error: ' . $e->getMessage());
    die('An error occurred. Please try again.');
}

// Helper function for status badge color
function getStatusBadgeClass($status)
{
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Escrow Panel — LocalLink Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/mobile.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/users.css">
    <link rel="stylesheet" href="assets/css/products.css">
    <link rel="stylesheet" href="assets/css/admin-mobile.css">
    <style>
        .status-filter {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .status-filter .btn-sm {
            font-size: 0.85rem;
        }

        .transaction-row {
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 0;
        }

        .transaction-row:last-child {
            border-bottom: none;
        }

        .dispute-actions {
            display: flex;
            gap: 0.5rem;
        }

        .dispute-actions form {
            display: inline;
        }
    </style>
</head>

<body class="home-page">

    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-brand">LocalLink <span>🛍️</span></div>
            <nav>
                <a href="dashboard.php">🏠 DashBoard</a>
                <a href="users.php">👤 Users</a>
                <a href="products.php">📦 Products</a>
                <a href="categories.php">🗂️ Categories</a>
                <a href="escrow_panel.php" class="active">Escrow Disputes</a>
                <a href="<?= BASE_URL ?>index.php">LocalLink</a>
            </nav>
            <div class="sidebar-footer">
                <a href="<?= BASE_URL ?>logout.php" class="logout-link">→ Log out</a>
            </div>
        </aside>

        <main class="admin-main">

            <div class="admin-topbar">
                <h1 class="admin-page-title">Escrow Transactions</h1>
                <div class="topbar-right">
                    <button class="admin-hamburger" id="adminMenuToggle">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <button class="topbar-icon">🔍</button>
                    <button class="topbar-icon">🔔</button>
                    <div class="topbar-user">
                        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
                        <?= sanitize($_SESSION['name']) ?>
                    </div>
                </div>
            </div>

            <div class="admin-page-subtitle"></div>

            <div class="admin-content">

                <div id="pageAlert" class="alert d-none mb-3"></div>

                <div class="admin-card">

                    <div class="row">
                        <div class="col-12">

                            <?php if (isset($error_message)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?= htmlspecialchars($error_message) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <!-- Status Filter -->
                            <div class="status-filter">
                                <a href="?status=" class="btn btn-sm <?= !$status_filter ? 'btn-primary' : 'btn-outline-primary' ?>">
                                    All
                                </a>
                                <?php foreach ($allowed_statuses as $s): ?>
                                    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status_filter === $s ? 'btn-primary' : 'btn-outline-primary' ?>">
                                        <?= ucfirst($s) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>

                            <!-- Transactions Table -->
                            <div class="card shadow-sm">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Product</th>
                                                <th>Buyer</th>
                                                <th>Seller</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($transactions)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center py-4 text-muted">
                                                        No transactions found.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($transactions as $trans): ?>
                                                    <tr>
                                                        <td><strong>#<?= $trans['id'] ?></strong></td>
                                                        <td><?= htmlspecialchars(substr($trans['product_title'], 0, 30)) ?></td>
                                                        <td><?= htmlspecialchars($trans['buyer_name']) ?></td>
                                                        <td><?= htmlspecialchars($trans['seller_name']) ?></td>
                                                        <td>R<?= number_format($trans['amount'], 2) ?></td>
                                                        <td>
                                                            <span class="badge <?= getStatusBadgeClass($trans['status']) ?>">
                                                                <?= ucfirst($trans['status']) ?>
                                                            </span>
                                                            <?php if ($trans['dispute_count'] > 0): ?>
                                                                <span class="badge bg-danger ms-2">
                                                                    Disputed
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= date('M d, Y', strtotime($trans['created_at'])) ?></td>
                                                        <td>
                                                            <?php if ($trans['status'] === 'disputed' && isset($disputes_by_transaction[$trans['id']])): ?>
                                                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                                    data-bs-target="#disputeModal"
                                                                    data-escrow-id="<?= $trans['id'] ?>"
                                                                    data-reason="<?= htmlspecialchars($disputes_by_transaction[$trans['id']]['reason']) ?>">
                                                                    Resolve
                                                                </button>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
    </div>
    </main>

    <!-- Dispute Resolution Modal -->
    <div class="modal fade" id="disputeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Resolve Dispute</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="escrow_id" name="escrow_id">
                        <p class="mb-3"><strong>Dispute Reason:</strong></p>
                        <div class="alert alert-info" id="reason_text"></div>
                        <div class="mb-3">
                            <label for="admin_note" class="form-label">Admin Note</label>
                            <textarea class="form-control" id="admin_note" name="admin_note" rows="2"
                                placeholder="Optional note for record..."></textarea>
                        </div>
                        <div class="alert alert-warning">
                            <strong>Choose an action:</strong>
                            <p class="mb-0 mt-2">Click a button below to resolve this dispute.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="action" value="release_to_seller" class="btn btn-success">
                            Release to Seller
                        </button>
                        <button type="submit" name="action" value="refund_to_buyer" class="btn btn-info">
                            Refund to Buyer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-mobile.js"></script>
    <script>
        // Populate modal with dispute data
        const disputeModal = document.getElementById('disputeModal');
        disputeModal.addEventListener('show.bs.modal', function(e) {
            const button = e.relatedTarget;
            const escrowId = button.dataset.escrowId;
            const reason = button.dataset.reason;

            document.getElementById('escrow_id').value = escrowId;
            document.getElementById('reason_text').textContent = reason;
        });
    </script>
</body>

</html>