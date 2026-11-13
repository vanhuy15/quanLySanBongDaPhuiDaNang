<?php
session_start();
include '../../includes/db.php';
require_admin();
// Chức năng khôi phục điểm uy tín thủ công đã được vô hiệu hóa (người dùng tự tích lũy điểm qua các trận đấu)
header("Location: index.php");
exit();

