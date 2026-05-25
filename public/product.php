<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$productId = (int)($_GET['id'] ?? 0);
if (!$productId) redirect(BASE_URL . 'index.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product — LocalLink</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/home.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/product.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/review.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/mobile.css">
</head>

<body class="home-page">

    <!-- TOP NAVBAR -->
    <header class="top-navbar">
        <div class="nav-left">
            <a href="index.php" class="brand-logo">LocalLink <span class="logo-icon">🛍️</span></a>
        </div>
        <div class="nav-center">
            <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" class="search-bar" placeholder="Search"
                    onkeydown="if(event.key==='Enter') window.location='index.php?search='+this.value">
            </div>
        </div>
        <div class="nav-right">
            <!-- <a href="<?= BASE_URL ?>cart.php" class="nav-icon-btn">🛒</a> -->
            <a href="<?= BASE_URL ?>messages.php" class="nav-icon-btn">💬</a>
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
                    <div class="avatar-circle"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
                    <div class="avatar-dropdown" id="avatarDropdown">
                        <a href="dashboard.php">👤 Profile</a>
                        <!-- <a href="orders.php">📦 Orders</a> -->
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
                        <a href="logout.php" class="text-danger">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn-login-nav">Login</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- SECONDARY NAV -->
    <nav class="secondary-nav">
        <div class="sec-nav-left">
            <a href="index.php" class="sec-nav-link">← Back</a>
            <button class="hamburger-mobile" id="mobileMenuBtn" aria-label="Open menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        <div class="sec-nav-right">
            <?php if (isLoggedIn()): ?>
                <a href="seller/add-product.php" class="btn-list-item">+ List a New Item</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- LOADING STATE -->
    <div id="pageLoader" class="page-loader">
        <div class="loader-spinner"></div>
    </div>

    <!-- PRODUCT DETAIL CONTENT -->
    <div class="product-detail-wrap d-none" id="productDetail">
        <div class="pd-container">

            <!-- LEFT — images -->
            <div class="pd-images">
                <div class="pd-main-img-wrap">
                    <img id="mainImage" src="" alt="Product image">
                </div>
                <!-- Thumbnail strip -->
                <div class="pd-thumbnails" id="thumbnailStrip">
                    <!-- Populated by product.js -->
                </div>
            </div>

            <!-- RIGHT — details -->
            <div class="pd-info">

                <!-- Title -->
                <h1 class="pd-title" id="pdTitle">Loading...</h1>

                <!-- Price -->
                <div class="pd-price" id="pdPrice">ZAR —</div>

                <!-- Badges row -->
                <div class="pd-badges">
                    <span class="badge-negotiable">Negociable</span>
                </div>

                <!-- Location -->
                <div class="pd-location">
                    <span>📍</span>
                    <span id="pdLocation">—</span>
                </div>

                <!-- Description -->
                <div class="pd-section-label">Description</div>
                <div class="pd-description" id="pdDescription">—</div>

                <!-- Action buttons -->
                <div class="pd-actions">
                    <button class="btn-wishlist" id="btnWishlist">
                        🤍 Add to wishlist
                    </button>
                    <button class="btn-share" title="Share">⬆</button>
                    <button class="btn-more" title="More">•••</button>
                </div>

            </div><!-- /pd-info -->

            <!-- SELLER CARD -->
            <div class="seller-card">
                <div class="seller-card-avatar" id="sellerAvatar">?</div>
                <div class="seller-card-name" id="sellerName">—</div>
                <div class="seller-card-location" id="sellerLocation">—</div>
                <div class="seller-card-badge">Community Approved</div>

                <!-- Star rating -->
                <div class="star-row" id="starRow">
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star empty">★</span>
                </div>
                <div class="review-count" id="reviewCount"></div>

                <!-- CTA buttons -->
                <a href="#" class="btn-send-message" id="btnMessage">Send Message</a>
                <button class="btn-make-offer" id="btnOffer">Make Offer</button>
            </div>

        </div><!-- /pd-container -->

        <!-- REVIEWS SECTION -->
        <div class="pd-reviews-wrap">

            <!-- Header row -->
            <div class="rv-header">
                <div class="rv-header-left">
                    <h2 class="pd-section-title">Seller Reviews</h2>
                    <!-- Star summary -->
                    <div class="rv-summary" id="rvSummary">
                        <span class="rv-avg-score" id="rvAvgScore">—</span>
                        <div class="rv-avg-stars" id="rvAvgStars"></div>
                        <span class="rv-count" id="rvCount"></span>
                    </div>
                </div>

                <!-- Leave a review button — shown to logged-in users who haven't reviewed yet -->
                <button class="rv-btn-write" id="openReviewModal">
                    ✍️ Write a Review
                </button>
            </div>

            <!-- Rating breakdown bars -->
            <div class="rv-breakdown" id="rvBreakdown"></div>

            <!-- Reviews list -->
            <div id="reviewsList" class="reviews-list">
                <p class="text-muted" style="font-size:.9rem">Loading reviews...</p>
            </div>

        </div>

        <div class="modal-overlay d-none" id="reviewModal">
            <div class="modal-box rv-modal">

                <div class="modal-header">
                    <h2>Write a Review</h2>
                    <button class="rv-modal-close" id="closeReviewModal">&times;</button>
                </div>

                <p class="rv-modal-product-name" id="reviewProductName"></p>

                <div id="reviewAlert" class="alert d-none mb-3"></div>

                <form id="reviewForm">

                    <!-- Interactive star picker -->
                    <div class="rv-star-picker-label">Your Rating <span class="text-danger">*</span></div>
                    <div class="rv-star-picker" id="starPicker">
                        <span class="rv-star-pick" data-value="1">★</span>
                        <span class="rv-star-pick" data-value="2">★</span>
                        <span class="rv-star-pick" data-value="3">★</span>
                        <span class="rv-star-pick" data-value="4">★</span>
                        <span class="rv-star-pick" data-value="5">★</span>
                    </div>
                    <div class="rv-rating-label" id="ratingLabel">Click to rate</div>
                    <input type="hidden" name="rating" id="ratingInput" value="0">

                    <!-- Comment -->
                    <div class="rv-field">
                        <label class="rv-label">
                            Your Review <span class="text-muted" style="font-weight:400">(optional)</span>
                        </label>
                        <textarea name="comment" id="reviewComment"
                            class="rv-textarea"
                            placeholder="Share your experience with this product or seller..."
                            rows="4" maxlength="500"></textarea>
                        <div class="rv-char-count"><span id="charCount">0</span> / 500</div>
                    </div>

                    <div class="rv-modal-footer">
                        <button type="button" class="rv-btn-cancel" id="closeReviewModal2">Cancel</button>
                        <button type="submit" class="rv-btn-submit" id="submitReviewBtn">
                            Submit Review
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div><!-- /product-detail-wrap -->

    <!-- NOT FOUND STATE -->
    <div id="notFound" class="not-found d-none">
        <div>
            <div style="font-size:3rem">😕</div>
            <h2>Product not found</h2>
            <p>This listing may have been removed.</p>
            <a href="index.php" class="btn-list-item" style="display:inline-block;margin-top:12px">← Back to listings</a>
        </div>
    </div>

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
        const PRODUCT_ID = <?= $productId ?>;
        const BASE_URL = '<?= BASE_URL ?>';
        const IS_LOGGED = <?= isLoggedIn() ? 'true' : 'false' ?>;
        const USER_ROLE = '<?= getRole() ?>';
        const USER_ID = <?= $_SESSION['user_id'] ?? 0 ?>;
        // SELLER_ID and IS_OWN_LISTING will be set by JS after product loads
    </script>

    <script>
        // Mobile nav data (read by mobile-nav.js)
        window.MOB_LOGGED = <?= isLoggedIn() ? 'true' : 'false' ?>;
        window.MOB_NAME = '<?= sanitize($_SESSION["name"] ?? "Guest") ?>';
        window.MOB_ROLE = '<?= sanitize($_SESSION["role"] ?? "") ?>';
        window.MOB_ADMIN = <?= isAdmin() ? 'true' : 'false' ?>;
        window.MOB_AVATAR = '<?= !empty($_SESSION["avatar"]) ? BASE_URL . "uploads/avatars/" . $_SESSION["avatar"] : "" ?>';
    </script>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/product.js"></script>
    <script src="<?= BASE_URL ?>assets/js/reviews.js"></script>
    <script src="<?= BASE_URL ?>assets/js/mobile-nav.js"></script>

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