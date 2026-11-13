-- Migration 000: Khởi tạo cấu trúc CSDL cơ sở (Base Tables)
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `nguoi_dung` (
  `nguoi_dung_id` int(11) NOT NULL AUTO_INCREMENT,
  `ten_dang_nhap` varchar(50) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `ho_ten` varchar(50) NOT NULL,
  `sdt` varchar(10) NOT NULL,
  `email` varchar(100) NOT NULL,
  `vai_tro` enum('admin','khach_hang') NOT NULL DEFAULT 'khach_hang',
  `trang_thai` varchar(20) NOT NULL DEFAULT 'active',
  `avatar` varchar(255) DEFAULT 'images/avatar/default_avatar.png',
  `so_lan_bung_san` int(11) DEFAULT 0,
  `diem_uy_tin` int(11) DEFAULT 100,
  PRIMARY KEY (`nguoi_dung_id`),
  UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `san_bong` (
  `san_id` int(11) NOT NULL AUTO_INCREMENT,
  `ten_san` varchar(100) NOT NULL,
  `dia_chi` varchar(255) NOT NULL,
  `so_luong` int(11) NOT NULL DEFAULT 1,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `loai_san` enum('san_5','san_5_7','san_7') NOT NULL DEFAULT 'san_5',
  `quy_tac_ghep` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0: Không ghép, 2: 2 sân 5 = 1 sân 7, 3: 3 sân 5 = 1 sân 7',
  `gia_san_7` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`san_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `khung_gio` (
  `khung_gio_id` int(11) NOT NULL AUTO_INCREMENT,
  `san_id` int(11) NOT NULL,
  `gio_bat_dau` time NOT NULL,
  `gio_ket_thuc` time NOT NULL,
  `gia_tien` decimal(10,2) NOT NULL,
  `gia_tien_san_7` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`khung_gio_id`),
  KEY `san_id` (`san_id`),
  CONSTRAINT `fk_khunggio_san` FOREIGN KEY (`san_id`) REFERENCES `san_bong` (`san_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dat_san` (
  `dat_san_id` int(11) NOT NULL AUTO_INCREMENT,
  `nguoi_dung_id` int(11) NOT NULL,
  `khung_gio_id` int(11) NOT NULL,
  `ngay_dat` date NOT NULL,
  `trang_thai` enum('Chờ thanh toán','Chờ xác nhận','Đã xác nhận','Hủy','Đã hủy','Chờ hoàn tiền','Đã hoàn tiền') NOT NULL DEFAULT 'Chờ thanh toán',
  `ten_san_con` varchar(10) DEFAULT NULL,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `loai_san_dat` enum('san_5','san_7') NOT NULL DEFAULT 'san_5',
  `san_con_khoa` varchar(50) DEFAULT NULL COMMENT 'Danh sách sân con bị khóa, VD: A,B,C',
  `thong_tin_hoan_tien` text DEFAULT NULL,
  PRIMARY KEY (`dat_san_id`),
  KEY `nguoi_dung_id` (`nguoi_dung_id`),
  KEY `khung_gio_id` (`khung_gio_id`),
  CONSTRAINT `fk_datsan_khunggio` FOREIGN KEY (`khung_gio_id`) REFERENCES `khung_gio` (`khung_gio_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_datsan_user` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`nguoi_dung_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `thanh_toan` (
  `thanh_toan_id` int(11) NOT NULL AUTO_INCREMENT,
  `dat_san_id` int(11) NOT NULL,
  `phuong_thuc` enum('tien_mat','chuyen_khoan') NOT NULL,
  `trang_thai` enum('cho_xu_ly','da_thanh_toan','huy') NOT NULL DEFAULT 'cho_xu_ly',
  `ngay_thanh_toan` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`thanh_toan_id`),
  KEY `dat_san_id` (`dat_san_id`),
  CONSTRAINT `fk_thanhtoan_datsan` FOREIGN KEY (`dat_san_id`) REFERENCES `dat_san` (`dat_san_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `danh_gia` (
  `danh_gia_id` int(11) NOT NULL AUTO_INCREMENT,
  `san_id` int(11) NOT NULL,
  `nguoi_dung_id` int(11) NOT NULL,
  `dat_san_id` int(11) DEFAULT NULL,
  `so_sao` tinyint(4) NOT NULL DEFAULT 5,
  `noi_dung` text DEFAULT NULL,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`danh_gia_id`),
  KEY `idx_san` (`san_id`),
  KEY `idx_nguoi_dung` (`nguoi_dung_id`),
  KEY `idx_dat_san` (`dat_san_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `thong_bao` (
  `thong_bao_id` int(11) NOT NULL AUTO_INCREMENT,
  `nguoi_dung_id` int(11) NOT NULL,
  `tieu_de` varchar(255) NOT NULL,
  `noi_dung` text NOT NULL,
  `loai` varchar(50) DEFAULT 'info',
  `lien_ket` varchar(255) DEFAULT NULL,
  `da_doc` tinyint(1) DEFAULT 0,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`thong_bao_id`),
  KEY `nguoi_dung_id` (`nguoi_dung_id`),
  CONSTRAINT `thong_bao_ibfk_1` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`nguoi_dung_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `lich_su_uy_tin` (
  `lich_su_id` int(11) NOT NULL AUTO_INCREMENT,
  `nguoi_dung_id` int(11) NOT NULL,
  `so_diem` int(11) NOT NULL,
  `diem_sau_cung` int(11) NOT NULL,
  `loai_hanh_dong` varchar(50) NOT NULL,
  `ghi_chu` varchar(255) NOT NULL,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`lich_su_id`),
  KEY `nguoi_dung_id` (`nguoi_dung_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `het_han` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed data cơ sở cho Sân bóng & Khung giờ
INSERT IGNORE INTO `san_bong` (`san_id`, `ten_san`, `dia_chi`, `so_luong`, `latitude`, `longitude`, `loai_san`, `quy_tac_ghep`, `gia_san_7`) VALUES ('1', 'Sân bóng đá Trường Đại Học Sư Phạm-Đà Nẵng', '459 Tôn Đức Thắng, Hoà Khánh Nam, Liên Chiểu, Đà Nẵng.', '3', '16.06164936010281', '108.15903725740309', 'san_5', '3', NULL);
INSERT IGNORE INTO `san_bong` (`san_id`, `ten_san`, `dia_chi`, `so_luong`, `latitude`, `longitude`, `loai_san`, `quy_tac_ghep`, `gia_san_7`) VALUES ('2', 'Sân bóng đá 743-Nam Cao', '743 Nam Cao, Hoà Khánh Nam, Liên Chiểu, Đà Nẵng.', '6', '16.062028320991143', '108.14808364199817', 'san_5_7', '3', NULL);
INSERT IGNORE INTO `san_bong` (`san_id`, `ten_san`, `dia_chi`, `so_luong`, `latitude`, `longitude`, `loai_san`, `quy_tac_ghep`, `gia_san_7`) VALUES ('3', 'Sân bóng đá Nguyễn Chánh', '86 Nguyễn Chánh, Hoà Khánh Bắc, Liên Chiểu, Đà Nẵng.', '3', '16.08429337929586', '108.1508912537842', 'san_5', '0', NULL);
INSERT IGNORE INTO `san_bong` (`san_id`, `ten_san`, `dia_chi`, `so_luong`, `latitude`, `longitude`, `loai_san`, `quy_tac_ghep`, `gia_san_7`) VALUES ('4', 'Sân bóng đá Ngọc Thạch', '207 Phạm Như Xương, Hoà Khánh Nam, Liên Chiểu, Đà Nẵng.', '4', '16.062952524409702', '108.14934899561075', 'san_5', '3', NULL);
INSERT IGNORE INTO `san_bong` (`san_id`, `ten_san`, `dia_chi`, `so_luong`, `latitude`, `longitude`, `loai_san`, `quy_tac_ghep`, `gia_san_7`) VALUES ('5', 'Sân bóng đá Chuyên Việt-Hòa Khánh', '151 Âu Cơ, Hoà Khánh Bắc, Liên Chiểu, Đà Nẵng.', '6', '16.069170362977705', '108.14248928120129', 'san_5', '0', NULL);
INSERT IGNORE INTO `san_bong` (`san_id`, `ten_san`, `dia_chi`, `so_luong`, `latitude`, `longitude`, `loai_san`, `quy_tac_ghep`, `gia_san_7`) VALUES ('6', 'Sân Bóng Đá Làng Hòa Mỹ', '120 Nguyễn Huy Tưởng, Hoà An, Liên Chiểu, Đà Nẵng.', '6', '16.050876183545686', '108.16880445896899', 'san_5_7', '3', NULL);
INSERT IGNORE INTO `san_bong` (`san_id`, `ten_san`, `dia_chi`, `so_luong`, `latitude`, `longitude`, `loai_san`, `quy_tac_ghep`, `gia_san_7`) VALUES ('7', 'Sân bóng đá Mini Mikazuki', 'Xuân Thiều 21, Hoà Hiệp Nam, Liên Chiểu, Đà Nẵng.', '2', '16.09473384235854', '108.14357604041129', 'san_5', '0', NULL);
INSERT IGNORE INTO `san_bong` (`san_id`, `ten_san`, `dia_chi`, `so_luong`, `latitude`, `longitude`, `loai_san`, `quy_tac_ghep`, `gia_san_7`) VALUES ('8', 'Sân bóng đá Hòa Phú', 'Nguyễn Chơn, Hoà Minh, Liên Chiểu, Đà Nẵng.', '2', '16.07546070014982', '108.16543511712645', 'san_5', '0', NULL);
INSERT IGNORE INTO `san_bong` (`san_id`, `ten_san`, `dia_chi`, `so_luong`, `latitude`, `longitude`, `loai_san`, `quy_tac_ghep`, `gia_san_7`) VALUES ('9', 'Sân bóng đá KING SPORT', 'Xuân Thiều 2, Hoà Hiệp Nam, Liên Chiểu, Đà Nẵng.', '6', '16.107451338110124', '108.13411466677593', 'san_5_7', '2', NULL);
INSERT IGNORE INTO `san_bong` (`san_id`, `ten_san`, `dia_chi`, `so_luong`, `latitude`, `longitude`, `loai_san`, `quy_tac_ghep`, `gia_san_7`) VALUES ('10', 'Sân bóng đá Chơn Tâm 1', '39 Mộc Bài 1, Hoà Khánh Nam, Liên Chiểu, Đà Nẵng.', '2', '16.0699029554421', '108.15547927243526', 'san_5', '0', NULL);
INSERT IGNORE INTO `san_bong` (`san_id`, `ten_san`, `dia_chi`, `so_luong`, `latitude`, `longitude`, `loai_san`, `quy_tac_ghep`, `gia_san_7`) VALUES ('11', 'Sân bóng đá Trang Hoàng', '86 Duy Tân, Hải Châu, Đà Nẵng', '5', '16.050500', '108.212000', 'san_5', '0', NULL);
INSERT IGNORE INTO `san_bong` (`san_id`, `ten_san`, `dia_chi`, `so_luong`, `latitude`, `longitude`, `loai_san`, `quy_tac_ghep`, `gia_san_7`) VALUES ('12', 'Sân Bóng Đá Mini Trường Đại Học Thể Dục Thể Thao Đà Nẵng', '44 Đ. Dũng Sĩ Thanh Khê, Thanh Khê Tây, Thanh Khê, Đà Nẵng.', '4', '16.07164650065979', '108.18135454267211', 'san_5', '0', NULL);
INSERT IGNORE INTO `san_bong` (`san_id`, `ten_san`, `dia_chi`, `so_luong`, `latitude`, `longitude`, `loai_san`, `quy_tac_ghep`, `gia_san_7`) VALUES ('14', 'Sân bóng đá Khu Công Nghiệp Hòa Khánh', '439 Âu Cơ, Liên Chiểu, Đà Nẵng.', '1', '16.075053123502883', '108.13434492987044', 'san_7', '3', NULL);
INSERT IGNORE INTO `san_bong` (`san_id`, `ten_san`, `dia_chi`, `so_luong`, `latitude`, `longitude`, `loai_san`, `quy_tac_ghep`, `gia_san_7`) VALUES ('16', 'Sân bóng đá 532 Sport', 'Khánh An 6, Hòa Khánh, Đà Nẵng.', '1', '16.063899831496805', '108.1428029823805', 'san_7', '3', NULL);

INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('1', '1', '16:30:00', '17:30:00', '250000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('2', '1', '17:30:00', '18:30:00', '350000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('3', '1', '18:30:00', '19:30:00', '350000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('4', '1', '19:30:00', '20:30:00', '300000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('5', '1', '20:30:00', '21:30:00', '250000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('6', '2', '16:30:00', '17:30:00', '250000.00', '360000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('7', '2', '17:30:00', '18:30:00', '350000.00', '540000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('8', '2', '18:30:00', '19:30:00', '350000.00', '540000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('9', '2', '19:30:00', '20:30:00', '300000.00', '470000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('10', '2', '20:30:00', '21:30:00', '250000.00', '390000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('11', '3', '16:30:00', '17:30:00', '220000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('12', '3', '17:30:00', '18:30:00', '320000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('13', '3', '18:30:00', '19:30:00', '320000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('14', '3', '19:30:00', '20:30:00', '280000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('15', '3', '20:30:00', '21:30:00', '220000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('16', '4', '16:30:00', '17:30:00', '280000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('17', '4', '17:30:00', '18:30:00', '380000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('18', '4', '18:30:00', '19:30:00', '380000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('19', '4', '19:30:00', '20:30:00', '320000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('20', '4', '20:30:00', '21:30:00', '280000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('21', '5', '16:30:00', '17:30:00', '250000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('22', '5', '17:30:00', '18:30:00', '350000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('23', '5', '18:30:00', '19:30:00', '350000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('24', '5', '19:30:00', '20:30:00', '300000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('25', '5', '20:30:00', '21:30:00', '250000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('26', '6', '16:30:00', '17:30:00', '250000.00', '390000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('27', '6', '17:30:00', '18:30:00', '350000.00', '540000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('28', '6', '18:30:00', '19:30:00', '350000.00', '540000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('29', '6', '19:30:00', '20:30:00', '300000.00', '470000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('30', '6', '20:30:00', '21:30:00', '250000.00', '390000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('31', '7', '16:30:00', '17:30:00', '240000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('32', '7', '17:30:00', '18:30:00', '340000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('33', '7', '18:30:00', '19:30:00', '340000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('34', '7', '19:30:00', '20:30:00', '290000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('35', '7', '20:30:00', '21:30:00', '240000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('36', '8', '16:30:00', '17:30:00', '230000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('37', '8', '17:30:00', '18:30:00', '330000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('38', '8', '18:30:00', '19:30:00', '330000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('39', '8', '19:30:00', '20:30:00', '280000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('40', '8', '20:30:00', '21:30:00', '230000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('41', '9', '16:30:00', '17:30:00', '250000.00', '390000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('42', '9', '17:30:00', '18:30:00', '350000.00', '540000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('43', '9', '18:30:00', '19:30:00', '350000.00', '540000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('44', '9', '19:30:00', '20:30:00', '300000.00', '470000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('45', '9', '20:30:00', '21:30:00', '250000.00', '390000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('46', '10', '16:30:00', '17:30:00', '260000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('47', '10', '17:30:00', '18:30:00', '360000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('48', '10', '18:30:00', '19:30:00', '360000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('49', '10', '19:30:00', '20:30:00', '310000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('50', '10', '20:30:00', '21:30:00', '260000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('51', '11', '16:30:00', '17:30:00', '250000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('52', '11', '17:30:00', '18:30:00', '350000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('53', '11', '18:30:00', '19:30:00', '350000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('54', '11', '19:30:00', '20:30:00', '300000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('55', '11', '20:30:00', '21:30:00', '250000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('56', '12', '16:30:00', '17:30:00', '220000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('57', '12', '17:30:00', '18:30:00', '320000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('58', '12', '18:30:00', '19:30:00', '320000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('59', '12', '19:30:00', '20:30:00', '280000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('60', '12', '20:30:00', '21:30:00', '220000.00', '0.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('61', '16', '16:30:00', '17:30:00', '0.00', '300000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('62', '16', '17:30:00', '18:30:00', '0.00', '350000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('63', '16', '18:30:00', '19:30:00', '0.00', '400000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('64', '16', '19:30:00', '20:30:00', '0.00', '460000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('65', '16', '20:30:00', '21:30:00', '0.00', '530000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('66', '14', '16:30:00', '17:30:00', '0.00', '280000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('67', '14', '17:30:00', '18:30:00', '0.00', '330000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('68', '14', '18:30:00', '19:30:00', '0.00', '360000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('69', '14', '19:30:00', '20:30:00', '0.00', '400000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('70', '14', '20:30:00', '21:30:00', '0.00', '444000.00');
INSERT IGNORE INTO `khung_gio` (`khung_gio_id`, `san_id`, `gio_bat_dau`, `gio_ket_thuc`, `gia_tien`, `gia_tien_san_7`) VALUES ('71', '16', '21:30:00', '22:30:00', '0.00', '600000.00');

SET FOREIGN_KEY_CHECKS = 1;
