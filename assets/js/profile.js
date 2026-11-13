/**
 * USER PROFILE MODULE JAVASCRIPT (assets/js/profile.js)
 * Manages Profile Avatar Ajax Upload, Form Validations, Password toggling, and Reputation Modal
 */

document.addEventListener("DOMContentLoaded", function () {
  initAvatarUpload();
  initInfoFormValidation();
  initPasswordFormValidation();
  initReputationModalEvents();
});

function initAvatarUpload() {
  const avatarContainer = document.getElementById("avatarUploadContainer");
  const avatarInput = document.getElementById("avatarFileInput");
  const avatarImg = document.getElementById("userProfileAvatarImg");
  const avatarSpinner = document.getElementById("avatarSpinner");
  const menuAvatar = document.getElementById("menuUserAvatar");
  const alertContainer = document.getElementById("ajaxAlertContainer");

  if (avatarContainer && avatarInput) {
    avatarContainer.addEventListener("click", () => {
      avatarInput.click();
    });

    avatarInput.addEventListener("change", function () {
      if (!this.files || !this.files[0]) return;
      const file = this.files[0];

      if (file.size > 5 * 1024 * 1024) {
        alert("Dung lượng ảnh không được vượt quá 5MB!");
        return;
      }

      const formData = new FormData();
      formData.append("avatar_file", file);
      formData.append("ajax_upload", "1");

      if (avatarSpinner) avatarSpinner.style.display = "flex";

      fetch("index.php?controller=User&action=profile", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (avatarSpinner) avatarSpinner.style.display = "none";
          if (data.success) {
            const newSrc = data.avatar + "?t=" + new Date().getTime();
            if (avatarImg) avatarImg.src = newSrc;
            if (menuAvatar) menuAvatar.src = newSrc;

            if (alertContainer) {
              alertContainer.replaceChildren();
              const box = document.createElement("div");
              box.className = "success-box";
              box.style.marginBottom = "1.5rem";
              box.textContent = data.message || "Cập nhật ảnh đại diện thành công!";
              alertContainer.appendChild(box);
            }
          } else {
            if (alertContainer) {
              alertContainer.replaceChildren();
              const box = document.createElement("div");
              box.className = "error";
              box.style.marginBottom = "1.5rem";
              box.textContent = data.message || "Tải ảnh lên thất bại!";
              alertContainer.appendChild(box);
            }
          }
        })
        .catch((err) => {
          if (avatarSpinner) avatarSpinner.style.display = "none";
          alert("Lỗi kết nối khi tải ảnh lên, vui lòng thử lại.");
        });
    });
  }
}

function initInfoFormValidation() {
  const infoForm = document.getElementById("infoForm");
  if (!infoForm) return;

  infoForm.addEventListener("submit", function (e) {
    const emailInput = document.getElementById("emailInput");
    if (emailInput) {
      const emailVal = emailInput.value.trim();
      const emailRegex =
        /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|vn|net|org|edu|gov|info|biz|io|co|me|xyz|ai|pro|com\.vn|edu\.vn|gov\.vn|net\.vn|org\.vn)$/i;
      if (!emailRegex.test(emailVal)) {
        e.preventDefault();
        alert("Email không hợp lệ, vui lòng kiểm tra lại!");
        emailInput.focus();
        return;
      }
    }

    const sdtInput = document.getElementById("sdtInput");
    if (sdtInput) {
      const sdtVal = sdtInput.value.trim();
      const phoneRegex = /^[0-9]{10}$/;
      if (!phoneRegex.test(sdtVal)) {
        e.preventDefault();
        alert(
          "Số điện thoại không hợp lệ! Vui lòng nhập đúng 10 chữ số (Ví dụ: 0912345678)."
        );
        sdtInput.focus();
        return;
      }
    }
  });
}

function initPasswordFormValidation() {
  const passwordForm = document.getElementById("passwordForm");
  if (!passwordForm) return;

  passwordForm.addEventListener("submit", function (e) {
    const pOld = document.getElementById("mk_cu")
      ? document.getElementById("mk_cu").value
      : "";
    const p1 = document.getElementById("mk_moi")
      ? document.getElementById("mk_moi").value
      : "";
    const p2 = document.getElementById("mk_xac_nhan")
      ? document.getElementById("mk_xac_nhan").value
      : "";

    if (!pOld || !p1 || !p2) {
      e.preventDefault();
      alert("Vui lòng điền đầy đủ cả 3 trường mật khẩu!");
      return;
    }

    if (p1 === pOld) {
      e.preventDefault();
      alert("Mật khẩu mới không được trùng với mật khẩu hiện tại!");
      return;
    }

    if (p1 !== p2) {
      e.preventDefault();
      alert("Mật khẩu mới và mật khẩu xác nhận không khớp!");
      return;
    }

    if (p1.length < 6) {
      e.preventDefault();
      alert("Mật khẩu mới phải có ít nhất 6 ký tự!");
      return;
    }
  });
}

function togglePasswordVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const icon = btn.querySelector("i");
  if (input.type === "password") {
    input.type = "text";
    if (icon) {
      icon.classList.remove("fa-eye");
      icon.classList.add("fa-eye-slash");
    }
    btn.setAttribute("aria-label", "Ẩn mật khẩu");
  } else {
    input.type = "password";
    if (icon) {
      icon.classList.remove("fa-eye-slash");
      icon.classList.add("fa-eye");
    }
    btn.setAttribute("aria-label", "Hiện mật khẩu");
  }
}

// User Reputation Modal Logic & Pagination
var userRepCurrentPage = 1;
var userRepPageSize = 5;

function renderUserRepPage(page) {
  var rows = document.querySelectorAll(".user-rep-row");
  var totalRows = rows.length;
  if (totalRows === 0) return;

  var totalPages = Math.ceil(totalRows / userRepPageSize);
  userRepCurrentPage = page;

  var start = (page - 1) * userRepPageSize + 1;
  var end = Math.min(page * userRepPageSize, totalRows);

  rows.forEach(function (r) {
    var idx = parseInt(r.getAttribute("data-index"));
    if (idx >= start && idx <= end) {
      r.style.display = "";
    } else {
      r.style.display = "none";
    }
  });

  var info = document.getElementById("userRepPaginationInfo");
  if (info) {
    info.innerText =
      "Hiển thị " + start + " - " + end + " / " + totalRows + " bản ghi";
  }

  var container = document.getElementById("userRepPaginationBtns");
  if (!container) return;
  container.replaceChildren();

  if (totalPages <= 1) return;

  // Prev Button
  var prevBtn = document.createElement("button");
  prevBtn.type = "button";
  prevBtn.textContent = "\u2190";
  prevBtn.disabled = userRepCurrentPage === 1;
  prevBtn.style.cssText =
    "padding: 5px 10px; border-radius: 6px; border: 1px solid #cbd5e1; background: " +
    (userRepCurrentPage === 1 ? "#f8fafc" : "#ffffff") +
    "; color: " +
    (userRepCurrentPage === 1 ? "#94a3b8" : "#334155") +
    "; cursor: " +
    (userRepCurrentPage === 1 ? "not-allowed" : "pointer") +
    "; font-size: 0.82rem;";
  prevBtn.onclick = function () {
    if (userRepCurrentPage > 1) renderUserRepPage(userRepCurrentPage - 1);
  };
  container.appendChild(prevBtn);

  // Page numbers
  for (var p = 1; p <= totalPages; p++) {
    (function (pageNum) {
      var pageBtn = document.createElement("button");
      pageBtn.type = "button";
      pageBtn.innerText = pageNum;
      var isActive = pageNum === userRepCurrentPage;
      pageBtn.style.cssText =
        "padding: 5px 11px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; cursor: pointer; border: 1px solid " +
        (isActive ? "#0d9488" : "#cbd5e1") +
        "; background: " +
        (isActive ? "#0d9488" : "#ffffff") +
        "; color: " +
        (isActive ? "#ffffff" : "#334155") +
        ";";
      pageBtn.onclick = function () {
        renderUserRepPage(pageNum);
      };
      container.appendChild(pageBtn);
    })(p);
  }

  // Next Button
  var nextBtn = document.createElement("button");
  nextBtn.type = "button";
  nextBtn.textContent = "\u2192";
  nextBtn.disabled = userRepCurrentPage === totalPages;
  nextBtn.style.cssText =
    "padding: 5px 10px; border-radius: 6px; border: 1px solid #cbd5e1; background: " +
    (userRepCurrentPage === totalPages ? "#f8fafc" : "#ffffff") +
    "; color: " +
    (userRepCurrentPage === totalPages ? "#94a3b8" : "#334155") +
    "; cursor: " +
    (userRepCurrentPage === totalPages ? "not-allowed" : "pointer") +
    "; font-size: 0.82rem;";
  nextBtn.onclick = function () {
    if (userRepCurrentPage < totalPages)
      renderUserRepPage(userRepCurrentPage + 1);
  };
  container.appendChild(nextBtn);
}

function openUserReputationModal() {
  var modal = document.getElementById("userReputationModal");
  if (modal) {
    modal.style.display = "flex";
    renderUserRepPage(1);
  }
}

function closeUserReputationModal() {
  var modal = document.getElementById("userReputationModal");
  if (modal) modal.style.display = "none";
}

function initReputationModalEvents() {
  window.addEventListener("click", function (e) {
    var modal = document.getElementById("userReputationModal");
    if (modal && e.target === modal) {
      closeUserReputationModal();
    }
  });
}

