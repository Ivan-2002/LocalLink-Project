// admin/assets/js/admin-mobile.js
// Handles the admin sidebar drawer on mobile
// Include on ALL admin pages after jQuery

$(function () {
  // Inject overlay if not present
  if (!$("#adminSidebarOverlay").length) {
    $("body").append(
      '<div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>',
    );
  }

  function openSidebar() {
    $(".sidebar").addClass("open");
    $("#adminSidebarOverlay").addClass("open");
    $("body").css("overflow", "hidden");
    $(".admin-hamburger").addClass("open");
  }

  function closeSidebar() {
    $(".sidebar").removeClass("open");
    $("#adminSidebarOverlay").removeClass("open");
    $("body").css("overflow", "");
    $(".admin-hamburger").removeClass("open");
  }

  $(document).on("click", ".admin-hamburger", function () {
    $(".sidebar").hasClass("open") ? closeSidebar() : openSidebar();
  });

  $(document).on("click", "#adminSidebarOverlay", closeSidebar);
  $(document).on("keydown", function (e) {
    if (e.key === "Escape") closeSidebar();
  });

  // Close after nav click on mobile
  $(document).on("click", ".sidebar nav a", function () {
    if (window.innerWidth <= 768) setTimeout(closeSidebar, 150);
  });
});
