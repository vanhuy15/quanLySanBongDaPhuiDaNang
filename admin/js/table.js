/**
 * ADMIN TABLE MODULE
 * Staggered fade & slide-in animations for admin tables
 */

function animateTables() {
  const rows = document.querySelectorAll(".admin-table tbody tr");
  rows.forEach((row, index) => {
    row.style.opacity = "0";
    row.style.transform = "translateY(15px)";
    setTimeout(() => {
      row.style.transition = "all 0.35s cubic-bezier(0.4, 0, 0.2, 1)";
      row.style.opacity = "1";
      row.style.transform = "translateY(0)";
    }, index * 40);
  });
}
