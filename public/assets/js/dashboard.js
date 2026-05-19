$(function () {
  const API = BASE_URL + "../api/user/";
  let dashData = null; // store loaded data for filtering

  // ── Load all dashboard data ────────────────────────────────
  function loadDashboard() {
    $.get(API + "get-dashboard.php")
      .done((res) => {
        if (!res.success) return;
        dashData = res;

        // Populate hero
        $("#dbName").text(res.user.name);
        $("#dbMeta").text(
          (res.user.location || "No location set") +
            " • Member since " +
            res.stats.member_since,
        );
        $("#statListings").text(res.stats.active_listings);
        $("#statOrders").text(res.stats.total_orders);
        $("#statSince").text(res.stats.member_since);

        // Populate profile form
        $("#profileName").val(res.user.name);
        $("#profileEmail").val(res.user.email);
        $("#profileLocation").val(res.user.location || "");

        // Account card
        $("#accountRole").text(
          res.user.role.charAt(0).toUpperCase() + res.user.role.slice(1),
        );
        $("#accountSince").text(res.stats.member_since);
        $("#accountListings").text(
          res.stats.active_listings +
            " active / " +
            res.stats.total_listings +
            " total",
        );

        // Render listings and orders
        renderListings(res.listings, "all");
        renderOrders(res.orders);
      })
      .fail(() => {
        $("#listingsGrid").html(
          '<div class="db-loading text-danger">Failed to load. Please refresh.</div>',
        );
      });
  }

  // ── Render listings grid ───────────────────────────────────
  function renderListings(listings, filter) {
    const grid = $("#listingsGrid");
    const empty = $("#listingsEmpty");
    const filtered =
      filter === "all" ? listings : listings.filter((l) => l.status === filter);

    if (!filtered.length) {
      grid.empty();
      empty.removeClass("d-none");
      return;
    }

    empty.addClass("d-none");
    const html = filtered
      .map((l) => {
        const imgHtml = l.image_url
          ? `<img src="${l.image_url}" alt="${esc(l.title)}">`
          : `<div class="db-listing-img">🛍️</div>`;

        return `
        <div class="db-listing-card">
          <div class="db-listing-img">${imgHtml}</div>
          <span class="db-listing-status ${l.status}">${l.status}</span>
          <div class="db-listing-info">
            <div class="db-listing-title">${esc(l.title)}</div>
            <div class="db-listing-cat">${esc(l.category_name || "Uncategorised")}</div>
            <div class="db-listing-price">${l.price_formatted}</div>
            <div class="db-listing-actions">
              <a href="${BASE_URL}seller/edit-product.php?id=${l.id}"
                 class="db-btn-edit">✏️ Edit</a>
              <button class="db-btn-del"
                      onclick="confirmDelete(${l.id}, '${esc(l.title)}')">🗑️</button>
            </div>
          </div>
        </div>`;
      })
      .join("");

    grid.html(html);
  }

  // ── Render orders list ─────────────────────────────────────
  function renderOrders(orders) {
    const list = $("#ordersList");
    const empty = $("#ordersEmpty");

    if (!orders.length) {
      list.empty();
      empty.removeClass("d-none");
      return;
    }

    empty.addClass("d-none");
    const html = orders
      .map((o) => {
        const date = new Date(o.created_at).toLocaleDateString("en-ZA", {
          day: "numeric",
          month: "short",
          year: "numeric",
        });
        return `
        <div class="db-order-card">
          <div class="db-order-left">
            <div class="db-order-id">Order #${o.id}</div>
            <div class="db-order-items">${esc(o.items)}</div>
            <div class="db-order-date">${date}</div>
          </div>
          <div class="db-order-right">
            <div class="db-order-total">${o.total_formatted}</div>
            <span class="db-order-status ${o.status}">${o.status}</span>
            <div class="db-order-pay">${o.payment_method.toUpperCase()}</div>
          </div>
        </div>`;
      })
      .join("");

    list.html(html);
  }

  // ── Tab switching ──────────────────────────────────────────
  $(".db-tab").on("click", function () {
    const tab = $(this).data("tab");
    $(".db-tab").removeClass("active");
    $(this).addClass("active");
    $(".db-panel").removeClass("active");
    $("#tab-" + tab).addClass("active");
  });

  // ── Listing filter buttons ─────────────────────────────────
  $(document).on("click", ".db-filter-btn", function () {
    if (!dashData) return;
    $(".db-filter-btn").removeClass("active");
    $(this).addClass("active");
    renderListings(dashData.listings, $(this).data("filter"));
  });

  // ── Update profile ─────────────────────────────────────────
  $("#profileForm").on("submit", function (e) {
    e.preventDefault();
    const btn = $(this).find("button[type=submit]");
    btn.prop("disabled", true).text("Saving...");

    $.post(API + "update-profile.php", $(this).serialize())
      .done((res) => {
        if (res.success) {
          showFormAlert("profileAlert", "✅ Profile updated!", "success");
          // Update hero name live
          $("#dbName").text($("#profileName").val());
        } else {
          showFormAlert("profileAlert", res.error, "danger");
        }
      })
      .fail((xhr) =>
        showFormAlert(
          "profileAlert",
          xhr.responseJSON?.error || "Failed.",
          "danger",
        ),
      )
      .always(() => btn.prop("disabled", false).text("Save Changes"));
  });

  // ── Change password ────────────────────────────────────────
  $("#passwordForm").on("submit", function (e) {
    e.preventDefault();
    const btn = $(this).find("button[type=submit]");
    btn.prop("disabled", true).text("Updating...");

    $.post(API + "update-profile.php", $(this).serialize())
      .done((res) => {
        if (res.success) {
          showFormAlert("passwordAlert", "✅ Password updated!", "success");
          $("#passwordForm")[0].reset();
        } else {
          showFormAlert("passwordAlert", res.error, "danger");
        }
      })
      .fail((xhr) =>
        showFormAlert(
          "passwordAlert",
          xhr.responseJSON?.error || "Failed.",
          "danger",
        ),
      )
      .always(() => btn.prop("disabled", false).text("Update Password"));
  });

  // ── Delete listing (quick delete from dashboard) ───────────
  window.confirmDelete = function (id, title) {
    if (!confirm(`Delete "${title}"? This cannot be undone.`)) return;

    $.post(BASE_URL + "../api/products/delete-product.php", { product_id: id })
      .done((res) => {
        if (res.success) {
          loadDashboard(); // reload to reflect deletion
        } else {
          alert(res.error || "Could not delete.");
        }
      })
      .fail(() => alert("Failed to delete. Try again."));
  };

  // ── Avatar dropdown ────────────────────────────────────────
  $("#avatarToggle").on("click", function (e) {
    e.stopPropagation();
    $("#avatarDropdown").toggleClass("open");
  });
  $(document).on("click", () => $("#avatarDropdown").removeClass("open"));

  // ── Helpers ────────────────────────────────────────────────
  function showFormAlert(id, msg, type) {
    $("#" + id)
      .removeClass("d-none alert-success alert-danger")
      .addClass("alert alert-" + type)
      .text(msg);
    if (type === "success") {
      setTimeout(() => $("#" + id).addClass("d-none"), 4000);
    }
  }

  function esc(str) {
    return String(str || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  // ── Init ──────────────────────────────────────────────────
  loadDashboard();
});
