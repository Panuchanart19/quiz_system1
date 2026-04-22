<?php
require_once '../config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('../page1.php');
}

$attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผลลัพธ์
$result_query = $conn->prepare("SELECT qa.*, q.title, q.description, q.show_answers, q.pass_score,
                                u.full_name, qc.name as category_name
                                FROM quiz_attempts qa
                                JOIN quizzes q ON qa.quiz_id = q.id
                                JOIN users u ON qa.user_id = u.id
                                LEFT JOIN quiz_categories qc ON q.category_id = qc.id
                                WHERE qa.id = ? AND qa.user_id = ?");
$result_query->bind_param("ii", $attempt_id, $user_id);
$result_query->execute();
$result = $result_query->get_result()->fetch_assoc();

if (!$result) {
    redirect('dashboard.php');
}

// ดึงคำตอบทั้งหมด
$answers_query = $conn->prepare("SELECT ua.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, 
                                 q.correct_answer, q.explanation
                                 FROM user_answers ua
                                 JOIN questions q ON ua.question_id = q.id
                                 WHERE ua.attempt_id = ?
                                 ORDER BY ua.id");
$answers_query->bind_param("i", $attempt_id);
$answers_query->execute();
$answers = $answers_query->get_result();

$passed = $result['score'] >= $result['pass_score'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผลการทำข้อสอบ - <?php echo SITE_NAME; ?></title>
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
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .result-header {
            background: white;
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .result-icon {
            font-size: 5em;
            margin-bottom: 20px;
            animation: bounce 1s;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .result-title {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: <?php echo $passed ? '#4CAF50' : '#f44336'; ?>;
        }
        
        .result-subtitle {
            font-size: 1.2em;
            color: #666;
            margin-bottom: 30px;
        }
        
        .score-circle {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: conic-gradient(
                <?php echo $passed ? '#4CAF50' : '#f44336'; ?> 0deg,
                <?php echo $passed ? '#4CAF50' : '#f44336'; ?> <?php echo $result['score'] * 3.6; ?>deg,
                #e0e0e0 <?php echo $result['score'] * 3.6; ?>deg
            );
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 30px auto;
            position: relative;
        }
        
        .score-inner {
            width: 170px;
            height: 170px;
            background: white;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .score-value {
            font-size: 3em;
            font-weight: bold;
            color: <?php echo $passed ? '#4CAF50' : '#f44336'; ?>;
        }
        
        .score-label {
            color: #666;
            font-size: 1.1em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
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
        
        .answers-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.8em;
            margin-bottom: 25px;
            color: #333;
        }
        
        .answer-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #e0e0e0;
        }
        
        .answer-item.correct {
            border-left-color: #4CAF50;
            background: #e8f5e9;
        }
        
        .answer-item.incorrect {
            border-left-color: #f44336;
            background: #ffebee;
        }
        
        .question-num {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .question-text {
            font-size: 1.1em;
            color: #333;
            margin-bottom: 15px;
        }
        
        .answer-row {
            display: flex;
            gap: 20px;
            margin: 10px 0;
            align-items: center;
        }
        
        .answer-label {
            font-weight: 600;
            min-width: 100px;
        }
        
        .answer-value {
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .correct-answer {
            background: #4CAF50;
            color: white;
        }
        
        .wrong-answer {
            background: #f44336;
            color: white;
        }
        
        .explanation {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-top: 15px;
            border-radius: 6px;
        }
        
        .explanation-title {
            font-weight: bold;
            color: #856404;
            margin-bottom: 5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 15px 30px;
            border-radius: 10px;
            border: none;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h1>📊 ผลการทำข้อสอบ</h1>
            <a href="dashboard.php" style="color: white; text-decoration: none;">← กลับหน้าหลัก</a>
        </div>
    </nav>
    
    <div class="container">
        <!-- Result Header -->
        <div class="result-header">
            <div class="result-icon"><?php echo $passed ? '🎉' : '📝'; ?></div>
            <h1 class="result-title"><?php echo $passed ? 'ยินดีด้วย! ผ่านการทดสอบ' : 'เกือบแล้ว! พยายามอีกนิด'; ?></h1>
            <p class="result-subtitle"><?php echo clean($result['title']); ?></p>
            
            <div class="score-circle">
                <div class="score-inner">
                    <div class="score-value"><?php echo number_format($result['score'], 1); ?>%</div>
                    <div class="score-label">คะแนน</div>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $result['correct_answers']; ?>/<?php echo $result['total_questions']; ?></div>
                    <div class="stat-label">ตอบถูก</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value"><?php echo $result['total_questions'] - $result['correct_answers']; ?></div>
                    <div class="stat-label">ตอบผิด</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value"><?php echo formatTime($result['time_spent']); ?></div>
                    <div class="stat-label">เวลาที่ใช้</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value"><?php echo $result['pass_score']; ?>%</div>
                    <div class="stat-label">คะแนนผ่าน</div>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="take_quiz.php?id=<?php echo $result['quiz_id']; ?>" class="btn btn-primary">🔄 ทำอีกครั้ง</a>
                <a href="dashboard.php" class="btn btn-secondary">📚 เลือกชุดข้อสอบอื่น</a>
            </div>
        </div>
        
        <!-- Answers Review -->
        <?php if ($result['show_answers']): ?>
            <div class="answers-section">
                <h2 class="section-title">📝 เฉลยข้อสอบ</h2>
                
                <?php 
                $question_num = 1;
                while ($answer = $answers->fetch_assoc()): 
                    $is_correct = $answer['is_correct'];
                ?>
                    <div class="answer-item <?php echo $is_correct ? 'correct' : 'incorrect'; ?>">
                        <div class="question-num">คำถามข้อที่ <?php echo $question_num++; ?></div>
                        <div class="question-text"><?php echo clean($answer['question_text']); ?></div>
                        
                        <div class="answer-row">
                            <span class="answer-label">คำตอบของคุณ:</span>
                            <span class="answer-value <?php echo $is_correct ? 'correct-answer' : 'wrong-answer'; ?>">
                                <?php echo $answer['user_answer']; ?>. 
                                <?php echo clean($answer['option_' . strtolower($answer['user_answer'])]); ?>
                            </span>
                        </div>
                        
                        <?php if (!$is_correct): ?>
                            <div class="answer-row">
                                <span class="answer-label">คำตอบที่ถูก:</span>
                                <span class="answer-value correct-answer">
                                    <?php echo $answer['correct_answer']; ?>. 
                                    <?php echo clean($answer['option_' . strtolower($answer['correct_answer'])]); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($answer['explanation']): ?>
                            <div class="explanation">
                                <div class="explanation-title">💡 คำอธิบาย:</div>
                                <?php echo clean($answer['explanation']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>