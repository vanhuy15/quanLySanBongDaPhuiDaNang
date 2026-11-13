/**
 * ADMIN TOAST MODULE
 * Handles auto-dismissing notifications and floating toast alerts
 */

function showAdminMessage(message, type = "success", duration = 3000) {
  const div = document.createElement("div");
  div.className = `admin-message ${type}`;
  div.textContent = message;
  div.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 12px 22px;
        background: ${type === "success" ? "linear-gradient(135deg, #10b981, #059669)" : "linear-gradient(135deg, #ef4444, #dc2626)"};
        color: white; border-radius: 10px; font-weight: 600;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        opacity: 0; transform: translateY(-20px);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); z-index: 99999;
    `;
  document.body.appendChild(div);
  setTimeout(() => {
    div.style.opacity = "1";
    div.style.transform = "translateY(0)";
  }, 10);
  setTimeout(() => {
    div.style.opacity = "0";
    div.style.transform = "translateY(-20px)";
    setTimeout(() => div.remove(), 400);
  }, duration);
}

function autoHideAdminMessages() {
  const msgs = document.querySelectorAll(".admin-message");
  msgs.forEach((msg) => {
    setTimeout(() => {
      msg.style.opacity = "0";
      msg.style.transform = "translateY(-20px)";
      setTimeout(() => msg.remove(), 400);
    }, 3000);
  });
}
