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

    // Populate buy form product ID
    $("#buyProductId").val(p.id);

    // Reviews
    // renderReviews(p.reviews);

    // Show page, hide loader
    // $("#pageLoader").addClass("d-none");
    // $("#productDetail").removeClass("d-none");

    // Show Edit / Delete buttons only to owner or admin
    if (IS_LOGGED && (USER_ID === p.seller_id || USER_ROLE === "admin")) {
      $(".pd-actions").append(
        `<a href="${BASE_URL}seller/edit-product.php?id=${p.id}"class="btn-edit-listing">✏️ Edit Listing</a>`,
      );
    }

    // ── Hide buyer actions if the logged-in user is the seller ──
    if (IS_LOGGED && USER_ID === p.seller_id) {
      $("#btnMessage").hide();
      $("#btnOffer").hide();

      // Optional visual touch: replace them with an owner badge or notification
      $(".seller-card").append(
        '<div class="text-muted text-center mt-2 small" style="font-style: italic;">✨ You are the seller of this item</div>',
      );
    }
    // Set seller ID for reviews system
    window.SELLER_ID = parseInt(p.seller_id);
    window.IS_OWN_LISTING = IS_LOGGED && USER_ID === parseInt(p.seller_id);

    // Show page, hide loader — BEFORE calling loadReviews
    $("#pageLoader").addClass("d-none");
    $("#productDetail").removeClass("d-none");

    // Load seller reviews — MUST be last
    loadReviews();
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
  // function renderReviews(reviews) {
  //   const list = $("#reviewsList");
  //   if (!reviews || reviews.length === 0) {
  //     list.html(
  //       '<p class="text-muted" style="font-size:.9rem">No reviews yet. Be the first to review this product!</p>',
  //     );
  //     return;
  //   }

  //   const html = reviews
  //     .map((r) => {
  //       const stars = "★".repeat(r.rating) + "☆".repeat(5 - r.rating);
  //       const date = new Date(r.created_at).toLocaleDateString("en-ZA", {
  //         day: "numeric",
  //         month: "short",
  //         year: "numeric",
  //       });
  //       return `
  //       <div class="review-item">
  //         <div class="review-header">
  //           <span class="review-author">${esc(r.buyer_name)}</span>
  //           <span class="review-date">${date}</span>
  //         </div>
  //         <div class="review-stars">${stars}</div>
  //         <div class="review-text">${esc(r.comment || "")}</div>
  //       </div>`;
  //     })
  //     .join("");

  //   list.html(html);
  // }

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

  // ── PHOTO UPLOAD LOGIC ──
  const photoArea = $("#photoArea");

  // Only run this if we are on a page with a photo upload area
  if (photoArea.length > 0) {
    const imageInput = $("#imageInput");
    const imagePreview = $("#imagePreview");
    const uploadPrompt = $("#uploadPrompt");
    const removeImage = $("#removeImage");

    // 1. Trigger the hidden file input
    photoArea.on("click", function (e) {
      // Only trigger if we didn't click the 'Remove' button specifically
      if (!$(e.target).closest("#removeImage").length) {
        imageInput.trigger("click");
      }
    });

    // 2. Handle Image Selection & Preview
    imageInput.on("change", function () {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          imagePreview.attr("src", e.target.result).removeClass("d-none");
          uploadPrompt.addClass("d-none");
          removeImage.removeClass("d-none");
        };
        reader.readAsDataURL(file);
      }
    });

    // 3. Handle Remove
    removeImage.on("click", function (e) {
      e.stopPropagation();
      imageInput.val(""); // Clear the actual file from the input
      imagePreview.attr("src", "").addClass("d-none");
      uploadPrompt.removeClass("d-none");
      removeImage.addClass("d-none");
    });

    // ── DRAG AND DROP ADDITION ──
    photoArea.on("dragover", function (e) {
      e.preventDefault();
      $(this).addClass("drag-active"); // Add a CSS class for visual feedback
    });

    photoArea.on("dragleave", function () {
      $(this).removeClass("drag-active");
    });

    photoArea.on("drop", function (e) {
      e.preventDefault();
      $(this).removeClass("drag-active");
      const files = e.originalEvent.dataTransfer.files;
      if (files.length > 0) {
        imageInput[0].files = files; // Assign files to the input
        imageInput.trigger("change"); // Trigger the preview logic
      }
    });
  }
});
