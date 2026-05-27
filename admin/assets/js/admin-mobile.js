// Handles the admin sidebar drawer on mobile

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
    $("#adminMenuToggle").addClass("open");
  }

  function closeSidebar() {
    $(".sidebar").removeClass("open");
    $("#adminSidebarOverlay").removeClass("open");
    $("body").css("overflow", "");
    $("#adminMenuToggle").removeClass("open");
  }

  // Toggle sidebar on hamburger click
  $(document).on("click", "#adminMenuToggle", function () {
    $(".sidebar").hasClass("open") ? closeSidebar() : openSidebar();
  });

  // Close sidebar when clicking overlay
  $(document).on("click", "#adminSidebarOverlay", closeSidebar);

  // Close sidebar on Escape key
  $(document).on("keydown", function (e) {
    if (e.key === "Escape") closeSidebar();
  });

  // Close after nav click on mobile
  $(document).on("click", ".sidebar nav a", function () {
    if (window.innerWidth <= 768) setTimeout(closeSidebar, 150);
  });
});
