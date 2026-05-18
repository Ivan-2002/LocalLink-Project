// Fetches product data from API and renders the detail page

$(function () {
  const API = BASE_URL + "../api/products/get-product.php?id=" + PRODUCT_ID;

  // ── Load product
  $.get(API)
    .done((res) => {
      if (!res.success) return showNotFound();
      renderProduct(res.product);
    })
    .fail(() => showNotFound());

  // ── Render
  function renderProduct(p) {
    // Update page title
    document.title = p.title + " — LocalLink";

    // Breadcrumb
    if (p.category_name) {
      $("#breadcrumbCat").text(p.category_name);
    }

    // Main image
    if (p.image_url) {
      $("#mainImage").attr("src", p.image_url).attr("alt", p.title);
    } else {
      $("#mainImage").replaceWith(
        '<div class="pd-main-img-placeholder">🛍️</div>',
      );
    }

    // Thumbnails — show main image + placeholder extras
    renderThumbnails(p);

    // Title, price, location, description
    $("#pdTitle").text(p.title);
    $("#pdPrice").text(p.price_formatted);
    $("#pdLocation").text(p.location || "Location not specified");
    $("#pdDescription").text(p.description || "No description provided.");

    // Category badge
    if (p.category_name) {
      $(".pd-badges").append(
        `<span class="badge-category">${esc(p.category_name)}</span>`,
      );
    }

    // Seller card
    $("#sellerAvatar").text(p.seller_initial);
    $("#sellerName").text(p.seller_name);
    $("#sellerLocation").text(p.seller_location || p.location || "");

    // Stars
    renderStars(p.avg_rating);
    const reviewLabel =
      p.review_count > 0
        ? `${p.review_count} review${p.review_count > 1 ? "s" : ""}`
        : "No reviews yet";
    $("#reviewCount").text(reviewLabel);

    // Message button — link to messages with seller pre-selected
    $("#btnMessage").attr(
      "href",
      IS_LOGGED
        ? `messages.php?to=${p.seller_id}&product=${p.id}`
        : `login.php`,
    );

    // Reviews
    renderReviews(p.reviews);

    // Show page, hide loader
    $("#pageLoader").addClass("d-none");
    $("#productDetail").removeClass("d-none");
  }

  // ── Thumbnails ─────────────────────────────────────────────
  function renderThumbnails(p) {
    const strip = $("#thumbnailStrip");
    strip.empty();

    if (p.image_url) {
      // Main image thumb (active by default)
      const thumb = $(`
        <div class="pd-thumb active">
          <img src="${p.image_url}" alt="thumb">
        </div>`);
      thumb.on("click", () => {
        $("#mainImage").attr("src", p.image_url);
        $(".pd-thumb").removeClass("active");
        thumb.addClass("active");
      });
      strip.append(thumb);

      // Placeholder thumbs (show product image repeated — in real app
      // you'd have multiple images per product)
      for (let i = 1; i < 3; i++) {
        const extra = $(`
          <div class="pd-thumb">
            <img src="${p.image_url}" alt="thumb ${i + 1}">
          </div>`);
        extra.on("click", function () {
          $("#mainImage").attr("src", p.image_url);
          $(".pd-thumb").removeClass("active");
          $(this).addClass("active");
        });
        strip.append(extra);
      }

      // "+2" more button (decorative for now)
      strip.append('<div class="pd-thumb-more">+2</div>');
    }
  }

  // ── Star rating ────────────────────────────────────────────
  function renderStars(rating) {
    const starRow = $("#starRow");
    starRow.empty();
    for (let i = 1; i <= 5; i++) {
      const filled = i <= Math.round(rating);
      starRow.append(`<span class="star ${filled ? "" : "empty"}">★</span>`);
    }
  }

  // ── Reviews ────────────────────────────────────────────────
  function renderReviews(reviews) {
    const list = $("#reviewsList");
    if (!reviews || reviews.length === 0) {
      list.html(
        '<p class="text-muted" style="font-size:.9rem">No reviews yet. Be the first to review this product!</p>',
      );
      return;
    }

    const html = reviews
      .map((r) => {
        const stars = "★".repeat(r.rating) + "☆".repeat(5 - r.rating);
        const date = new Date(r.created_at).toLocaleDateString("en-ZA", {
          day: "numeric",
          month: "short",
          year: "numeric",
        });
        return `
        <div class="review-item">
          <div class="review-header">
            <span class="review-author">${esc(r.buyer_name)}</span>
            <span class="review-date">${date}</span>
          </div>
          <div class="review-stars">${stars}</div>
          <div class="review-text">${esc(r.comment || "")}</div>
        </div>`;
      })
      .join("");

    list.html(html);
  }

  // ── Wishlist button ────────────────────────────────────────
  $("#btnWishlist").on("click", function () {
    if (!IS_LOGGED) {
      window.location.href = BASE_URL + "login.php";
      return;
    }
    const btn = $(this);
    if (btn.hasClass("wishlisted")) {
      btn.removeClass("wishlisted").text("🤍 Add to wishlist");
    } else {
      btn.addClass("wishlisted").text("❤️ Wishlisted");
    }
  });

  // ── Make Offer button ──────────────────────────────────────
  $("#btnOffer").on("click", function () {
    if (!IS_LOGGED) {
      window.location.href = BASE_URL + "login.php";
      return;
    }
    // For now show a simple prompt — can be upgraded to a modal later
    alert(
      "Offer feature coming soon! Use Send Message to negotiate with the seller.",
    );
  });

  // ── Share button ───────────────────────────────────────────
  $(".btn-share").on("click", function () {
    if (navigator.share) {
      navigator.share({ title: document.title, url: window.location.href });
    } else {
      navigator.clipboard.writeText(window.location.href);
      alert("Link copied to clipboard!");
    }
  });

  // ── Avatar dropdown ────────────────────────────────────────
  $("#avatarToggle").on("click", function (e) {
    e.stopPropagation();
    $("#avatarDropdown").toggleClass("open");
  });
  $(document).on("click", () => $("#avatarDropdown").removeClass("open"));

  // ── Not found ─────────────────────────────────────────────
  function showNotFound() {
    $("#pageLoader").addClass("d-none");
    $("#notFound").removeClass("d-none");
  }

  // ── Helper: escape HTML ────────────────────────────────────
  function esc(str) {
    return String(str || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }
});
