/**
 * AUTHENTICATION MODULE JAVASCRIPT (assets/js/auth.js)
 * Manages Password Visibility Toggles and Client-side Form Validations
 */

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

document.addEventListener("DOMContentLoaded", function () {
  // 1. Login Form Validation
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      const u = document.getElementById("username")?.value.trim() || "";
      const p = document.getElementById("password")?.value.trim() || "";
      if (u === "" || p === "") {
        e.preventDefault();
        alert("Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!");
      }
    });
  }

  // 2. Register Form Validation
  const registerForm = document.getElementById("registerForm");
  if (registerForm) {
    registerForm.addEventListener("submit", function (e) {
      const p1 = document.getElementById("mat_khau")?.value || "";
      const p2 = document.getElementById("nhap_lai_mat_khau")?.value || "";
      const phone = document.getElementById("sdt")?.value.trim() || "";
      const email = document.getElementById("email")?.value.trim() || "";

      // Kiểm tra Mật khẩu
      if (p1 !== p2) {
        e.preventDefault();
        alert("Mật khẩu nhập lại không khớp!");
        return;
      }

      // Kiểm tra Số điện thoại (10 chữ số)
      const phoneRegex = /^[0-9]{10}$/;
      if (!phoneRegex.test(phone)) {
        e.preventDefault();
        alert("Số điện thoại phải gồm 10 chữ số!");
        return;
      }

      // Kiểm tra Email
      const emailRegex =
        /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|vn|net|org|edu|gov|info|biz|io|co|me|xyz|ai|pro|com\.vn|edu\.vn|gov\.vn|net\.vn|org\.vn)$/i;
      if (!emailRegex.test(email)) {
        e.preventDefault();
        alert("Email không hợp lệ, vui lòng kiểm tra và thử lại!");
        document.getElementById("email")?.focus();
        return;
      }
    });
  }

  // 3. Reset Password Form Validation
  const resetForm = document.getElementById("resetPasswordForm") || document.querySelector("form[action*='reset']");
  if (resetForm) {
    resetForm.addEventListener("submit", function (e) {
      const pass = document.getElementById("mat_khau_moi")?.value || "";
      const confirmPass = document.getElementById("xac_nhan_mat_khau")?.value || "";
      if (pass !== confirmPass) {
        e.preventDefault();
        alert("Mật khẩu xác nhận không khớp!");
      }
    });
  }
});

