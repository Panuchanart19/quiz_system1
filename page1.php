<?php
require_once 'config.php';

// ถ้าล็อกอินแล้วให้ไปหน้าหลัก
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } elseif (isCreator()) {
        redirect('creator/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}

// จัดการ Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $conn->prepare("SELECT id, username, password, role, full_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php');
            if ($user['role'] === 'admin') {
                 redirect('admin/dashboard.php');
            } elseif($user['role'] === 'creator') {
                redirect('creator/dashboard.php');
            }else{
                redirect('user/dashboard.php');
            }
            

        } else {
            $error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error = "ไม่พบผู้ใช้งาน";
    }
}

// จัดการ Register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = $_POST['reg_username'] ?? '';
    $email = $_POST['reg_email'] ?? '';
    $password = $_POST['reg_password'] ?? '';
    $confirm_password = $_POST['reg_confirm_password'] ?? '';
    $full_name = $_POST['reg_full_name'] ?? '';
    $role = $_POST['reg_role'] ?? 'user';
    
    // ตรวจสอบข้อมูล
    if ($password !== $confirm_password) {
        $reg_error = "รหัสผ่านไม่ตรงกัน";
    } else {
        // ตรวจสอบ username ซ้ำ
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $reg_error = "ชื่อผู้ใช้หรืออีเมลนี้มีในระบบแล้ว";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $hashed_password, $email, $full_name, $role);
            
            if ($stmt->execute()) {
                $success = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
            } else {
                $reg_error = "เกิดข้อผิดพลาด กรุณาลองใหม่";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - เข้าสู่ระบบ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: flex;
        }
        
        .left-panel {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .left-panel h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
        }
        
        .left-panel p {
            font-size: 1.1em;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .right-panel {
            flex: 1;
            padding: 60px 40px;
        }
        
        .form-container {
            display: none;
        }
        
        .form-container.active {
            display: block;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        h2 {
            color: #333;
            margin-bottom: 30px;
            font-size: 2em;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .toggle-form {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .toggle-form a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }
        
        .toggle-form a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .left-panel {
                padding: 40px 30px;
            }
            
            .left-panel h1 {
                font-size: 2em;
            }
            
            .right-panel {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <h1>🎯 Quiz System</h1>
            <p>ระบบทดสอบความรู้ออนไลน์</p>
        </div>
        
        <div class="right-panel">
            <!-- Login Form -->
            <div class="form-container active" id="loginForm">
                <h2>เข้าสู่ระบบ</h2>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo clean($error); ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo clean($success); ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>ชื่อผู้ใช้</label>
                        <input type="text" name="username" required placeholder="กรอกชื่อผู้ใช้">
                    </div>
                    
                    <div class="form-group">
                        <label>รหัสผ่าน</label>
                        <input type="password" name="password" required placeholder="กรอกรหัสผ่าน">
                    </div>
                    
                    <button type="submit" name="login" class="btn">เข้าสู่ระบบ</button>
                </form>
                
                <div class="toggle-form">
                    ยังไม่มีบัญชี? <a onclick="toggleForm()">สมัครสมาชิก</a>
                </div>
            </div>
            
            <!-- Register Form -->
            <div class="form-container" id="registerForm">
                <h2>สมัครสมาชิก</h2>
                
                <?php if (isset($reg_error)): ?>
                    <div class="alert alert-error"><?php echo clean($reg_error); ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>ชื่อผู้ใช้</label>
                        <input type="text" name="reg_username" required placeholder="ตัวอักษรและตัวเลข 4-20 ตัว">
                    </div>
                    
                    <div class="form-group">
                        <label>ชื่อ-นามสกุล</label>
                        <input type="text" name="reg_full_name" required placeholder="กรอกชื่อ-นามสกุล">
                    </div>
                    
                    <div class="form-group">
                        <label>อีเมล</label>
                        <input type="email" name="reg_email" required placeholder="example@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label>ประเภทผู้ใช้</label>
                        <select name="reg_role" required>
                            <option value="user">ผู้ใช้ทั่วไป</option>
                            <option value="creator">ผู้สร้างชุดข้อสอบ</option>
                                <?php 
                                    // เช็คจำนวน admin ก่อนแสดงตัวเลือก
                                    $adminCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
                                    if ($adminCount == 0): 
                                ?>
                                    <option value="admin">ผู้ดูแลระบบ</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>รหัสผ่าน</label>
                        <input type="password" name="reg_password" required placeholder="อย่างน้อย 6 ตัวอักษร">
                    </div>
                    
                    <div class="form-group">
                        <label>ยืนยันรหัสผ่าน</label>
                        <input type="password" name="reg_confirm_password" required placeholder="กรอกรหัสผ่านอีกครั้ง">
                    </div>
                    
                    <button type="submit" name="register" class="btn">สมัครสมาชิก</button>
                </form>
                
                <div class="toggle-form">
                    มีบัญชีแล้ว? <a onclick="toggleForm()">เข้าสู่ระบบ</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleForm() {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            
            loginForm.classList.toggle('active');
            registerForm.classList.toggle('active');
        }
    </script>
</body>
</html>