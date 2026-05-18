<?php
// admin/categories.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Category Management — TownMarket Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>

<body>

    <div class="admin-wrapper">

        <!-- ── SIDEBAR ─────────────────────────────── -->
        <aside class="sidebar">
            <div class="sidebar-brand">LocalLink <span>Admin</span></div>
            <nav>
                <a href="dashboard.php">🏠 DashBoard</a>
                <a href="users.php">👤 Users</a>
                <a href="products.php">📦 Products</a>
                <a href="categories.php" class="active">🗂️ Categories</a>
            </nav>
            <div class="sidebar-footer">
                <span>👤 <?= sanitize($_SESSION['name']) ?></span>
                <a href="<?= BASE_URL ?>logout.php" class="logout-link">→ Log out</a>
            </div>
        </aside>

        <!-- ── MAIN ────────────────────────────────── -->
        <main class="admin-main">

            <!-- Top bar -->
            <div class="admin-topbar">
                <h1 class="admin-page-title">Category Management</h1>
                <div class="topbar-right">
                    <button class="topbar-icon" title="Search">🔍</button>
                    <button class="topbar-icon" title="Notifications">🔔</button>
                    <div class="topbar-user">
                        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
                        <?= sanitize(explode(' ', $_SESSION['name'])[0]) ?>
                    </div>
                </div>
            </div>
            <div class="admin-page-subtitle"></div>

            <!-- Content -->
            <div class="admin-content">

                <!-- Page alert -->
                <div id="pageAlert" class="alert d-none mb-3" role="alert"></div>

                <div class="admin-card">

                    <!-- Search + Add row -->
                    <div class="d-flex align-items-center justify-content-between mb-3 gap-3 flex-wrap">
                        <div class="admin-search-wrap mb-0" style="flex:1; max-width:420px">
                            <span class="admin-search-icon">🔍</span>
                            <input type="text" id="catSearch" class="admin-search" placeholder="Search Category">
                        </div>
                        <button class="btn-admin-primary" id="openAddModal">+ Add</button>
                    </div>

                    <!-- Accordion list -->
                    <div class="cat-accordion" id="catAccordion">
                        <div class="text-center py-4 text-muted">Loading...</div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- ── ADD Modal ──────────────────────────────── -->
    <div class="modal-overlay d-none" id="addModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Add Category</h2>
                <button class="modal-close" data-close="addModal">&times;</button>
            </div>
            <form id="addForm">
                <div class="mb-3">
                    <label class="form-label">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Electronics & Gadgets" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Parent Category <span class="text-muted small">(leave empty for top-level)</span></label>
                    <select name="parent_id" class="form-select" id="addParentSelect">
                        <option value="">None — top-level category</option>
                    </select>
                </div>
                <div id="addAlert" class="alert d-none mb-3"></div>
                <div class="modal-footer-btns">
                    <button type="button" class="btn-admin-ghost" data-close="addModal">Cancel</button>
                    <button type="submit" class="btn-admin-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── EDIT Modal ─────────────────────────────── -->
    <div class="modal-overlay d-none" id="editModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Edit Category</h2>
                <button class="modal-close" data-close="editModal">&times;</button>
            </div>
            <form id="editForm">
                <input type="hidden" name="id" id="editId">
                <div class="mb-3">
                    <label class="form-label">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="editName" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Parent Category</label>
                    <select name="parent_id" class="form-select" id="editParentSelect">
                        <option value="">None — top-level category</option>
                    </select>
                </div>
                <div id="editAlert" class="alert d-none mb-3"></div>
                <div class="modal-footer-btns">
                    <button type="button" class="btn-admin-ghost" data-close="editModal">Cancel</button>
                    <button type="submit" class="btn-admin-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── DELETE Confirm Modal ───────────────────── -->
    <div class="modal-overlay d-none" id="deleteModal">
        <div class="modal-box modal-sm">
            <div class="modal-header">
                <h2>Delete Category</h2>
                <button class="modal-close" data-close="deleteModal">&times;</button>
            </div>
            <p class="mb-4 text-muted">Are you sure you want to delete <strong id="deleteNameLabel" class="text-dark"></strong>? This cannot be undone.</p>
            <div id="deleteAlert" class="alert d-none mb-3"></div>
            <div class="modal-footer-btns">
                <button type="button" class="btn-admin-ghost" data-close="deleteModal">Cancel</button>
                <button type="button" class="btn-danger-solid" id="confirmDeleteBtn">Yes, Delete</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        const API = '<?= BASE_URL ?>../api/categories/';
        let allCategories = [];
        let deleteTargetId = null;

        // ── Load & render ───────────────────────────────────────────
        function loadCategories() {
            $.get(API + 'get-categories.php')
                .done(res => {
                    allCategories = res.categories;
                    renderAccordion(res.categories);
                    populateParentDropdowns(res.categories);
                })
                .fail(() => showPageAlert('Failed to load categories.', 'danger'));
        }

        function renderAccordion(cats, searchTerm = '') {
            const accordion = $('#catAccordion');
            const parents = cats.filter(c => !c.parent_id);
            const children = cats.filter(c => c.parent_id);

            // Filter by search
            const term = searchTerm.toLowerCase();
            const filteredParents = term ?
                parents.filter(p =>
                    p.name.toLowerCase().includes(term) ||
                    children.some(c => c.parent_id == p.id && c.name.toLowerCase().includes(term))
                ) :
                parents;

            if (!filteredParents.length) {
                accordion.html('<div class="text-center py-5 text-muted">No categories found.</div>');
                return;
            }

            let html = '';
            filteredParents.forEach(parent => {
                const kids = children.filter(c => c.parent_id == parent.id);
                const isOpen = term ? 'open' : ''; // auto-open when searching

                html += `
      <div class="cat-parent-row ${isOpen}" data-id="${parent.id}">
        <div class="cat-parent-header">
          <div class="cat-parent-left">
            <span class="cat-chevron">›</span>
            <span>${parent.name}</span>
          </div>
          <div class="cat-parent-actions">
            <button class="btn-icon-edit"   onclick="openEdit(${parent.id})"                    title="Edit">✏️</button>
            <button class="btn-icon-delete" onclick="openDelete(${parent.id}, '${parent.name}')" title="Delete">🗑️</button>
          </div>
        </div>
        <div class="cat-children">
          ${kids.length ? kids.map(child => `
            <div class="cat-child-row">
              <span class="cat-child-name">${child.name}</span>
              <div class="cat-child-actions">
                <button class="btn-icon-edit"   onclick="openEdit(${child.id})"                    title="Edit">✏️</button>
                <button class="btn-icon-delete" onclick="openDelete(${child.id}, '${child.name}')" title="Delete">🗑️</button>
              </div>
            </div>
          `).join('') : '<div class="cat-no-children">No subcategories</div>'}
        </div>
      </div>`;
            });

            accordion.html(html);
        }

        // ── Accordion toggle ────────────────────────────────────────
        $(document).on('click', '.cat-parent-header', function() {
            $(this).closest('.cat-parent-row').toggleClass('open');
        });

        // ── Live search ─────────────────────────────────────────────
        $('#catSearch').on('input', function() {
            renderAccordion(allCategories, $(this).val());
        });

        // ── Parent dropdowns ────────────────────────────────────────
        function populateParentDropdowns(cats) {
            const topLevel = cats.filter(c => !c.parent_id);
            let opts = '<option value="">None — top-level category</option>';
            topLevel.forEach(c => {
                opts += `<option value="${c.id}">${c.name}</option>`;
            });
            $('#addParentSelect, #editParentSelect').html(opts);
        }

        // ── Open modals ─────────────────────────────────────────────
        $('#openAddModal').on('click', () => {
            $('#addForm')[0].reset();
            hideAlert('addAlert');
            openModal('addModal');
        });

        function openEdit(id) {
            const cat = allCategories.find(c => c.id == id);
            if (!cat) return;
            $('#editId').val(cat.id);
            $('#editName').val(cat.name);
            populateParentDropdowns(allCategories);
            $('#editParentSelect').val(cat.parent_id || '');
            hideAlert('editAlert');
            openModal('editModal');
        }

        function openDelete(id, name) {
            deleteTargetId = id;
            $('#deleteNameLabel').text(name);
            hideAlert('deleteAlert');
            openModal('deleteModal');
        }

        // ── Modal helpers ───────────────────────────────────────────
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

        // ── Add ─────────────────────────────────────────────────────
        $('#addForm').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('[type=submit]');
            btn.prop('disabled', true).text('Adding...');
            $.post(API + 'add-category.php', $(this).serialize())
                .done(res => {
                    if (res.success) {
                        closeModal('addModal');
                        showPageAlert('Category added!', 'success');
                        loadCategories();
                    } else showAlert('addAlert', res.error, 'danger');
                })
                .fail(xhr => showAlert('addAlert', xhr.responseJSON?.error || 'Failed.', 'danger'))
                .always(() => btn.prop('disabled', false).text('Add Category'));
        });

        // ── Edit ────────────────────────────────────────────────────
        $('#editForm').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('[type=submit]');
            btn.prop('disabled', true).text('Saving...');
            $.post(API + 'edit-category.php', $(this).serialize())
                .done(res => {
                    if (res.success) {
                        closeModal('editModal');
                        showPageAlert('Category updated!', 'success');
                        loadCategories();
                    } else showAlert('editAlert', res.error, 'danger');
                })
                .fail(xhr => showAlert('editAlert', xhr.responseJSON?.error || 'Failed.', 'danger'))
                .always(() => btn.prop('disabled', false).text('Save Changes'));
        });

        // ── Delete ──────────────────────────────────────────────────
        $('#confirmDeleteBtn').on('click', function() {
            if (!deleteTargetId) return;
            const btn = $(this);
            btn.prop('disabled', true).text('Deleting...');
            $.post(API + 'delete-category.php', {
                    id: deleteTargetId
                })
                .done(res => {
                    if (res.success) {
                        closeModal('deleteModal');
                        showPageAlert('Category deleted.', 'success');
                        loadCategories();
                    } else showAlert('deleteAlert', res.error, 'danger');
                })
                .fail(xhr => showAlert('deleteAlert', xhr.responseJSON?.error || 'Failed.', 'danger'))
                .always(() => btn.prop('disabled', false).text('Yes, Delete'));
        });

        // ── Alert helpers ────────────────────────────────────────────
        function showPageAlert(msg, type) {
            $('#pageAlert').removeClass('d-none alert-success alert-danger')
                .addClass('alert alert-' + type).text(msg);
            setTimeout(() => $('#pageAlert').addClass('d-none'), 4000);
        }

        function showAlert(id, msg, type) {
            $('#' + id).removeClass('d-none alert-danger alert-success')
                .addClass('alert alert-' + type).text(msg);
        }

        function hideAlert(id) {
            $('#' + id).addClass('d-none').text('');
        }

        // ── Init ─────────────────────────────────────────────────────
        loadCategories();
    </script>
</body>

</html>