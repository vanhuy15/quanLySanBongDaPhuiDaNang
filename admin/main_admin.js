/**
 * ADMIN MASTER JAVASCRIPT
 * Modular entry point and bootstrap coordinator
 */

document.addEventListener("DOMContentLoaded", function () {
  // 1. Table animations
  if (typeof animateTables === "function") {
    animateTables();
  }

  // 2. Custom confirmation modals
  if (typeof setupConfirmActions === "function") {
    setupConfirmActions();
  }
  if (typeof setupLogoutConfirmAdmin === "function") {
    setupLogoutConfirmAdmin();
  }

  // 3. Floating messages / alerts
  if (typeof autoHideAdminMessages === "function") {
    autoHideAdminMessages();
  }

  // 4. Analytics & Charts
  if (typeof setupDashboardChart === "function") {
    setupDashboardChart();
  }
  if (typeof setupRevenueReportChart === "function") {
    setupRevenueReportChart();
  }

  // 5. Khung gio auto end time
  setupKhungGioAutoCalc();

  // 6. Global outside click handler for dropdowns
  document.addEventListener("click", function (e) {
    const notifMenu = document.getElementById("notifDropdownMenu");
    if (notifMenu && !notifMenu.contains(e.target) && !e.target.closest("#notifBellBtn")) {
      notifMenu.classList.remove("show");
    }
  });
});

/**
 * Admin Logout confirmation
 */
function handleAdminLogout(e, url) {
  if (e) e.preventDefault();
  if (confirm("Bạn có chắc chắn muốn đăng xuất khỏi hệ thống quản trị?")) {
    window.location.href = url || "/quanlysanbong/admin/logout.php";
  }
}

/**
 * Notifications dropdown toggle
 */
function toggleNotifDropdown(e) {
  if (e) e.stopPropagation();
  const menu = document.getElementById("notifDropdownMenu");
  if (menu) {
    menu.classList.toggle("show");
  }
}

/**
 * Toggle Add Pitch Form (Sân bóng)
 */
function toggleAddForm() {
  const container = document.getElementById("addPitchFormContainer");
  if (!container) return;
  if (container.style.display === "none" || container.style.display === "") {
    container.style.display = "block";
    container.scrollIntoView({ behavior: "smooth" });
  } else {
    container.style.display = "none";
  }
}

/**
 * Toggle ghép sân quy tắc box
 */
function toggleQuyTacGhep(val) {
  const box = document.getElementById("quyTacGhepBox");
  if (box) {
    box.style.display = val === "san_5_7" ? "block" : "none";
  }
}

/**
 * Khung giờ auto calc end time (+1h)
 */
function setupKhungGioAutoCalc() {
  const startTimeInput = document.getElementById("gio_bat_dau");
  const endTimeInput = document.getElementById("gio_ket_thuc");

  if (startTimeInput && endTimeInput) {
    startTimeInput.addEventListener("change", function () {
      if (this.value) {
        let [hours, minutes] = this.value.split(":").map(Number);
        hours = (hours + 1) % 24;
        let newHours = hours.toString().padStart(2, "0");
        let newMinutes = minutes.toString().padStart(2, "0");
        endTimeInput.value = `${newHours}:${newMinutes}`;
      }
    });
  }
}
