<?php
require_once '../config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('../page1.php');
}

$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลชุดข้อสอบ
$quiz_query = $conn->prepare("SELECT q.*, c.name as category_name, 
                              (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
                              FROM quizzes q
                              LEFT JOIN quiz_categories c ON q.category_id = c.id
                              WHERE q.id = ? AND q.is_active = 1");
$quiz_query->bind_param("i", $quiz_id);
$quiz_query->execute();
$quiz = $quiz_query->get_result()->fetch_assoc();

if (!$quiz) {
    redirect('dashboard.php');
}

// เริ่มการทำข้อสอบ
if (!isset($_SESSION['attempt_id']) || $_SESSION['quiz_id'] != $quiz_id) {
    // สร้างรอบการทำข้อสอบใหม่
    $stmt = $conn->prepare("INSERT INTO quiz_attempts (user_id, quiz_id, total_questions, status) VALUES (?, ?, ?, 'in_progress')");
    $stmt->bind_param("iii", $user_id, $quiz_id, $quiz['question_count']);
    $stmt->execute();
    
    $_SESSION['attempt_id'] = $conn->insert_id;
    $_SESSION['quiz_id'] = $quiz_id;
    $_SESSION['current_question'] = 1;
    $_SESSION['start_time'] = time();
}

$attempt_id = $_SESSION['attempt_id'];
$current_question_num = $_SESSION['current_question'];

// ดึงคำถาม
$questions_query = "SELECT * FROM questions WHERE quiz_id = ?";
if ($quiz['shuffle_questions']) {
    $questions_query .= " ORDER BY RAND()";
} else {
    $questions_query .= " ORDER BY order_num, id";
}

$stmt = $conn->prepare($questions_query);
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$all_questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ตรวจสอบว่ามีคำถามหรือไม่
if (empty($all_questions)) {
    setAlert('ชุดข้อสอบนี้ยังไม่มีคำถาม', 'error');
    redirect('dashboard.php');
}

// บันทึกคำตอบ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer'])) {
    $question_id = (int)$_POST['question_id'];
    $answer = $_POST['answer'];
    $question_time = (int)$_POST['time_spent'];
    
    // หาคำตอบที่ถูกต้อง
    $correct_query = $conn->prepare("SELECT correct_answer FROM questions WHERE id = ?");
    $correct_query->bind_param("i", $question_id);
    $correct_query->execute();
    $correct_answer = $correct_query->get_result()->fetch_assoc()['correct_answer'];
    
    $is_correct = ($answer === $correct_answer) ? 1 : 0;
    
    // บันทึกคำตอบ
    $save_answer = $conn->prepare("INSERT INTO user_answers (attempt_id, question_id, user_answer, is_correct, time_spent) VALUES (?, ?, ?, ?, ?)");
    $save_answer->bind_param("iisii", $attempt_id, $question_id, $answer, $is_correct, $question_time);
    $save_answer->execute();
    
    $_SESSION['current_question']++;
    
    // ถ้าตอบครบแล้ว
    if ($_SESSION['current_question'] > count($all_questions)) {
        // คำนวณคะแนน
        $correct_count = $conn->query("SELECT COUNT(*) as count FROM user_answers WHERE attempt_id = $attempt_id AND is_correct = 1")->fetch_assoc()['count'];
        $score = ($correct_count / count($all_questions)) * 100;
        $total_time = time() - $_SESSION['start_time'];
        
        // อัพเดทผลลัพธ์
        $update = $conn->prepare("UPDATE quiz_attempts SET score = ?, correct_answers = ?, time_spent = ?, status = 'completed', completed_at = NOW() WHERE id = ?");
        $update->bind_param("diii", $score, $correct_count, $total_time, $attempt_id);
        $update->execute();
        
        // ล้าง session
        unset($_SESSION['attempt_id']);
        unset($_SESSION['quiz_id']);
        unset($_SESSION['current_question']);
        unset($_SESSION['start_time']);
        
        redirect("result.php?attempt_id=$attempt_id");
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?id=$quiz_id");
    exit();
}

$current_question = $all_questions[$current_question_num - 1];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo clean($quiz['title']); ?> - <?php echo SITE_NAME; ?></title>
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
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .progress-bar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-weight: 600;
            color: #333;
        }
        
        .progress {
            height: 20px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
        }
        
        .question-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .question-header {
            margin-bottom: 30px;
        }
        
        .question-number {
            color: #667eea;
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .question-text {
            font-size: 1.5em;
            color: #333;
            line-height: 1.6;
        }
        
        .timer {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            font-size: 1.5em;
            font-weight: bold;
            margin: 20px 0;
            border: 2px solid #ffc107;
        }
        
        .options {
            margin-top: 30px;
        }
        
        .option {
            background: #f8f9fa;
            border: 3px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px 25px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .option:hover {
            background: #e8eaf6;
            border-color: #667eea;
            transform: translateX(5px);
        }
        
        .option.selected {
            background: #e8eaf6;
            border-color: #667eea;
        }
        
        .option-label {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2em;
            flex-shrink: 0;
        }
        
        .option-text {
            font-size: 1.1em;
            color: #333;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.2em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 30px;
            transition: transform 0.2s;
        }
        
        .btn-submit:hover {
            transform: scale(1.02);
        }
        
        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        input[type="radio"] {
            display: none;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h1>📝 <?php echo clean($quiz['title']); ?></h1>
            <div>⏱️ <span id="totalTime">00:00</span></div>
        </div>
    </nav>
    
    <div class="container">
        <!-- Progress Bar -->
        <div class="progress-bar">
            <div class="progress-info">
                <span>ข้อ <?php echo $current_question_num; ?> จาก <?php echo count($all_questions); ?></span>
                <span><?php echo round(($current_question_num / count($all_questions)) * 100); ?>%</span>
            </div>
            <div class="progress">
                <div class="progress-fill" style="width: <?php echo ($current_question_num / count($all_questions)) * 100; ?>%"></div>
            </div>
        </div>
        
        <!-- Question Card -->
        <div class="question-card">
            <div class="question-header">
                <div class="question-number">คำถามข้อที่ <?php echo $current_question_num; ?></div>
                <div class="question-text"><?php echo clean($current_question['question_text']); ?></div>
            </div>
            
            <?php if ($quiz['time_per_question'] > 0): ?>
                <div class="timer" id="timer">⏱️ เวลาคงเหลือ: <span id="timeLeft"><?php echo $quiz['time_per_question']; ?></span> วินาที</div>
            <?php endif; ?>
            
            <form method="POST" id="quizForm">
                <input type="hidden" name="question_id" value="<?php echo $current_question['id']; ?>">
                <input type="hidden" name="time_spent" id="timeSpent" value="0">
                
                <div class="options">
                    <label class="option" onclick="selectOption(this, 'A')">
                        <div class="option-label">A</div>
                        <div class="option-text"><?php echo clean($current_question['option_a']); ?></div>
                        <input type="radio" name="answer" value="A" required>
                    </label>
                    
                    <label class="option" onclick="selectOption(this, 'B')">
                        <div class="option-label">B</div>
                        <div class="option-text"><?php echo clean($current_question['option_b']); ?></div>
                        <input type="radio" name="answer" value="B" required>
                    </label>
                    
                    <label class="option" onclick="selectOption(this, 'C')">
                        <div class="option-label">C</div>
                        <div class="option-text"><?php echo clean($current_question['option_c']); ?></div>
                        <input type="radio" name="answer" value="C" required>
                    </label>
                    
                    <label class="option" onclick="selectOption(this, 'D')">
                        <div class="option-label">D</div>
                        <div class="option-text"><?php echo clean($current_question['option_d']); ?></div>
                        <input type="radio" name="answer" value="D" required>
                    </label>
                </div>
                
                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    <?php echo $current_question_num < count($all_questions) ? 'ข้อถัดไป →' : 'ส่งคำตอบ ✓'; ?>
                </button>
            </form>
        </div>
    </div>
    
    <script>
        let questionStartTime = Date.now();
        let totalStartTime = <?php echo $_SESSION['start_time']; ?> * 1000;
        let timePerQuestion = <?php echo $quiz['time_per_question']; ?>;
        let timeLeft = timePerQuestion;
        
        function selectOption(element, value) {
            document.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            document.querySelector(`input[value="${value}"]`).checked = true;
            document.getElementById('submitBtn').disabled = false;
        }
        
        // อัพเดทเวลารวม
        setInterval(() => {
            let elapsed = Math.floor((Date.now() - totalStartTime) / 1000);
            let minutes = Math.floor(elapsed / 60);
            let seconds = elapsed % 60;
            document.getElementById('totalTime').textContent = 
                String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            
            // อัพเดทเวลาที่ใช้ในข้อนี้
            let questionTime = Math.floor((Date.now() - questionStartTime) / 1000);
            document.getElementById('timeSpent').value = questionTime;
        }, 1000);
        
        // นับถอยหลังต่อข้อ
        if (timePerQuestion > 0) {
            let countdown = setInterval(() => {
                timeLeft--;
                document.getElementById('timeLeft').textContent = timeLeft;
                
                if (timeLeft <= 10) {
                    document.getElementById('timer').style.background = '#f8d7da';
                    document.getElementById('timer').style.color = '#721c24';
                    document.getElementById('timer').style.borderColor = '#f44336';
                }
                
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    // ส่งฟอร์มอัตโนมัติเมื่อหมดเวลา
                    if (!document.querySelector('input[name="answer"]:checked')) {
                        // ถ้ายังไม่เลือก ให้เลือกข้อแรก
                        document.querySelector('input[name="answer"]').checked = true;
                    }
                    document.getElementById('quizForm').submit();
                }
            }, 1000);
        }
        
        // ป้องกันการกด Back
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function() {
            window.history.pushState(null, null, window.location.href);
            if (confirm('คุณต้องการออกจากการทำข้อสอบหรือไม่?')) {
                window.location.href = 'dashboard.php';
            }
        };
    </script>
</body>
</html>