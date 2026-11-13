<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

// Kiểm tra quyền admin
require_admin();

$filter_date = isset($_GET['date']) ? clean_input($_GET['date']) : '';
$min_date = '2025-01-01';
if (!empty($filter_date) && $filter_date < $min_date) {
    $filter_date = $min_date;
}
$pay_method = isset($_GET['method']) ? clean_input($_GET['method']) : 'all';

// Xây dựng điều kiện truy vấn
$where_clauses = ["ds.deleted_at IS NULL"];
$params = [];
$param_types = "";

if (!empty($filter_date)) {
    $where_clauses[] = "DATE(ds.ngay_dat) = ?";
    $params[] = $filter_date;
    $param_types .= "s";
}

if ($pay_method !== 'all' && !empty($pay_method)) {
    $where_clauses[] = "tt.phuong_thuc = ?";
    $params[] = $pay_method;
    $param_types .= "s";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

$sql = "SELECT ds.dat_san_id, 
               nd.ho_ten, 
               nd.sdt, 
               nd.email,
               sb.ten_san, 
               ds.ten_san_con, 
               ds.loai_san_dat,
               kg.gio_bat_dau, 
               kg.gio_ket_thuc, 
               COALESCE(CASE WHEN ds.loai_san_dat = 'san_7' AND kg.gia_tien_san_7 IS NOT NULL THEN kg.gia_tien_san_7 ELSE kg.gia_tien END, kg.gia_tien) as gia_tien,
               COALESCE(tt.phuong_thuc, 'Chưa xác định') as phuong_thuc,
               ds.trang_thai, 
               ds.ngay_dat,
               ds.ngay_tao
        FROM dat_san ds
        JOIN nguoi_dung nd ON ds.nguoi_dung_id = nd.nguoi_dung_id
        JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
        JOIN san_bong sb ON kg.san_id = sb.san_id
        LEFT JOIN thanh_toan tt ON ds.dat_san_id = tt.dat_san_id
        $where_sql
        ORDER BY ds.ngay_dat DESC, ds.dat_san_id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
$total_revenue = 0;
$total_bookings = 0;
$confirmed_bookings = 0;
$pending_bookings = 0;
$refund_pending_bookings = 0;
$refunded_bookings = 0;
$cancelled_bookings = 0;
$transfer_count = 0;
$cash_count = 0;

while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
    $total_bookings++;
    if ($row['trang_thai'] === 'Đã xác nhận') {
        $total_revenue += floatval($row['gia_tien']);
        $confirmed_bookings++;
        if ($row['phuong_thuc'] === 'chuyen_khoan') $transfer_count++;
        elseif ($row['phuong_thuc'] === 'tien_mat') $cash_count++;
    } elseif ($row['trang_thai'] === 'Chờ xác nhận') {
        $pending_bookings++;
    } elseif ($row['trang_thai'] === 'Chờ hoàn tiền') {
        $refund_pending_bookings++;
    } elseif ($row['trang_thai'] === 'Đã hoàn tiền') {
        $refunded_bookings++;
    } else {
        $cancelled_bookings++;
    }
}
$stmt->close();

// File Name
$date_suffix = !empty($filter_date) ? $filter_date : date('Ymd_His');
$filename = "Bao_Cao_Doanh_Thu_SanBong_" . $date_suffix . ".xls";

// Headers for Excel (.xls via XML/HTML format)
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Output UTF-8 BOM
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Báo Cáo Doanh Thu</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
</head>
<body style="font-family: 'Times New Roman', Times, serif; font-size: 13pt; color: #1e293b;">

<table border="1" cellpadding="8" cellspacing="0" style="font-family: 'Times New Roman', Times, serif; border-collapse: collapse; width: 100%;">
    <colgroup>
        <col width="55">   <!-- STT -->
        <col width="90">   <!-- Mã Đơn -->
        <col width="180">  <!-- Tên Khách Hàng -->
        <col width="135">  <!-- Số Điện Thoại -->
        <col width="200">  <!-- Email -->
        <col width="240">  <!-- Tên Sân Bóng -->
        <col width="95">   <!-- Sân Con -->
        <col width="95">   <!-- Loại Sân -->
        <col width="140">  <!-- Khung Giờ -->
        <col width="120">  <!-- Ngày Đá -->
        <col width="160">  <!-- Doanh Thu (VNĐ) -->
        <col width="170">  <!-- Phương Thức -->
        <col width="145">  <!-- Trạng Thái -->
        <col width="155">  <!-- Thời Gian Đặt -->
    </colgroup>

    <!-- 1. TIÊU ĐỀ CHÍNH -->
    <tr>
        <th colspan="14" style="background-color: #0f5132; color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 18pt; font-weight: bold; text-align: center; vertical-align: middle; height: 55px; border: 1px solid #0f5132;">
            BÁO CÁO CHI TIẾT DOANH THU &amp; LỊCH SỬ ĐẶT SÂN
        </th>
    </tr>

    <!-- 2. THÔNG TIN BỘ LỌC BÁO CÁO -->
    <tr>
        <td colspan="2" style="background-color: #f1f5f9; font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #cbd5e1;">Thời gian lập:</td>
        <td colspan="5" style="background-color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13pt; text-align: center; vertical-align: middle; border: 1px solid #cbd5e1;"><?php echo date('d/m/Y - H:i:s'); ?> (Ban Quản Trị)</td>
        <td colspan="3" style="background-color: #f1f5f9; font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #cbd5e1;">Bộ lọc ngày đá:</td>
        <td colspan="4" style="background-color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; color: #0284c7; text-align: center; vertical-align: middle; border: 1px solid #cbd5e1;">
            <?php echo !empty($filter_date) ? date('d/m/Y', strtotime($filter_date)) : 'Tất cả thời gian'; ?>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="background-color: #f1f5f9; font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #cbd5e1;">Hình thức thanh toán:</td>
        <td colspan="5" style="background-color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13pt; text-align: center; vertical-align: middle; border: 1px solid #cbd5e1;">
            <?php 
                if ($pay_method === 'chuyen_khoan') echo 'Chuyển khoản VietQR (Đã lọc)';
                elseif ($pay_method === 'tien_mat') echo 'Tiền mặt tại sân (Đã lọc)';
                else echo 'Tất cả phương thức thanh toán';
            ?>
        </td>
        <td colspan="3" style="background-color: #f1f5f9; font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #cbd5e1;">Tình trạng đồng bộ:</td>
        <td colspan="4" style="background-color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13pt; color: #059669; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #cbd5e1;">
            Đã đồng bộ dữ liệu thời gian thực
        </td>
    </tr>

    <!-- DÒNG TRỐNG -->
    <tr><td colspan="14" style="height: 12px; background-color: #ffffff; border: none;"></td></tr>

    <!-- 3. BLOCK KPI TỔNG HỢP -->
    <tr>
        <td colspan="4" style="background-color: #f0f9ff; border: 2px solid #0284c7; text-align: center; vertical-align: middle; padding: 14px; font-family: 'Times New Roman', Times, serif;">
            <div style="font-size: 12pt; color: #0369a1; font-weight: bold; text-transform: uppercase;">TỔNG SỐ LƯỢT ĐẶT SÂN</div>
            <div style="font-size: 22pt; font-weight: bold; color: #0284c7; margin-top: 4px;">
                <?php echo $total_bookings; ?> <span style="font-size: 13pt; font-weight: normal;">lượt</span>
            </div>
        </td>
        <td colspan="5" style="background-color: #f0fdf4; border: 2px solid #16a34a; text-align: center; vertical-align: middle; padding: 14px; font-family: 'Times New Roman', Times, serif;">
            <div style="font-size: 12pt; color: #15803d; font-weight: bold; text-transform: uppercase;">ĐƠN THÀNH CÔNG (ĐÃ CHỐT)</div>
            <div style="font-size: 22pt; font-weight: bold; color: #16a34a; margin-top: 4px;">
                <?php echo $confirmed_bookings; ?> <span style="font-size: 13pt; font-weight: normal;">đơn</span>
                <br>
                <span style="font-size: 12pt; color: #475569; font-weight: normal;">(Chuyển khoản: <?php echo $transfer_count; ?> &bull; Tiền mặt: <?php echo $cash_count; ?>)</span>
            </div>
        </td>
        <td colspan="5" style="background-color: #fff7ed; border: 3px solid #ea580c; text-align: center; vertical-align: middle; padding: 14px; font-family: 'Times New Roman', Times, serif;">
            <div style="font-size: 12pt; color: #9a3412; font-weight: bold; text-transform: uppercase;">TỔNG DOANH THU THỰC THU</div>
            <div style="font-size: 26pt; font-weight: 900; color: #ea580c; margin-top: 4px;">
                <?php echo number_format($total_revenue, 0, '.', ','); ?> <span style="font-size: 15pt; font-weight: bold;">VNĐ</span>
            </div>
        </td>
    </tr>

    <!-- DÒNG TRỐNG -->
    <tr><td colspan="14" style="height: 14px; background-color: #ffffff; border: none;"></td></tr>

    <!-- 4. TIÊU ĐỀ CÁC CỘT DỮ LIỆU -->
    <tr>
        <th style="background-color: #1e3a5f; color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13.5pt; font-weight: bold; text-align: center; vertical-align: middle; height: 42px; border: 1px solid #0f2744;">STT</th>
        <th style="background-color: #1e3a5f; color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13.5pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #0f2744;">Mã Đơn</th>
        <th style="background-color: #1e3a5f; color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13.5pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #0f2744;">Khách Hàng</th>
        <th style="background-color: #1e3a5f; color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13.5pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #0f2744;">Số Điện Thoại</th>
        <th style="background-color: #1e3a5f; color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13.5pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #0f2744;">Email Liên Hệ</th>
        <th style="background-color: #1e3a5f; color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13.5pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #0f2744;">Tên Sân Bóng</th>
        <th style="background-color: #1e3a5f; color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13.5pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #0f2744;">Sân Con</th>
        <th style="background-color: #1e3a5f; color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13.5pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #0f2744;">Loại Sân</th>
        <th style="background-color: #1e3a5f; color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13.5pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #0f2744;">Khung Giờ</th>
        <th style="background-color: #1e3a5f; color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13.5pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #0f2744;">Ngày Đá</th>
        <th style="background-color: #1e3a5f; color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13.5pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #0f2744;">Doanh Thu (VNĐ)</th>
        <th style="background-color: #1e3a5f; color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13.5pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #0f2744;">Phương Thức</th>
        <th style="background-color: #1e3a5f; color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13.5pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #0f2744;">Trạng Thái</th>
        <th style="background-color: #1e3a5f; color: #ffffff; font-family: 'Times New Roman', Times, serif; font-size: 13.5pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #0f2744;">Thời Gian Đặt</th>
    </tr>

    <!-- 5. DANH SÁCH CHI TIẾT VỚI MÀU SẮC TRỰC QUAN INLINE 100% -->
    <?php if (count($rows) > 0): ?>
        <?php $stt = 1; ?>
        <?php foreach ($rows as $r): ?>
            <?php 
                $bg_row = ($stt % 2 == 0) ? '#f8fafc' : '#ffffff';
                $loai_san_txt = ($r['loai_san_dat'] === 'san_7') ? 'Sân 7' : 'Sân 5';
                $khung_gio_txt = date('H:i', strtotime($r['gio_bat_dau'])) . ' - ' . date('H:i', strtotime($r['gio_ket_thuc']));
                $ngay_da_txt = date('d/m/Y', strtotime($r['ngay_dat']));
                $ngay_tao_txt = !empty($r['ngay_tao']) ? date('d/m/Y H:i', strtotime($r['ngay_tao'])) : '';
                
                // Format phương thức thanh toán
                if ($r['phuong_thuc'] === 'chuyen_khoan') {
                    $pt_style = 'background-color: #eff6ff; color: #1d4ed8; font-weight: bold;';
                    $pt_label = 'Chuyển khoản VietQR';
                } elseif ($r['phuong_thuc'] === 'tien_mat') {
                    $pt_style = 'background-color: #ecfdf5; color: #047857; font-weight: bold;';
                    $pt_label = 'Tiền mặt tại sân';
                } else {
                    $pt_style = 'background-color: #f1f5f9; color: #64748b;';
                    $pt_label = 'Chưa xác định';
                }

                // Trạng thái badge inline
                if ($r['trang_thai'] === 'Đã xác nhận') {
                    $st_style = 'background-color: #dcfce7; color: #15803d; font-weight: bold;';
                    $st_label = 'Đã xác nhận';
                } elseif ($r['trang_thai'] === 'Chờ xác nhận') {
                    $st_style = 'background-color: #fef3c7; color: #b45309; font-weight: bold;';
                    $st_label = 'Chờ duyệt';
                } elseif ($r['trang_thai'] === 'Chờ hoàn tiền') {
                    $st_style = 'background-color: #ede9fe; color: #6d28d9; font-weight: bold;';
                    $st_label = 'Chờ hoàn tiền';
                } elseif ($r['trang_thai'] === 'Đã hoàn tiền') {
                    $st_style = 'background-color: #dbeafe; color: #1d4ed8; font-weight: bold;';
                    $st_label = 'Đã hoàn tiền';
                } else {
                    $st_style = 'background-color: #fee2e2; color: #b91c1c; font-weight: bold;';
                    $st_label = 'Đã hủy';
                }
            ?>
            <tr style="background-color: <?php echo $bg_row; ?>;">
                <td style="border: 1px solid #cbd5e1; font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; color: #64748b; text-align: center; vertical-align: middle;"><?php echo $stt++; ?></td>
                <td style="border: 1px solid #cbd5e1; font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; color: #1e3a5f; background-color: #f0f9ff; text-align: center; vertical-align: middle;">#<?php echo $r['dat_san_id']; ?></td>
                <td style="border: 1px solid #cbd5e1; font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; color: #0f172a; text-align: center; vertical-align: middle;"><?php echo htmlspecialchars($r['ho_ten']); ?></td>
                <td style="border: 1px solid #cbd5e1; font-family: 'Times New Roman', Times, serif; font-size: 13pt; mso-number-format: '\@'; font-weight: bold; text-align: center; vertical-align: middle;"><?php echo htmlspecialchars($r['sdt']); ?></td>
                <td style="border: 1px solid #cbd5e1; font-family: 'Times New Roman', Times, serif; font-size: 13pt; color: #475569; text-align: center; vertical-align: middle;"><?php echo htmlspecialchars($r['email']); ?></td>
                <td style="border: 1px solid #cbd5e1; font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; color: #0f766e; text-align: center; vertical-align: middle;"><?php echo htmlspecialchars($r['ten_san']); ?></td>
                <td style="border: 1px solid #cbd5e1; font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; text-align: center; vertical-align: middle;"><?php echo htmlspecialchars($r['ten_san_con']); ?></td>
                <td style="border: 1px solid #cbd5e1; font-family: 'Times New Roman', Times, serif; font-size: 13pt; text-align: center; vertical-align: middle;"><?php echo $loai_san_txt; ?></td>
                <td style="border: 1px solid #cbd5e1; font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; color: #0369a1; text-align: center; vertical-align: middle;"><?php echo $khung_gio_txt; ?></td>
                <td style="border: 1px solid #cbd5e1; font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; text-align: center; vertical-align: middle;"><?php echo $ngay_da_txt; ?></td>
                <td style="border: 1px solid #cbd5e1; font-family: 'Times New Roman', Times, serif; font-size: 13.5pt; font-weight: bold; color: #047857; background-color: #f0fdf4; text-align: center; vertical-align: middle; mso-number-format: '\#\,\#\#0';"><?php echo number_format((float)$r['gia_tien'], 0, '.', ','); ?></td>
                <td style="border: 1px solid #cbd5e1; font-family: 'Times New Roman', Times, serif; font-size: 12.5pt; text-align: center; vertical-align: middle; <?php echo $pt_style; ?>"><?php echo $pt_label; ?></td>
                <td style="border: 1px solid #cbd5e1; font-family: 'Times New Roman', Times, serif; font-size: 12.5pt; text-align: center; vertical-align: middle; <?php echo $st_style; ?>"><?php echo $st_label; ?></td>
                <td style="border: 1px solid #cbd5e1; font-family: 'Times New Roman', Times, serif; font-size: 12pt; color: #64748b; text-align: center; vertical-align: middle;"><?php echo $ngay_tao_txt; ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="14" style="border: 1px solid #cbd5e1; font-family: 'Times New Roman', Times, serif; padding: 35px; color: #94a3b8; font-size: 15pt; text-align: center; vertical-align: middle;">Không tìm thấy dữ liệu đặt sân phù hợp trong khoảng thời gian này.</td>
        </tr>
    <?php endif; ?>

    <!-- 6. DÒNG TỔNG KẾT DOANH THU CUỐI BẢNG -->
    <tr>
        <td colspan="10" style="background-color: #1e3a5f; color: #ffffff; font-family: 'Times New Roman', Times, serif; border: 1px solid #1e3a5f; padding: 14px; font-size: 14pt; font-weight: bold; text-align: center; vertical-align: middle; text-transform: uppercase;">
            TỔNG DOANH THU THỰC THU (CÁC ĐƠN ĐÃ CHỐT):
        </td>
        <td style="background-color: #7c2d12; color: #ffedd5; font-family: 'Times New Roman', Times, serif; border: 1px solid #7c2d12; font-size: 16pt; font-weight: 900; padding: 14px 10px; text-align: center; vertical-align: middle; mso-number-format: '\@';">
            <?php echo number_format($total_revenue, 0, '.', ','); ?> VNĐ
        </td>
        <td colspan="3" style="background-color: #1e3a5f; color: #cbd5e1; font-family: 'Times New Roman', Times, serif; border: 1px solid #1e3a5f; font-size: 13pt; text-align: center; vertical-align: middle;">
            (<?php echo $confirmed_bookings; ?> đơn thành công / <?php echo $total_bookings; ?> tổng đơn)
        </td>
    </tr>
</table>

</body>
</html>
