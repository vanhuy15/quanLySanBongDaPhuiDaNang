<?php
namespace Controllers;

use Core\Controller;

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
        $this->redirect('admin/datsan/index.php');
    }

    public function pitches() {
        $this->requireAdmin();
        $this->redirect('admin/sanbong/index.php');
    }

    public function schedules() {
        $this->requireAdmin();
        $this->redirect('admin/khunggio/index.php');
    }

    public function users() {
        $this->requireAdmin();
        $this->redirect('admin/user/index.php');
    }

    public function revenue() {
        $this->requireAdmin();
        $this->redirect('admin/doanhthu/index.php');
    }

    public function reviews() {
        $this->requireAdmin();
        $this->redirect('admin/danhgia/index.php');
    }
}
