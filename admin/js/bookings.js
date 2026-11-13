/**
 * ADMIN BOOKINGS MODULE JAVASCRIPT (admin/js/bookings.js)
 * Manages AJAX Table Loading, Status Updating, and Soft Delete
 */

let currentBookingStatus = "tat_ca";

function getAdminCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute("content") : "";
}

function loadBookings(status) {
  currentBookingStatus = status;
  const tbody = document.getElementById("bookings-tbody");
  if (tbody) {
    tbody.innerHTML = '<tr><td colspan="7">Đang tải dữ liệu...</td></tr>';
  }

  fetch("index.php?controller=Admin&action=getBookingsData&status=" + encodeURIComponent(status))
    .then((res) => res.text())
    .then((html) => {
      if (tbody) tbody.innerHTML = html;
    })
    .catch((err) => {
      console.error(err);
      if (tbody) {
        tbody.innerHTML = '<tr><td colspan="7" style="color: #dc2626;">Lỗi khi tải dữ liệu!</td></tr>';
      }
    });
}

function updateStatus(id, action) {
  if (!confirm("Chắc chắn thực hiện hành động này?")) return;

  const csrfToken = getAdminCsrfToken();
  const formData = new FormData();
  formData.append("id", id);
  formData.append("action", action);
  formData.append("csrf_token", csrfToken);

  fetch("index.php?controller=Admin&action=updateBookingStatus", {
    method: "POST",
    headers: { "X-CSRF-TOKEN": csrfToken },
    body: formData,
  })
    .then(() => loadBookings(currentBookingStatus))
    .catch((err) => {
      console.error(err);
      alert("Lỗi khi cập nhật trạng thái!");
    });
}

function deleteBooking(id) {
  if (
    !confirm(
      "Bạn có chắc chắn muốn xóa đơn này (Soft Delete)? Dữ liệu sẽ được ẩn đi nhưng vẫn còn trong Database."
    )
  )
    return;

  const csrfToken = getAdminCsrfToken();
  const formData = new FormData();
  formData.append("id", id);
  formData.append("csrf_token", csrfToken);

  fetch("index.php?controller=Admin&action=deleteBooking", {
    method: "POST",
    headers: { "X-CSRF-TOKEN": csrfToken },
    body: formData,
  })
    .then(() => loadBookings(currentBookingStatus))
    .catch((err) => {
      console.error(err);
      alert("Lỗi khi xóa đơn đặt sân!");
    });
}

document.addEventListener("DOMContentLoaded", function () {
  if (document.getElementById("bookings-tbody")) {
    loadBookings("tat_ca");
  }
});

