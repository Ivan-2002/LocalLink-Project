<?php
// Homepage
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Fetch categories for sidebar
$catStmt = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name");
$categories = $catStmt->fetchAll();

$pageTitle = 'Home — LocalLink';
$userName  = $_SESSION['name'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/home.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/mobile.css">
</head>

<body class="home-page">

    <!-- NAV BAR -->
    <header class="top-navbar">
        <div class="nav-left">
            <a href="index.php" class="brand-logo">
                LocalLink <span class="logo-icon">🛍️</span>
            </a>
        </div>

        <div class="nav-center">
            <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchInput" class="search-bar" placeholder="Search">
            </div>
        </div>

        <div class="nav-right">
            <!-- <a href="<?= BASE_URL ?>cart.php" class="nav-icon-btn" title="Cart">🛒</a> -->
            <a href="<?= BASE_URL ?>messages.php" class="nav-icon-btn" title="Messages">💬</a>
            <?php if (isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>escrow/my-transactions.php" class="nav-icon-btn" title="My Escrow Transactions">📦</a>
            <?php endif; ?>
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
            <?php if (isLoggedIn()): ?>
                <div class="avatar-wrap" id="avatarToggle">
                    <div class="avatar-circle">
                        <?php if (!empty($_SESSION['avatar'])): ?>
                            <img src="assets/uploads/avatars/<?= $_SESSION['avatar'] ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="avatar-dropdown" id="avatarDropdown">
                        <a href="<?= BASE_URL ?>dashboard.php">👤 Profile</a>
                        <!-- <a href="<?= BASE_URL ?>orders.php">📦 Orders</a> -->
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
            <?php else: ?>
                <a href="<?= BASE_URL ?>login.php" class="btn-login-nav">Login</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- SECOND NAV BAR -->
    <nav class="secondary-nav">
        <div class="sec-nav-left">
            <button class="hamburger-btn" id="catMenuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <a href="#" class="sec-nav-link active" data-tab="all">For You</a>
            <!-- <a href="#" class="sec-nav-link" data-tab="for_you">Categories</a>
            <a href="#" class="sec-nav-link" data-tab="spotlights">Community Spotlights</a> -->
        </div>
        <div class="sec-nav-right">
            <?php if (isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>seller/add-product.php" class="btn-list-item">+ List a New Item</a>
            <?php endif; ?>
        </div>
        <aside class="left-sidebar" id="leftSidebar">
        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay" style="display: none;"></div>
    </nav>

    <!-- MAIN LAYOUT -->
    <div class="home-layout">
        <!-- SIDE BAR -->
        <aside class="left-sidebar mobile-cat-sidebar" id="leftCatSidebar">
            <div class="sidebar-user">
                <p class="sidebar-hello">Hello <?= isLoggedIn() ? 'User' : 'Guest' ?></p>
                <p class="sidebar-username"><?= sanitize($userName) ?></p>
            </div>
            <div class="sidebar-label">Categories</div>
            <ul class="cat-list">
                <li class="cat-item <?= !isset($_GET['category']) ? 'active' : '' ?>">
                    <a href="index.php">All Items</a>
                </li>
                <?php foreach ($categories as $cat): ?>
                    <li class="cat-item <?= (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'active' : '' ?>">
                        <a href="index.php?category=<?= $cat['id'] ?>">
                            <?= sanitize($cat['name']) ?>
                            <span class="cat-arrow">›</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <div class="mobile-cat-overlay" id="mobileCatOverlay"></div>

        <!-- ── SIDEBAR OVERLAY (mobile) ─────────────── -->
        <!-- <div class="sidebar-overlay d-none" id="sidebarOverlay"></div> -->

        <!-- MAIN CONTENT -->
        <div class="main-content">

            <!-- Filter bar -->
            <div class="filter-bar">
                <strong class="filter-title">Filters</strong>
                <div class="filter-group">
                    <label>Location</label>
                    <input type="text" id="filterLocation" class="filter-input" placeholder="Any location">
                </div>
                <div class="filter-group">
                    <label>Sort by</label>
                    <select id="filterSort" class="filter-select">
                        <option value="newest">Newest</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date Listed</label>
                    <select id="filterDate" class="filter-select">
                        <option value="">Any time</option>
                        <option value="today">Today</option>
                        <option value="week">This week</option>
                        <option value="month">This month</option>
                    </select>
                </div>
                <button class="btn-apply-filter" id="applyFilter">Apply</button>
            </div>

            <!-- Local Favorites Carousel -->
            <!-- <div class="section-header">
                <span class="section-title">⭐ Local Favorites</span>
            </div>
            <div class="carousel-wrap">
                <button class="carousel-btn left" id="carouselPrev">&#8249;</button>
                <div class="carousel-track" id="carouselTrack">
                     Loaded by JS 
                    <div class="carousel-placeholder">Loading favourites...</div>
                </div>
                <button class="carousel-btn right" id="carouselNext">&#8250;</button>
            </div> -->

            <!-- Product Grid -->
            <div class="section-header mt-4">
                <span class="section-title">🛍️ All Listings</span>
                <span class="product-count" id="productCount"></span>
            </div>
            <div class="product-grid" id="productGrid">
                <!-- Loaded by home.js -->
                <div class="loading-state">Loading products...</div>
            </div>

            <!-- No results -->
            <div class="empty-state d-none" id="emptyState">
                <p>😕 No products found.</p>
                <small>Try a different category or search term.</small>
            </div>

        </div><!-- /main-content -->
    </div><!-- /home-layout -->

    <!-- FOOTER -->
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
    <script src="<?= BASE_URL ?>assets/js/home.js"></script>
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
    </script>
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