<?php
require_once '../config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('../page1.php');
}

// ดึงข้อมูล Leaderboard
$leaderboard_query = "SELECT u.id, u.full_name, u.username,
                      COUNT(DISTINCT qa.id) as total_attempts,
                      AVG(qa.score) as avg_score,
                      MAX(qa.score) as best_score,
                      SUM(qa.correct_answers) as total_correct
                      FROM users u
                      LEFT JOIN quiz_attempts qa ON u.id = qa.user_id AND qa.status = 'completed'
                      WHERE u.role = 'user'
                      GROUP BY u.id
                      HAVING total_attempts > 0
                      ORDER BY avg_score DESC, total_correct DESC
                      LIMIT 50";
$leaderboard = $conn->query($leaderboard_query);

// หาอันดับของผู้ใช้ปัจจุบัน
$user_id = $_SESSION['user_id'];
$user_rank_query = "SELECT ranked.rank FROM (
                    SELECT u.id, 
                    ROW_NUMBER() OVER (ORDER BY AVG(qa.score) DESC, SUM(qa.correct_answers) DESC) as rank
                    FROM users u
                    LEFT JOIN quiz_attempts qa ON u.id = qa.user_id AND qa.status = 'completed'
                    WHERE u.role = 'user'
                    GROUP BY u.id
                    HAVING COUNT(DISTINCT qa.id) > 0
                    ) as ranked
                    WHERE ranked.id = $user_id";
$user_rank = $conn->query($user_rank_query)->fetch_assoc()['rank'] ?? '-';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อันดับคะแนน - <?php echo SITE_NAME; ?></title>
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
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .page-title {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: #666;
            font-size: 1.2em;
        }
        
        .user-rank-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .user-rank-title {
            font-size: 1.2em;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .user-rank-value {
            font-size: 3em;
            font-weight: bold;
        }
        
        .leaderboard-table {
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
        
        tbody tr.current-user {
            background: #e8eaf6;
            font-weight: 600;
        }
        
        .rank {
            font-size: 1.5em;
            font-weight: bold;
            min-width: 60px;
            text-align: center;
        }
        
        .rank-1 {
            color: #FFD700;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        
        .rank-2 {
            color: #C0C0C0;
        }
        
        .rank-3 {
            color: #CD7F32;
        }
        
        .trophy {
            font-size: 1.8em;
        }
        
        .score-bar {
            background: #e0e0e0;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .score-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.5s;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h1>🏆 อันดับคะแนน</h1>
            <div class="nav-right">
                <a href="dashboard.php" class="nav-link">← กลับหน้าหลัก</a>
                <a href="history.php" class="nav-link">📊 ประวัติ</a>
                <a href="../logout.php" class="nav-link">ออกจากระบบ</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">🏆 อันดับคะแนนสูงสุด</h1>
            <p class="page-subtitle">เทียบผลการทำข้อสอบกับผู้ใช้คนอื่น ๆ</p>
        </div>
        
        <?php if ($user_rank !== '-'): ?>
            <div class="user-rank-card">
                <div class="user-rank-title">อันดับของคุณ</div>
                <div class="user-rank-value">#<?php echo $user_rank; ?></div>
            </div>
        <?php endif; ?>
        
        <div class="leaderboard-table">
            <table>
                <thead>
                    <tr>
                        <th>อันดับ</th>
                        <th>ชื่อผู้ใช้</th>
                        <th>จำนวนข้อสอบ</th>
                        <th>คะแนนเฉลี่ย</th>
                        <th>คะแนนสูงสุด</th>
                        <th>ตอบถูกรวม</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    while ($row = $leaderboard->fetch_assoc()): 
                        $is_current_user = ($row['id'] == $user_id);
                        $rank_class = '';
                        $trophy = '';
                        
                        if ($rank == 1) {
                            $rank_class = 'rank-1';
                            $trophy = '🥇';
                        } elseif ($rank == 2) {
                            $rank_class = 'rank-2';
                            $trophy = '🥈';
                        } elseif ($rank == 3) {
                            $rank_class = 'rank-3';
                            $trophy = '🥉';
                        }
                    ?>
                        <tr class="<?php echo $is_current_user ? 'current-user' : ''; ?>">
                            <td>
                                <div class="rank <?php echo $rank_class; ?>">
                                    <?php echo $trophy ? $trophy : "#$rank"; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo clean($row['full_name']); ?></strong>
                                <?php if ($is_current_user): ?>
                                    <span style="color: #667eea; margin-left: 10px;">⭐ (คุณ)</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['total_attempts']; ?> ครั้ง</td>
                            <td>
                                <strong style="color: #667eea; font-size: 1.2em;">
                                    <?php echo number_format($row['avg_score'], 1); ?>%
                                </strong>
                                <div class="score-bar">
                                    <div class="score-fill" style="width: <?php echo $row['avg_score']; ?>%"></div>
                                </div>
                            </td>
                            <td><?php echo number_format($row['best_score'], 1); ?>%</td>
                            <td><?php echo $row['total_correct']; ?> ข้อ</td>
                        </tr>
                    <?php 
                        $rank++;
                    endwhile; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>