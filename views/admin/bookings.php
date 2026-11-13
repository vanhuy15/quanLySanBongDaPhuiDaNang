<?php include __DIR__ . '/../../admin/header_admin.php'; ?>

<div class="admin-module">
    <h2>Quản lý đặt sân</h2>

    <div class="status-filter-container">
        <a href="javascript:void(0)" onclick="loadBookings('tat_ca')" class="status-box"><span>Tất cả</span></a>
        <a href="javascript:void(0)" onclick="loadBookings('cho-xac-nhan')" class="status-box status-cho"><span>Chờ duyệt</span></a>
        <a href="javascript:void(0)" onclick="loadBookings('da-xac-nhan')" class="status-box status-xacnhan"><span>Đã chốt</span></a>
        <a href="javascript:void(0)" onclick="loadBookings('huy')" class="status-box status-huy"><span>Hủy / Hoàn tiền</span></a>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Mã Đơn</th>
                <th>Khách hàng</th>
                <th>Thông tin sân</th>
                <th>Thời gian</th>
                <th>Thanh toán</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody id="bookings-tbody">
            <tr><td colspan="7">Đang tải dữ liệu...</td></tr>
        </tbody>
    </table>
</div>

<script src="/quanlysanbong/admin/js/bookings.js?v=1.0.1"></script>

<?php include __DIR__ . '/../../admin/footer_admin.php'; ?>
