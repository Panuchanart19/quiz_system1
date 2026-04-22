<?php
require_once '../config.php';

if (!isLoggedIn() || !isCreator()) {
    redirect('../page1.php');
}

$creator_id = $_SESSION['user_id'];

// สถิติของ Creator
$my_quizzes = $conn->query("SELECT COUNT(*) as count FROM quizzes WHERE created_by = $creator_id")->fetch_assoc()['count'];
$total_questions = $conn->query("SELECT COUNT(*) as count FROM questions q JOIN quizzes qz ON q.quiz_id = qz.id WHERE qz.created_by = $creator_id")->fetch_assoc()['count'];
$total_attempts = $conn->query("SELECT COUNT(*) as count FROM quiz_attempts qa JOIN quizzes qz ON qa.quiz_id = qz.id WHERE qz.created_by = $creator_id AND qa.status = 'completed'")->fetch_assoc()['count'];

// ชุดข้อสอบของฉัน
$my_quizzes_query = "SELECT q.*, c.name as category_name,
                     (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count,
                     (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id AND status = 'completed') as attempt_count
                     FROM quizzes q
                     LEFT JOIN quiz_categories c ON q.category_id = c.id
                     WHERE q.created_by = $creator_id
                     ORDER BY q.created_at DESC";
$quizzes = $conn->query($my_quizzes_query);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creator Dashboard - <?php echo SITE_NAME; ?></title>
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
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            font-weight: 600;
        }
        
        .quiz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }
        
        .quiz-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .quiz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .quiz-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        
        .quiz-title {
            font-size: 1.3em;
            margin-bottom: 5px;
        }
        
        .quiz-body {
            padding: 20px;
        }
        
        .quiz-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 0.9em;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9em;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-edit {
            background: #2196F3;
            color: white;
        }
        
        .btn-questions {
            background: #4CAF50;
            color: white;
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
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-icon {
            font-size: 5em;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>📝 Creator Panel</h2>
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
            <a href="../logout.php" class="menu-item">
                🚪 ออกจากระบบ
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">📊 Creator Dashboard</h1>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-value"><?php echo $my_quizzes; ?></div>
                <div class="stat-label">ชุดข้อสอบของฉัน</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">❓</div>
                <div class="stat-value"><?php echo $total_questions; ?></div>
                <div class="stat-label">คำถามทั้งหมด</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✍️</div>
                <div class="stat-value"><?php echo $total_attempts; ?></div>
                <div class="stat-label">ครั้งที่ถูกทำข้อสอบ</div>
            </div>
        </div>
        
        <!-- My Quizzes -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">📝 ชุดข้อสอบของฉัน</h2>
                <a href="manage_quizzes.php" class="btn">+ สร้างชุดข้อสอบใหม่</a>
            </div>
            
            <?php if ($quizzes->num_rows > 0): ?>
                <div class="quiz-grid">
                    <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                        <div class="quiz-card">
                            <div class="quiz-header">
                                <h3 class="quiz-title"><?php echo clean($quiz['title']); ?></h3>
                                <div style="opacity: 0.9; font-size: 0.9em;">
                                    📁 <?php echo clean($quiz['category_name']); ?>
                                </div>
                            </div>
                            
                            <div class="quiz-body">
                                <div class="quiz-meta">
                                    <div class="meta-item">
                                        📝 <?php echo $quiz['question_count']; ?> ข้อ
                                    </div>
                                    <div class="meta-item">
                                        ✍️ ทำแล้ว <?php echo $quiz['attempt_count']; ?> ครั้ง
                                    </div>
                                    <div class="meta-item">
                                        <span class="badge <?php echo $quiz['is_active'] ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $quiz['is_active'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="btn-group">
                                    <a href="manage_quizzes.php?edit=<?php echo $quiz['id']; ?>" class="btn-sm btn-edit">
                                        ✏️ แก้ไข
                                    </a>
                                    <a href="manage_questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn-sm btn-questions">
                                        ❓ จัดการคำถาม
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <p style="font-size: 1.2em;">คุณยังไม่มีชุดข้อสอบ</p>
                    <p style="margin-top: 10px; color: #666;">เริ่มสร้างชุดข้อสอบแรกของคุณเลย!</p>
                    <a href="manage_quizzes.php" class="btn" style="margin-top: 20px;">+ สร้างชุดข้อสอบใหม่</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>