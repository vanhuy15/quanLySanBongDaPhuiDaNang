<?php
session_start();
include '../../includes/db.php';
require_admin();

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id <= 0) {
    echo "<p style='color: #dc2626; padding: 20px; text-align: center;'>ID người dùng không hợp lệ.</p>";
    exit();
}

// Đồng bộ tự động các trận đã kết thúc của người này
syncCompletedMatchesReputation($conn, $user_id);

$stmt_u = $conn->prepare("SELECT ho_ten, email, sdt, diem_uy_tin, so_lan_bung_san FROM nguoi_dung WHERE nguoi_dung_id = ?");
$stmt_u->bind_param("i", $user_id);
$stmt_u->execute();
$user = $stmt_u->get_result()->fetch_assoc();
$stmt_u->close();

if (!$user) {
    echo "<p style='color: #dc2626; padding: 20px; text-align: center;'>Không tìm thấy người dùng.</p>";
    exit();
}

$stmt_logs = $conn->prepare("SELECT * FROM lich_su_uy_tin WHERE nguoi_dung_id = ? ORDER BY lich_su_id DESC");
$stmt_logs->bind_param("i", $user_id);
$stmt_logs->execute();
$logs = $stmt_logs->get_result();
$stmt_logs->close();
?>

<div style="padding: 4px 0;">
    <!-- USER HEADER INFO -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 20px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h4 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f172a;"><?php echo htmlspecialchars($user['ho_ten']); ?></h4>
            <span style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($user['email'] ?: $user['sdt']); ?></span>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <div style="text-align: right;">
                <span style="display: block; font-size: 0.72rem; color: #64748b; text-transform: uppercase; font-weight: 700;">Điểm hiện tại</span>
                <strong style="font-size: 1.25rem; font-weight: 800; color: <?php echo ($user['diem_uy_tin'] >= 80) ? '#059669' : (($user['diem_uy_tin'] >= 60) ? '#0284c7' : '#dc2626'); ?>;">
                    <?php echo $user['diem_uy_tin']; ?>/100
                </strong>
            </div>
            <?php if ($user['so_lan_bung_san'] > 0 && $user['diem_uy_tin'] < 80): ?>
                <span style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                    <i class="fas fa-exclamation-triangle"></i> Bùng <?php echo $user['so_lan_bung_san']; ?> lần
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- LOG TIMELINE TABLE (NO HORIZONTAL SCROLLBAR, FULL FIT) -->
    <?php if ($logs && $logs->num_rows > 0): ?>
        <div style="border: 1px solid #e2e8f0; border-radius: 10px; background: #ffffff; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.86rem; text-align: center;">
                <thead>
                    <tr style="background: #f1f5f9; color: #475569; font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                        <th style="padding: 12px 14px; border-bottom: 1px solid #e2e8f0; text-align: center; width: 140px; white-space: nowrap;">THỜI GIAN</th>
                        <th style="padding: 12px 14px; border-bottom: 1px solid #e2e8f0; text-align: center; width: 110px; white-space: nowrap;">BIẾN ĐỘNG</th>
                        <th style="padding: 12px 14px; border-bottom: 1px solid #e2e8f0; text-align: center; width: 130px; white-space: nowrap;">ĐIỂM SAU CÙNG</th>
                        <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: center;">NỘI DUNG CHI TIẾT</th>
                    </tr>
                </thead>
                <tbody id="adminRepLogTableBody">
                    <?php 
                    $total_count = $logs->num_rows;
                    $row_idx = 0;
                    while($l = $logs->fetch_assoc()): 
                        $row_idx++;
                        $pts = (int)$l['so_diem'];
                        if ($pts > 0) {
                            $pts_badge = "<span style='display: inline-block; white-space: nowrap; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.82rem;'>+$pts đ</span>";
                        } elseif ($pts < 0) {
                            $pts_badge = "<span style='display: inline-block; white-space: nowrap; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.82rem;'>$pts đ</span>";
                        } else {
                            $pts_badge = "<span style='display: inline-block; white-space: nowrap; background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.82rem;'>0 đ</span>";
                        }
                    ?>
                    <tr class="admin-rep-row" data-index="<?php echo $row_idx; ?>" style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;">
                        <td style="padding: 12px 14px; color: #64748b; font-size: 0.82rem; white-space: nowrap; text-align: center; vertical-align: middle;">
                            <?php echo date('d/m/Y H:i', strtotime($l['ngay_tao'])); ?>
                        </td>
                        <td style="padding: 12px 14px; text-align: center; vertical-align: middle; white-space: nowrap;">
                            <?php echo $pts_badge; ?>
                        </td>
                        <td style="padding: 12px 14px; font-weight: 800; color: #0f172a; text-align: center; vertical-align: middle; white-space: nowrap;">
                            <?php echo $l['diem_sau_cung']; ?>/100
                        </td>
                        <td style="padding: 12px 16px; color: #334155; font-weight: 600; text-align: center; vertical-align: middle;">
                            <?php echo htmlspecialchars($l['ghi_chu']); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION CONTROLS -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 6px; flex-wrap: wrap; gap: 10px;">
            <span id="adminRepPaginationInfo" style="font-size: 0.82rem; color: #64748b; font-weight: 600;">
                Hiển thị 1 - 5 / <?php echo $total_count; ?> bản ghi
            </span>
            <div id="adminRepPaginationBtns" style="display: flex; gap: 6px; align-items: center;"></div>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: #94a3b8; padding: 24px;">Chưa có dữ liệu biến động điểm nào.</p>
    <?php endif; ?>
</div>
