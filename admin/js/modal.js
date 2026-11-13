/**
 * ADMIN MODAL MODULE
 * Manages custom confirmation dialogs for delete, cancel, and logout actions
 */

function openAdminModal(options) {
  let overlay = document.getElementById("adminConfirmModal");
  if (!overlay) {
    overlay = document.createElement("div");
    overlay.id = "adminConfirmModal";
    overlay.className = "admin-modal-overlay";
    overlay.innerHTML = `
      <div class="admin-modal-box">
        <div class="admin-modal-icon warning" id="adminModalIcon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 class="admin-modal-title" id="adminModalTitle">Xác nhận</h3>
        <p class="admin-modal-text" id="adminModalText">Bạn có chắc muốn thực hiện thao tác này?</p>
        <div class="admin-modal-actions">
          <button type="button" class="admin-modal-btn-cancel" id="adminModalCancel">Hủy bỏ</button>
          <button type="button" class="admin-modal-btn-confirm" id="adminModalConfirm">Xác nhận</button>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);

    document.getElementById("adminModalCancel").addEventListener("click", () => {
      overlay.classList.remove("show");
    });
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) overlay.classList.remove("show");
    });
  }

  const titleEl = document.getElementById("adminModalTitle");
  const textEl = document.getElementById("adminModalText");
  const iconEl = document.getElementById("adminModalIcon");
  const confirmBtn = document.getElementById("adminModalConfirm");

  titleEl.textContent = options.title || "Xác nhận hành động";
  textEl.textContent = options.message || "Bạn có chắc muốn tiếp tục?";
  
  iconEl.className = "admin-modal-icon " + (options.type || "warning");
  iconEl.innerHTML = options.type === "danger" 
    ? '<i class="fas fa-trash-alt"></i>' 
    : (options.type === "info" ? '<i class="fas fa-question-circle"></i>' : '<i class="fas fa-exclamation-triangle"></i>');

  // Clone confirm button to clean up previously bound listeners
  const newConfirmBtn = confirmBtn.cloneNode(true);
  confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

  newConfirmBtn.addEventListener("click", () => {
    overlay.classList.remove("show");
    if (typeof options.onConfirm === "function") {
      options.onConfirm();
    }
  });

  overlay.classList.add("show");
}

function setupConfirmActions() {
  document.querySelectorAll(".btn-delete, .btn-confirm").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      const href = this.getAttribute("href");
      const msg = this.dataset.confirm || "Hành động này không thể hoàn tác. Bạn có chắc muốn tiếp tục?";
      const isDelete = this.classList.contains("btn-delete") || href?.includes("xoa");

      openAdminModal({
        title: isDelete ? "Xác nhận xóa" : "Xác nhận thay đổi",
        message: msg,
        type: isDelete ? "danger" : "warning",
        onConfirm: () => {
          if (href && href !== "#" && href !== "javascript:void(0)") {
            window.location.href = href;
          } else if (this.type === "submit" || this.closest("form")) {
            this.closest("form").submit();
          }
        }
      });
    });
  });
}

function setupLogoutConfirmAdmin() {
  // Handled directly via handleAdminLogout in main_admin.js
}

/**
 * Admin Refund VietQR Modal
 */
function openAdminRefundQR(datSanId, bank, stk, name, amount, statusReturn) {
  if (!stk || stk === '') {
    alert('Khách hàng chưa cung cấp số tài khoản!');
    return;
  }
  
  const memo = "HOANTIEN DATSAN " + datSanId;
  const qrUrl = `https://img.vietqr.io/image/${bank}-${stk}-compact2.png?amount=${amount}&addInfo=${encodeURIComponent(memo)}&accountName=${encodeURIComponent(name)}`;
  
  const qrImg = document.getElementById('admin_qr_img');
  const qrBank = document.getElementById('admin_qr_bank');
  const qrStk = document.getElementById('admin_qr_stk');
  const qrName = document.getElementById('admin_qr_name');
  const qrAmt = document.getElementById('admin_qr_amt');
  const confirmLink = document.getElementById('admin_confirm_refund_link');

  if (qrImg) qrImg.src = qrUrl;
  if (qrBank) qrBank.textContent = bank;
  if (qrStk) qrStk.textContent = stk;
  if (qrName) qrName.textContent = name;
  if (qrAmt) qrAmt.textContent = Number(amount).toLocaleString('vi-VN') + ' VNĐ';
  if (confirmLink) {
    confirmLink.href = `capnhat.php?id=${datSanId}&action=hoantien_xong&status_return=${statusReturn}`;
  }
  
  const modal = document.getElementById('adminRefundModal');
  if (modal) modal.style.display = 'flex';
}

function closeAdminRefundQR() {
  const modal = document.getElementById('adminRefundModal');
  if (modal) modal.style.display = 'none';
}

/**
 * Admin Reputation History Modal & Pagination
 */
function initReputationPagination(container) {
  if (!container) return;
  const rows = container.querySelectorAll('.admin-rep-row');
  const pageSize = 5;
  const totalRows = rows.length;
  const totalPages = Math.ceil(totalRows / pageSize);
  let currentPage = 1;

  function renderAdminPage(page) {
    currentPage = page;
    const start = (page - 1) * pageSize + 1;
    const end = Math.min(page * pageSize, totalRows);

    rows.forEach(function (r) {
      const idx = parseInt(r.getAttribute('data-index'));
      if (idx >= start && idx <= end) {
        r.style.display = '';
      } else {
        r.style.display = 'none';
      }
    });

    const info = container.querySelector('#adminRepPaginationInfo');
    if (info) {
      info.innerText = 'Hiển thị ' + start + ' - ' + end + ' / ' + totalRows + ' bản ghi';
    }

    renderAdminPaginationBtns();
  }

  function renderAdminPaginationBtns() {
    const btnContainer = container.querySelector('#adminRepPaginationBtns');
    if (!btnContainer) return;
    btnContainer.innerHTML = '';

    if (totalPages <= 1) return;

    // Prev Button
    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.disabled = currentPage === 1;
    prevBtn.style.cssText =
      'padding: 5px 10px; border-radius: 6px; border: 1px solid #cbd5e1; background: ' +
      (currentPage === 1 ? '#f8fafc' : '#ffffff') +
      '; color: ' +
      (currentPage === 1 ? '#94a3b8' : '#334155') +
      '; cursor: ' +
      (currentPage === 1 ? 'not-allowed' : 'pointer') +
      '; font-size: 0.78rem;';
    prevBtn.onclick = function () {
      if (currentPage > 1) renderAdminPage(currentPage - 1);
    };
    btnContainer.appendChild(prevBtn);

    // Page Number Buttons
    for (let p = 1; p <= totalPages; p++) {
      const pageBtn = document.createElement('button');
      pageBtn.type = 'button';
      pageBtn.innerText = p;
      const isActive = p === currentPage;
      pageBtn.style.cssText =
        'padding: 5px 11px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; cursor: pointer; border: 1px solid ' +
        (isActive ? '#007a64' : '#cbd5e1') +
        '; background: ' +
        (isActive ? '#007a64' : '#ffffff') +
        '; color: ' +
        (isActive ? '#ffffff' : '#334155') +
        ';';
      pageBtn.onclick = function () {
        renderAdminPage(p);
      };
      btnContainer.appendChild(pageBtn);
    }

    // Next Button
    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.style.cssText =
      'padding: 5px 10px; border-radius: 6px; border: 1px solid #cbd5e1; background: ' +
      (currentPage === totalPages ? '#f8fafc' : '#ffffff') +
      '; color: ' +
      (currentPage === totalPages ? '#94a3b8' : '#334155') +
      '; cursor: ' +
      (currentPage === totalPages ? 'not-allowed' : 'pointer') +
      '; font-size: 0.78rem;';
    nextBtn.onclick = function () {
      if (currentPage < totalPages) renderAdminPage(currentPage + 1);
    };
    btnContainer.appendChild(nextBtn);
  }

  renderAdminPage(1);
}

function openReputationModal(userId) {
  const modal = document.getElementById('reputationHistoryModal');
  const body = document.getElementById('reputationModalBody');
  if (!modal || !body) return;

  modal.style.display = 'flex';
  body.innerHTML = '<div style="text-align: center; padding: 30px; color: #64748b;"><i class="fas fa-spinner fa-spin" style="font-size: 1.8rem; color: #007a64; margin-bottom: 10px;"></i><p>Đang tải dữ liệu lịch sử tín dụng...</p></div>';
  
  fetch('get_reputation_history.php?id=' + encodeURIComponent(userId))
    .then(response => response.text())
    .then(html => {
      body.innerHTML = html;
      initReputationPagination(body);
    })
    .catch(err => {
      body.innerHTML = '<p style="color: #dc2626; text-align: center; padding: 20px;">Lỗi khi tải lịch sử: ' + err + '</p>';
    });
}

function closeReputationModal() {
  const modal = document.getElementById('reputationHistoryModal');
  if (modal) modal.style.display = 'none';
}

// Global modal backdrop close handlers
window.addEventListener('click', function(e) {
  const repModal = document.getElementById('reputationHistoryModal');
  if (repModal && e.target === repModal) {
    closeReputationModal();
  }
  const refundModal = document.getElementById('adminRefundModal');
  if (refundModal && e.target === refundModal) {
    closeAdminRefundQR();
  }
});

