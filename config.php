<?php
// config.php - การตั้งค่าฐานข้อมูลและระบบ

// ตั้งค่าการแสดงข้อผิดพลาด
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ข้อมูลการเชื่อมต่อฐานข้อมูล
define('DB_HOST', '127.0.0.1');  // ลองใช้ 127.0.0.1 แทน localhost
define('DB_PORT', '3306');       // พอร์ต MySQL (ปกติคือ 3306)
define('DB_USER', 'root');
define('DB_PASS', '');  // ใส่รหัสผ่าน MySQL ของคุณที่นี่ (ถ้ามี)
define('DB_NAME', 'quiz_system');

// ตั้งค่าระบบ
define('SITE_NAME', 'Quiz System');
define('SITE_URL', 'http://localhost/quiz');

// เชื่อมต่อฐานข้อมูล
try {
    // ลองเชื่อมต่อแบบระบุพอร์ต
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    if ($conn->connect_error) {
        // แสดงข้อความช่วยแก้ปัญหา
        die("
        <div style='font-family: Arial; padding: 20px; background: #ffebee; border-left: 4px solid #f44336; margin: 20px;'>
            <h2 style='color: #c62828;'>⚠️ ไม่สามารถเชื่อมต่อฐานข้อมูลได้</h2>
            <p><strong>ข้อผิดพลาด:</strong> " . $conn->connect_error . "</p>
            <h3>วิธีแก้ไข:</h3>
            <ol>
                <li>ตรวจสอบว่า <strong>MySQL ทำงานอยู่</strong> ใน XAMPP/WAMP Control Panel</li>
                <li>ตรวจสอบ <strong>username และ password</strong> ใน config.php (ปกติคือ root/'ว่าง')</li>
                <li>ลองเปลี่ยน DB_HOST เป็น <strong>'localhost'</strong> หรือ <strong>'127.0.0.1'</strong></li>
                <li>ตรวจสอบ <strong>พอร์ต MySQL</strong> (ปกติคือ 3306)</li>
                <li>สร้างฐานข้อมูล <strong>'quiz_system'</strong> ใน phpMyAdmin</li>
            </ol>
            <p style='margin-top: 20px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107;'>
                <strong>💡 เคล็ดลับ:</strong> เปิด phpMyAdmin ที่ <a href='http://localhost/phpmyadmin'>http://localhost/phpmyadmin</a> 
                เพื่อตรวจสอบว่าเชื่อมต่อได้หรือไม่
            </p>
        </div>
        ");
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("
    <div style='font-family: Arial; padding: 20px; background: #ffebee; border-left: 4px solid #f44336; margin: 20px;'>
        <h2 style='color: #c62828;'>⚠️ เกิดข้อผิดพลาด</h2>
        <p><strong>รายละเอียด:</strong> " . $e->getMessage() . "</p>
    </div>
    ");
}

// เริ่มต้น Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ฟังก์ชันตรวจสอบการล็อกอิน
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ฟังก์ชันตรวจสอบสิทธิ์ Admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// ฟังก์ชันตรวจสอบสิทธิ์ Creator
function isCreator() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'creator';
}

// ฟังก์ชันตรวจสอบว่าเป็น Admin หรือ Creator
function isAdminOrCreator() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'creator');
}

// ฟังก์ชันตรวจสอบว่าเป็น User ธรรมดา
function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

// ฟังก์ชันเปลี่ยนเส้นทาง
function redirect($url) {
    header("Location: $url");
    exit();
}

// // ฟังก์ชันป้องกัน XSS
// function clean($data) {
//     return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
// }

// ฟังก์ชันป้องกัน XSS
function clean($data) {
    // ตรวจสอบว่าเป็น null, false, หรือค่าว่าง
    if ($data === null || $data === false || $data === '') {
        return '';
    }
    
    // แปลงเป็น string ก่อน
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

// ฟังก์ชันแสดงข้อความแจ้งเตือน
function setAlert($message, $type = 'success') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

function showAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        $icon = $alert['type'] === 'success' ? '✓' : '✗';
        $class = $alert['type'] === 'success' ? 'alert-success' : 'alert-error';
        echo "<div class='alert {$class}'>{$icon} " . clean($alert['message']) . "</div>";
        unset($_SESSION['alert']);
    }
}

// ฟังก์ชันสร้าง URL
function url($path = '') {
    return SITE_URL . '/' . ltrim($path, '/');
}

// ฟังก์ชันคำนวณระยะเวลา
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return $diff . ' วินาทีที่แล้ว';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' นาทีที่แล้ว';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' ชั่วโมงที่แล้ว';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' วันที่แล้ว';
    } else {
        return date('d/m/Y H:i', $timestamp);
    }
}

// ฟังก์ชันแปลงวินาทีเป็นเวลา
function formatTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
    } else {
        return sprintf("%02d:%02d", $minutes, $secs);
    }
}

// ฟังก์ชันแปลงระดับความยาก
function getDifficultyText($difficulty) {
    $levels = [
        'easy' => 'ง่าย',
        'medium' => 'ปานกลาง',
        'hard' => 'ยาก'
    ];
    return $levels[$difficulty] ?? 'ไม่ระบุ';
}

// ฟังก์ชันแปลงระดับความยากเป็นสี
function getDifficultyColor($difficulty) {
    $colors = [
        'easy' => '#4CAF50',
        'medium' => '#FF9800',
        'hard' => '#f44336'
    ];
    return $colors[$difficulty] ?? '#9e9e9e';
}
?>