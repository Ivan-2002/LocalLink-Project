<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/categories.php';

requireLogin();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>List a New Item — LocalLink</title>
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
            <a href="<?= BASE_URL ?>cart.php" class="nav-icon-btn">🛒</a>
            <a href="<?= BASE_URL ?>messages.php" class="nav-icon-btn">💬</a>
            <a href="#" class="nav-icon-btn">🔔</a>
            <div class="avatar-wrap" id="avatarToggle">
                <div class="avatar-circle"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
                <div class="avatar-dropdown" id="avatarDropdown">
                    <a href="<?= BASE_URL ?>profile.php">👤 Profile</a>
                    <a href="<?= BASE_URL ?>orders.php">📦 Orders</a>
                    <a href="<?= BASE_URL ?>seller/dashboard.php">🏪 My Shop</a>
                    <hr>
                    <a href="<?= BASE_URL ?>logout.php" class="text-danger">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- ── PAGE HEADER ────────────────────────────────────────── -->
    <div class="ap-page-header">
        <a href="<?= BASE_URL ?>index.php" class="ap-close-btn" title="Cancel">✕</a>
        <h1 class="ap-page-title">List a New Item</h1>
        <div></div><!-- spacer for flex centering -->
    </div>

    <!-- ── ALERT ──────────────────────────────────────────────── -->
    <div id="pageAlert" class="alert d-none ap-alert" role="alert"></div>

    <!-- ── MAIN FORM ──────────────────────────────────────────── -->
    <form id="addProductForm" enctype="multipart/form-data">

        <div class="ap-container">

            <!-- ══ LEFT COLUMN ══════════════════════════════════════ -->
            <div class="ap-left">

                <!-- Step 1: Photos -->
                <div class="ap-step-label">Step 1: Item Photos</div>

                <div class="ap-photo-area" id="photoArea">
                    <input type="file" name="image" id="imageInput"
                        accept="image/jpeg,image/png,image/webp" style="display:none">

                    <!-- Preview (hidden until image selected) -->
                    <img id="imagePreview" src="" alt="Preview" class="ap-image-preview d-none">

                    <!-- Upload prompt (shown by default) -->
                    <div class="ap-upload-prompt" id="uploadPrompt">
                        <div class="ap-camera-icon">📷</div>
                        <div class="ap-upload-title">Add a photo</div>
                        <div class="ap-upload-sub">(from Camera or Gallery)</div>
                        <div class="ap-upload-hint">Click to open a file selector</div>
                    </div>

                    <!-- Remove button -->
                    <button type="button" class="ap-remove-img d-none" id="removeImage">✕ Remove</button>
                </div>

                <p class="ap-photo-tip">Clear photos from front and back works best</p>

            </div><!-- /ap-left -->

            <!-- ══ RIGHT COLUMN ═════════════════════════════════════ -->
            <div class="ap-right">

                <!-- Step 2: Item Details -->
                <div class="ap-step-label">Step 2: Item Details</div>

                <div class="ap-field">
                    <label class="ap-label">Item Name</label>
                    <input type="text" name="title" class="ap-input"
                        placeholder="e.g. iPhone 16 Pro" required maxlength="200">
                </div>

                <div class="ap-row">
                    <div class="ap-field" style="flex:1">
                        <label class="ap-label">Price (ZAR)</label>
                        <input type="number" name="price" class="ap-input"
                            placeholder="11999" min="0" step="0.01" required>
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
                        <?= categoryOptions($pdo) ?>
                    </select>
                </div>

                <!-- Step 3: Description & Location -->
                <div class="ap-step-label" style="margin-top:18px">
                    Step 3: Description &amp; Location
                </div>

                <div class="ap-field">
                    <label class="ap-label">Describe a Item</label>
                    <textarea name="description" class="ap-input ap-textarea"
                        placeholder="Mention key features, condition, or flaws."
                        rows="4" maxlength="1000"></textarea>
                </div>

                <div class="ap-field">
                    <label class="ap-label">Location</label>
                    <input type="text" name="location" class="ap-input"
                        placeholder="Cape Town (example)"
                        value="<?= sanitize($_SESSION['location'] ?? '') ?>">
                </div>

                <!-- Step 4: Enhanced Trading Options -->
                <div class="ap-step-label" style="margin-top:18px">
                    Step 4: Enhanced Trading Options
                </div>

                <div class="ap-field">
                    <label class="ap-label">Suggested Safe Meet-up Spot?</label>
                    <input type="text" name="meetup_spot" class="ap-input"
                        placeholder="Suggest like a 'Public Library' or local 'Shop'">
                </div>

                <!-- Buttons -->
                <div class="ap-btn-row">
                    <button type="button" class="ap-btn-draft" id="saveDraft">Save Draft</button>
                    <button type="submit" class="ap-btn-submit">List Item</button>
                </div>

            </div><!-- /ap-right -->

        </div><!-- /ap-container -->
    </form>

    <!-- ── FOOTER ─────────────────────────────────────────────── -->
    <footer class="home-footer">
        <span>© 2026 LocalLink — Community Market | Cape Town</span>
        <div class="footer-links">
            <a href="#">Help</a>
            <a href="#">Contact</a>
            <a href="#">About our Community</a>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        const BASE_URL = '<?= BASE_URL ?>';

        $(function() {

            // ── Image upload & preview ─────────────────────────────────
            // Clicking the dashed area opens the file picker
            $('#photoArea').on('click', function(e) {
                if (!$(e.target).is('#removeImage')) {
                    $('#imageInput').trigger('click');
                }
            });

            $('#imageInput').on('change', function() {
                const file = this.files[0];
                if (!file) return;

                // Validate size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showAlert('Image must be under 2MB.', 'danger');
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
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
                $('#removeImage').addClass('d-none');
            });

            // ── Submit — List Item ─────────────────────────────────────
            $('#addProductForm').on('submit', function(e) {
                e.preventDefault();
                submitForm('active');
            });

            // ── Save Draft ─────────────────────────────────────────────
            $('#saveDraft').on('click', function() {
                submitForm('pending');
            });

            function submitForm(status) {
                const formData = new FormData($('#addProductForm')[0]);
                formData.append('status', status);

                const submitBtn = status === 'active' ?
                    $('#addProductForm [type=submit]') :
                    $('#saveDraft');
                const origText = submitBtn.text();
                submitBtn.prop('disabled', true).text('Saving...');

                $.ajax({
                        url: BASE_URL + '../api/products/add-product.php',
                        type: 'POST',
                        data: formData,
                        processData: false, // don't let jQuery transform FormData
                        contentType: false, // let browser set multipart boundary
                    })
                    .done(res => {
                        if (res.success) {
                            showAlert(
                                status === 'active' ?
                                '✅ Item listed successfully! Redirecting...' :
                                '📝 Draft saved!',
                                'success'
                            );
                            if (status === 'active') {
                                setTimeout(() => window.location.href = BASE_URL + 'seller/dashboard.php', 1200);
                            }
                        } else {
                            showAlert(res.error || 'Something went wrong.', 'danger');
                        }
                    })
                    .fail(xhr => showAlert(xhr.responseJSON?.error || 'Failed to submit. Try again.', 'danger'))
                    .always(() => submitBtn.prop('disabled', false).text(origText));
            }

            // ── Alert ──────────────────────────────────────────────────
            function showAlert(msg, type) {
                $('#pageAlert')
                    .removeClass('d-none alert-success alert-danger')
                    .addClass('alert alert-' + type)
                    .text(msg);
                $('html, body').animate({
                    scrollTop: 0
                }, 300);
                if (type === 'success') {
                    setTimeout(() => $('#pageAlert').addClass('d-none'), 4000);
                }
            }

            // ── Avatar dropdown ────────────────────────────────────────
            $('#avatarToggle').on('click', function(e) {
                e.stopPropagation();
                $('#avatarDropdown').toggleClass('open');
            });
            $(document).on('click', () => $('#avatarDropdown').removeClass('open'));

        });
    </script>
</body>

</html>