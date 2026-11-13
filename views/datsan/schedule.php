<?php
/**
 * View Partial: Schedule Table & Pitch Map
 * Rendered by DatSanController::getSchedule()
 */
use Models\DatSan;
?>
<!-- Header & Mode Switcher -->
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
    <h3 class="lich-header" style="margin:0;">
        Lịch sân "<?php echo htmlspecialchars($ten_san_xem); ?>" ngày <?php echo date("d/m/Y", strtotime($ngay_dat)); ?>
    </h3>

    <?php if ($loai_san_goc === 'san_5_7'): ?>
        <!-- Toggle Mode: Sân 5 / Sân 7 -->
        <div style="display: flex; background: #e2e8f0; padding: 4px; border-radius: 50px; gap: 4px;">
            <button type="button" onclick="switchBookingMode('san_5')" style="border: none; padding: 6px 16px; border-radius: 50px; font-size: 0.86rem; font-weight: 700; cursor: pointer; transition: all 0.2s; <?php echo ($view_mode === 'san_5') ? 'background: #0d9488; color: white;' : 'background: transparent; color: #475569;'; ?>">
                Đặt Sân 5
            </button>
            <button type="button" onclick="switchBookingMode('san_7')" style="border: none; padding: 6px 16px; border-radius: 50px; font-size: 0.86rem; font-weight: 700; cursor: pointer; transition: all 0.2s; <?php echo ($view_mode === 'san_7') ? 'background: #0d9488; color: white;' : 'background: transparent; color: #475569;'; ?>">
                Đặt Sân 7 (Ghép <?php echo $quy_tac_ghep; ?> sân)
            </button>
        </div>
    <?php endif; ?>
</div>

<table class="lichsu-table" style="margin-top:15px;">
    <thead>
        <tr>
            <th>Khung giờ</th>
            <th>Giá tiền (<?php echo ($view_mode === 'san_7') ? 'Sân 7' : 'Sân 5'; ?>)</th>
            <th>Trạng thái (Click vào sân trống để chọn)</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($kg = $khung_gio_list->fetch_assoc()) : 
            // Lấy tất cả các sân con bị khóa trong khung giờ này qua Model
            $locked_sub_pitches = DatSan::getLockedSubPitches($kg['khung_gio_id'], $ngay_dat);

            // Giá tiền theo chế độ
            if ($view_mode === 'san_7') {
                $gia_hien_thi = !empty($kg['gia_tien_san_7']) ? $kg['gia_tien_san_7'] : ($kg['gia_tien'] * ($quy_tac_ghep > 0 ? $quy_tac_ghep : 1.6));
            } else {
                $gia_hien_thi = $kg['gia_tien'];
            }

            // Tính số sân còn lại theo chế độ
            $has_available_slot = false;
        ?>
            <tr class="schedule-row">
                <td><strong><?php echo date("H:i", strtotime($kg['gio_bat_dau'])) . ' - ' . date("H:i", strtotime($kg['gio_ket_thuc'])); ?></strong></td>
                <td class="price-cell"><?php echo number_format($gia_hien_thi); ?> VND</td>

                <td>
                    <div class="mini-pitch-map" style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                        <?php
                        if ($view_mode === 'san_5') {
                            // Chế độ xem & đặt Sân 5
                            for ($i = 0; $i < $tong_so_san; $i++) {
                                $pitch_name = $sub_pitches[$i] ?? ($i + 1);
                                $is_booked = in_array($pitch_name, $locked_sub_pitches);
                                
                                if (!$is_booked) $has_available_slot = true;

                                if ($is_booked) {
                                    // SÂN 5 - ĐÃ ĐẶT: đỏ, X mark
                                    echo "<svg class='mini-pitch-svg booked' viewBox='0 0 80 115' title='Sân $pitch_name đã đặt'><rect width='80' height='115' rx='9' fill='#ef4444'/><rect x='6' y='6' width='68' height='76' rx='3' fill='none' stroke='white' stroke-width='1.2' opacity='0.25'/><line x1='6' y1='44' x2='74' y2='44' stroke='white' stroke-width='1.2' opacity='0.25'/><circle cx='40' cy='44' r='9' fill='none' stroke='white' stroke-width='1.2' opacity='0.25'/><line x1='28' y1='26' x2='52' y2='60' stroke='white' stroke-width='3.5' stroke-linecap='round' opacity='0.9'/><line x1='52' y1='26' x2='28' y2='60' stroke='white' stroke-width='3.5' stroke-linecap='round' opacity='0.9'/><rect x='12' y='91' width='56' height='18' rx='9' fill='rgba(255,255,255,0.18)'/><text x='40' y='100' text-anchor='middle' dominant-baseline='middle' font-family='Inter,sans-serif' font-size='9' font-weight='700' fill='rgba(255,255,255,0.85)'>Đã đặt</text></svg>";
                                } else {
                                    // SÂN 5 - TRỐNG: teal xanh, field lines, letter badge
                                    echo "<svg class='mini-pitch-svg available' data-pitch-name='$pitch_name' data-lock-keys='$pitch_name' data-mode='san_5' viewBox='0 0 80 115' title='Sân $pitch_name (Trống)'><rect class='pitch-fill' width='80' height='115' rx='9' fill='#0d9488'/><rect x='6' y='6' width='68' height='76' rx='3' fill='none' stroke='white' stroke-width='1.3' opacity='0.4'/><line x1='6' y1='44' x2='74' y2='44' stroke='white' stroke-width='1.3' opacity='0.4'/><circle cx='40' cy='44' r='9' fill='none' stroke='white' stroke-width='1.3' opacity='0.4'/><circle cx='40' cy='44' r='2' fill='white' opacity='0.5'/><rect x='20' y='6' width='40' height='10' fill='none' stroke='white' stroke-width='1' opacity='0.28'/><rect x='20' y='72' width='40' height='10' fill='none' stroke='white' stroke-width='1' opacity='0.28'/><rect x='16' y='90' width='48' height='19' rx='9.5' fill='white' opacity='0.95'/><text x='40' y='100' text-anchor='middle' dominant-baseline='middle' font-family='Inter,sans-serif' font-size='13' font-weight='800' fill='#0d9488'>$pitch_name</text></svg>";
                                }
                            }
                        } else {
                            // Chế độ xem & đặt Sân 7 (Ghép từ các sân 5)
                            foreach ($groups_san_7 as $g7) {
                                $g_name = $g7['name'];
                                $g_comps = $g7['components'];
                                $g_key = $g7['key'];

                                // Sân 7 chỉ khả dụng khi TẤT CẢ các sân thành phần đều KHÔNG bị khóa
                                $conflict_pitches = array_intersect($g_comps, $locked_sub_pitches);
                                $is_group_locked = (count($conflict_pitches) > 0);

                                if (!$is_group_locked) $has_available_slot = true;

                                if ($is_group_locked) {
                                    $conflict_str = implode(', ', $conflict_pitches);
                                    $g_num = $g7['num'] ?? 1;
                                    // SÂN 7 - ĐÃ ĐẶT: đỏ, field markings mờ, X mark
                                    echo "<svg class='mini-pitch-svg pitch-7 booked' viewBox='0 0 95 130' title='Sân 7.$g_num bị chặn ($conflict_str đã đặt)'><rect width='95' height='130' rx='9' fill='#ef4444'/><rect x='7' y='8' width='81' height='96' rx='3' fill='none' stroke='white' stroke-width='1.3' opacity='0.2'/><line x1='7' y1='56' x2='88' y2='56' stroke='white' stroke-width='1.3' opacity='0.2'/><circle cx='47.5' cy='56' r='11' fill='none' stroke='white' stroke-width='1.3' opacity='0.2'/><rect x='24' y='8' width='47' height='13' fill='none' stroke='white' stroke-width='1' opacity='0.15'/><path d='M 38.5 21 A 9 9 0 0 0 56.5 21' fill='none' stroke='white' stroke-width='1' opacity='0.15'/><rect x='24' y='91' width='47' height='13' fill='none' stroke='white' stroke-width='1' opacity='0.15'/><path d='M 38.5 91 A 9 9 0 0 1 56.5 91' fill='none' stroke='white' stroke-width='1' opacity='0.15'/><line x1='30' y1='34' x2='65' y2='78' stroke='white' stroke-width='3.5' stroke-linecap='round' opacity='0.9'/><line x1='65' y1='34' x2='30' y2='78' stroke='white' stroke-width='3.5' stroke-linecap='round' opacity='0.9'/><rect x='15' y='109' width='65' height='18' rx='9' fill='rgba(255,255,255,0.18)'/><text x='47.5' y='118' text-anchor='middle' dominant-baseline='middle' font-family='Inter,sans-serif' font-size='9' font-weight='700' fill='rgba(255,255,255,0.85)'>Đã đặt</text></svg>";
                                } else {
                                    $g_num = $g7['num'] ?? 1;
                                    // SÂN 7 - TRỐNG: teal xanh, penalty box + D-arc 2 đầu, badge 7.N
                                    echo "<svg class='mini-pitch-svg pitch-7 available' data-pitch-name='$g_name' data-lock-keys='$g_key' data-mode='san_7' viewBox='0 0 95 130' title='$g_name (Trống)'><rect class='pitch-fill' width='95' height='130' rx='9' fill='#0d9488'/><rect x='7' y='8' width='81' height='96' rx='3' fill='none' stroke='white' stroke-width='1.3' opacity='0.4'/><line x1='7' y1='56' x2='88' y2='56' stroke='white' stroke-width='1.3' opacity='0.4'/><circle cx='47.5' cy='56' r='11' fill='none' stroke='white' stroke-width='1.3' opacity='0.4'/><circle cx='47.5' cy='56' r='2' fill='white' opacity='0.5'/><rect x='24' y='8' width='47' height='13' fill='none' stroke='white' stroke-width='1' opacity='0.32'/><path d='M 38.5 21 A 9 9 0 0 0 56.5 21' fill='none' stroke='white' stroke-width='1' opacity='0.32'/><rect x='24' y='91' width='47' height='13' fill='none' stroke='white' stroke-width='1' opacity='0.32'/><path d='M 38.5 91 A 9 9 0 0 1 56.5 91' fill='none' stroke='white' stroke-width='1' opacity='0.32'/><rect x='15' y='109' width='65' height='19' rx='9.5' fill='white' opacity='0.95'/><text x='47.5' y='118.5' text-anchor='middle' dominant-baseline='middle' font-family='Inter,sans-serif' font-size='12' font-weight='800' fill='#0d9488'>7.$g_num</text></svg>";
                                }
                            }
                        }
                        ?>
                    </div>
                </td>
                <td>
                    <form onsubmit="submitBooking(event, this)" style="margin:0;">
                        <input type="hidden" name="khung_gio_id" value="<?php echo $kg['khung_gio_id']; ?>">
                        <input type="hidden" name="ngay_dat" value="<?php echo $ngay_dat; ?>">
                        <input type="hidden" name="loai_san_dat" value="<?php echo $view_mode; ?>">
                        <input type="hidden" name="ten_san_con" class="hidden-pitch-name" value="">
                        <input type="hidden" name="san_con_khoa" class="hidden-lock-keys" value="">
                        <button type="submit" class="btn-thanhtoan" <?php echo (!$has_available_slot) ? 'disabled' : ''; ?>>
                            <?php echo (!$has_available_slot) ? 'Đã Hết' : 'Đặt Ngay'; ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

