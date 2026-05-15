$(function () {
  // ================= TAB SWITCHING ====================
  function switchTab(tab) {
    // 1. Hide both forms
    $("#loginForm, #registerForm").addClass("d-none");

    // 2. Remove 'active' from all buttons with the class .tab-btn
    $(".tab-btn").removeClass("active");

    if (tab === "register") {
      // 3. Show register form and highlight register button
      $("#registerForm").removeClass("d-none");
      $('.tab-btn[data-tab="register"]').addClass("active");

      // Update the sub-text link and heading (Optional but nice)
      $("#cardHeading").text("Create Account");
      $("#cardSub").html(
        'Already have an account? <a href="#" class="switch-tab" data-tab="login">Login</a>',
      );
    } else {
      // 4. Show login form and highlight login button
      $("#loginForm").removeClass("d-none");
      $('.tab-btn[data-tab="login"]').addClass("active");

      // Update the sub-text link and heading
      $("#cardHeading").text("Welcome Back");
      $("#cardSub").html(
        'Don\'t have an account yet? <a href="#" class="switch-tab" data-tab="register">Sign up</a>',
      );
    }

    hideAlert();
  }

  // GLOBAL CLICK LISTENER
  $(document).on("click", "[data-tab], .switch-tab", function (e) {
    e.preventDefault();
    const targetTab = $(this).data("tab");
    switchTab(targetTab);
  });

  // ================= ALERT HELPERS ===================
  function showAlert(msg, type = "danger") {
    $("#authAlert")
      .removeClass("d-none alert-danger alert-success alert-warning")
      .addClass("alert-" + type)
      .text(msg);
  }

  function hideAlert() {
    $("#authAlert").addClass("d-none").text("");
  }

  function setLoading(btn, loading, originalText = "Submit") {
    if (loading) {
      btn
        .prop("disabled", true)
        .html(
          '<span class="spinner-border spinner-border-sm me-2"></span>Please wait...',
        );
    } else {
      btn.prop("disabled", false).text(originalText);
    }
  }

  // ================= LOGIN  ====================
  $("#loginForm").on("submit", function (e) {
    e.preventDefault();
    hideAlert();

    const btn = $(this).find('button[type="submit"]');
    const originalText = "Login"; // Matches your HTML button text
    setLoading(btn, true);

    $.post("../api/auth/login.php", $(this).serialize())
      .done(function (res) {
        if (res.success) {
          showAlert("Login successful! Redirecting...", "success");
          // Redirect to the URL provided by your PHP script
          setTimeout(() => (window.location.href = res.redirect), 800);
        } else {
          showAlert(res.error || "Login failed.");
          setLoading(btn, false, originalText); // Reset button
        }
      })
      .fail(function (xhr) {
        const res = xhr.responseJSON;
        showAlert(res?.error || "Connection error. Please try again.");
        setLoading(btn, false, originalText); // Reset button
      });
  });

  // ================= Register  =================
  $("#registerForm").on("submit", function (e) {
    e.preventDefault();
    hideAlert();

    const btn = $(this).find('button[type="submit"]');
    const originalText = "Create Account"; // Matches your HTML button text
    setLoading(btn, true);

    $.post("../api/auth/register.php", $(this).serialize())
      .done(function (res) {
        if (res.success) {
          showAlert("Account created! Redirecting...", "success");
          setTimeout(() => (window.location.href = res.redirect), 800);
        } else {
          showAlert(res.error || "Registration failed.");
          setLoading(btn, false, originalText); // Reset button
        }
      })
      .fail(function (xhr) {
        const res = xhr.responseJSON;
        showAlert(res?.error || "Registration error. Please try again.");
        setLoading(btn, false, originalText); // Reset button
      });
  });
});
