<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../page1.php');
}

// สถิติรวม
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$total_quizzes = $conn->query("SELECT COUNT(*) as count FROM quizzes")->fetch_assoc()['count'];
$total_questions = $conn->query("SELECT COUNT(*) as count FROM questions")->fetch_assoc()['count'];
$total_attempts = $conn->query("SELECT COUNT(*) as count FROM quiz_attempts WHERE status = 'completed'")->fetch_assoc()['count'];

// ชุดข้อสอบล่าสุด
$recent_quizzes = $conn->query("SELECT q.*, c.name as category_name, u.full_name as creator,
                                (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
                                FROM quizzes q
                                LEFT JOIN quiz_categories c ON q.category_id = c.id
                                LEFT JOIN users u ON q.created_by = u.id
                                ORDER BY q.created_at DESC LIMIT 5");

// ผู้ใช้งานล่าสุด
$recent_users = $conn->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
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
        
        .sidebar-header p {
            font-size: 0.9em;
            opacity: 0.8;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 2em;
            color: #333;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1em;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 1.5em;
            color: #333;
        }
        
        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
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
        }
        
        .badge-success {
            background: #4CAF50;
            color: white;
        }
        
        .badge-warning {
            background: #FF9800;
            color: white;
        }
        
        .badge-danger {
            background: #f44336;
            color: white;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9em;
        }
        
        .btn-edit {
            background: #2196F3;
        }
        
        .btn-delete {
            background: #f44336;
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
            <a href="dashboard.php" class="menu-item active">
                📊 Dashboard
            </a>
            <a href="manage_quizzes.php" class="menu-item">
                📝 สร้างชุดข้อสอบ
            </a>
            <a href="manage_questions.php" class="menu-item">
                ❓ จัดการคำถาม
            </a>
            <a href="manage_users.php" class="menu-item">
                👥 จัดการผู้ใช้
            </a>
            <a href="../logout.php" class="menu-item">
                🚪 ออกจากระบบ
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">📊 Dashboard</h1>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo $total_users; ?></div>
                <div class="stat-label">ผู้ใช้งานทั้งหมด</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-value"><?php echo $total_quizzes; ?></div>
                <div class="stat-label">ชุดข้อสอบ</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">❓</div>
                <div class="stat-value"><?php echo $total_questions; ?></div>
                <div class="stat-label">คำถามทั้งหมด</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✍️</div>
                <div class="stat-value"><?php echo $total_attempts; ?></div>
                <div class="stat-label">จำนวนการทำข้อสอบ</div>
            </div>
        </div>
        
        <!-- Recent Quizzes -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">📝 ชุดข้อสอบล่าสุด</h2>
                <a href="manage_quizzes.php" class="btn">+ สร้างชุดข้อสอบใหม่</a>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>ชื่อชุดข้อสอบ</th>
                        <th>หมวดหมู่</th>
                        <th>ความยาก</th>
                        <th>จำนวนข้อ</th>
                        <th>สถานะ</th>
                        <th>สร้างโดย</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($quiz = $recent_quizzes->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo clean($quiz['title']); ?></strong></td>
                            <td><?php echo clean($quiz['category_name']); ?></td>
                            <td>
                                <span class="badge" style="background-color: <?php echo getDifficultyColor($quiz['difficulty']); ?>">
                                    <?php echo getDifficultyText($quiz['difficulty']); ?>
                                </span>
                            </td>
                            <td><?php echo $quiz['question_count']; ?> ข้อ</td>
                            <td>
                                <span class="badge <?php echo $quiz['is_active'] ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo $quiz['is_active'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?>
                                </span>
                            </td>
                            <td><?php echo clean($quiz['creator']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Recent Users -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">👥 ผู้ใช้งานล่าสุด</h2>
                <a href="manage_users.php" class="btn">ดูทั้งหมด</a>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>ชื่อผู้ใช้</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>อีเมล</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $recent_users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo clean($user['username']); ?></td>
                            <td><?php echo clean($user['full_name']); ?></td>
                            <td><?php echo clean($user['email']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>