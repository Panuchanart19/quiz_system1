<?php
require_once '../config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('../page1.php');
}

// ดึงข้อมูลสถิติของผู้ใช้
$user_id = $_SESSION['user_id'];

// จำนวนข้อสอบที่ทำทั้งหมด
$total_attempts = $conn->query("SELECT COUNT(*) as count FROM quiz_attempts WHERE user_id = $user_id AND status = 'completed'")->fetch_assoc()['count'];

// คะแนนเฉลี่ย
$avg_score = $conn->query("SELECT AVG(score) as avg FROM quiz_attempts WHERE user_id = $user_id AND status = 'completed'")->fetch_assoc()['avg'] ?? 0;

// ชุดข้อสอบทั้งหมด
$total_quizzes = $conn->query("SELECT COUNT(*) as count FROM quizzes WHERE is_active = 1")->fetch_assoc()['count'];

// ดึงข้อสอบทั้งหมด
$quizzes_query = "SELECT q.*, c.name as category_name, 
                  (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count,
                  (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id AND user_id = $user_id AND status = 'completed') as attempt_count
                  FROM quizzes q
                  LEFT JOIN quiz_categories c ON q.category_id = c.id
                  WHERE q.is_active = 1
                  ORDER BY q.created_at DESC";
$quizzes = $conn->query($quizzes_query);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลัก - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar h1 {
            font-size: 1.5em;
        }
        
        .nav-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 3em;
            margin-bottom: 10px;
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
        
        .section-title {
            font-size: 2em;
            margin-bottom: 25px;
            color: #333;
        }
        
        .quiz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }
        
        .quiz-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .quiz-card:hover {
            transform: translateY(-8px);
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
        
        .quiz-category {
            opacity: 0.9;
            font-size: 0.9em;
        }
        
        .quiz-body {
            padding: 20px;
        }
        
        .quiz-description {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .quiz-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 0.9em;
        }
        
        .difficulty-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            color: white;
        }
        
        .btn-start {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-start:hover {
            transform: scale(1.02);
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
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h1>🎯 <?php echo SITE_NAME; ?></h1>
            <div class="nav-right">
                <span>สวัสดี, <?php echo clean($_SESSION['full_name']); ?></span>
                <a href="history.php" class="nav-link">📊 ประวัติการทำข้อสอบ</a>
                <a href="leaderboard.php" class="nav-link">🏆 อันดับคะแนน</a>
                <a href="../logout.php" class="nav-link">ออกจากระบบ</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <?php showAlert(); ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-value"><?php echo $total_attempts; ?></div>
                <div class="stat-label">ทำแบบทดสอบแล้ว</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-value"><?php echo number_format($avg_score, 1); ?>%</div>
                <div class="stat-label">คะแนนเฉลี่ย</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-value"><?php echo $total_quizzes; ?></div>
                <div class="stat-label">ชุดข้อสอบทั้งหมด</div>
            </div>
        </div>
        
        <!-- Available Quizzes -->
        <h2 class="section-title">ชุดข้อสอบทั้งหมด</h2>
        
        <div class="quiz-grid">
            <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                <div class="quiz-card">
                    <div class="quiz-header">
                        <h3 class="quiz-title"><?php echo clean($quiz['title']); ?></h3>
                        <div class="quiz-category">📁 <?php echo clean($quiz['category_name']); ?></div>
                    </div>
                    
                    <div class="quiz-body">
                        <p class="quiz-description"><?php echo clean($quiz['description']); ?></p>
                        
                        <div class="quiz-meta">
                            <div class="meta-item">
                                📝 <?php echo $quiz['question_count']; ?> ข้อ
                            </div>
                            
                            <?php if ($quiz['time_per_question'] > 0): ?>
                                <div class="meta-item">
                                    ⏱️ <?php echo $quiz['time_per_question']; ?> วิ/ข้อ
                                </div>
                            <?php endif; ?>
                            
                            <div class="meta-item">
                                <span class="difficulty-badge" style="background-color: <?php echo getDifficultyColor($quiz['difficulty']); ?>">
                                    <?php echo getDifficultyText($quiz['difficulty']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($quiz['attempt_count'] > 0): ?>
                            <p style="color: #4caf50; margin-bottom: 15px; font-size: 0.9em;">
                                ✓ ทำแล้ว <?php echo $quiz['attempt_count']; ?> ครั้ง
                            </p>
                        <?php endif; ?>
                        
                        <a href="take_quiz.php?id=<?php echo $quiz['id']; ?>">
                            <button class="btn-start">
                                <?php echo $quiz['attempt_count'] > 0 ? '🔄 ทำอีกครั้ง' : '▶️ เริ่มทำข้อสอบ'; ?>
                            </button>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>