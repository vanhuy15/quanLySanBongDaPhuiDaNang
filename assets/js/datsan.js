/**
 * DATSAN BOOKING INTERACTION MODULE (assets/js/datsan.js)
 * Manages Dynamic Schedule AJAX Loading, SVG Mini Pitch Selection, and Booking Submissions
 */

let currentBookingMode = "san_5";

function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute("content") : "";
}

function switchBookingMode(mode) {
  currentBookingMode = mode;
  loadSchedule();
}

function loadSchedule() {
  const sanIdEl = document.getElementById("san_id");
  const ngayDatEl = document.getElementById("ngay_dat");
  const container = document.getElementById("schedule-container");

  if (!sanIdEl || !ngayDatEl || !container) return;

  const san_id = sanIdEl.value;
  const ngay_dat = ngayDatEl.value;

  container.innerHTML = `
    <div class="loading-state">
      <i class="fas fa-circle-notch fa-spin"></i>
      <p>Đang cập nhật lịch sân...</p>
    </div>
  `;

  fetch(
    `index.php?controller=DatSan&action=getSchedule&san_id=${san_id}&ngay_dat=${ngay_dat}&mode=${currentBookingMode}`
  )
    .then((response) => response.text())
    .then((html) => {
      container.innerHTML = html;
      attachPitchEvents();
    })
    .catch((err) => {
      console.error(err);
      container.innerHTML = `<div class="error-box" style="padding: 20px; text-align: center; color: #dc2626;">Lỗi khi tải lịch sân. Vui lòng thử lại!</div>`;
    });
}

function attachPitchEvents() {
  function clearAllSelections() {
    document.querySelectorAll(".mini-pitch-svg").forEach((p) => p.classList.remove("selected"));
    document.querySelectorAll(".hidden-pitch-name").forEach((inp) => (inp.value = ""));
    document.querySelectorAll(".hidden-lock-keys").forEach((inp) => (inp.value = ""));
    document.querySelectorAll(".schedule-row").forEach((r) => r.classList.remove("active-row"));
  }

  const availablePitches5 = document.querySelectorAll(".mini-pitch-svg.available");
  availablePitches5.forEach((pitch) => {
    pitch.addEventListener("click", function () {
      clearAllSelections();

      const row = this.closest(".schedule-row");
      if (row) row.classList.add("active-row");

      this.classList.add("selected");

      const pitchName = this.getAttribute("data-pitch-name");
      const lockKeys = this.getAttribute("data-lock-keys") || pitchName;

      if (row) {
        const nameInput = row.querySelector(".hidden-pitch-name");
        const keysInput = row.querySelector(".hidden-lock-keys");
        if (nameInput) nameInput.value = pitchName;
        if (keysInput) keysInput.value = lockKeys;
      }
    });
  });
}

function submitBooking(e, formElement) {
  e.preventDefault();
  const formData = new FormData(formElement);
  const token = getCsrfToken();
  if (!formData.has("csrf_token") || !formData.get("csrf_token")) {
    formData.append("csrf_token", token);
  }

  if (!formData.get("ten_san_con")) {
    alert("Vui lòng click chọn một sân trống trên sơ đồ trước khi bấm Đặt Ngay!");
    return;
  }

  const submitBtn = formElement.querySelector('button[type="submit"]');
  const originalText = submitBtn ? submitBtn.innerHTML : "";
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
  }

  fetch("index.php?controller=DatSan&action=bookSubmit", {
    method: "POST",
    headers: {
      "X-CSRF-TOKEN": token,
    },
    body: formData,
  })
    .then((res) => res.json())
    .then((data) => {
      const msgContainer = document.getElementById("message-container");
      if (data.success) {
        if (msgContainer) {
          msgContainer.innerHTML = `<div class='success-box' style="background: #ecfdf5; color: #047857; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #a7f3d0; font-weight: 600;">${data.message} Đang chuyển đến trang thanh toán...</div>`;
        }
        window.location.href = data.redirect;
      } else {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
        if (msgContainer) {
          msgContainer.innerHTML = `<div class='error-box' style="background: #fef2f2; color: #b91c1c; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #fecaca; font-weight: 600;">${data.message}</div>`;
        }
        window.scrollTo({ top: 0, behavior: "smooth" });
        loadSchedule();
      }
    })
    .catch((err) => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
      console.error(err);
      alert("Lỗi kết nối máy chủ khi đặt sân!");
    });
}

document.addEventListener("DOMContentLoaded", function () {
  const urlParams = new URLSearchParams(window.location.search);
  const tabParam = urlParams.get("tab");
  if (tabParam) {
    currentBookingMode = tabParam;
  }

  const sanIdEl = document.getElementById("san_id");
  const ngayDatEl = document.getElementById("ngay_dat");

  if (sanIdEl) {
    sanIdEl.addEventListener("change", function () {
      currentBookingMode = "san_5";
      loadSchedule();
    });
  }

  if (ngayDatEl) {
    ngayDatEl.addEventListener("change", loadSchedule);
  }

  if (document.getElementById("schedule-container")) {
    loadSchedule();
  }
});

