<?php
namespace Controllers;

use Core\Controller;
use Config\Database;

class HomeController extends Controller {

    /**
     * Extract logic query chung — dùng cho cả index() và searchAjax().
     * Loại bỏ vi phạm DRY khi cé là hai phương thức giống nhau ~100 dòng.
     */
    private function fetchPitchesData(
        string $search_term,
        string $loai_san,
        float $min_price,
        float $max_price,
        int $page,
        int $limit = 8
    ): array {
        $conn = Database::getConnection();
        $start = ($page - 1) * $limit;

        $where_clauses = ["1=1"];
        $params = [];
        $types = "";

        if (!empty($search_term)) {
            $where_clauses[] = "(sb.ten_san LIKE ? OR sb.dia_chi LIKE ?)";
            $search_param = "%" . $search_term . "%";
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= "ss";
        }

        if (!empty($loai_san) && $loai_san !== 'all') {
            if ($loai_san === 'san_5') {
                $where_clauses[] = "(sb.loai_san = 'san_5' OR sb.loai_san = 'san_5_7')";
            } elseif ($loai_san === 'san_7') {
                $where_clauses[] = "(sb.loai_san = 'san_7' OR sb.loai_san = 'san_5_7')";
            } else {
                $where_clauses[] = "sb.loai_san = ?";
                $params[] = $loai_san;
                $types .= "s";
            }
        }

        if ($min_price > 0) {
            $where_clauses[] = "EXISTS (
                SELECT 1 FROM khung_gio kg 
                WHERE kg.san_id = sb.san_id AND (
                    (sb.loai_san = 'san_5' AND kg.gia_tien >= ?) OR
                    (sb.loai_san = 'san_7' AND kg.gia_tien_san_7 >= ?) OR
                    (sb.loai_san = 'san_5_7' AND (kg.gia_tien >= ? OR kg.gia_tien_san_7 >= ?))
                )
            )";
            $params[] = $min_price;
            $params[] = $min_price;
            $params[] = $min_price;
            $params[] = $min_price;
            $types .= "dddd";
        }

        if ($max_price > 0) {
            $where_clauses[] = "EXISTS (
                SELECT 1 FROM khung_gio kg 
                WHERE kg.san_id = sb.san_id AND (
                    (sb.loai_san = 'san_5' AND kg.gia_tien <= ? AND kg.gia_tien > 0) OR
                    (sb.loai_san = 'san_7' AND kg.gia_tien_san_7 <= ? AND kg.gia_tien_san_7 > 0) OR
                    (sb.loai_san = 'san_5_7' AND ((kg.gia_tien <= ? AND kg.gia_tien > 0) OR (kg.gia_tien_san_7 <= ? AND kg.gia_tien_san_7 > 0)))
                )
            )";
            $params[] = $max_price;
            $params[] = $max_price;
            $params[] = $max_price;
            $params[] = $max_price;
            $types .= "dddd";
        }

        $where_sql = implode(" AND ", $where_clauses);

        // Count Total
        $sql_total = "SELECT COUNT(DISTINCT sb.san_id) as total FROM san_bong sb WHERE $where_sql";
        $stmt_total = $conn->prepare($sql_total);
        if (!empty($params)) {
            $stmt_total->bind_param($types, ...$params);
        }
        $stmt_total->execute();
        $total_rows = $stmt_total->get_result()->fetch_assoc()['total'] ?? 0;
        $total_pages = ceil($total_rows / $limit);
        $stmt_total->close();

        // Get Pitches with Rating & Price Info
        $sql = "SELECT sb.*,
                       (SELECT COALESCE(AVG(so_sao), 0) FROM danh_gia WHERE san_id = sb.san_id) as avg_rating,
                       (SELECT COUNT(danh_gia_id) FROM danh_gia WHERE san_id = sb.san_id) as total_reviews,
                       COALESCE(MIN(CASE 
                           WHEN sb.loai_san = 'san_7' THEN NULLIF(kg.gia_tien_san_7, 0)
                           WHEN sb.loai_san = 'san_5' THEN NULLIF(kg.gia_tien, 0)
                           ELSE LEAST(COALESCE(NULLIF(kg.gia_tien, 0), 999999999), COALESCE(NULLIF(kg.gia_tien_san_7, 0), 999999999))
                       END), 0) as min_price,
                       COALESCE(MAX(CASE 
                           WHEN sb.loai_san = 'san_7' THEN kg.gia_tien_san_7
                           WHEN sb.loai_san = 'san_5' THEN kg.gia_tien
                           ELSE GREATEST(COALESCE(kg.gia_tien, 0), COALESCE(kg.gia_tien_san_7, 0))
                       END), 0) as max_price
                FROM san_bong sb
                LEFT JOIN khung_gio kg ON sb.san_id = kg.san_id
                WHERE $where_sql
                GROUP BY sb.san_id
                ORDER BY sb.san_id ASC
                LIMIT ?, ?";

        $params[] = $start;
        $params[] = $limit;
        $types .= "ii";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $san_bongs = [];
        while ($row = $res->fetch_assoc()) {
            $san_bongs[] = $row;
        }
        $stmt->close();

        return [
            'san_bongs'   => $san_bongs,
            'total_rows'  => $total_rows,
            'total_pages' => $total_pages,
        ];
    }

    public function index() {
        // Cleanup đơn quá hạn — dùng centralized method thay vì copy-paste query
        Database::cleanupExpiredBookings();

        $limit = 8;
        $page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        $search_term = isset($_GET['search'])   ? trim($_GET['search'])  : '';
        $loai_san    = isset($_GET['loai_san']) ? trim($_GET['loai_san']): 'all';
        $min_price   = (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) ? floatval($_GET['min_price']) : 0;
        $max_price   = (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) ? floatval($_GET['max_price']) : 0;

        $data = $this->fetchPitchesData($search_term, $loai_san, $min_price, $max_price, $page, $limit);

        $this->render('home/index', [
            'san_bongs'   => $data['san_bongs'],
            'search_term' => $search_term,
            'loai_san'    => $loai_san,
            'min_price'   => $min_price,
            'max_price'   => $max_price,
            'page'        => $page,
            'total_pages' => $data['total_pages'],
            'total_rows'  => $data['total_rows'],
        ]);
    }

    public function searchAjax() {
        // Cleanup đơn quá hạn — dùng centralized method thay vì copy-paste query
        Database::cleanupExpiredBookings();

        $limit = 8;
        $page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        $search_term = isset($_GET['search'])   ? trim($_GET['search'])  : '';
        $loai_san    = isset($_GET['loai_san']) ? trim($_GET['loai_san']): 'all';
        $min_price   = (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) ? floatval($_GET['min_price']) : 0;
        $max_price   = (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) ? floatval($_GET['max_price']) : 0;

        // Dùng shared method — không còn copy-paste 80 dòng query
        $data = $this->fetchPitchesData($search_term, $loai_san, $min_price, $max_price, $page, $limit);
        $san_bongs  = $data['san_bongs'];
        $total_pages = $data['total_pages'];
        $total_rows  = $data['total_rows'];

        $html_cards = $this->renderPartial('home/pitch_cards', [
            'san_bongs' => $san_bongs,
            'loai_san'  => $loai_san
        ]);

        $html_pagination = $this->renderPartial('home/pagination', [
            'total_pages' => $total_pages,
            'page'        => $page
        ]);

        $this->jsonResponse([
            'success' => true,
            'html_cards' => $html_cards,
            'html_pagination' => $html_pagination,
            'total_rows' => $total_rows
        ]);
    }

    public function detail() {
        $san_id = intval($_GET['san_id'] ?? 0);
        if ($san_id <= 0) {
            $this->redirect('index.php');
        }

        $conn = Database::getConnection();

        // Thông tin sân
        $stmt = $conn->prepare("
            SELECT sb.*,
                   (SELECT COALESCE(AVG(so_sao), 0) FROM danh_gia WHERE san_id = sb.san_id) as avg_rating,
                   (SELECT COUNT(danh_gia_id) FROM danh_gia WHERE san_id = sb.san_id) as total_reviews,
                   COALESCE(MIN(CASE 
                       WHEN sb.loai_san = 'san_7' THEN NULLIF(kg.gia_tien_san_7, 0)
                       WHEN sb.loai_san = 'san_5' THEN NULLIF(kg.gia_tien, 0)
                       ELSE LEAST(COALESCE(NULLIF(kg.gia_tien, 0), 999999999), COALESCE(NULLIF(kg.gia_tien_san_7, 0), 999999999))
                   END), 0) as min_price,
                   COALESCE(MAX(CASE 
                       WHEN sb.loai_san = 'san_7' THEN kg.gia_tien_san_7
                       WHEN sb.loai_san = 'san_5' THEN kg.gia_tien
                       ELSE GREATEST(COALESCE(kg.gia_tien, 0), COALESCE(kg.gia_tien_san_7, 0))
                   END), 0) as max_price
            FROM san_bong sb
            LEFT JOIN khung_gio kg ON kg.san_id = sb.san_id
            WHERE sb.san_id = ?
            GROUP BY sb.san_id
        ");
        $stmt->bind_param("i", $san_id);
        $stmt->execute();
        $san = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$san) {
            $this->redirect('index.php');
        }

        // Khung giờ
        $kg_stmt = $conn->prepare("SELECT * FROM khung_gio WHERE san_id = ? ORDER BY gio_bat_dau");
        $kg_stmt->bind_param("i", $san_id);
        $kg_stmt->execute();
        $khung_gios = $kg_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $kg_stmt->close();

        // Phân trang đánh giá
        $review_page = max(1, intval($_GET['review_page'] ?? 1));
        $review_limit = 5;
        $review_offset = ($review_page - 1) * $review_limit;

        $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM danh_gia WHERE san_id = ?");
        $count_stmt->bind_param("i", $san_id);
        $count_stmt->execute();
        $total_reviews = $count_stmt->get_result()->fetch_assoc()['total'];
        $total_review_pages = ceil($total_reviews / $review_limit);
        $count_stmt->close();

        $rv_stmt = $conn->prepare("
            SELECT dg.*, nd.ho_ten, nd.avatar
            FROM danh_gia dg
            JOIN nguoi_dung nd ON dg.nguoi_dung_id = nd.nguoi_dung_id
            WHERE dg.san_id = ?
            ORDER BY dg.ngay_tao DESC
            LIMIT ? OFFSET ?
        ");
        $rv_stmt->bind_param("iii", $san_id, $review_limit, $review_offset);
        $rv_stmt->execute();
        $reviews = $rv_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $rv_stmt->close();

        // Phân phối sao (1-5)
        $dist_stmt = $conn->prepare("
            SELECT so_sao, COUNT(*) as cnt
            FROM danh_gia WHERE san_id = ?
            GROUP BY so_sao
        ");
        $dist_stmt->bind_param("i", $san_id);
        $dist_stmt->execute();
        $dist_result = $dist_stmt->get_result();
        $star_dist = [1=>0, 2=>0, 3=>0, 4=>0, 5=>0];
        while ($row = $dist_result->fetch_assoc()) {
            $star_dist[$row['so_sao']] = (int)$row['cnt'];
        }
        $dist_stmt->close();

        $this->render('home/detail', [
            'san'               => $san,
            'khung_gios'        => $khung_gios,
            'reviews'           => $reviews,
            'review_page'       => $review_page,
            'total_review_pages'=> $total_review_pages,
            'total_reviews'     => $total_reviews,
            'star_dist'         => $star_dist,
        ]);
    }
}

