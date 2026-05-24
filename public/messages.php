<?php
// public/messages.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

// Pre-open a conversation if coming from product page
$openPartnerId = (int)($_GET['to']      ?? 0);
$openProductId = (int)($_GET['product'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Messages — TownMarket</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/home.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/messages.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/mobile.css">
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
            <a href="<?= BASE_URL ?>index.php" class="nav-icon-btn">🏠</a>
            <a href="<?= BASE_URL ?>messages.php" class="nav-icon-btn active-nav">💬</a>
            <div class="nav-icon-btn notif-wrap" id="bellWrap">
                🔔
                <span class="notif-badge d-none" id="notifBadge">0</span>
            </div>
            <div class="avatar-wrap" id="avatarToggle">
                <div class="avatar-circle"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
                <div class="avatar-dropdown" id="avatarDropdown">
                    <a href="<?= BASE_URL ?>dashboard.php">👤 Profile</a>
                    <a href="<?= BASE_URL ?>messages.php">💬 Messages</a>
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

    <!-- ── MESSAGES LAYOUT ────────────────────────────────────── -->
    <div class="msg-layout">

        <!-- ── LEFT: Conversations list ──────────────────────────── -->
        <aside class="msg-sidebar">
            <div class="msg-sidebar-header">Recent conversation</div>
            <div class="msg-conv-list" id="convList">
                <div class="msg-loading">Loading...</div>
            </div>
        </aside>

        <!-- ── RIGHT: Chat window ─────────────────────────────────── -->
        <div class="msg-chat" id="msgChat">

            <!-- Empty state (no conversation selected) -->
            <div class="msg-empty-state" id="msgEmptyState">
                <div class="msg-empty-icon">💬</div>
                <p>Select a conversation to start chatting</p>
            </div>

            <!-- Chat area (hidden until conversation selected) -->
            <div class="msg-chat-inner d-none" id="msgChatInner">

                <!-- Chat header -->
                <div class="msg-chat-header">
                    <div class="msg-chat-partner">
                        <div class="msg-partner-avatar" id="chatPartnerAvatar">?</div>
                        <span class="msg-partner-name" id="chatPartnerName">—</span>
                    </div>
                    <button class="msg-close-btn" id="closeChatBtn" title="Close">✕</button>
                </div>

                <!-- Messages area -->
                <div class="msg-body" id="msgBody">
                    <!-- Messages injected by JS -->
                </div>

                <!-- Input area -->
                <div class="msg-input-wrap">
                    <input type="text" id="msgInput"
                        class="msg-input" placeholder="Type your message..">
                    <label class="msg-attach-btn" title="Attach (coming soon)">
                        📎
                    </label>
                    <button class="msg-send-btn" id="sendBtn">Send</button>
                </div>

            </div><!-- /msg-chat-inner -->
        </div><!-- /msg-chat -->

    </div><!-- /msg-layout -->

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
        const MY_ID = <?= $_SESSION['user_id'] ?>;
        const OPEN_PARTNER = <?= $openPartnerId ?>;
        const OPEN_PRODUCT = <?= $openProductId ?>;
    </script>
    <script>
        // Mobile nav data (read by mobile-nav.js)
        window.MOB_LOGGED = <?= isLoggedIn() ? 'true' : 'false' ?>;
        window.MOB_NAME = '<?= sanitize($_SESSION["name"] ?? "Guest") ?>';
        window.MOB_ROLE = '<?= sanitize($_SESSION["role"] ?? "") ?>';
        window.MOB_ADMIN = <?= isAdmin() ? 'true' : 'false' ?>;
        window.MOB_AVATAR = '<?= !empty($_SESSION["avatar"]) ? BASE_URL . "uploads/avatars/" . $_SESSION["avatar"] : "" ?>';
    </script>
    <script src="<?= BASE_URL ?>assets/js/mobile-nav.js"></script>
    <script src="<?= BASE_URL ?>assets/js/messages.js"></script>
</body>

</html>