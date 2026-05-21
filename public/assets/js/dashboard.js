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
        updateAvatarDisplay(res.user.avatar_url, res.user.name);
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
        updateProfileAvatarDisplay(res.user.avatar_url, res.user.name);
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

  // ── Avatar display helpers ─────────────────────────────────
  function updateAvatarDisplay(url, name) {
    const avatar = $("#dbAvatar");
    if (url) {
      avatar.html(`<img src="${url}" alt="Avatar">`);
    } else {
      avatar.text(name.charAt(0).toUpperCase());
    }
  }

  function updateProfileAvatarDisplay(url, name) {
    const preview = $("#profileAvatarPreview");
    if (url) {
      // Keep the overlay, add the image behind it
      preview.prepend(`<img src="${url}" alt="Avatar"
                         style="position:absolute;inset:0;width:100%;
                                height:100%;object-fit:cover;border-radius:50%;">`);
    } else {
      preview.prepend(`<span style="font-size:2rem;font-weight:700;color:#fff">
                       ${name.charAt(0).toUpperCase()}</span>`);
    }
  }

  // ── HERO AVATAR CLICK ──
  $("#heroAvatarWrap").on("click", function (e) {
    // Only trigger if we didn't click the input itself
    if (e.target.id !== "avatarFileInput") {
      $("#avatarFileInput").trigger("click");
    }
  });

  // ── PROFILE TAB AVATAR CLICK ──
  $("#profileAvatarPreview").on("click", function (e) {
    if (e.target.id !== "profileAvatarInput") {
      $("#profileAvatarInput").trigger("click");
    }
  });

  // ── SHIELD: Stop bubbling from inputs ──
  $("#avatarFileInput, #profileAvatarInput").on("click", function (e) {
    e.stopPropagation();
  });

  // ── HANDLE FILE SELECTION (Hero) ──
  $("#avatarFileInput").on("change", function () {
    const file = this.files[0];
    if (!file) return;

    // Show preview in hero immediately
    const reader = new FileReader();
    reader.onload = (e) => {
      $("#dbAvatar").html(`<img src="${e.target.result}" alt="Avatar">`);
    };
    reader.readAsDataURL(file);

    // Sync to the actual form input WITHOUT triggering another change event
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);
    $("#profileAvatarInput")[0].files = dataTransfer.files;

    // Auto-submit the profile form
    submitProfileWithAvatar();
  });

  // ── HANDLE FILE SELECTION (Profile Tab) ──
  $("#profileAvatarInput").on("change", function () {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
      // Update the Profile Tab Preview
      $("#profileAvatarPreview img, #profileAvatarPreview span").remove();
      $("#profileAvatarPreview").prepend(
        `<img src="${e.target.result}" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:50%;">`,
      );
      // Also update the Hero Preview
      $("#dbAvatar").html(`<img src="${e.target.result}" alt="Avatar">`);
    };
    reader.readAsDataURL(file);
  });

  // ── Profile form submit (with FormData for file) ──────────
  $("#profileForm").on("submit", function (e) {
    e.preventDefault();
    submitProfileWithAvatar();
  });

  function submitProfileWithAvatar() {
    const btn = $("#profileForm button[type=submit]");
    const formData = new FormData($("#profileForm")[0]);
    btn.prop("disabled", true).text("Saving...");

    $.ajax({
      url: BASE_URL + "../api/user/update-profile.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
    })
      .done((res) => {
        if (res.success) {
          showFormAlert("profileAlert", "✅ Profile updated!", "success");
          $("#dbName").text($("#profileName").val());
          // Update hero avatar if new URL returned
          if (res.avatar_url) {
            updateAvatarDisplay(res.avatar_url, $("#profileName").val());
            updateProfileAvatarDisplay(res.avatar_url, $("#profileName").val());
          }
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
  }

  // ── Init ──────────────────────────────────────────────────
  loadDashboard();
});
