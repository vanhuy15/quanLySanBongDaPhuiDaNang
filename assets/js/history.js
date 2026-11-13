/**
 * BOOKING HISTORY MODULE JAVASCRIPT (assets/js/history.js)
 * Manages Date Filtering Constraints, Review Submission Modal, and Cancellation / Refund Requests
 */

document.addEventListener("DOMContentLoaded", function () {
  initDateConstraints();
  initStarRatingEvents();
});

function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute("content") : "";
}

function initDateConstraints() {
  const tuNgay = document.getElementById("tu_ngay");
  const denNgay = document.getElementById("den_ngay");

  if (!tuNgay || !denNgay) return;

  function updateDateConstraints() {
    if (tuNgay.value) denNgay.min = tuNgay.value;
    else denNgay.min = "";

    if (denNgay.value) tuNgay.max = denNgay.value;
    else tuNgay.max = "";
  }

  tuNgay.addEventListener("change", updateDateConstraints);
  denNgay.addEventListener("change", updateDateConstraints);
  updateDateConstraints();
}

const starLabels = {
  1: "1/5 - Rất tệ",
  2: "2/5 - Tạm được",
  3: "3/5 - Bình thường",
  4: "4/5 - Tốt, hài lòng",
  5: "5/5 - Rất tuyệt vời!",
};

function initStarRatingEvents() {
  document.querySelectorAll(".star-item").forEach((s) => {
    s.addEventListener("click", function () {
      const val = parseInt(this.getAttribute("data-val"), 10);
      setRating(val);
    });
  });
}

function setRating(val) {
  const soSaoInput = document.getElementById("modalSoSao");
  const starLabel = document.getElementById("starLabel");
  if (soSaoInput) soSaoInput.value = val;
  if (starLabel) starLabel.textContent = starLabels[val] || `${val}/5`;

  const stars = document.querySelectorAll(".star-item");
  stars.forEach((s) => {
    const sVal = parseInt(s.getAttribute("data-val"), 10);
    if (sVal <= val) {
      s.style.color = "#f59e0b";
    } else {
      s.style.color = "#e2e8f0";
    }
  });
}

function openReviewModal(datSanId, sanId, tenSan, soSao = 5, noiDung = "") {
  document.getElementById("modalDatSanId").value = datSanId;
  document.getElementById("modalSanId").value = sanId;
  document.getElementById("modalPitchName").textContent = "Sân: " + tenSan;
  document.getElementById("modalNoiDung").value = noiDung;
  setRating(soSao);

  const modal = document.getElementById("reviewModal");
  if (modal) modal.style.display = "flex";
}

function closeReviewModal() {
  const modal = document.getElementById("reviewModal");
  if (modal) modal.style.display = "none";
}

function submitReview(e) {
  e.preventDefault();
  const form = document.getElementById("reviewForm");
  if (!form) return;
  const formData = new FormData(form);
  const btn = document.getElementById("submitReviewBtn");
  const originalText = btn ? btn.innerHTML : "";
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
  }

  fetch("index.php?controller=Review&action=submit", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.json())
    .then((data) => {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
      if (data.success) {
        alert(data.message);
        closeReviewModal();
        location.reload();
      } else {
        alert(data.message);
      }
    })
    .catch((err) => {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
      console.error(err);
      alert("Có lỗi xảy ra khi gửi đánh giá, vui lòng thử lại.");
    });
}

function cancelUserBooking(datSanId) {
  if (
    !confirm(
      "Bạn có chắc chắn muốn hủy đơn đặt sân #" +
        datSanId +
        "? Sân sẽ được mở lại cho người khác đặt."
    )
  ) {
    return;
  }

  const token = getCsrfToken();
  const formData = new FormData();
  formData.append("dat_san_id", datSanId);
  formData.append("csrf_token", token);

  fetch("index.php?controller=User&action=cancelBooking", {
    method: "POST",
    headers: {
      "X-CSRF-TOKEN": token,
    },
    body: formData,
  })
    .then(async (res) => {
      const text = await res.text();
      try {
        const data = JSON.parse(text);
        alert(data.message);
        if (data.success) {
          location.reload();
        }
      } catch (err) {
        alert(text || "Lỗi xử lý yêu cầu hủy sân!");
      }
    })
    .catch((err) => {
      console.error(err);
      alert("Lỗi kết nối máy chủ!");
    });
}

function openRefundModal(datSanId, tenSan, giaTien) {
  document.getElementById("refund_dat_san_id").value = datSanId;
  document.getElementById("refund_ten_san").textContent = tenSan;
  document.getElementById("refund_so_tien").textContent =
    parseInt(giaTien, 10).toLocaleString("vi-VN") + " đ";
  const modal = document.getElementById("refundModal");
  if (modal) modal.style.display = "flex";
}

function closeRefundModal() {
  const modal = document.getElementById("refundModal");
  if (modal) modal.style.display = "none";
  const form = document.getElementById("refundForm");
  if (form) form.reset();
}

function submitRefundRequest(e) {
  e.preventDefault();
  const form = document.getElementById("refundForm");
  if (!form) return;
  const formData = new FormData(form);
  const token = getCsrfToken();
  if (!formData.has("csrf_token") || !formData.get("csrf_token")) {
    formData.append("csrf_token", token);
  }
  const btn = document.getElementById("submitRefundBtn");
  const originalText = btn ? btn.innerHTML : "";
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
  }

  fetch("index.php?controller=User&action=cancelBooking", {
    method: "POST",
    headers: {
      "X-CSRF-TOKEN": token,
    },
    body: formData,
  })
    .then(async (res) => {
      const text = await res.text();
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
      try {
        const data = JSON.parse(text);
        alert(data.message);
        if (data.success) {
          closeRefundModal();
          location.reload();
        }
      } catch (err) {
        alert(text || "Lỗi xử lý yêu cầu hoàn tiền!");
      }
    })
    .catch((err) => {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
      console.error(err);
      alert("Lỗi kết nối máy chủ!");
    });
}

