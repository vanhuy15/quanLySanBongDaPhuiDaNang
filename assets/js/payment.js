/**
 * PAYMENT MODULE JAVASCRIPT (assets/js/payment.js)
 * Manages Payment Method Toggles, VietQR Copy text, and Hold Countdown
 */

document.addEventListener("DOMContentLoaded", function () {
  initPaymentView();
});

function initPaymentView() {
  const timerEl = document.getElementById("countdownTimer");
  if (timerEl) {
    const rawRemaining = timerEl.dataset.remainingSeconds;
    let remaining = rawRemaining ? parseInt(rawRemaining, 10) : 0;
    const isCashLocked = timerEl.dataset.cashLocked === "1";

    function formatTime(sec) {
      const m = Math.floor(sec / 60);
      const s = sec % 60;
      return (m < 10 ? "0" : "") + m + ":" + (s < 10 ? "0" : "") + s;
    }

    timerEl.textContent = formatTime(remaining);

    const countdownInterval = setInterval(() => {
      remaining--;
      if (remaining <= 0) {
        clearInterval(countdownInterval);
        timerEl.textContent = "00:00 (Hết hạn)";
        alert("Thời gian giữ chỗ 10 phút đã hết hạn! Sân đã được tự động mở lại cho người khác.");
        window.location.reload();
      } else {
        timerEl.textContent = formatTime(remaining);
      }
    }, 1000);

    if (isCashLocked) {
      toggleQR();
    }
  }

  // Bind change on radios
  document.querySelectorAll('input[name="phuong_thuc"]').forEach((radio) => {
    radio.addEventListener("change", toggleQR);
  });
}

function toggleQR() {
  const checkedRadio = document.querySelector('input[name="phuong_thuc"]:checked');
  const pt = checkedRadio ? checkedRadio.value : "";
  const qrBox = document.getElementById("chuyenKhoanBox");
  const btnWrapper = document.getElementById("submitBtnWrapper");
  const btnText = document.getElementById("submitBtnText");
  const submitBtn = document.getElementById("btnSubmitPayment");

  if (!pt) {
    if (qrBox) qrBox.style.display = "none";
    if (btnWrapper) btnWrapper.style.display = "none";
    return;
  }

  if (btnWrapper) {
    btnWrapper.style.display = "block";
  }

  if (pt === "chuyen_khoan") {
    if (qrBox) qrBox.style.display = "block";
    if (btnText) btnText.textContent = "Xác nhận chuyển khoản";
    if (submitBtn) {
      submitBtn.style.background = "#0284c7";
      submitBtn.style.boxShadow = "0 4px 12px rgba(2, 132, 199, 0.25)";
    }
  } else if (pt === "tien_mat") {
    if (qrBox) qrBox.style.display = "none";
    if (btnText) btnText.textContent = "Xác nhận đặt sân";
    if (submitBtn) {
      submitBtn.style.background = "#0d9488";
      submitBtn.style.boxShadow = "0 4px 12px rgba(13, 148, 136, 0.25)";
    }
  }
}

function copyText(elementId, btnEl) {
  const textEl = document.getElementById(elementId);
  if (!textEl) return;
  const text = textEl.textContent.trim();

  function setSuccessState() {
    btnEl.innerHTML = '<i class="fas fa-check" style="color: #10b981;"></i>';
    btnEl.classList.add("copied");
    setTimeout(() => {
      btnEl.innerHTML = '<i class="far fa-copy"></i>';
      btnEl.classList.remove("copied");
    }, 1500);
  }

  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard
      .writeText(text)
      .then(setSuccessState)
      .catch(() => fallbackCopy(text, setSuccessState));
  } else {
    fallbackCopy(text, setSuccessState);
  }
}

function fallbackCopy(text, callback) {
  const temp = document.createElement("textarea");
  temp.value = text;
  temp.style.position = "fixed";
  temp.style.left = "-9999px";
  document.body.appendChild(temp);
  temp.select();
  try {
    document.execCommand("copy");
    if (callback) callback();
  } catch (e) {
    alert("Đã sao chép: " + text);
  }
  document.body.removeChild(temp);
}

