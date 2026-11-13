<?php
/**
 * View Partial: Admin Booking Table Rows
 * Rendered by AdminController::filterAjax()
 */
?>
<?php if (!empty($bookings) && count($bookings) > 0): ?>
    <?php foreach ($bookings as $row): ?>
        <tr>
            <td>#<?php echo $row['dat_san_id']; ?></td>
            <td><strong><?php echo htmlspecialchars($row['ho_ten']); ?></strong><br><?php echo htmlspecialchars($row['sdt']); ?></td>
            <td><strong><?php echo htmlspecialchars($row['ten_san']); ?></strong> - Sân: <?php echo htmlspecialchars($row['ten_san_con']); ?></td>
            <td><?php echo date("d/m/Y", strtotime($row['ngay_dat'])); ?><br><?php echo date("H:i", strtotime($row['gio_bat_dau'])); ?></td>
            <td>
                <?php echo number_format($row['gia_tien']); ?> đ<br>
                <?php echo ($row['phuong_thuc'] == 'vnpay' ? 'VNPay' : ($row['phuong_thuc'] == 'chuyen_khoan' ? 'CK' : 'Tiền mặt')); ?>
            </td>
            <td><?php echo htmlspecialchars($row['trang_thai']); ?></td>
            <td>
                <?php if ($row['trang_thai'] == 'Chờ xác nhận'): ?>
                    <button onclick="updateStatus(<?php echo $row['dat_san_id']; ?>, 'xacnhan')" class="btn-success">Duyệt</button> 
                    <button onclick="updateStatus(<?php echo $row['dat_san_id']; ?>, 'huy')" class="btn-warning">Hủy</button>
                <?php else: ?>
                    <button onclick="deleteBooking(<?php echo $row['dat_san_id']; ?>)" class="btn-delete">Xóa</button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 24px;">Không có dữ liệu đặt sân.</td></tr>
<?php endif; ?>

