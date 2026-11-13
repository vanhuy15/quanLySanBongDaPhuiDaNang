<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/menu.php'; ?>

<div class="search-bar-container">
    <div class="search-form" id="searchBoxWrapper">
        <i class="fas fa-search" style="color: #6b7280; margin-left: 10px;"></i>
        <input type="text" name="search" id="searchInput" placeholder="Tìm kiếm nhanh sân bóng theo tên sân hoặc địa chỉ..." value="<?php echo htmlspecialchars($search_term); ?>" autocomplete="off">
        
        <span id="searchSpinner" style="display: none; color: var(--primary-color); margin-right: 12px;"><i class="fas fa-spinner fa-spin"></i></span>
        
        <button type="button" id="clearSearchBtn" onclick="clearSearch()" style="display: <?php echo !empty($search_term) ? 'block' : 'none'; ?>; background: none; border: none; color: #9ca3af; font-size: 1.2rem; cursor: pointer; padding: 4px 8px; transition: color 0.2s;" title="Xóa tìm kiếm">
            <i class="fas fa-times-circle"></i>
        </button>

        <button type="button" id="filterToggleBtn" onclick="toggleFilterPanel()" style="width: 44px; height: 44px; border-radius: 50%; background: var(--primary-color); color: #ffffff; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; position: relative;" title="Bộ lọc">
            <i class="fas fa-sliders-h"></i>
            <span id="filterActiveBadge" style="display: none; position: absolute; top: 0; right: 0; width: 12px; height: 12px; background: #ef4444; border-radius: 50%; border: 2px solid white;"></span>
        </button>
    </div>

    <!-- Expandable Filter Drawer -->
    <div id="filterPanel" style="display: none; background: #ffffff; border-radius: 16px; padding: 1.5rem; margin-top: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
            <div>
                <label style="display: block; font-weight: 600; color: #374151; font-size: 0.9rem; margin-bottom: 0.75rem;">
                    Loại quy mô sân:
                </label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="filter-pill active" data-type="loai_san" data-value="all" onclick="selectFilter('loai_san', 'all', this)">Tất cả</button>
                    <button type="button" class="filter-pill" data-type="loai_san" data-value="san_5" onclick="selectFilter('loai_san', 'san_5', this)">Sân 5</button>
                    <button type="button" class="filter-pill" data-type="loai_san" data-value="san_7" onclick="selectFilter('loai_san', 'san_7', this)">Sân 7</button>
                </div>
            </div>

            <div>
                <label style="display: block; font-weight: 600; color: #374151; font-size: 0.9rem; margin-bottom: 0.75rem;">
                    Mức giá mong muốn (VNĐ):
                </label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="number" id="inputMinPrice" placeholder="Từ (VD: 200000)" value="<?php echo (!empty($min_price) && $min_price > 0) ? (int)$min_price : ''; ?>" min="0" step="10000" style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.9rem; outline: none;" oninput="debounceApplyPriceFilter()">
                    <span style="color: #9ca3af; font-weight: 600;">-</span>
                    <input type="number" id="inputMaxPrice" placeholder="Đến (VD: 500000)" value="<?php echo (!empty($max_price) && $max_price > 0) ? (int)$max_price : ''; ?>" min="0" step="10000" style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.9rem; outline: none;" oninput="debounceApplyPriceFilter()">
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #f3f4f6;">
            <button type="button" onclick="resetAllFilters()" style="background: none; border: none; color: #ef4444; font-size: 0.9rem; font-weight: 600; cursor: pointer; padding: 4px 8px; transition: opacity 0.2s;">
                <i class="fas fa-trash-alt"></i> Xóa bộ lọc
            </button>
        </div>
    </div>
</div>



<div class="san-bong-container" id="pitchListContainer">
    <?php include __DIR__ . '/pitch_cards.php'; ?>
</div>

<div id="paginationContainer">
    <?php include __DIR__ . '/pagination.php'; ?>
</div>

<div id="homeFilterData" 
     data-page="<?php echo (int)($page ?? 1); ?>" 
     data-loai-san="<?php echo htmlspecialchars($loai_san ?? 'all'); ?>" 
     data-min-price="<?php echo (!empty($min_price) && $min_price > 0) ? (int)$min_price : ''; ?>" 
     data-max-price="<?php echo (!empty($max_price) && $max_price > 0) ? (int)$max_price : ''; ?>" 
     style="display: none;"></div>

<script src="assets/js/home.js?v=1.0.1"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
