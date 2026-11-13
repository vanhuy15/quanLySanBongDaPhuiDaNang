/**
 * HOMEPAGE & LIVE FILTER MODULE JAVASCRIPT (assets/js/home.js)
 * Manages Live Search, AJAX Pitch Filtering, and Debounce
 */

let searchTimeout = null;
let currentPage = 1;
let currentLoaiSan = 'all';
let currentMinPrice = '';
let currentMaxPrice = '';
let priceFilterTimeout = null;

document.addEventListener("DOMContentLoaded", function () {
  const filterDataEl = document.getElementById("homeFilterData");
  if (filterDataEl) {
    currentPage = parseInt(filterDataEl.dataset.page || "1", 10);
    currentLoaiSan = filterDataEl.dataset.loaiSan || "all";
    currentMinPrice = filterDataEl.dataset.minPrice || "";
    currentMaxPrice = filterDataEl.dataset.maxPrice || "";
  }

  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      const clearBtn = document.getElementById("clearSearchBtn");
      if (clearBtn) {
        clearBtn.style.display = this.value.length > 0 ? "block" : "none";
      }

      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        triggerSearch(1);
      }, 500);
    });
  }
});

function toggleFilterPanel() {
  const panel = document.getElementById("filterPanel");
  if (!panel) return;
  if (panel.style.display === "none" || panel.style.display === "") {
    panel.style.display = "block";
  } else {
    panel.style.display = "none";
  }
}

function selectFilter(type, value, element) {
  const buttons = document.querySelectorAll(`button[data-type="${type}"]`);
  buttons.forEach((btn) => btn.classList.remove("active"));
  if (element) element.classList.add("active");
  if (type === "loai_san") currentLoaiSan = value;
  updateFilterBadge();
  triggerSearch(1);
}

function debounceApplyPriceFilter() {
  clearTimeout(priceFilterTimeout);
  priceFilterTimeout = setTimeout(() => {
    const minInput = document.getElementById("inputMinPrice");
    const maxInput = document.getElementById("inputMaxPrice");
    currentMinPrice = minInput ? minInput.value.trim() : "";
    currentMaxPrice = maxInput ? maxInput.value.trim() : "";
    updateFilterBadge();
    triggerSearch(1);
  }, 500);
}

function updateFilterBadge() {
  const badge = document.getElementById("filterActiveBadge");
  const hasFilter = currentLoaiSan !== "all" || currentMinPrice !== "" || currentMaxPrice !== "";
  if (badge) badge.style.display = hasFilter ? "inline-block" : "none";
}

function resetAllFilters() {
  currentLoaiSan = "all";
  currentMinPrice = "";
  currentMaxPrice = "";
  document.querySelectorAll(".filter-pill").forEach((btn) => {
    if (btn.getAttribute("data-value") === "all") btn.classList.add("active");
    else btn.classList.remove("active");
  });
  const minInput = document.getElementById("inputMinPrice");
  const maxInput = document.getElementById("inputMaxPrice");
  if (minInput) minInput.value = "";
  if (maxInput) maxInput.value = "";
  updateFilterBadge();
  const panel = document.getElementById("filterPanel");
  if (panel) panel.style.display = "none";
  triggerSearch(1);
}

function triggerSearch(page = 1) {
  currentPage = page;
  const searchInput = document.getElementById("searchInput");
  const query = searchInput ? searchInput.value.trim() : "";
  const spinner = document.getElementById("searchSpinner");
  const container = document.getElementById("pitchListContainer");
  const paginationContainer = document.getElementById("paginationContainer");

  if (spinner) spinner.style.display = "inline-block";

  const url = `index.php?controller=Home&action=searchAjax&search=${encodeURIComponent(query)}&loai_san=${encodeURIComponent(currentLoaiSan)}&min_price=${encodeURIComponent(currentMinPrice)}&max_price=${encodeURIComponent(currentMaxPrice)}&page=${page}`;

  fetch(url)
    .then((res) => res.json())
    .then((data) => {
      if (spinner) spinner.style.display = "none";
      if (data.success) {
        if (container) container.innerHTML = data.html_cards;
        if (paginationContainer) paginationContainer.innerHTML = data.html_pagination;
      }
    })
    .catch((err) => {
      if (spinner) spinner.style.display = "none";
      console.error(err);
    });
}

function changePage(page) {
  triggerSearch(page);
}

function clearSearch() {
  const searchInput = document.getElementById("searchInput");
  if (searchInput) searchInput.value = "";
  const clearBtn = document.getElementById("clearSearchBtn");
  if (clearBtn) clearBtn.style.display = "none";
  triggerSearch(1);
}

