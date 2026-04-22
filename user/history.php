<?php
require_once '../config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('../page1.php');
}

$user_id = $_SESSION['user_id'];

// ดึงประวัติการทำข้อสอบ
$history_query = $conn->prepare("SELECT qa.*, q.title, q.difficulty, qc.name as category_name
                                 FROM quiz_attempts qa
                                 JOIN quizzes q ON qa.quiz_id = q.id
                                 LEFT JOIN quiz_categories qc ON q.category_id = qc.id
                                 WHERE qa.user_id = ? AND qa.status = 'completed'
                                 ORDER BY qa.completed_at DESC");
$history_query->bind_param("i", $user_id);
$history_query->execute();
$history = $history_query->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการทำข้อสอบ - <?php echo SITE_NAME; ?></title>
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
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 2em;
            color: #333;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: #666;
            font-size: 1.1em;
        }
        
        .history-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        th {
            padding: 20px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .score-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            color: white;
        }
        
        .score-high {
            background: #4CAF50;
        }
        
        .score-medium {
            background: #FF9800;
        }
        
        .score-low {
            background: #f44336;
        }
        
        .difficulty-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
            color: white;
        }
        
        .btn-view {
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .btn-view:hover {
            background: #5568d3;
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
        
        .empty-text {
            font-size: 1.2em;
            margin-bottom: 30px;
        }
        
        .btn-start {
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h1>📊 ประวัติการทำข้อสอบ</h1>
            <div class="nav-right">
                <a href="dashboard.php" class="nav-link">← กลับหน้าหลัก</a>
                <a href="leaderboard.php" class="nav-link">🏆 อันดับคะแนน</a>
                <a href="../logout.php" class="nav-link">ออกจากระบบ</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h2 class="page-title">ประวัติการทำข้อสอบของคุณ</h2>
            <p class="page-subtitle">ดูผลการทำข้อสอบย้อนหลังทั้งหมด</p>
        </div>
        
        <div class="history-table">
            <?php if ($history->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ชื่อชุดข้อสอบ</th>
                            <th>หมวดหมู่</th>
                            <th>ความยาก</th>
                            <th>คะแนน</th>
                            <th>ถูก/ทั้งหมด</th>
                            <th>เวลาที่ใช้</th>
                            <th>วันที่ทำ</th>
                            <th>การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $history->fetch_assoc()): 
                            $score_class = $row['score'] >= 80 ? 'score-high' : ($row['score'] >= 60 ? 'score-medium' : 'score-low');
                        ?>
                            <tr>
                                <td><strong><?php echo clean($row['title']); ?></strong></td>
                                <td><?php echo clean($row['category_name']); ?></td>
                                <td>
                                    <span class="difficulty-badge" style="background-color: <?php echo getDifficultyColor($row['difficulty']); ?>">
                                        <?php echo getDifficultyText($row['difficulty']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="score-badge <?php echo $score_class; ?>">
                                        <?php echo number_format($row['score'], 1); ?>%
                                    </span>
                                </td>
                                <td><?php echo $row['correct_answers']; ?>/<?php echo $row['total_questions']; ?></td>
                                <td><?php echo formatTime($row['time_spent']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['completed_at'])); ?></td>
                                <td>
                                    <a href="result.php?attempt_id=<?php echo $row['id']; ?>" class="btn-view">ดูผลลัพธ์</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <div class="empty-text">คุณยังไม่ได้ทำข้อสอบ</div>
                    <a href="dashboard.php" class="btn-start">เริ่มทำข้อสอบ</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>