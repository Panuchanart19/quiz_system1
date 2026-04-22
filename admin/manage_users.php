<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../page1.php');
}

// สร้างผู้ใช้ใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];
    $role = $_POST['role'];
    
    // ตรวจสอบ username ซ้ำ
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        setAlert('ชื่อผู้ใช้หรืออีเมลนี้มีในระบบแล้ว', 'error');
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $password, $email, $full_name, $role);
        
        if ($stmt->execute()) {
            setAlert('เพิ่มผู้ใช้สำเร็จ!', 'success');
        } else {
            setAlert('เกิดข้อผิดพลาด', 'error');
        }
    }
    redirect('manage_users.php');
}

// ลบผู้ใช้
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // ป้องกันการลบตัวเอง
    if ($id == $_SESSION['user_id']) {
        setAlert('ไม่สามารถลบบัญชีของตัวเองได้', 'error');
    } else {
        $conn->query("DELETE FROM users WHERE id = $id");
        setAlert('ลบผู้ใช้สำเร็จ!', 'success');
    }
    redirect('manage_users.php');
}

// แก้ไขผู้ใช้
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $role = $_POST['role'];
    
    // ตรวจสอบ username/email ซ้ำ (ยกเว้นของตัวเอง)
    $check = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $check->bind_param("ssi", $username, $email, $id);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        setAlert('ชื่อผู้ใช้หรืออีเมลนี้มีในระบบแล้ว', 'error');
    } else {
        // ถ้ามีการเปลี่ยนรหัสผ่าน
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=?, full_name=?, role=? WHERE id=?");
            $stmt->bind_param("sssssi", $username, $email, $password, $full_name, $role, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, full_name=?, role=? WHERE id=?");
            $stmt->bind_param("ssssi", $username, $email, $full_name, $role, $id);
        }
        
        if ($stmt->execute()) {
            setAlert('แก้ไขผู้ใช้สำเร็จ!', 'success');
        }
    }
    redirect('manage_users.php');
}

// ดึงข้อมูลผู้ใช้ทั้งหมดพร้อมสถิติ
$users_query = "SELECT u.*, 
                COUNT(DISTINCT qa.id) as total_attempts,
                AVG(qa.score) as avg_score,
                MAX(qa.score) as best_score
                FROM users u
                LEFT JOIN quiz_attempts qa ON u.id = qa.user_id AND qa.status = 'completed'
                GROUP BY u.id
                ORDER BY u.created_at DESC";
$users = $conn->query($users_query);

// สำหรับแก้ไข
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_user = $conn->query("SELECT * FROM users WHERE id = $edit_id")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            display: flex;
        }
        
        .sidebar {
            width: 260px;
            background: #2c3e50;
            min-height: 100vh;
            color: white;
            position: fixed;
        }
        
        .sidebar-header {
            padding: 30px 20px;
            background: #1a252f;
            text-align: center;
        }
        
        .sidebar-header h2 {
            font-size: 1.5em;
            margin-bottom: 5px;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .menu-item:hover, .menu-item.active {
            background: #34495e;
        }
        
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 30px;
        }
        
        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 2em;
            color: #333;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        
        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
        }
        
        .btn-cancel {
            background: #9e9e9e;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            color: #666;
            font-weight: 600;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
            color: white;
            white-space: nowrap;
        }
        
        .badge-admin {
            background: #f44336;
        }

        .badge-creator {
            background: #8ce753;  /* สีส้ม */
        }

        .badge-user {
            background: #2196F3;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9em;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .btn-edit {
            background: #2196F3;
            color: white;
        }
        
        .btn-delete {
            background: #f44336;
            color: white;
        }
        
        .btn-view {
            background: #4CAF50;
            color: white;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        
        .user-stats {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🎯 Quiz Admin</h2>
            <p><?php echo clean($_SESSION['full_name']); ?></p>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                📊 Dashboard
            </a>
            <a href="manage_quizzes.php" class="menu-item">
                📝 สร้างชุดข้อสอบ
            </a>
            <a href="manage_questions.php" class="menu-item">
                ❓ จัดการคำถาม
            </a>
            <a href="manage_users.php" class="menu-item active">
                👥 จัดการผู้ใช้
            </a>
            <a href="../logout.php" class="menu-item">
                🚪 ออกจากระบบ
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">👥 จัดการผู้ใช้</h1>
        </div>
        
        <?php showAlert(); ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value">
                    <?php echo $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count']; ?>
                </div>
                <div class="stat-label">ผู้ใช้ทั่วไป</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">👨‍💼</div>
                <div class="stat-value">
                    <?php echo $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count']; ?>
                </div>
                <div class="stat-label">ผู้ดูแลระบบ</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-value">
                    <?php echo $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count']; ?>
                </div>
                <div class="stat-label">สมัครวันนี้</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✍️</div>
                <div class="stat-value">
                    <?php echo $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM quiz_attempts WHERE DATE(started_at) = CURDATE()")->fetch_assoc()['count']; ?>
                </div>
                <div class="stat-label">ใช้งานวันนี้</div>
            </div>
        </div>
        
        <!-- Form เพิ่ม/แก้ไขผู้ใช้ -->
        <div class="section">
            <h2 style="margin-bottom: 20px;">
                <?php echo $edit_user ? '✏️ แก้ไขผู้ใช้' : '➕ เพิ่มผู้ใช้ใหม่'; ?>
            </h2>
            
            <form method="POST">
                <?php if ($edit_user): ?>
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>ชื่อผู้ใช้ *</label>
                        <input type="text" name="username" value="<?php echo $edit_user['username'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>ชื่อ-นามสกุล *</label>
                        <input type="text" name="full_name" value="<?php echo $edit_user['full_name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>อีเมล *</label>
                        <input type="email" name="email" value="<?php echo $edit_user['email'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>ประเภท *</label>
                        <select name="role" required>
                            <option value="user" <?php echo ($edit_user && $edit_user['role'] == 'user') ? 'selected' : ''; ?>>ผู้ใช้ทั่วไป</option>
                            <option value="creator" <?php echo ($edit_user && $edit_user['role'] == 'creator') ? 'selected' : ''; ?>>ผู้สร้างชุดข้อสอบ</option>
                            <option value="admin" <?php echo ($edit_user && $edit_user['role'] == 'admin') ? 'selected' : ''; ?>>ผู้ดูแลระบบ</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            รหัสผ่าน <?php echo $edit_user ? '(เว้นว่างถ้าไม่เปลี่ยน)' : '*'; ?>
                        </label>
                        <input type="password" name="password" <?php echo !$edit_user ? 'required' : ''; ?> placeholder="<?php echo $edit_user ? 'เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน' : 'อย่างน้อย 6 ตัวอักษร'; ?>">
                    </div>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" name="<?php echo $edit_user ? 'update' : 'create'; ?>" class="btn">
                        <?php echo $edit_user ? '💾 บันทึกการแก้ไข' : '➕ เพิ่มผู้ใช้'; ?>
                    </button>
                    <?php if ($edit_user): ?>
                        <a href="manage_users.php" class="btn btn-cancel">ยกเลิก</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- รายการผู้ใช้ -->
        <div class="section">
            <h2 style="margin-bottom: 20px;">📋 ผู้ใช้ทั้งหมด</h2>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>ชื่อผู้ใช้</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>อีเมล</th>
                        <th>ประเภท</th>
                        <th>สถิติ</th>
                        <th>วันที่สมัคร</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo clean($user['username']); ?></strong>
                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                    <span style="color: #667eea; font-size: 0.85em;"> (คุณ)</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo clean($user['full_name']); ?></td>
                            <td><?php echo clean($user['email']); ?></td>
                            <td>
                                <?php
                                // กำหนดค่า badge ก่อน
                                if ($user['role'] === 'admin') {
                                    $badge = 'badge-admin';
                                    $role_text = '👨‍💼 Admin';
                                } elseif ($user['role'] === 'creator') {
                                    $badge = 'badge-creator';
                                    $role_text = '✏️ Creator';
                                } else {
                                    $badge = 'badge-user';
                                    $role_text = '👤 User';
                                }
                                ?>
                                <span class="badge <?php echo $badge; ?>">
                                    <?php echo $role_text; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['total_attempts'] > 0): ?>
                                    <div class="user-stats">
                                        📝 ทำข้อสอบ: <?php echo $user['total_attempts']; ?> ครั้ง<br>
                                        ⭐ คะแนนเฉลี่ย: <?php echo number_format($user['avg_score'], 1); ?>%<br>
                                        🏆 คะแนนสูงสุด: <?php echo number_format($user['best_score'], 1); ?>%
                                    </div>
                                <?php else: ?>
                                    <span style="color: #999;">ยังไม่ได้ทำข้อสอบ</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="?edit=<?php echo $user['id']; ?>" class="btn-sm btn-edit">
                                        ✏️ แก้ไข
                                    </a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?php echo $user['id']; ?>" class="btn-sm btn-delete" onclick="return confirm('ต้องการลบผู้ใช้ <?php echo clean($user['username']); ?>?')">
                                            🗑️ ลบ
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>