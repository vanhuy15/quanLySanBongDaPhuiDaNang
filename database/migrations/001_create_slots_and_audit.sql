-- Migration 001: Tạo bảng quản lý chi tiết slot sân con và Unique Constraint cấp CSDL
CREATE TABLE IF NOT EXISTS `dat_san_slots` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `dat_san_id` INT NOT NULL,
    `khung_gio_id` INT NOT NULL,
    `ngay_dat` DATE NOT NULL,
    `ten_san_con` VARCHAR(50) NOT NULL,
    `trang_thai` ENUM('active', 'released') DEFAULT 'active',
    `active_key` VARCHAR(100) GENERATED ALWAYS AS (IF(`trang_thai` = 'active', CONCAT(`khung_gio_id`, '_', `ngay_dat`, '_', `ten_san_con`), NULL)) STORED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_active_slot` (`active_key`),
    INDEX `idx_dat_san_id` (`dat_san_id`),
    INDEX `idx_slot_lookup` (`khung_gio_id`, `ngay_dat`, `ten_san_con`, `trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration 002: Bảng Audit Logs lưu vết thao tác Admin
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `target_type` VARCHAR(50) NOT NULL,
    `target_id` INT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_action` (`user_id`, `action`),
    INDEX `idx_target` (`target_type`, `target_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
