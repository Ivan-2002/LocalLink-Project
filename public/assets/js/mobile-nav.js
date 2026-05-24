// public/assets/js/mobile-nav.js
// ============================================================
// Shared mobile navigation drawer — works on ALL pages.
// Include this script on every page after jQuery.
// ============================================================

$(function () {
  // ── Build the drawer HTML once and inject into body ────────
  // Only build if not already there (safe to include on every page)
  if ($("#mobileNavDrawer").length) return;

  // Collect user info from page (set by PHP in each page)
  const userName = window.MOB_NAME || "Guest";
  const userRole = window.MOB_ROLE || "";
  const userInitial = userName.charAt(0).toUpperCase();
  const avatarUrl = window.MOB_AVATAR || null;
  const isLoggedIn = window.MOB_LOGGED || false;
  const isAdmin = window.MOB_ADMIN || false;
  const baseUrl = window.BASE_URL || "/";

  const avatarHtml = avatarUrl
    ? `<img src="${avatarUrl}" alt="${userInitial}">`
    : userInitial;

  // Build nav links depending on login state
  // <a href="${baseUrl}dashboard.php" class="mobile-nav-link">
  //   <span class="nav-icon">👤</span> My Dashboard
  // </a>
  // <a href="${baseUrl}messages.php"  class="mobile-nav-link">
  //   <span class="nav-icon">💬</span> Messages
  // </a>
  const authLinks = isLoggedIn
    ? `
    <a href="${baseUrl}index.php"     class="mobile-nav-link">
      <span class="nav-icon">🏠</span> Home
    </a>
    <a href="${baseUrl}seller/add-product.php" class="mobile-nav-link">
      <span class="nav-icon">➕</span> List an Item
    </a>
    <div class="mobile-nav-divider"></div>
    ${
      isAdmin
        ? `
      <a href="${baseUrl}../admin/dashboard.php" class="mobile-nav-link admin-link">
        <span class="nav-icon">🛡️</span> Admin Panel
      </a>
      <div class="mobile-nav-divider"></div>
    `
        : ""
    }
    <a href="${baseUrl}logout.php" class="mobile-nav-link logout-link">
      <span class="nav-icon">🚪</span> Logout
    </a>
  `
    : `
    <a href="${baseUrl}index.php"  class="mobile-nav-link">
      <span class="nav-icon">🏠</span> Home
    </a>
    <a href="${baseUrl}login.php"  class="mobile-nav-link">
      <span class="nav-icon">🔑</span> Login
    </a>
    <a href="${baseUrl}login.php#register" class="mobile-nav-link">
      <span class="nav-icon">✍️</span> Register
    </a>
  `;

  const userBlock = isLoggedIn
    ? `
    <div class="mobile-nav-user">
      <div class="mobile-nav-avatar">${avatarHtml}</div>
      <div class="mobile-nav-user-info">
        <div class="mobile-nav-name">${userName}</div>
        <div class="mobile-nav-role">${userRole}</div>
      </div>
    </div>
  `
    : "";

  const drawerHtml = `
    <!-- Overlay -->
    <div class="mobile-nav-overlay" id="mobileNavOverlay"></div>

    <!-- Drawer -->
    <div class="mobile-nav-drawer" id="mobileNavDrawer">
      <div class="mobile-nav-header">
        <a href="${baseUrl}index.php" class="mobile-nav-brand">
          LocalLink 🛍️
        </a>
        <button class="mobile-nav-close" id="mobileNavClose">&times;</button>
      </div>

      ${userBlock}

      <div class="mobile-nav-links">
        ${authLinks}
      </div>

      <div class="mobile-nav-footer">
        © 2026 TownMarket
      </div>
    </div>
  `;

  $("body").append(drawerHtml);

  // ── Open / Close ───────────────────────────────────────────
  function openDrawer() {
    $("#mobileNavDrawer").addClass("open");
    $("#mobileNavOverlay").addClass("open");
    $("body").css("overflow", "hidden");
    // Animate hamburger to X
    $(".hamburger-mobile").addClass("open");
  }

  function closeDrawer() {
    $("#mobileNavDrawer").removeClass("open");
    $("#mobileNavOverlay").removeClass("open");
    $("body").css("overflow", "");
    $(".hamburger-mobile").removeClass("open");
  }

  // Hamburger click — any .hamburger-mobile on the page
  $(document).on("click", ".hamburger-mobile", function () {
    const isOpen = $("#mobileNavDrawer").hasClass("open");
    isOpen ? closeDrawer() : openDrawer();
  });

  // Close button inside drawer
  $(document).on("click", "#mobileNavClose", closeDrawer);

  // Overlay click
  $(document).on("click", "#mobileNavOverlay", closeDrawer);

  // Close on nav link click (smooth UX)
  $(document).on("click", ".mobile-nav-link", function () {
    // Small delay so the page starts navigating before drawer closes
    setTimeout(closeDrawer, 150);
  });

  // Close on Escape key
  $(document).on("keydown", function (e) {
    if (e.key === "Escape") closeDrawer();
  });

  // ── Mark active link ───────────────────────────────────────
  const currentPath = window.location.pathname;
  $(".mobile-nav-link").each(function () {
    const href = $(this).attr("href") || "";
    if (href && currentPath.endsWith(href.split("/").pop())) {
      $(this).addClass("active");
    }
  });
});
