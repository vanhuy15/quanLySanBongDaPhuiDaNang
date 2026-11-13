<div align="center">

  # 👋 Xin chào, tôi là Hoàng Văn Huy! (vanhuy15)
  ### 🚀 Backend & Fullstack Developer | Software QA & Architecture Enthusiast
  
  <p align="center">
    <a href="https://github.com/vanhuy15">
      <img src="https://komarev.com/ghpvc/?username=vanhuy15&style=flat-square&color=0d9488&label=Profile+Views" alt="Profile Views" />
    </a>
    <img src="https://img.shields.io/badge/Focus-High_Performance_Backend-0d9488?style=flat-square" alt="Focus" />
    <img src="https://img.shields.io/badge/Architecture-Clean_MVC_%7C_Modular-blue?style=flat-square" alt="Architecture" />
    <img src="https://img.shields.io/badge/Testing-100%25_Full_Coverage-brightgreen?style=flat-square" alt="Testing" />
  </p>

  <p align="center">
    Đam mê xây dựng các hệ thống web tối ưu hiệu năng cao, kiến trúc sạch (Clean MVC / Domain-Driven Design), xử lý các bài toán tranh chấp tài nguyên đồng thời (Race Condition) và áp dụng tiêu chuẩn kiểm thử phần mềm toàn diện (Unit, API, E2E, Load, Security).
  </p>

  ---

</div>

## 🌟 DỰ ÁN NỔI BẬT (FEATURED PROJECT)

<div align="center">

### 🏟️ [Hệ Thống Đặt Sân Bóng Đá Phủi Đà Nẵng (quanLySanBongDaPhuiDaNang)](https://github.com/vanhuy15/quanLySanBongDaPhuiDaNang)
*Nền tảng chuyển đổi số toàn diện cho chuỗi sân bóng mini phủi tại Đà Nẵng*

</div>

```mermaid
graph LR
    Client["Khách hàng<br/>(Client Portal)"] -->|Chọn sân & Giữ chỗ 10p| Core["Pure MVC Core Engine<br/>(PHP 8.2 + MySQL)"]
    Admin["Quản trị viên<br/>(Admin Portal)"] -->|Duyệt đơn, Báo cáo KPI| Core
    Core -->|Khóa hàng chống trùng slot| Lock["SELECT ... FOR UPDATE<br/>(MySQL Transaction)"]
    Core -->|Thanh toán tự động| VietQR["VietQR Napas 247<br/>(MB Bank Auto)"]
    Core -->|Bảo vệ toàn diện| QA["5 Bộ Testing Suites<br/>(PHPUnit • Playwright • Postman • JMeter • ZAP)"]
```

* **Kiến trúc Pure MVC không framework:** Tự thiết kế Router, Middleware, Base Controller và Singleton Database, phân tách 100% mã nguồn theo mô hình modular CSS/JS dễ bảo trì.
* **Xử lý Race Condition & Khóa chéo:** Áp dụng `SELECT ... FOR UPDATE` triệt tiêu 100% rủi ro đặt trùng sân khi hàng chục người cùng thao tác; xử lý logic ghép sân 7 từ các sân 5 con linh hoạt.
* **Hệ thống Điểm Uy Tín (Reputation System):** Tự động theo dõi hành vi, thưởng điểm đánh giá (+2đ), phạt hủy sát giờ (-5đ đến -15đ) và chế tài khách bùng sân (-25đ).
* **Tích hợp 5 Công cụ Kiểm thử Chuyên nghiệp:**
  * 🟢 **PHPUnit 10.5:** Kiểm thử hộp trắng 41 test cases (110 assertions) bao phủ 100% thuật toán cốt lõi.
  * 🟠 **Postman / Newman:** Kiểm thử tự động 23 endpoints API phân hệ Client & Admin.
  * 🔵 **Playwright E2E:** 10 luồng kiểm thử tự động giao diện trình duyệt thực tế Chromium.
  * 🟣 **Apache JMeter 5.6:** Giả lập tải đồng thời 50 threads tranh chấp slot và 200 CCU tải đọc.
  * 🔴 **OWASP ZAP & Custom Auditor:** Kiểm thử an ninh mạng tự động 7/7 chốt chặn OWASP Top 10.

👉 **Khám phá mã nguồn & Kịch bản Test:** [github.com/vanhuy15/quanLySanBongDaPhuiDaNang](https://github.com/vanhuy15/quanLySanBongDaPhuiDaNang)

---

## 🛠️ KỸ NĂNG & CÔNG NGHỆ (TECH STACK)

| Lĩnh vực | Công nghệ / Công cụ sử dụng |
| :--- | :--- |
| **Backend & Core** | ![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white) ![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square&logo=mysql&logoColor=white) ![Custom MVC](https://img.shields.io/badge/Architecture-Pure_MVC-blue?style=flat-square) ![RESTful API](https://img.shields.io/badge/API-RESTful-orange?style=flat-square) |
| **Frontend & UI** | ![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat-square&logo=javascript&logoColor=black) ![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat-square&logo=html5&logoColor=white) ![CSS3](https://img.shields.io/badge/CSS3-Modular-1572B6?style=flat-square&logo=css3&logoColor=white) ![Chart.js](https://img.shields.io/badge/Chart.js-FF6384?style=flat-square&logo=chartdotjs&logoColor=white) |
| **Testing & QA** | ![PHPUnit](https://img.shields.io/badge/PHPUnit-White--box-377755?style=flat-square&logo=php&logoColor=white) ![Playwright](https://img.shields.io/badge/Playwright-E2E_UI-2EAD33?style=flat-square&logo=playwright&logoColor=white) ![Postman](https://img.shields.io/badge/Postman-API_Test-FF6C37?style=flat-square&logo=postman&logoColor=white) ![Apache JMeter](https://img.shields.io/badge/JMeter-Load_Test-D22128?style=flat-square&logo=apache&logoColor=white) ![OWASP ZAP](https://img.shields.io/badge/OWASP-Security-005E8A?style=flat-square&logo=owasp&logoColor=white) |
| **DevOps & Tools** | ![Git](https://img.shields.io/badge/Git-F05032?style=flat-square&logo=git&logoColor=white) ![GitHub](https://img.shields.io/badge/GitHub-181717?style=flat-square&logo=github&logoColor=white) ![XAMPP](https://img.shields.io/badge/XAMPP-Apache-FB7A24?style=flat-square&logo=apache&logoColor=white) ![Composer](https://img.shields.io/badge/Composer-885630?style=flat-square&logo=composer&logoColor=white) ![Node.js](https://img.shields.io/badge/Node.js-339933?style=flat-square&logo=nodedotjs&logoColor=white) |

---

## 📊 THỐNG KÊ HOẠT ĐỘNG GITHUB (GITHUB STATS)

<div align="center">
  <img src="https://github-readme-stats.vercel.app/api?username=vanhuy15&show_icons=true&theme=tokyonight&hide_border=true&count_private=true" height="165" alt="GitHub Stats" />
  <img src="https://github-readme-stats.vercel.app/api/top-langs/?username=vanhuy15&layout=compact&theme=tokyonight&hide_border=true" height="165" alt="Top Languages" />
</div>

<div align="center" style="margin-top: 10px;">
  <img src="https://streak-stats.demolab.com?user=vanhuy15&theme=tokyonight&hide_border=true" height="165" alt="GitHub Streak Stats" />
</div>

---

## 📫 KẾT NỐI VỚI TÔI (CONNECT WITH ME)

<p align="center">
  <a href="mailto:alexanderhuy2@gmail.com">
    <img src="https://img.shields.io/badge/Gmail-alexanderhuy2%40gmail.com-D14836?style=for-the-badge&logo=gmail&logoColor=white" alt="Email" />
  </a>
  <a href="https://github.com/vanhuy15">
    <img src="https://img.shields.io/badge/GitHub-vanhuy15-181717?style=for-the-badge&logo=github&logoColor=white" alt="GitHub" />
  </a>
</p>

<div align="center">
  <sub>⭐️ Đừng ngần ngại ghé thăm và để lại một ngôi sao (Star) nếu bạn thấy các dự án của tôi hữu ích!</sub>
</div>
