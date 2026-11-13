<?php
session_start();
include '../../includes/db.php';
include '../header_admin.php';

require_admin();

// Thống kê người dùng
$total_users = $conn->query("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro = 'khach_hang'")->fetch_row()[0];
$avg_reputation = $conn->query("SELECT ROUND(AVG(diem_uy_tin)) FROM nguoi_dung WHERE vai_tro = 'khach_hang'")->fetch_row()[0] ?? 100;
$banned_users = $conn->query("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro = 'khach_hang' AND trang_thai = 'banned'")->fetch_row()[0];

// Lấy danh sách khách hàng
$result = $conn->query("SELECT * FROM nguoi_dung WHERE vai_tro = 'khach_hang' ORDER BY nguoi_dung_id DESC");
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý Khách hàng</h1>
        <p class="page-subtitle">Quản lý tài khoản, phân quyền và điểm uy tín của khách hàng.</p>
    </div>
</div>

<!-- DATA TABLE CARD -->
<div class="table-card">
    <div class="table-card-header">
        <h3 class="table-card-title">Danh sách Khách hàng</h3>
        <span style="font-size: 0.85rem; color: var(--text-muted);">Tổng cộng: <strong><?php echo $total_users; ?></strong> tài khoản</span>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>KHÁCH HÀNG</th>
                    <th>VAI TRÒ</th>
                    <th>ĐIỂM UY TÍN</th>
                    <th>TRẠNG THÁI</th>
                    <th style="text-align: center;">THAO TÁC</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0) : ?>
                    <?php while ($row = $result->fetch_assoc()) : 
                        $first_char = mb_substr(trim($row['ho_ten']), 0, 1, 'UTF-8');
                        $uy_tin = (int)($row['diem_uy_tin'] ?? 100);
                        $bar_color = ($uy_tin >= 80) ? '#007a64' : (($uy_tin >= 60) ? '#0284c7' : (($uy_tin >= 40) ? '#d97706' : '#dc2626'));
                    ?>
                    <tr>
                        <!-- Khách hàng -->
                        <td>
                            <div class="user-cell">
                                <div class="avatar-letter avatar-teal">
                                    <?php echo htmlspecialchars(mb_strtoupper($first_char, 'UTF-8')); ?>
                                </div>
                                <div class="user-cell-info">
                                    <span class="user-cell-name"><?php echo htmlspecialchars($row['ho_ten']); ?></span>
                                    <span class="user-cell-sub"><?php echo htmlspecialchars($row['email'] ?: $row['sdt']); ?></span>
                                </div>
                            </div>
                        </td>

                        <!-- Vai trò -->
                        <td>
                            <span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: var(--radius-full); font-size: 0.78rem; font-weight: 700;">
                                Khách hàng
                            </span>
                        </td>

                        <!-- Điểm uy tín (Progress Bar) -->
                        <td style="min-width: 160px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; background: #e2e8f0; height: 8px; border-radius: var(--radius-full); overflow: hidden;">
                                    <div style="width: <?php echo max(0, min(100, $uy_tin)); ?>%; background: <?php echo $bar_color; ?>; height: 100%; border-radius: var(--radius-full); transition: width 0.3s ease;"></div>
                                </div>
                                <span style="font-weight: 800; font-size: 0.88rem; color: <?php echo $bar_color; ?>; min-width: 28px;">
                                    <?php echo $uy_tin; ?>
                                </span>
                            </div>
                            <?php if (!empty($row['so_lan_bung_san']) && (int)$row['so_lan_bung_san'] > 0 && $uy_tin < 80): ?>
                                <span style="color: #dc2626; font-size: 0.72rem; font-weight: 700; display: block; margin-top: 2px;">
                                    Bùng <?php echo $row['so_lan_bung_san']; ?> lần
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Trạng thái -->
                        <td>
                            <?php if ($row['trang_thai'] == 'active' || is_null($row['trang_thai'])) : ?>
                                <span class="badge badge-xacnhan"><span class="badge-dot"></span> Hoạt động</span>
                            <?php elseif ($row['trang_thai'] == 'banned') : ?>
                                <span class="badge badge-huy"><span class="badge-dot"></span> Đã khóa</span>
                            <?php else : ?>
                                <span class="badge badge-hoantien"><span class="badge-dot"></span> Vô hiệu hóa</span>
                            <?php endif; ?>
                        </td>

                        <!-- Thao tác -->
                        <td style="text-align: center;">
                            <div class="action-buttons-group" style="justify-content: center;">
                                <a href="sua.php?id=<?php echo $row['nguoi_dung_id']; ?>" class="btn-action-icon btn-edit" title="Chỉnh sửa thông tin">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <button type="button" class="btn-action-icon" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;" title="Xem lịch sử tín dụng" onclick="openReputationModal(<?php echo $row['nguoi_dung_id']; ?>)">
                                    <i class="fas fa-history"></i>
                                </button>

                                <?php if ($row['trang_thai'] == 'banned') : ?>
                                    <form method="POST" action="capnhat_trangthai.php" style="display:inline;" onsubmit="return confirm('Mở khóa tài khoản này?');">
                                        <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>
                                        <input type="hidden" name="id" value="<?php echo $row['nguoi_dung_id']; ?>">
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="btn-action-icon btn-accept" title="Mở khóa tài khoản" style="background:none; border:none; cursor:pointer; padding:0;">
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                    </form>
                                <?php else : ?>
                                    <form method="POST" action="capnhat_trangthai.php" style="display:inline;" onsubmit="return confirm('Khóa tài khoản này?');">
                                        <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>
                                        <input type="hidden" name="id" value="<?php echo $row['nguoi_dung_id']; ?>">
                                        <input type="hidden" name="status" value="banned">
                                        <button type="submit" class="btn-action-icon btn-cancel" title="Khóa tài khoản" style="background:none; border:none; cursor:pointer; padding:0;">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align: center; color: var(--text-muted);">
                            Chưa có tài khoản khách hàng nào trong hệ thống.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Đồng bộ tự động các trận bóng đã kết thúc
syncCompletedMatchesReputation($conn);
?>
<!-- MODAL XEM LỊCH SỬ TÍN DỤNG -->
<div id="reputationHistoryModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: #ffffff; width: 95%; max-width: 820px; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); overflow: hidden; margin: 20px; animation: modalFadeIn 0.2s ease;">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;">
                Lịch sử điểm tín dụng
            </h3>
            <button type="button" onclick="closeReputationModal()" style="background: none; border: none; font-size: 1.4rem; color: #64748b; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <div id="reputationModalBody" style="padding: 24px;">
            <div style="text-align: center; padding: 30px; color: #64748b;">
                <i class="fas fa-spinner fa-spin" style="font-size: 1.8rem; color: #007a64; margin-bottom: 10px;"></i>
                <p>Đang tải dữ liệu lịch sử tín dụng...</p>
            </div>
        </div>
        <div style="padding: 14px 24px; border-top: 1px solid #e2e8f0; text-align: right; background: #f8fafc;">
            <button type="button" class="btn-outline-modern" onclick="closeReputationModal()" style="padding: 8px 18px; font-size: 0.88rem;">Đóng</button>
        </div>
    </div>
</div>

<?php include '../footer_admin.php'; ?>