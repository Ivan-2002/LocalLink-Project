<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard — TownMarket</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/admin-mobile.css">
</head>

<body>
    <div class="admin-wrapper">

        <!-- ── SIDEBAR ─────────────────────────────── -->
        <aside class="sidebar">
            <div class="sidebar-brand">LocalLink <span>🛍️</span></div>
            <nav>
                <a href="dashboard.php" class="active">🏠 DashBoard</a>
                <a href="users.php">👤 Users</a>
                <a href="products.php">📦 Products</a>
                <a href="categories.php">🗂️ Categories</a>
                <a href="<?= BASE_URL ?>index.php">LocalLink</a>
            </nav>
            <div class="sidebar-footer">
                <a href="<?= BASE_URL ?>logout.php" class="logout-link">→ Log out</a>
            </div>
        </aside>

        <!-- ── MAIN ────────────────────────────────── -->
        <main class="admin-main">

            <!-- Top bar -->
            <div class="admin-topbar">
                <h1 class="admin-page-title">Admin Dashboard</h1>
                <div class="topbar-right">
                    <button class="topbar-icon">🔍</button>
                    <button class="topbar-icon">🔔</button>
                    <div class="topbar-user">
                        <div class="topbar-avatar">
                            <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
                        </div>
                        <?= sanitize($_SESSION['name']) ?>
                    </div>
                </div>
            </div>
            <div class="admin-page-subtitle"></div>

            <div class="admin-content">

                <!-- Alert -->
                <div id="pageAlert" class="alert d-none mb-4"></div>

                <!-- ── STAT CARDS ──────────────────────── -->
                <div class="adm-stats-grid" id="statsGrid">
                    <div class="adm-stat-card skeleton"></div>
                    <div class="adm-stat-card skeleton"></div>
                    <div class="adm-stat-card skeleton"></div>
                    <div class="adm-stat-card skeleton"></div>
                </div>

                <!-- ── RECENT ACTIVITY ─────────────────── -->
                <h2 class="adm-section-title">Recent Activity</h2>
                <div class="adm-activity-grid">
                    <div class="adm-activity-card">
                        <div class="adm-activity-header">New Listings</div>
                        <ul class="adm-activity-list" id="recentListings">
                            <li class="adm-loading">Loading...</li>
                        </ul>
                    </div>
                    <div class="adm-activity-card">
                        <div class="adm-activity-header">New Users</div>
                        <ul class="adm-activity-list" id="recentUsers">
                            <li class="adm-loading">Loading...</li>
                        </ul>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        $(function() {
            $.get(BASE_URL + '../api/admin/get-dashboard-stats.php')
                .done(res => {
                    if (!res.success) return;
                    const s = res.stats;

                    $('#statsGrid').html(`
        <div class="adm-stat-card">
          <div class="adm-stat-label">Total Users</div>
          <div class="adm-stat-value">${Number(s.total_users).toLocaleString()}</div>
        </div>
        <div class="adm-stat-card">
          <div class="adm-stat-label">Total Products</div>
          <div class="adm-stat-value">${Number(s.total_products).toLocaleString()}</div>
        </div>
        <div class="adm-stat-card">
          <div class="adm-stat-label">Total Orders</div>
          <div class="adm-stat-value">${Number(s.total_orders).toLocaleString()}</div>
        </div>
        <div class="adm-stat-card">
          <div class="adm-stat-label">Reported Items</div>
          <div class="adm-stat-value">${Number(s.reported_items).toLocaleString()}</div>
        </div>
      `);

                    $('#recentListings').html(
                        res.listings.length ?
                        res.listings.map(l => `<li>New Listing: <strong>${esc(l.title)}</strong> ${l.time_ago}</li>`).join('') :
                        '<li class="adm-loading">No listings yet.</li>'
                    );

                    $('#recentUsers').html(
                        res.users.length ?
                        res.users.map(u => `<li>New User: <strong>${esc(u.name)}</strong> ${u.time_ago}</li>`).join('') :
                        '<li class="adm-loading">No users yet.</li>'
                    );
                })
                .fail(() => {
                    $('#pageAlert').removeClass('d-none').addClass('alert alert-danger')
                        .text('Failed to load dashboard stats.');
                });

            function esc(s) {
                return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }
        });
    </script>
</body>

</html>