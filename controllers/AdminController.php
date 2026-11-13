<?php
namespace Controllers;

use Core\Controller;
use Config\Database;
use Models\DatSan;
use Models\DoanhThu;
use Models\User;
use Models\SanBong;

class AdminController extends Controller {
    
    private function requireAdmin() {
        if (!isset($_SESSION['vai_tro']) || $_SESSION['vai_tro'] !== 'admin') {
            $this->redirect('index.php');
        }
    }

    public function index() {
        $this->requireAdmin();
        $this->redirect('admin/index.php');
    }
    
    public function bookings() {
        $this->requireAdmin();
        $this->render('admin/bookings');
    }
    
    public function getBookingsData() {
        $this->requireAdmin();
        $conn = Database::getConnection();
        
        $status_filter = isset($_GET['status']) ? $_GET['status'] : 'tat_ca';
        $where_sql = "WHERE ds.deleted_at IS NULL";
        
        if ($status_filter == 'cho-xac-nhan') $where_sql .= " AND ds.trang_thai = 'Chờ xác nhận'";
        elseif ($status_filter == 'da-xac-nhan') $where_sql .= " AND ds.trang_thai = 'Đã xác nhận'";
        elseif ($status_filter == 'huy') $where_sql .= " AND ds.trang_thai IN ('Hủy', 'Đã hủy', 'Chờ hoàn tiền')";

        $sql = "SELECT ds.*, nd.ho_ten, nd.sdt, kg.gio_bat_dau, kg.gio_ket_thuc, kg.gia_tien, sb.ten_san, tt.trang_thai as tt_trang_thai, tt.phuong_thuc
                FROM dat_san ds
                JOIN nguoi_dung nd ON ds.nguoi_dung_id = nd.nguoi_dung_id
                JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
                JOIN san_bong sb ON kg.san_id = sb.san_id
                LEFT JOIN thanh_toan tt ON ds.dat_san_id = tt.dat_san_id
                $where_sql
                ORDER BY ds.ngay_dat DESC, ds.dat_san_id DESC LIMIT 50";

        $result = $conn->query($sql);
        $bookings = [];
        while($row = $result->fetch_assoc()){
            $bookings[] = $row;
        }
        
        // Render view partial adhering to MVC
        echo $this->renderPartial('admin/booking_rows', ['bookings' => $bookings]);
    }
    
    public function updateBookingStatus() {
        $this->requireAdmin();
        $id = intval($_POST['id']);
        $action = $_POST['action'] ?? '';
        $conn = Database::getConnection();

        // Fix SQL Injection: Dùng prepared statement
        if ($action === 'xacnhan') {
            $trang_thai = 'Đã xác nhận';
            $stmt = $conn->prepare("UPDATE dat_san SET trang_thai = ? WHERE dat_san_id = ?");
            $stmt->bind_param("si", $trang_thai, $id);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'huy') {
            $trang_thai = 'Đã hủy';
            $stmt = $conn->prepare("UPDATE dat_san SET trang_thai = ? WHERE dat_san_id = ?");
            $stmt->bind_param("si", $trang_thai, $id);
            $stmt->execute();
            $stmt->close();
        }
        $this->jsonResponse(['success' => true]);
    }
    
    public function deleteBooking() {
        $this->requireAdmin();
        $id = intval($_POST['id']);
        $conn = Database::getConnection();

        // Fix SQL Injection: Dùng prepared statement cho SOFT DELETE
        $stmt = $conn->prepare("UPDATE dat_san SET deleted_at = NOW() WHERE dat_san_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $this->jsonResponse(['success' => true]);
    }
}
