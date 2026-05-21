<?php
// admin/users.php
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
    <title>User Management — TownMarket Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/users.css">
</head>

<body>
    <div class="admin-wrapper">

        <!-- ── SIDEBAR ─────────────────────────────── -->
        <aside class="sidebar">
            <div class="sidebar-brand">LocalLink <span>🛍️</span></div>
            <nav>
                <a href="dashboard.php">🏠 DashBoard</a>
                <a href="users.php" class="active">👤 Users</a>
                <a href="products.php">📦 Products</a>
                <a href="categories.php">🗂️ Categories</a>
            </nav>
            <div class="sidebar-footer">
                <a href="<?= BASE_URL ?>logout.php" class="logout-link">→ Log out</a>
            </div>
        </aside>

        <!-- ── MAIN ────────────────────────────────── -->
        <main class="admin-main">

            <!-- Top bar -->
            <div class="admin-topbar">
                <h1 class="admin-page-title">User Management</h1>
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

                <!-- Alert -->
                <div id="pageAlert" class="alert d-none mb-3"></div>

                <div class="admin-card">

                    <!-- ── Toolbar ──────────────────────── -->
                    <div class="um-toolbar">
                        <div class="admin-search-wrap">
                            <span class="admin-search-icon">🔍</span>
                            <input type="text" id="userSearch"
                                class="admin-search" placeholder="Search User">
                        </div>
                        <div class="um-toolbar-right">
                            <div class="um-filter-wrap" id="filterToggleWrap">
                                <button class="um-btn-filter" id="filterToggle">
                                    ▼ Filter
                                </button>
                                <div class="um-filter-dropdown d-none" id="filterDropdown">
                                    <div class="um-filter-group">
                                        <label>Role</label>
                                        <select id="filterRole" class="um-filter-select">
                                            <option value="">All roles</option>
                                            <option value="user">User</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                    <div class="um-filter-group">
                                        <label>Status</label>
                                        <select id="filterStatus" class="um-filter-select">
                                            <option value="">All statuses</option>
                                            <option value="active">Active</option>
                                            <option value="blocked">Suspended</option>
                                        </select>
                                    </div>
                                    <button class="um-btn-apply" id="applyFilter">Apply</button>
                                </div>
                            </div>
                            <button class="um-btn-new-admin" id="openNewAdminModal">
                                + New Admin
                            </button>
                        </div>
                    </div>

                    <!-- ── Users Table ──────────────────── -->
                    <div class="table-responsive">
                        <table class="um-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>User Name ↕</th>
                                    <th>Email ↕</th>
                                    <th>Role ↕</th>
                                    <th>Status ↕</th>
                                    <th></th><!-- actions column -->
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <tr>
                                    <td colspan="6" class="um-loading">Loading users...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Row count -->
                    <div class="um-row-count" id="rowCount"></div>

                </div>
            </div>
        </main>
    </div>

    <!-- ══════════════════════════════════════════════
     ROW ACTION DROPDOWN (shared, moved by JS)
══════════════════════════════════════════════ -->
    <div class="um-action-menu d-none" id="actionMenu">
        <a href="#" class="um-action-item" id="actionViewProfile">
            👤 View Profile
        </a>
        <a href="#" class="um-action-item" id="actionMessage">
            💬 Message User
        </a>
        <button class="um-action-item um-action-suspend" id="actionSuspend">
            🚫 Suspend User
        </button>
        <button class="um-action-item um-action-unsuspend d-none" id="actionUnsuspend">
            ✅ Reactivate User
        </button>
        <hr class="um-action-divider">
        <button class="um-action-item um-action-admin" id="actionMakeAdmin">
            🛡️ Make Admin
        </button>
        <button class="um-action-item um-action-remove-admin d-none" id="actionRemoveAdmin">
            ⬇️ Remove Admin
        </button>
    </div>

    <!-- ══════════════════════════════════════════════
     USER PROFILE MODAL
══════════════════════════════════════════════ -->
    <div class="modal-overlay d-none" id="profileModal">
        <div class="modal-box um-profile-modal">

            <div class="modal-header">
                <h2>User Profile</h2>
                <button class="modal-close" data-close="profileModal">&times;</button>
            </div>

            <!-- Profile hero -->
            <div class="um-profile-hero">
                <div class="um-profile-avatar" id="modalAvatar">?</div>
                <div class="um-profile-info">
                    <h3 id="modalName">—</h3>
                    <p id="modalEmail">—</p>
                    <p id="modalLocation">—</p>
                    <div class="um-profile-badges">
                        <span class="um-badge-role" id="modalRole">—</span>
                        <span class="um-badge-status" id="modalStatus">—</span>
                    </div>
                    <p class="um-profile-since" id="modalSince">—</p>
                </div>
            </div>

            <!-- Their listings -->
            <div class="um-profile-listings-label">Listings</div>
            <div class="um-profile-listings" id="modalListings">
                <div class="um-loading">Loading listings...</div>
            </div>

            <!-- Modal action buttons -->
            <div class="um-profile-actions">
                <button class="um-btn-message-modal" id="modalMessageBtn">💬 Message</button>
                <button class="um-btn-suspend-modal" id="modalSuspendBtn">🚫 Suspend</button>
                <button class="um-btn-unsuspend-modal d-none" id="modalUnsuspendBtn">✅ Reactivate</button>
            </div>

        </div>
    </div>

    <!-- ══════════════════════════════════════════════
     NEW ADMIN MODAL
══════════════════════════════════════════════ -->
    <div class="modal-overlay d-none" id="newAdminModal">
        <div class="modal-box modal-sm">
            <div class="modal-header">
                <h2>Create New Admin</h2>
                <button class="modal-close" data-close="newAdminModal">&times;</button>
            </div>
            <p class="text-muted mb-3" style="font-size:.88rem">
                Search for an existing user and promote them to admin.
            </p>
            <div class="admin-search-wrap mb-3">
                <span class="admin-search-icon">🔍</span>
                <input type="text" id="adminSearchInput"
                    class="admin-search" placeholder="Search by name or email">
            </div>
            <div id="adminSearchResults" class="um-admin-search-results"></div>
            <div id="newAdminAlert" class="alert d-none mt-3"></div>
            <div class="modal-footer-btns mt-3">
                <button class="btn-admin-ghost" data-close="newAdminModal">Cancel</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        const ADMIN_API = BASE_URL + '../api/admin/';
        const MSG_URL = BASE_URL + 'messages.php?to=';

        let activeUserId = 0;
        let activeUserData = null;
        let allUsers = [];

        // ══════════════════════════════════════════════
        // LOAD USERS
        // ══════════════════════════════════════════════
        function loadUsers(params = {}) {
            $('#usersTableBody').html('<tr><td colspan="6" class="um-loading">Loading...</td></tr>');

            $.get(ADMIN_API + 'get-users.php', params)
                .done(res => {
                    allUsers = res.users;
                    renderTable(res.users);
                })
                .fail(() => showPageAlert('Failed to load users.', 'danger'));
        }

        function renderTable(users) {
            const tbody = $('#usersTableBody');
            $('#rowCount').text(users.length + ' user' + (users.length !== 1 ? 's' : ''));

            if (!users.length) {
                tbody.html('<tr><td colspan="6" class="um-loading">No users found.</td></tr>');
                return;
            }

            const html = users.map(u => {
                const statusBadge = u.status === 'active' ?
                    '<span class="um-status-active">Active</span>' :
                    '<span class="um-status-suspended">Suspended</span>';

                const roleBadge = u.role === 'admin' ?
                    '<span class="um-role-admin">Admin</span>' :
                    '<span class="um-role-user">User</span>';

                const avatarHtml = u.avatar_url ?
                    `<img src="${u.avatar_url}" alt="${esc(u.name)}" class="um-row-avatar">` :
                    `<div class="um-row-avatar-init">${u.initial}</div>`;

                return `
      <tr data-id="${u.id}">
        <td><input type="checkbox" class="um-row-check"></td>
        <td>
          <div class="um-name-cell">
            ${avatarHtml}
            <span>${esc(u.name)}</span>
          </div>
        </td>
        <td class="um-email">${esc(u.email)}</td>
        <td>${roleBadge}</td>
        <td>${statusBadge}</td>
        <td>
          <button class="um-row-action-btn" data-id="${u.id}" title="Actions">
            •••
          </button>
        </td>
      </tr>`;
            }).join('');

            tbody.html(html);
        }

        // ══════════════════════════════════════════════
        // ROW ACTION DROPDOWN
        // ══════════════════════════════════════════════
        $(document).on('click', '.um-row-action-btn', function(e) {
            e.stopPropagation();
            const userId = $(this).data('id');
            const user = allUsers.find(u => u.id == userId);
            if (!user) return;

            activeUserId = userId;
            activeUserData = user;

            // Update menu items based on user state
            const isSuspended = user.status === 'blocked';
            const isAdmin = user.role === 'admin';

            $('#actionSuspend').toggleClass('d-none', isSuspended);
            $('#actionUnsuspend').toggleClass('d-none', !isSuspended);
            $('#actionMakeAdmin').toggleClass('d-none', isAdmin);
            $('#actionRemoveAdmin').toggleClass('d-none', !isAdmin);

            // Position dropdown near the button
            const btn = $(this);
            const offset = btn.offset();
            const menu = $('#actionMenu');

            menu.css({
                top: offset.top + btn.outerHeight() + 4,
                left: offset.left - menu.outerWidth() + btn.outerWidth(),
            }).removeClass('d-none');
        });

        // Close dropdown when clicking elsewhere
        $(document).on('click', function() {
            $('#actionMenu').addClass('d-none');
        });
        $('#actionMenu').on('click', e => e.stopPropagation());

        // ── View Profile ───────────────────────────────────────────
        $('#actionViewProfile').on('click', function(e) {
            e.preventDefault();
            $('#actionMenu').addClass('d-none');
            openProfileModal(activeUserId);
        });

        // ── Message ────────────────────────────────────────────────
        $('#actionMessage').on('click', function(e) {
            e.preventDefault();
            $('#actionMenu').addClass('d-none');
            window.location.href = MSG_URL + activeUserId;
        });

        // ── Suspend ────────────────────────────────────────────────
        $('#actionSuspend').on('click', function() {
            $('#actionMenu').addClass('d-none');
            if (!confirm(`Suspend ${activeUserData.name}? They will not be able to login.`)) return;
            updateUser(activeUserId, 'suspend');
        });

        // ── Unsuspend ──────────────────────────────────────────────
        $('#actionUnsuspend').on('click', function() {
            $('#actionMenu').addClass('d-none');
            updateUser(activeUserId, 'unsuspend');
        });

        // ── Make Admin ─────────────────────────────────────────────
        $('#actionMakeAdmin').on('click', function() {
            $('#actionMenu').addClass('d-none');
            if (!confirm(`Promote ${activeUserData.name} to Admin?`)) return;
            updateUser(activeUserId, 'make_admin');
        });

        // ── Remove Admin ───────────────────────────────────────────
        $('#actionRemoveAdmin').on('click', function() {
            $('#actionMenu').addClass('d-none');
            if (!confirm(`Remove admin rights from ${activeUserData.name}?`)) return;
            updateUser(activeUserId, 'remove_admin');
        });

        // ══════════════════════════════════════════════
        // PROFILE MODAL
        // ══════════════════════════════════════════════
        function openProfileModal(userId) {
            // Reset modal
            $('#modalListings').html('<div class="um-loading">Loading...</div>');
            openModal('profileModal');

            $.get(ADMIN_API + 'get-user-profile.php', {
                    id: userId
                })
                .done(res => {
                    if (!res.success) return;
                    const u = res.user;

                    // Avatar
                    if (u.avatar_url) {
                        $('#modalAvatar').html(`<img src="${u.avatar_url}" alt="${esc(u.name)}">`);
                    } else {
                        $('#modalAvatar').text(u.initial);
                    }

                    $('#modalName').text(u.name);
                    $('#modalEmail').text(u.email);
                    $('#modalLocation').text(u.location || 'No location set');
                    $('#modalRole').text(u.role.charAt(0).toUpperCase() + u.role.slice(1))
                        .attr('class', u.role === 'admin' ? 'um-badge-role admin' : 'um-badge-role');
                    $('#modalStatus').text(u.status === 'active' ? 'Active' : 'Suspended')
                        .attr('class', u.status === 'active' ? 'um-badge-status active' : 'um-badge-status suspended');
                    $('#modalSince').text('Member since ' + u.member_since);

                    // Suspend / Unsuspend button
                    const isSuspended = u.status === 'blocked';
                    $('#modalSuspendBtn').toggleClass('d-none', isSuspended);
                    $('#modalUnsuspendBtn').toggleClass('d-none', !isSuspended);

                    // Message button
                    $('#modalMessageBtn').off('click').on('click', () => {
                        window.location.href = MSG_URL + u.id;
                    });
                    $('#modalSuspendBtn').off('click').on('click', () => {
                        closeModal('profileModal');
                        updateUser(u.id, 'suspend');
                    });
                    $('#modalUnsuspendBtn').off('click').on('click', () => {
                        closeModal('profileModal');
                        updateUser(u.id, 'unsuspend');
                    });

                    // Listings grid
                    renderModalListings(res.listings);
                });
        }

        function renderModalListings(listings) {
            if (!listings.length) {
                $('#modalListings').html('<div class="um-loading">No listings yet.</div>');
                return;
            }
            const html = listings.map(l => {
                const img = l.image_url ?
                    `<img src="${l.image_url}" alt="${esc(l.title)}">` :
                    `<div class="um-listing-placeholder">🛍️</div>`;
                return `
      <a href="${BASE_URL}product.php?id=${l.id}"
         class="um-listing-card" target="_blank">
        <div class="um-listing-img">${img}</div>
        <div class="um-listing-title">${esc(l.title)}</div>
        <div class="um-listing-price">${l.price_formatted}</div>
        <span class="um-listing-status ${l.status}">${l.status}</span>
      </a>`;
            }).join('');
            $('#modalListings').html(html);
        }

        // ══════════════════════════════════════════════
        // UPDATE USER (suspend/unsuspend/role)
        // ══════════════════════════════════════════════
        function updateUser(userId, action) {
            $.post(ADMIN_API + 'update-user.php', {
                    user_id: userId,
                    action
                })
                .done(res => {
                    if (res.success) {
                        showPageAlert(res.message, 'success');
                        loadUsers(currentFilters());
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
        $('#userSearch').on('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => loadUsers(currentFilters()), 400);
        });

        $('#filterToggle').on('click', function(e) {
            e.stopPropagation();
            $('#filterDropdown').toggleClass('d-none');
        });
        $(document).on('click', function() {
            $('#filterDropdown').addClass('d-none');
        });
        $('#filterDropdown').on('click', e => e.stopPropagation());

        $('#applyFilter').on('click', function() {
            $('#filterDropdown').addClass('d-none');
            loadUsers(currentFilters());
        });

        function currentFilters() {
            return {
                search: $('#userSearch').val().trim(),
                role: $('#filterRole').val(),
                status: $('#filterStatus').val(),
            };
        }

        // ══════════════════════════════════════════════
        // SELECT ALL CHECKBOX
        // ══════════════════════════════════════════════
        $('#selectAll').on('change', function() {
            $('.um-row-check').prop('checked', $(this).is(':checked'));
        });

        // ══════════════════════════════════════════════
        // NEW ADMIN MODAL — search and promote
        // ══════════════════════════════════════════════
        $('#openNewAdminModal').on('click', () => {
            $('#adminSearchInput').val('');
            $('#adminSearchResults').empty();
            hideAlert('newAdminAlert');
            openModal('newAdminModal');
        });

        let adminSearchTimer;
        $('#adminSearchInput').on('input', function() {
            clearTimeout(adminSearchTimer);
            const q = $(this).val().trim();
            if (!q) {
                $('#adminSearchResults').empty();
                return;
            }

            adminSearchTimer = setTimeout(() => {
                $.get(ADMIN_API + 'get-users.php', {
                        search: q,
                        role: 'user'
                    })
                    .done(res => {
                        if (!res.users.length) {
                            $('#adminSearchResults').html('<div class="um-loading">No users found.</div>');
                            return;
                        }
                        const html = res.users.slice(0, 5).map(u => `
          <div class="um-admin-result-row">
            <div class="um-admin-result-info">
              <strong>${esc(u.name)}</strong>
              <span>${esc(u.email)}</span>
            </div>
            <button class="um-btn-promote" data-id="${u.id}" data-name="${esc(u.name)}">
              Promote
            </button>
          </div>`).join('');
                        $('#adminSearchResults').html(html);
                    });
            }, 350);
        });

        $(document).on('click', '.um-btn-promote', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            if (!confirm(`Promote ${name} to Admin?`)) return;

            $.post(ADMIN_API + 'update-user.php', {
                    user_id: id,
                    action: 'make_admin'
                })
                .done(res => {
                    if (res.success) {
                        showAlert('newAdminAlert', `✅ ${name} is now an admin.`, 'success');
                        loadUsers(currentFilters());
                    } else {
                        showAlert('newAdminAlert', res.error, 'danger');
                    }
                });
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

        // ══════════════════════════════════════════════
        // ALERT HELPERS
        // ══════════════════════════════════════════════
        function showPageAlert(msg, type) {
            $('#pageAlert').removeClass('d-none alert-success alert-danger')
                .addClass('alert alert-' + type).text(msg);
            setTimeout(() => $('#pageAlert').addClass('d-none'), 4000);
        }

        function showAlert(id, msg, type) {
            $('#' + id).removeClass('d-none alert-success alert-danger')
                .addClass('alert alert-' + type).text(msg);
        }

        function hideAlert(id) {
            $('#' + id).addClass('d-none').text('');
        }

        function esc(s) {
            return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // ── Init ──────────────────────────────────────
        loadUsers();
    </script>
</body>

</html>