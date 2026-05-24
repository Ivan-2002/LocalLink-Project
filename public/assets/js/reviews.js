// Requires these constants from product.php:
//   BASE_URL, PRODUCT_ID, IS_LOGGED, SELLER_ID, IS_OWN_LISTING

const REVIEWS_API = BASE_URL + "../api/reviews/";
const RATING_LABELS = ["", "Poor", "Fair", "Good", "Very Good", "Excellent"];

let selectedRating = 0;
let userHasReviewed = false;

// ══════════════════════════════════════════════
// LOAD SELLER REVIEWS
// ══════════════════════════════════════════════
function loadReviews() {
  $.get(REVIEWS_API + "get-reviews.php", { seller_id: SELLER_ID })
    .done((res) => {
      if (!res.success) return;
      userHasReviewed = res.user_reviewed;
      renderReviewSummary(res.stats);
      renderBreakdown(res.stats);
      renderReviews(res.reviews);
      updateWriteButton();
    })
    .fail(() => {
      $("#reviewsList").html(
        '<p class="text-muted">Could not load reviews.</p>',
      );
    });
}

// ── Summary ────────────────────────────────────────────────
function renderReviewSummary(stats) {
  const avg = parseFloat(stats.avg_rating) || 0;
  const count = parseInt(stats.review_count) || 0;

  $("#rvAvgScore").text(avg > 0 ? avg.toFixed(1) : "—");
  $("#rvAvgStars").html(buildStars(avg));
  $("#rvCount").text(
    count > 0
      ? count + " review" + (count !== 1 ? "s" : "") + " of this seller"
      : "No seller reviews yet",
  );

  // Also update seller card stars on the product page
  if (typeof renderStars === "function") renderStars(avg);
  $("#reviewCount").text(
    count > 0
      ? count + " seller review" + (count !== 1 ? "s" : "")
      : "No reviews yet",
  );
}

// ── Breakdown bars ──────────────────────────────────────────
function renderBreakdown(stats) {
  const total = parseInt(stats.review_count) || 0;
  if (!total) {
    $("#rvBreakdown").empty();
    return;
  }

  const counts = {
    5: parseInt(stats.five_star) || 0,
    4: parseInt(stats.four_star) || 0,
    3: parseInt(stats.three_star) || 0,
    2: parseInt(stats.two_star) || 0,
    1: parseInt(stats.one_star) || 0,
  };

  let html = "";
  for (let i = 5; i >= 1; i--) {
    const pct = Math.round((counts[i] / total) * 100);
    html += `
      <div class="rv-breakdown-row">
        <span class="rv-breakdown-label">${i} ★</span>
        <div class="rv-breakdown-bar-wrap">
          <div class="rv-breakdown-bar" style="width:${pct}%"></div>
        </div>
        <span class="rv-breakdown-count">${counts[i]}</span>
      </div>`;
  }
  $("#rvBreakdown").html(html);
}

// ── Review cards ────────────────────────────────────────────
function renderReviews(reviews) {
  const list = $("#reviewsList");

  if (!reviews || !reviews.length) {
    list.html(`
      <div class="rv-empty">
        <div class="rv-empty-icon">⭐</div>
        <div class="rv-empty-title">No seller reviews yet</div>
        <div class="rv-empty-sub">
          Be the first to review this seller!
        </div>
      </div>`);
    return;
  }

  const html = reviews
    .map((r) => {
      const avatarHtml = r.avatar_url
        ? `<img src="${r.avatar_url}" alt="${esc(r.buyer_name)}">`
        : r.initial;

      return `
      <div class="review-item">
        <div class="review-header">
          <div class="review-author-row">
            <div class="review-avatar">${avatarHtml}</div>
            <div>
              <div class="review-author">${esc(r.buyer_name)}</div>
              <div class="review-date">${r.date_formatted}</div>
            </div>
          </div>
          <div class="review-stars">${buildStars(r.rating)}</div>
        </div>
        ${
          r.comment
            ? `<p class="review-text">${esc(r.comment)}</p>`
            : `<p class="review-text" style="color:#b5a48a;font-style:italic">
               No comment left.
             </p>`
        }
      </div>`;
    })
    .join("");

  list.html(html);
}

// ── Build star HTML ─────────────────────────────────────────
function buildStars(rating) {
  let html = "";
  for (let i = 1; i <= 5; i++) {
    html += `<span class="star ${i <= Math.round(rating) ? "" : "empty"}">★</span>`;
  }
  return html;
}

// ── Write button state ──────────────────────────────────────
function updateWriteButton() {
  const btn = $("#openReviewModal");

  // Hide button entirely on own listing
  if (IS_OWN_LISTING) {
    btn.hide();
    return;
  }

  if (!IS_LOGGED) {
    btn
      .text("Login to Review")
      .off("click")
      .on("click", () => {
        window.location.href = BASE_URL + "login.php";
      });
  } else if (userHasReviewed) {
    btn.text("✅ You reviewed this seller").prop("disabled", true);
  } else {
    btn.text("✍️ Review this Seller").prop("disabled", false);
  }
}

// ══════════════════════════════════════════════
// STAR PICKER
// ══════════════════════════════════════════════
$(document).on("mouseenter", ".rv-star-pick", function () {
  highlightStars($(this).data("value"));
  $("#ratingLabel")
    .text(RATING_LABELS[$(this).data("value")])
    .addClass("rated");
});

$(document).on("mouseleave", ".rv-star-picker", function () {
  highlightStars(selectedRating);
  if (!selectedRating) {
    $("#ratingLabel").text("Click to rate").removeClass("rated");
  } else {
    $("#ratingLabel").text(RATING_LABELS[selectedRating]);
  }
});

$(document).on("click", ".rv-star-pick", function () {
  selectedRating = $(this).data("value");
  $("#ratingInput").val(selectedRating);
  highlightStars(selectedRating);
  $("#ratingLabel").text(RATING_LABELS[selectedRating]).addClass("rated");
});

function highlightStars(upTo) {
  $(".rv-star-pick").each(function () {
    const v = $(this).data("value");
    $(this).toggleClass("hovered", v <= upTo && upTo > 0);
    $(this).toggleClass("selected", v <= selectedRating);
  });
}

// ══════════════════════════════════════════════
// OPEN / CLOSE MODAL
// ══════════════════════════════════════════════
$("#openReviewModal").on("click", function () {
  if (!IS_LOGGED) {
    window.location.href = BASE_URL + "login.php";
    return;
  }
  if (userHasReviewed || IS_OWN_LISTING) return;

  // Reset
  selectedRating = 0;
  $("#ratingInput").val(0);
  $("#reviewComment").val("");
  $("#charCount").text("0");
  $("#ratingLabel").text("Click to rate").removeClass("rated");
  $(".rv-star-pick").removeClass("hovered selected");
  hideReviewAlert();

  // Show seller name in modal subtitle
  $("#reviewProductName").text("Reviewing seller: " + $("#sellerName").text());

  $("#reviewModal").removeClass("d-none");
});

$("#closeReviewModal, #closeReviewModal2").on("click", () => {
  $("#reviewModal").addClass("d-none");
});
$("#reviewModal").on("click", function (e) {
  if ($(e.target).is("#reviewModal")) $(this).addClass("d-none");
});

// Char counter
$("#reviewComment").on("input", function () {
  $("#charCount").text($(this).val().length);
});

// ══════════════════════════════════════════════
// SUBMIT REVIEW
// ══════════════════════════════════════════════
$("#reviewForm").on("submit", function (e) {
  e.preventDefault();
  hideReviewAlert();

  if (!selectedRating) {
    showReviewAlert("Please select a star rating.", "danger");
    return;
  }

  const btn = $("#submitReviewBtn");
  btn.prop("disabled", true).text("Submitting...");

  $.post(REVIEWS_API + "add-review.php", {
    seller_id: SELLER_ID, // ← seller not product
    rating: selectedRating,
    comment: $("#reviewComment").val().trim(),
  })
    .done((res) => {
      if (res.success) {
        showReviewAlert("✅ " + res.message, "success");
        userHasReviewed = true;
        updateWriteButton();
        prependNewReview(res.review);
        renderReviewSummary({
          avg_rating: res.avg_rating,
          review_count: res.review_count,
        });
        setTimeout(() => $("#reviewModal").addClass("d-none"), 1400);
      } else {
        showReviewAlert(res.error || "Submission failed.", "danger");
        btn.prop("disabled", false).text("Submit Review");
      }
    })
    .fail((xhr) => {
      showReviewAlert(
        xhr.responseJSON?.error || "Something went wrong.",
        "danger",
      );
      btn.prop("disabled", false).text("Submit Review");
    });
});

// Add new review to top of list immediately
function prependNewReview(r) {
  const today = new Date().toLocaleDateString("en-ZA", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
  $(".rv-empty").remove();
  $("#reviewsList").prepend(`
    <div class="review-item" style="animation:fadeUp .35s ease both">
      <div class="review-header">
        <div class="review-author-row">
          <div class="review-avatar">
            ${esc(r.buyer_name.charAt(0).toUpperCase())}
          </div>
          <div>
            <div class="review-author">${esc(r.buyer_name)}</div>
            <div class="review-date">${today}</div>
          </div>
        </div>
        <div class="review-stars">${buildStars(r.rating)}</div>
      </div>
      ${
        r.comment
          ? `<p class="review-text">${esc(r.comment)}</p>`
          : `<p class="review-text" style="color:#b5a48a;font-style:italic">No comment left.</p>`
      }
    </div>`);
}

// ── Alert helpers ───────────────────────────────────────────
function showReviewAlert(msg, type) {
  $("#reviewAlert")
    .removeClass("d-none alert-success alert-danger")
    .addClass("alert alert-" + type)
    .text(msg);
}
function hideReviewAlert() {
  $("#reviewAlert").addClass("d-none").text("");
}
function esc(s) {
  return String(s || "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}
