// public/assets/js/home.js
// ============================================================
// Handles all homepage interactivity:
//   - Load products from API
//   - Category sidebar clicks
//   - Secondary nav (Categories, For You, Community Spotlights)
//   - Hamburger menu open/close
//   - Search (live + enter key)
//   - Filters (location, sort, date)
//   - Carousel (local favorites)
// ============================================================

$(function () {
  // ── State ─────────────────────────────────────────────────
  // Keeps track of what's currently active so we can reload
  // when filters change without losing the current category
  const state = {
    category: 0, // active category ID (0 = all)
    search: "",
    sort: "newest",
    date: "",
    location: "",
    tab: "all", // 'all' | 'for_you' | 'spotlights'
  };

  const API = "../api/products/get-product.php";

  // ── Load products ─────────────────────────────────────────
  function loadProducts() {
    $("#productGrid").html(
      '<div class="loading-state">Loading products...</div>',
    );
    $("#emptyState").addClass("d-none");

    const params = {
      category: state.category,
      search: state.search,
      sort: state.sort,
      date: state.date,
      location: state.location,
    };
    if (state.tab === "for_you") params.for_you = 1;

    $.get(API, params)
      .done((res) => {
        if (!res.success) return showGridError("Failed to load products.");
        $("#productCount").text(
          res.count + " listing" + (res.count !== 1 ? "s" : ""),
        );

        if (res.count === 0) {
          $("#productGrid").empty();
          $("#emptyState").removeClass("d-none");
          return;
        }

        renderGrid(res.products);
        renderCarousel(res.products);
      })
      .fail(() => showGridError("Could not connect. Please refresh."));
  }

  // ── Render product grid ────────────────────────────────────
  function renderGrid(products) {
    const html = products.map((p) => productCardHtml(p)).join("");
    $("#productGrid").html(html);
  }

  function productCardHtml(p) {
    const img = p.image_url
      ? `<img src="${p.image_url}" alt="${escHtml(p.title)}" loading="lazy">`
      : `<div class="product-img-placeholder">🛍️</div>`;

    return `
      <a href="product.php?id=${p.id}" class="product-card">
        <div class="product-img-wrap">
          ${img}
          <span class="badge-approved">Community Approved</span>
        </div>
        <div class="product-info">
          <div class="product-title">${escHtml(p.title)}</div>
          <div class="product-location">📍 ${escHtml(p.location || "Location not set")}</div>
          <div class="product-price">${p.price_formatted}</div>
          <div class="seller-row">
            <div class="seller-avatar">${p.seller_initial}</div>
            <span class="seller-name">${escHtml(p.seller_name)}</span>
          </div>
        </div>
      </a>`;
  }

  // ── Render carousel (first 6 products as "Local Favorites") ─
  function renderCarousel(products) {
    const items = products.slice(0, 6);
    if (!items.length) {
      $("#carouselTrack").html(
        '<div class="carousel-placeholder">No favourites yet.</div>',
      );
      return;
    }

    const html = items
      .map((p, i) => {
        const img = p.image_url
          ? `<img src="${p.image_url}" alt="${escHtml(p.title)}" loading="lazy">`
          : `<div class="product-img-placeholder" style="height:100%">🛍️</div>`;
        return `<div class="carousel-item ${i === 0 ? "first" : ""}"
                   onclick="window.location='product.php?id=${p.id}'"
                   title="${escHtml(p.title)}">${img}</div>`;
      })
      .join("");

    $("#carouselTrack").html(html);
  }

  // ── Carousel scroll ────────────────────────────────────────
  $("#carouselNext").on("click", function () {
    $("#carouselTrack").animate({ scrollLeft: "+=150" }, 250);
  });
  $("#carouselPrev").on("click", function () {
    $("#carouselTrack").animate({ scrollLeft: "-=150" }, 250);
  });

  // ── Category sidebar clicks ────────────────────────────────
  // When you click Electronics → only Electronics products load
  $(document).on("click", ".cat-item a", function (e) {
    e.preventDefault();

    // Read category from href query string
    const href = $(this).attr("href");
    const params = new URLSearchParams(href.split("?")[1] || "");
    state.category = parseInt(params.get("category") || "0");
    state.tab = "all";

    // Update active style in sidebar
    $(".cat-item").removeClass("active");
    $(this).closest(".cat-item").addClass("active");

    // Update secondary nav active state
    $(".sec-nav-link").removeClass("active");
    $('[data-tab="all"]').addClass("active");

    loadProducts();
    closeSidebar(); // auto-close sidebar on mobile after selection
  });

  // ── Secondary nav tabs ─────────────────────────────────────
  // Categories → show all, For You → personalised, Spotlights → featured
  $(document).on("click", ".sec-nav-link", function (e) {
    e.preventDefault();
    const tab = $(this).data("tab");

    $(".sec-nav-link").removeClass("active");
    $(this).addClass("active");

    state.tab = tab || "all";
    state.category = 0;

    // Deselect sidebar category
    $(".cat-item").removeClass("active");
    $(".cat-item:first-child").addClass("active");

    loadProducts();
  });

  // ── Search ─────────────────────────────────────────────────
  let searchTimer;
  $("#searchInput").on("input", function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      state.search = $(this).val().trim();
      loadProducts();
    }, 400); // wait 400ms after user stops typing
  });

  // Enter key triggers search immediately
  $("#searchInput").on("keydown", function (e) {
    if (e.key === "Enter") {
      clearTimeout(searchTimer);
      state.search = $(this).val().trim();
      loadProducts();
    }
  });

  // ── Filters ────────────────────────────────────────────────
  $("#applyFilter").on("click", function () {
    state.location = $("#filterLocation").val().trim();
    state.sort = $("#filterSort").val();
    state.date = $("#filterDate").val();
    loadProducts();
  });

  // Sort dropdown changes immediately
  $("#filterSort").on("change", function () {
    state.sort = $(this).val();
    loadProducts();
  });

  // ── Hamburger menu ─────────────────────────────────────────
  // Opens/closes the left sidebar on desktop and mobile
  $("#sidebarToggle").on("click", function () {
    const sidebar = $("#leftSidebar");
    const overlay = $("#sidebarOverlay");
    const isOpen = sidebar.hasClass("open");

    if (isOpen) {
      closeSidebar();
    } else {
      sidebar.addClass("open");
      overlay.removeClass("d-none");
    }
  });

  // Click overlay to close sidebar
  $("#sidebarOverlay").on("click", closeSidebar);

  function closeSidebar() {
    $("#leftSidebar").removeClass("open");
    $("#sidebarOverlay").addClass("d-none");
  }

  // ── Avatar dropdown ────────────────────────────────────────
  $("#avatarToggle").on("click", function (e) {
    e.stopPropagation();
    $("#avatarDropdown").toggleClass("open");
  });
  $(document).on("click", function () {
    $("#avatarDropdown").removeClass("open");
  });

  // ── Helpers ────────────────────────────────────────────────
  function escHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function showGridError(msg) {
    $("#productGrid").html(
      `<div class="loading-state text-danger">${msg}</div>`,
    );
  }

  // ── Mobile Category Hamburger Menu ─────────────────────────
  function toggleCategoryMenu() {
    const sidebar = $("#leftCatSidebar");
    const overlay = $("#mobileCatOverlay");
    const isOpen = sidebar.hasClass("cat-sidebar-open");

    if (isOpen) {
      sidebar.removeClass("cat-sidebar-open").removeClass("open");
      overlay.removeClass("cat-overlay-open");
      $("body").css("overflow", "");
    } else {
      sidebar.addClass("cat-sidebar-open").addClass("open");
      overlay.addClass("cat-overlay-open");
      $("body").css("overflow", "hidden"); // Stop background scrolling
    }
  }

  // Open/Close on hamburger click
  $("#catMenuToggle").on("click", function (e) {
    e.stopPropagation();
    toggleCategoryMenu();
  });

  // Close when clicking on the dimmed background area
  $("#mobileCatOverlay").on("click", toggleCategoryMenu);

  // Helper helper function to close sidebar smoothly
  function closeSidebar() {
    $("#leftCatSidebar").removeClass("cat-sidebar-open").removeClass("open");
    $("#mobileCatOverlay").removeClass("cat-overlay-open");
    $("body").css("overflow", "");
  }

  // ── Initial Boot ──────────────────────────────────────────
  loadProducts();
});
