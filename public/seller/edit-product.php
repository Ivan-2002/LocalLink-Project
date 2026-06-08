<?php
// Edit form page
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/categories.php';

requireLogin();

$productId = (int)($_GET['id'] ?? 0);
if (!$productId) redirect(BASE_URL . 'index.php');

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) redirect(BASE_URL . 'index.php');

// Ownership check — redirect if not owner and not admin
$isOwner = (int)$product['seller_id'] === (int)$_SESSION['user_id'];
if (!$isOwner && !isAdmin()) redirect(BASE_URL . 'index.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Listing — LocalLink</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/home.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/add-product.css">
</head>

<body class="home-page">

    <!-- ── TOP NAVBAR ─────────────────────────────────────────── -->
    <header class="top-navbar">
        <div class="nav-left">
            <a href="<?= BASE_URL ?>index.php" class="brand-logo">
                LocalLink <span class="logo-icon">🛍️</span>
            </a>
        </div>
        <div class="nav-right">
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
            <div class="avatar-wrap" id="avatarToggle">
                <div class="avatar-circle"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
                <div class="avatar-dropdown" id="avatarDropdown">
                    <a href="<?= BASE_URL ?>dashboard.php">👤 Profile</a>
                    <a href="<?= BASE_URL ?>orders.php">📦 Orders</a>
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

    <!-- ── PAGE HEADER ────────────────────────────────────────── -->
    <div class="ap-page-header">
        <a href="javascript:history.back()" class="ap-close-btn" title="Cancel">✕</a>
        <h1 class="ap-page-title">Edit Listing</h1>
        <div></div>
    </div>

    <!-- ── ALERT ──────────────────────────────────────────────── -->
    <div id="pageAlert" class="alert d-none ap-alert" role="alert"></div>

    <!-- ── FORM ───────────────────────────────────────────────── -->
    <form id="editProductForm" enctype="multipart/form-data">
        <input type="hidden" name="product_id" value="<?= $productId ?>">

        <div class="ap-container">

            <!-- LEFT — Photo -->
            <div class="ap-left">
                <div class="ap-step-label">Step 1: Item Photo</div>

                <div class="ap-photo-area" id="photoArea">
                    <input type="file" name="image" id="imageInput"
                        accept="image/jpeg,image/png,image/webp" style="display:none">

                    <!-- Show existing image if there is one -->
                    <?php if ($product['image']): ?>
                        <img id="imagePreview"
                            src="<?= BASE_URL ?>uploads/<?= sanitize($product['image']) ?>"
                            alt="Product image" class="ap-image-preview">
                        <button type="button" class="ap-remove-img" id="removeImage">✕ Remove</button>
                    <?php else: ?>
                        <img id="imagePreview" src="" alt="Preview" class="ap-image-preview d-none">
                        <div class="ap-upload-prompt" id="uploadPrompt">
                            <div class="ap-camera-icon">📷</div>
                            <div class="ap-upload-title">Add a photo</div>
                            <div class="ap-upload-sub">(from Camera or Gallery)</div>
                            <div class="ap-upload-hint">Click to open a file selector</div>
                        </div>
                        <button type="button" class="ap-remove-img d-none" id="removeImage">✕ Remove</button>
                    <?php endif; ?>
                </div>

                <p class="ap-photo-tip">Clear photos from front and back works best</p>

                <!-- Delete listing button — danger zone -->
                <div style="margin-top: 28px;">
                    <button type="button" class="ap-btn-delete" id="deleteBtn">
                        🗑️ Delete this listing
                    </button>
                </div>
            </div>

            <!-- RIGHT — Fields (pre-filled) -->
            <div class="ap-right">

                <div class="ap-step-label">Step 2: Item Details</div>

                <div class="ap-field">
                    <label class="ap-label">Item Name</label>
                    <input type="text" name="title" class="ap-input"
                        value="<?= sanitize($product['title']) ?>"
                        placeholder="e.g. iPhone 16 Pro" required maxlength="200">
                </div>

                <div class="ap-row">
                    <div class="ap-field" style="flex:1">
                        <label class="ap-label">Price (ZAR)</label>
                        <input type="number" name="price" class="ap-input"
                            value="<?= $product['price'] ?>"
                            placeholder="0.00" min="0" step="0.01" required>
                    </div>
                    <div class="ap-field ap-negotiable-wrap">
                        <label class="ap-label">Negotiable?</label>
                        <label class="ap-toggle">
                            <input type="checkbox" name="negotiable" id="negotiableToggle" value="1">
                            <span class="ap-toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="ap-field">
                    <label class="ap-label">Category</label>
                    <select name="category_id" class="ap-input ap-select" required>
                        <?= categoryOptions($pdo, (int)$product['category_id']) ?>
                    </select>
                </div>

                <div class="ap-step-label" style="margin-top:18px">
                    Step 3: Description &amp; Location
                </div>

                <div class="ap-field">
                    <label class="ap-label">Describe the Item</label>
                    <textarea name="description" class="ap-input ap-textarea"
                        placeholder="Mention key features, condition, or flaws."
                        rows="4" maxlength="1000"><?= sanitize($product['description'] ?? '') ?></textarea>
                </div>

                <div class="ap-field">
                    <label class="ap-label">Location</label>
                    <input type="text" name="location" class="ap-input"
                        value="<?= sanitize($product['location'] ?? '') ?>"
                        placeholder="Cape Town (example)">
                </div>

                <!-- Buttons -->
                <div class="ap-btn-row">
                    <a href="javascript:history.back()" class="ap-btn-draft">Cancel</a>
                    <button type="submit" class="ap-btn-submit">Save Changes</button>
                </div>

            </div>
        </div>
    </form>

    <!-- ── DELETE CONFIRM MODAL ───────────────────────────────── -->
    <div class="modal-overlay d-none" id="deleteModal">
        <div class="modal-box">
            <div class="modal-header-simple">
                <h2>Delete Listing</h2>
            </div>
            <p style="color:#6b5a48; margin-bottom:20px">
                Are you sure you want to delete
                <strong style="color:#2b2017"><?= sanitize($product['title']) ?></strong>?
                This cannot be undone.
            </p>
            <div id="deleteAlert" class="alert d-none mb-3"></div>
            <div class="ap-btn-row">
                <button type="button" class="ap-btn-draft" id="cancelDelete">Cancel</button>
                <button type="button" class="ap-btn-delete-confirm" id="confirmDelete">
                    Yes, Delete
                </button>
            </div>
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        const PRODUCT_ID = <?= $productId ?>;

        $(function() {

            // ── Image upload preview ───────────────────────────────────
            const hasExisting = <?= $product['image'] ? 'true' : 'false' ?>;

            $('#photoArea').on('click', function(e) {
                if (!$(e.target).is('#removeImage')) $('#imageInput').trigger('click');
            });

            $('#imageInput').on('change', function() {
                const file = this.files[0];
                if (!file) return;
                if (file.size > 2 * 1024 * 1024) {
                    alert('Image must be under 2MB.');
                    return;
                }
                const reader = new FileReader();
                reader.onload = e => {
                    $('#imagePreview').attr('src', e.target.result).removeClass('d-none');
                    $('#uploadPrompt').addClass('d-none');
                    $('#removeImage').removeClass('d-none');
                };
                reader.readAsDataURL(file);
            });

            $('#removeImage').on('click', function(e) {
                e.stopPropagation();
                $('#imageInput').val('');
                $('#imagePreview').attr('src', '').addClass('d-none');
                $('#uploadPrompt').removeClass('d-none');
                $(this).addClass('d-none');
            });

            // ── Edit form submit ───────────────────────────────────────
            $('#editProductForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const btn = $(this).find('[type=submit]');
                btn.prop('disabled', true).text('Saving...');

                $.ajax({
                        url: BASE_URL + '../api/products/edit-product.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(res => {
                        if (res.success) {
                            showAlert('✅ Changes saved! Redirecting...', 'success');
                            setTimeout(() => window.location.href = BASE_URL + 'product.php?id=' + PRODUCT_ID, 1200);
                        } else {
                            showAlert(res.error || 'Something went wrong.', 'danger');
                            btn.prop('disabled', false).text('Save Changes');
                        }
                    })
                    .fail(xhr => {
                        showAlert(xhr.responseJSON?.error || 'Failed. Try again.', 'danger');
                        btn.prop('disabled', false).text('Save Changes');
                    });
            });

            // ── Delete modal ───────────────────────────────────────────
            $('#deleteBtn').on('click', () => $('#deleteModal').removeClass('d-none'));
            $('#cancelDelete').on('click', () => $('#deleteModal').addClass('d-none'));

            $('#confirmDelete').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true).text('Deleting...');

                $.post(BASE_URL + '../api/products/delete-product.php', {
                        product_id: PRODUCT_ID
                    })
                    .done(res => {
                        if (res.success) {
                            showAlert('🗑️ Listing deleted. Redirecting...', 'success');
                            setTimeout(() => window.location.href = BASE_URL + 'index.php', 1200);
                        } else {
                            $('#deleteAlert').removeClass('d-none alert-success')
                                .addClass('alert alert-danger').text(res.error);
                            btn.prop('disabled', false).text('Yes, Delete');
                        }
                    })
                    .fail(() => {
                        $('#deleteAlert').removeClass('d-none').addClass('alert alert-danger')
                            .text('Failed. Try again.');
                        btn.prop('disabled', false).text('Yes, Delete');
                    });
            });

            // ── Avatar dropdown ────────────────────────────────────────
            $('#avatarToggle').on('click', function(e) {
                e.stopPropagation();
                $('#avatarDropdown').toggleClass('open');
            });
            $(document).on('click', () => $('#avatarDropdown').removeClass('open'));

            // ── Alert helper ───────────────────────────────────────────
            function showAlert(msg, type) {
                $('#pageAlert').removeClass('d-none alert-success alert-danger')
                    .addClass('alert alert-' + type).text(msg);
                $('html,body').animate({
                    scrollTop: 0
                }, 250);
            }

        });
    </script>

    <script>

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