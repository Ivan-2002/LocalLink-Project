<?php
// User profile page
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Dashboard — LocalLink</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/home.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/mobile.css">
</head>

<body class="home-page">

    <!-- ── TOP NAVBAR ─────────────────────────────────────────── -->
    <header class="top-navbar">
        <div class="nav-left">
            <a href="<?= BASE_URL ?>index.php" class="brand-logo">
                LocalLink <span class="logo-icon">🛍️</span>
                <button class="hamburger-mobile" id="mobileMenuBtn" aria-label="Open menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </a>
        </div>
        <div class="nav-center">
            <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" class="search-bar" placeholder="Search"
                    onkeydown="if(event.key==='Enter') window.location='index.php?search='+this.value">
            </div>
        </div>
        <div class="nav-right">
            <a href="<?= BASE_URL ?>index.php" class="nav-icon-btn" title="Home">🏠</a>
            <a href="<?= BASE_URL ?>messages.php" class="nav-icon-btn" title="Messages">💬</a>
            <div class="nav-icon-btn notif-wrap" id="bellWrap" style="position:relative; cursor:pointer;">
                🔔
                <span class="notif-badge d-none" id="notifBadge"
                    style="position:absolute; top:-4px; right:-4px;
               background:#e03131; color:#fff;
               font-size:.62rem; font-weight:800;
               min-width:18px; height:18px;
               border-radius:9px; display:flex;
               align-items:center; justify-content:center;
               padding:0 4px; border:2px solid #fff;">0</span>
            </div>
            <div class="avatar-wrap" id="avatarToggle">
                <div class="avatar-circle" id="navAvatar">
                    <?php if (!empty($_SESSION['avatar'])): ?>
                        <img src="<?= BASE_URL ?>uploads/avatars/<?= $_SESSION['avatar'] ?>"
                            alt="Profile"
                            style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                    <?php else: ?>
                        <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="avatar-dropdown" id="avatarDropdown">
                    <a href="<?= BASE_URL ?>dashboard.php">👤 Profile</a>
                    <hr>
                    <?php if (isAdmin()): ?>
                        <hr>
                        <!-- Only admins see this link -->
                        <a href="<?= BASE_URL ?>../admin/dashboard.php"
                            style="color:#7c3aed; font-weight:700;">
                            🛡️ Admin Panel
                        </a>
                    <?php endif; ?>
                    <hr>
                    <a href="<?= BASE_URL ?>logout.php" class="text-danger">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- ── DASHBOARD BODY ─────────────────────────────────────── -->
    <div class="db-page">

        <!-- ── PROFILE HERO ───────────────────────────────────────── -->
        <div class="db-hero">
            <div class="db-hero-inner">

                <!-- Clickable avatar — clicking opens file picker -->
                <div class="db-avatar-wrap" id="heroAvatarWrap" title="Change photo">
                    <div class="db-avatar" id="dbAvatar">
                        <!-- JS fills this with <img> or initial letter -->
                    </div>
                    <div class="db-avatar-overlay">📷</div>
                    <!-- Hidden file input -->
                    <input type="file" id="avatarFileInput"
                        accept="image/jpeg,image/png,image/webp"
                        style="display:none">
                </div>

                <div class="db-hero-info">
                    <h1 class="db-hero-name" id="dbName"><?= sanitize($_SESSION['name']) ?></h1>
                    <p class="db-hero-meta" id="dbMeta">Loading...</p>
                </div>

                <div class="db-hero-stats" id="dbStats">
                    <div class="db-stat">
                        <span class="db-stat-value" id="statListings">—</span>
                        <span class="db-stat-label">Listings</span>
                    </div>
                    <div class="db-stat-divider"></div>
                    <div class="db-stat">
                        <span class="db-stat-value" id="statOrders">—</span>
                        <span class="db-stat-label">Orders</span>
                    </div>
                    <div class="db-stat-divider"></div>
                    <div class="db-stat">
                        <span class="db-stat-value" id="statSince">—</span>
                        <span class="db-stat-label">Member since</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── TABS ───────────────────────────────────────────────── -->
        <div class="db-tabs-wrap">
            <div class="db-tabs">
                <button class="db-tab active" data-tab="listings">🛍️ My Listings</button>
                <button class="db-tab" data-tab="orders">📦 My Orders</button>
                <button class="db-tab" data-tab="profile">👤 Profile Settings</button>
            </div>
        </div>

        <!-- ── TAB CONTENT ────────────────────────────────────────── -->
        <div class="db-content">

            <!-- ══ TAB 1 — MY LISTINGS ══════════════════════════════ -->
            <div class="db-panel active" id="tab-listings">

                <div class="db-panel-header">
                    <h2 class="db-panel-title">My Listings</h2>
                    <a href="<?= BASE_URL ?>seller/add-product.php" class="db-btn-primary">
                        + List New Item
                    </a>
                </div>

                <!-- Filter row -->
                <div class="db-filter-row">
                    <button class="db-filter-btn active" data-filter="all">All</button>
                    <button class="db-filter-btn" data-filter="active">Active</button>
                    <button class="db-filter-btn" data-filter="pending">Pending</button>
                </div>

                <div id="listingsGrid" class="db-listings-grid">
                    <div class="db-loading">Loading your listings...</div>
                </div>
                <div id="listingsEmpty" class="db-empty d-none">
                    <div class="db-empty-icon">🛍️</div>
                    <p class="db-empty-title">No listings yet</p>
                    <p class="db-empty-sub">Start selling by listing your first item!</p>
                    <a href="<?= BASE_URL ?>seller/add-product.php" class="db-btn-primary mt-3">
                        + List First Item
                    </a>
                </div>

            </div>

            <!-- ══ TAB 2 — MY ORDERS ════════════════════════════════ -->
            <div class="db-panel" id="tab-orders">

                <div class="db-panel-header">
                    <h2 class="db-panel-title">My Orders</h2>
                </div>

                <div id="ordersList" class="db-orders-list">
                    <div class="db-loading">Loading your orders...</div>
                </div>
                <div id="ordersEmpty" class="db-empty d-none">
                    <div class="db-empty-icon">📦</div>
                    <p class="db-empty-title">No orders yet</p>
                    <p class="db-empty-sub">Browse the marketplace and place your first order!</p>
                    <a href="<?= BASE_URL ?>index.php" class="db-btn-primary mt-3">Browse Listings</a>
                </div>

            </div>

            <!-- ══ TAB 3 — PROFILE SETTINGS ════════════════════════ -->
            <div class="db-panel" id="tab-profile">

                <div class="db-panel-header">
                    <h2 class="db-panel-title">Profile Settings</h2>
                </div>

                <div class="db-profile-grid">

                    <!-- Profile form -->
                    <div class="db-card">
                        <h3 class="db-card-title">Personal Information</h3>

                        <!-- Avatar section inside the profile form card -->
                        <div class="db-avatar-section">
                            <div class="db-avatar-preview" id="profileAvatarPreview">
                                <div class="db-avatar-preview-overlay">📷</div>
                                <!-- JS fills this -->
                            </div>
                            <div class="db-avatar-info">
                                <h4>Profile Photo</h4>
                                <p>JPG, PNG or WebP.<br>Max size 2MB.</p>
                                <label class="db-btn-upload-avatar" for="profileAvatarInput">
                                    📷 Change Photo
                                </label>
                            </div>
                        </div>

                        <div id="profileAlert" class="alert d-none mb-3"></div>

                        <form id="profileForm" enctype="multipart/form-data">
                            <!-- Hidden file input triggered by the label above -->
                            <input type="file" id="profileAvatarInput" name="avatar"
                                accept="image/jpeg,image/png,image/webp" style="display:none">

                            <div class="db-field">
                                <label class="db-label">Full Name</label>
                                <input type="text" name="name" id="profileName" class="db-input" required>
                            </div>
                            <div class="db-field">
                                <label class="db-label">Email Address</label>
                                <input type="email" name="email" id="profileEmail" class="db-input" required>
                            </div>
                            <div class="db-field">
                                <label class="db-label">Location</label>
                                <input type="text" name="location" id="profileLocation"
                                    class="db-input" placeholder="e.g. Soweto, GP">
                            </div>
                            <button type="submit" class="db-btn-primary w-100 mt-2">
                                Save Changes
                            </button>
                        </form>
                    </div>

                    <!-- Password form -->
                    <div class="db-card">
                        <h3 class="db-card-title">Change Password</h3>
                        <div id="passwordAlert" class="alert d-none mb-3"></div>
                        <form id="passwordForm">
                            <div class="db-field">
                                <label class="db-label">Current Password</label>
                                <input type="password" name="current_password"
                                    class="db-input" placeholder="••••••••">
                            </div>
                            <div class="db-field">
                                <label class="db-label">New Password</label>
                                <input type="password" name="new_password"
                                    class="db-input" placeholder="Min. 6 characters" minlength="6">
                            </div>
                            <button type="submit" class="db-btn-outline w-100 mt-2">
                                Update Password
                            </button>
                        </form>
                    </div>

                    <!-- Account info card -->
                    <div class="db-card db-account-card">
                        <h3 class="db-card-title">Account</h3>
                        <div class="db-account-row">
                            <span class="db-account-label">Role</span>
                            <span class="db-account-value" id="accountRole">—</span>
                        </div>
                        <div class="db-account-row">
                            <span class="db-account-label">Member since</span>
                            <span class="db-account-value" id="accountSince">—</span>
                        </div>
                        <div class="db-account-row">
                            <span class="db-account-label">Active listings</span>
                            <span class="db-account-value" id="accountListings">—</span>
                        </div>
                        <hr style="border-color:#ede3d6; margin: 16px 0">
                        <a href="<?= BASE_URL ?>logout.php" class="db-btn-danger w-100">
                            Logout
                        </a>
                    </div>

                </div>
            </div>

        </div><!-- /db-content -->
    </div><!-- /db-page -->

    <!-- ── FOOTER ─────────────────────────────────────────────── -->
    <footer class="home-footer">
        <span>© 2026 TownMarket — Community Market | Cape Town</span>
        <div class="footer-links">
            <a href="#">Help</a>
            <a href="#">Contact</a>
            <a href="#">About our Community</a>
        </div>
    </footer>

    <script>

    </script>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
    </script>
    <script src="<?= BASE_URL ?>assets/js/dashboard.js"></script>
    <script>
        $(function() {
            // Poll notification count every 10 seconds
            function pollNotifications() {
                $.get(BASE_URL + '../api/messages/get-unread-count.php')
                    .done(res => {
                        if (!res.success) return;
                        const badge = $('#notifBadge');
                        if (res.unread > 0) {
                            badge.text(res.unread).removeClass('d-none').css('display', 'flex');
                        } else {
                            badge.addClass('d-none');
                        }
                    });
            }

            // Bell click → go to messages
            $('#bellWrap').on('click', () => {
                window.location.href = BASE_URL + 'messages.php';
            });

            pollNotifications();
            setInterval(pollNotifications, 10000);
        });
    </script>
</body>

</html>