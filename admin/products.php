<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

requireAdmin();

// Fetch categories for filter dropdown
$cats = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Management — LocalLink Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/users.css">
    <link rel="stylesheet" href="assets/css/products.css">
    <link rel="stylesheet" href="assets/css/admin-mobile.css">
</head>

<body>
    <div class="admin-wrapper">

        <!-- ── SIDEBAR ─────────────────────────────── -->
        <aside class="sidebar">
            <div class="sidebar-brand">LocalLink <span>🛍️</span></div>
            <nav>
                <a href="dashboard.php">🏠 DashBoard</a>
                <a href="users.php">👤 Users</a>
                <a href="products.php" class="active">📦 Products</a>
                <a href="categories.php">🗂️ Categories</a>
                <a href="<?= BASE_URL ?>index.php">LocalLink</a>
            </nav>
            <div class="sidebar-footer">
                <a href="<?= BASE_URL ?>logout.php" class="logout-link">→ Log out</a>
            </div>
        </aside>

        <!-- ── MAIN ────────────────────────────────── -->
        <main class="admin-main">

            <div class="admin-topbar">
                <h1 class="admin-page-title">Product Management</h1>
                <div class="topbar-right">
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

                    <!-- ── Toolbar ──────────────────────── -->
                    <div class="um-toolbar">
                        <div class="admin-search-wrap">
                            <span class="admin-search-icon">🔍</span>
                            <input type="text" id="productSearch"
                                class="admin-search" placeholder="Search products or seller">
                        </div>
                        <div class="um-toolbar-right">
                            <div class="um-filter-wrap">
                                <button class="um-btn-filter" id="filterToggle">▼ Filter</button>
                                <div class="um-filter-dropdown d-none" id="filterDropdown">
                                    <div class="um-filter-group">
                                        <label>Status</label>
                                        <select id="filterStatus" class="um-filter-select">
                                            <option value="">All</option>
                                            <option value="active">Active</option>
                                            <option value="pending">Pending</option>
                                            <option value="removed">Removed</option>
                                        </select>
                                    </div>
                                    <div class="um-filter-group">
                                        <label>Category</label>
                                        <select id="filterCategory" class="um-filter-select">
                                            <option value="">All categories</option>
                                            <?php foreach ($cats as $c): ?>
                                                <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button class="um-btn-apply" id="applyFilter">Apply</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Products Table ───────────────── -->
                    <div class="table-responsive">
                        <table class="um-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>Product ↕</th>
                                    <th>Seller ↕</th>
                                    <th>Category ↕</th>
                                    <th>Price ↕</th>
                                    <th>Status ↕</th>
                                    <th>Listed ↕</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <tr>
                                    <td colspan="8" class="um-loading">Loading products...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="um-row-count" id="rowCount"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- ══════════════════════════════════════════════
     ROW ACTION DROPDOWN
══════════════════════════════════════════════ -->
    <div class="um-action-menu d-none" id="actionMenu">
        <a href="#" class="um-action-item" id="actionView" target="_blank">
            👁️ View Listing
        </a>
        <button class="um-action-item" id="actionApprove">✅ Approve</button>
        <button class="um-action-item" id="actionRestore">♻️ Restore</button>
        <button class="um-action-item um-action-suspend" id="actionRemove">🚫 Remove Listing</button>
        <hr class="um-action-divider">
        <button class="um-action-item" style="color:#e03131" id="actionDelete">
            🗑️ Delete Permanently
        </button>
    </div>

    <!-- ══════════════════════════════════════════════
     PRODUCT DETAIL MODAL
══════════════════════════════════════════════ -->
    <div class="modal-overlay d-none" id="productModal">
        <div class="modal-box pm-product-modal">
            <div class="modal-header">
                <h2>Product Detail</h2>
                <button class="modal-close" data-close="productModal">&times;</button>
            </div>

            <div class="pm-modal-body">
                <!-- Image -->
                <div class="pm-modal-img-wrap" id="modalImgWrap">
                    <div class="pm-modal-img-placeholder">🛍️</div>
                </div>

                <!-- Info -->
                <div class="pm-modal-info">
                    <h3 class="pm-modal-title" id="modalTitle">—</h3>
                    <div class="pm-modal-price" id="modalPrice">—</div>

                    <div class="pm-modal-row">
                        <span class="pm-modal-label">Status</span>
                        <span id="modalStatus">—</span>
                    </div>
                    <div class="pm-modal-row">
                        <span class="pm-modal-label">Category</span>
                        <span id="modalCategory">—</span>
                    </div>
                    <div class="pm-modal-row">
                        <span class="pm-modal-label">Seller</span>
                        <span id="modalSeller">—</span>
                    </div>
                    <div class="pm-modal-row">
                        <span class="pm-modal-label">Location</span>
                        <span id="modalLocation">—</span>
                    </div>
                    <div class="pm-modal-row">
                        <span class="pm-modal-label">Listed</span>
                        <span id="modalDate">—</span>
                    </div>

                    <!-- Modal actions -->
                    <div class="pm-modal-actions">
                        <a href="#" class="pm-btn-view" id="modalViewBtn" target="_blank">
                            👁️ View Live
                        </a>
                        <button class="pm-btn-approve d-none" id="modalApproveBtn">✅ Approve</button>
                        <button class="pm-btn-remove  d-none" id="modalRemoveBtn">🚫 Remove</button>
                        <button class="pm-btn-restore d-none" id="modalRestoreBtn">♻️ Restore</button>
                        <button class="pm-btn-delete" id="modalDeleteBtn">🗑️ Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        const ADMIN_API = BASE_URL + '../api/admin/';

        let allProducts = [];
        let activeProductId = 0;
        let activeProduct = null;
        let currentStatus = '';

        // ══════════════════════════════════════════════
        // LOAD PRODUCTS
        // ══════════════════════════════════════════════
        function loadProducts(params = {}) {
            $('#productsTableBody').html('<tr><td colspan="8" class="um-loading">Loading...</td></tr>');
            $.get(ADMIN_API + 'get-products.php', params)
                .done(res => {
                    allProducts = res.products;
                    renderTable(res.products);
                })
                .fail(() => showPageAlert('Failed to load products.', 'danger'));
        }

        function renderTable(products) {
            $('#rowCount').text(products.length + ' product' + (products.length !== 1 ? 's' : ''));

            if (!products.length) {
                $('#productsTableBody').html('<tr><td colspan="8" class="um-loading">No products found.</td></tr>');
                return;
            }

            const html = products.map(p => {
                const statusBadge = {
                    active: '<span class="um-status-active">Active</span>',
                    pending: '<span class="pm-status-pending">Pending</span>',
                    removed: '<span class="um-status-suspended">Removed</span>',
                } [p.status] || p.status;

                const imgHtml = p.image_url ?
                    `<img src="${p.image_url}" alt="${esc(p.title)}" class="pm-row-img">` :
                    `<div class="pm-row-img-placeholder">🛍️</div>`;

                return `
      <tr data-id="${p.id}">
        <td><input type="checkbox" class="um-row-check"></td>
        <td>
          <div class="pm-product-cell">
            ${imgHtml}
            <span class="pm-product-title">${esc(p.title)}</span>
          </div>
        </td>
        <td class="um-email">${esc(p.seller_name)}</td>
        <td class="um-email">${esc(p.category_name || '—')}</td>
        <td style="font-weight:700;color:#1a8a6f">${p.price_formatted}</td>
        <td>${statusBadge}</td>
        <td class="um-email">${p.date_listed}</td>
        <td>
          <button class="um-row-action-btn" data-id="${p.id}">•••</button>
        </td>
      </tr>`;
            }).join('');

            $('#productsTableBody').html(html);
        }

        // ══════════════════════════════════════════════
        // ROW ACTION DROPDOWN
        // ══════════════════════════════════════════════
        $(document).on('click', '.um-row-action-btn', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            const product = allProducts.find(p => p.id == id);
            if (!product) return;

            activeProductId = id;
            activeProduct = product;

            // Show/hide actions based on status
            const s = product.status;
            $('#actionApprove').toggleClass('d-none', s !== 'pending');
            $('#actionRestore').toggleClass('d-none', s !== 'removed');
            $('#actionRemove').toggleClass('d-none', s === 'removed');
            $('#actionView').attr('href', BASE_URL + 'product.php?id=' + id);

            const btn = $(this);
            const offset = btn.offset();
            const menu = $('#actionMenu');
            menu.css({
                top: offset.top + btn.outerHeight() + 4,
                left: offset.left - menu.outerWidth() + btn.outerWidth(),
            }).removeClass('d-none');
        });

        $(document).on('click', function() {
            $('#actionMenu').addClass('d-none');
        });
        $('#actionMenu').on('click', e => e.stopPropagation());

        // Open product modal on row click (not action btn)
        $(document).on('click', 'tbody tr', function(e) {
            if ($(e.target).is('input, button') || $(e.target).closest('button').length) return;
            const id = $(this).data('id');
            if (id) openProductModal(id);
        });

        $('#actionApprove').on('click', () => {
            closeMenu();
            updateProduct(activeProductId, 'approve');
        });
        $('#actionRemove').on('click', () => {
            closeMenu();
            confirmThenUpdate(activeProductId, 'remove', `Remove "${activeProduct.title}"?`);
        });
        $('#actionRestore').on('click', () => {
            closeMenu();
            updateProduct(activeProductId, 'restore');
        });
        $('#actionDelete').on('click', () => {
            closeMenu();
            confirmThenUpdate(activeProductId, 'delete', `Permanently delete "${activeProduct.title}"? This cannot be undone.`);
        });

        function closeMenu() {
            $('#actionMenu').addClass('d-none');
        }

        function confirmThenUpdate(id, action, msg) {
            if (confirm(msg)) updateProduct(id, action);
        }

        // ══════════════════════════════════════════════
        // PRODUCT DETAIL MODAL
        // ══════════════════════════════════════════════
        function openProductModal(id) {
            const p = allProducts.find(x => x.id == id);
            if (!p) return;
            activeProductId = id;
            activeProduct = p;

            // Image
            if (p.image_url) {
                $('#modalImgWrap').html(`<img src="${p.image_url}" alt="${esc(p.title)}">`);
            } else {
                $('#modalImgWrap').html('<div class="pm-modal-img-placeholder">🛍️</div>');
            }

            $('#modalTitle').text(p.title);
            $('#modalPrice').text(p.price_formatted);
            $('#modalCategory').text(p.category_name || '—');
            $('#modalSeller').text(p.seller_name);
            $('#modalDate').text(p.date_listed);

            const statusMap = {
                active: '<span class="um-status-active">Active</span>',
                pending: '<span class="pm-status-pending">Pending</span>',
                removed: '<span class="um-status-suspended">Removed</span>',
            };
            $('#modalStatus').html(statusMap[p.status] || p.status);

            // View link
            $('#modalViewBtn').attr('href', BASE_URL + 'product.php?id=' + p.id);

            // Action buttons based on status
            $('#modalApproveBtn').toggleClass('d-none', p.status !== 'pending');
            $('#modalRemoveBtn').toggleClass('d-none', p.status === 'removed');
            $('#modalRestoreBtn').toggleClass('d-none', p.status !== 'removed');

            // Wire up buttons
            $('#modalApproveBtn').off('click').on('click', () => {
                closeModal('productModal');
                updateProduct(p.id, 'approve');
            });
            $('#modalRemoveBtn').off('click').on('click', () => {
                closeModal('productModal');
                confirmThenUpdate(p.id, 'remove', `Remove "${p.title}"?`);
            });
            $('#modalRestoreBtn').off('click').on('click', () => {
                closeModal('productModal');
                updateProduct(p.id, 'restore');
            });
            $('#modalDeleteBtn').off('click').on('click', () => {
                closeModal('productModal');
                confirmThenUpdate(p.id, 'delete', `Permanently delete "${p.title}"? Cannot be undone.`);
            });

            openModal('productModal');
        }

        // ══════════════════════════════════════════════
        // UPDATE PRODUCT
        // ══════════════════════════════════════════════
        function updateProduct(id, action) {
            $.post(ADMIN_API + 'update-product.php', {
                    product_id: id,
                    action
                })
                .done(res => {
                    if (res.success) {
                        showPageAlert(res.message, 'success');
                        loadProducts(currentFilters());
                    } else {
                        showPageAlert(res.error, 'danger');
                    }
                })
                .fail(xhr => showPageAlert(xhr.responseJSON?.error || 'Action failed.', 'danger'));
        }

        // ══════════════════════════════════════════════
        // SEARCH + FILTERS
        // ══════════════════════════════════════════════
        let searchTimer;
        $('#productSearch').on('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => loadProducts(currentFilters()), 400);
        });

        // Status tabs
        $(document).on('click', '.pm-status-tab', function() {
            $('.pm-status-tab').removeClass('active');
            $(this).addClass('active');
            currentStatus = $(this).data('status');
            $('#filterStatus').val(currentStatus);
            loadProducts(currentFilters());
        });

        $('#filterToggle').on('click', function(e) {
            e.stopPropagation();
            $('#filterDropdown').toggleClass('d-none');
        });
        $(document).on('click', function() {
            $('#filterDropdown').addClass('d-none');
        });
        $('#filterDropdown').on('click', e => e.stopPropagation());

        $('#applyFilter').on('click', () => {
            $('#filterDropdown').addClass('d-none');
            loadProducts(currentFilters());
        });

        function currentFilters() {
            return {
                search: $('#productSearch').val().trim(),
                status: $('#filterStatus').val(),
                category: $('#filterCategory').val(),
            };
        }

        $('#selectAll').on('change', function() {
            $('.um-row-check').prop('checked', $(this).is(':checked'));
        });

        // ══════════════════════════════════════════════
        // MODAL HELPERS
        // ══════════════════════════════════════════════
        function openModal(id) {
            $('#' + id).removeClass('d-none');
        }

        function closeModal(id) {
            $('#' + id).addClass('d-none');
        }

        $(document).on('click', '[data-close]', function() {
            closeModal($(this).data('close'));
        });
        $(document).on('click', '.modal-overlay', function(e) {
            if ($(e.target).hasClass('modal-overlay')) closeModal($(this).attr('id'));
        });

        function showPageAlert(msg, type) {
            $('#pageAlert').removeClass('d-none alert-success alert-danger')
                .addClass('alert alert-' + type).text(msg);
            setTimeout(() => $('#pageAlert').addClass('d-none'), 4000);
        }

        function esc(s) {
            return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // ── Init ──────────────────────────────────────
        loadProducts();
    </script>
</body>

</html>