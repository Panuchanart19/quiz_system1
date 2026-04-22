<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../page1.php');
}

// เลือกชุดข้อสอบ
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

// สร้างคำถามใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $quiz_id = $_POST['quiz_id'];
    $question_text = $_POST['question_text'];
    $option_a = $_POST['option_a'];
    $option_b = $_POST['option_b'];
    $option_c = $_POST['option_c'];
    $option_d = $_POST['option_d'];
    $correct_answer = $_POST['correct_answer'];
    $explanation = $_POST['explanation'];
    $order_num = $_POST['order_num'] ?? 0;
    
    $stmt = $conn->prepare("INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, order_num) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssi", $quiz_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $explanation, $order_num);
    
    if ($stmt->execute()) {
        setAlert('เพิ่มคำถามสำเร็จ!', 'success');
    } else {
        setAlert('เกิดข้อผิดพลาด', 'error');
    }
    redirect("manage_questions.php?quiz_id=$quiz_id");
}

// ลบคำถาม
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM questions WHERE id = $id");
    setAlert('ลบคำถามสำเร็จ!', 'success');
    redirect("manage_questions.php?quiz_id=$quiz_id");
}

// แก้ไขคำถาม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = $_POST['question_id'];
    $quiz_id = $_POST['quiz_id'];
    $question_text = $_POST['question_text'];
    $option_a = $_POST['option_a'];
    $option_b = $_POST['option_b'];
    $option_c = $_POST['option_c'];
    $option_d = $_POST['option_d'];
    $correct_answer = $_POST['correct_answer'];
    $explanation = $_POST['explanation'];
    $order_num = $_POST['order_num'] ?? 0;
    
    $stmt = $conn->prepare("UPDATE questions SET question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_answer=?, explanation=?, order_num=? WHERE id=?");
    $stmt->bind_param("sssssssii", $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $explanation, $order_num, $id);
    
    if ($stmt->execute()) {
        setAlert('แก้ไขคำถามสำเร็จ!', 'success');
    }
    redirect("manage_questions.php?quiz_id=$quiz_id");
}

// ดึงข้อมูลชุดข้อสอบทั้งหมดสำหรับ dropdown
$quizzes = $conn->query("SELECT id, title FROM quizzes ORDER BY title");

// ดึงข้อมูลชุดข้อสอบที่เลือก
$selected_quiz = null;
if ($quiz_id > 0) {
    $selected_quiz = $conn->query("SELECT * FROM quizzes WHERE id = $quiz_id")->fetch_assoc();
    
    // ดึงคำถามในชุดนี้
    $questions = $conn->query("SELECT * FROM questions WHERE quiz_id = $quiz_id ORDER BY order_num, id");
}

// สำหรับแก้ไข
$edit_question = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_question = $conn->query("SELECT * FROM questions WHERE id = $edit_id")->fetch_assoc();
    if ($edit_question) {
        $quiz_id = $edit_question['quiz_id'];
        $selected_quiz = $conn->query("SELECT * FROM quizzes WHERE id = $quiz_id")->fetch_assoc();
        $questions = $conn->query("SELECT * FROM questions WHERE quiz_id = $quiz_id ORDER BY order_num, id");
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำถาม - <?php echo SITE_NAME; ?></title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 2em;
            color: #333;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .quiz-selector {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            color: white;
        }
        
        .quiz-selector select {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            margin-top: 10px;
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
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .option-input {
            display: flex;
            align-items: center;
            gap: 10px;
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
            flex-shrink: 0;
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
            vertical-align: top;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .question-preview {
            font-size: 1.05em;
            color: #333;
            margin-bottom: 10px;
        }
        
        .answer-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .answer-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.9em;
            background: #e0e0e0;
        }
        
        .answer-badge.correct {
            background: #4CAF50;
            color: white;
            font-weight: 600;
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
        }
        
        .btn-edit {
            background: #2196F3;
            color: white;
        }
        
        .btn-delete {
            background: #f44336;
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
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-icon {
            font-size: 5em;
            margin-bottom: 20px;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        
        .radio-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
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
            <a href="manage_questions.php" class="menu-item active">
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
            <h1 class="page-title">❓ จัดการคำถาม</h1>
            <a href="manage_quizzes.php" class="btn">← กลับไปสร้างชุดข้อสอบ</a>
        </div>
        
        <?php showAlert(); ?>
        
        <!-- Quiz Selector -->
        <div class="quiz-selector">
            <h3 style="margin-bottom: 10px;">📚 เลือกชุดข้อสอบ</h3>
            <select onchange="window.location.href='manage_questions.php?quiz_id=' + this.value">
                <option value="">-- เลือกชุดข้อสอบ --</option>
                <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                    <option value="<?php echo $quiz['id']; ?>" <?php echo ($quiz_id == $quiz['id']) ? 'selected' : ''; ?>>
                        <?php echo clean($quiz['title']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <?php if ($selected_quiz): ?>
            <!-- Form เพิ่ม/แก้ไขคำถาม -->
            <div class="section">
                <h2 style="margin-bottom: 20px;">
                    <?php echo $edit_question ? '✏️ แก้ไขคำถาม' : '➕ เพิ่มคำถามใหม่'; ?>
                </h2>
                <p style="color: #666; margin-bottom: 20px;">
                    ชุดข้อสอบ: <strong><?php echo clean($selected_quiz['title']); ?></strong>
                </p>
                
                <form method="POST">
                    <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                    <?php if ($edit_question): ?>
                        <input type="hidden" name="question_id" value="<?php echo $edit_question['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>คำถาม *</label>
                        <textarea name="question_text" required placeholder="พิมพ์คำถามที่นี่..."><?php echo $edit_question['question_text'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="options-grid">
                        <div class="form-group">
                            <label>ตัวเลือก A *</label>
                            <div class="option-input">
                                <div class="option-label">A</div>
                                <input type="text" name="option_a" value="<?php echo $edit_question['option_a'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>ตัวเลือก B *</label>
                            <div class="option-input">
                                <div class="option-label">B</div>
                                <input type="text" name="option_b" value="<?php echo $edit_question['option_b'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>ตัวเลือก C *</label>
                            <div class="option-input">
                                <div class="option-label">C</div>
                                <input type="text" name="option_c" value="<?php echo $edit_question['option_c'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>ตัวเลือก D *</label>
                            <div class="option-input">
                                <div class="option-label">D</div>
                                <input type="text" name="option_d" value="<?php echo $edit_question['option_d'] ?? ''; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>คำตอบที่ถูกต้อง *</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="correct_answer" value="A" <?php echo ($edit_question && $edit_question['correct_answer'] == 'A') ? 'checked' : ''; ?> required>
                                <span>A</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="correct_answer" value="B" <?php echo ($edit_question && $edit_question['correct_answer'] == 'B') ? 'checked' : ''; ?> required>
                                <span>B</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="correct_answer" value="C" <?php echo ($edit_question && $edit_question['correct_answer'] == 'C') ? 'checked' : ''; ?> required>
                                <span>C</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="correct_answer" value="D" <?php echo ($edit_question && $edit_question['correct_answer'] == 'D') ? 'checked' : ''; ?> required>
                                <span>D</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>คำอธิบาย / เฉลย</label>
                        <textarea name="explanation" placeholder="อธิบายเหตุผลของคำตอบที่ถูกต้อง (ไม่บังคับ)"><?php echo $edit_question['explanation'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>ลำดับที่ (ไม่บังคับ)</label>
                        <input type="number" name="order_num" value="<?php echo $edit_question['order_num'] ?? 0; ?>" min="0" placeholder="0 = ไม่กำหนดลำดับ">
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="<?php echo $edit_question ? 'update' : 'create'; ?>" class="btn">
                            <?php echo $edit_question ? '💾 บันทึกการแก้ไข' : '➕ เพิ่มคำถาม'; ?>
                        </button>
                        <?php if ($edit_question): ?>
                            <a href="manage_questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-cancel">ยกเลิก</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- รายการคำถาม -->
            <div class="section">
                <h2 style="margin-bottom: 20px;">📋 คำถามทั้งหมด (<?php echo $questions->num_rows; ?> ข้อ)</h2>
                
                <?php if ($questions->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ลำดับ</th>
                                <th>คำถามและตัวเลือก</th>
                                <th style="width: 120px;">คำตอบ</th>
                                <th style="width: 200px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $num = 1;
                            while ($q = $questions->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td style="text-align: center; font-weight: bold; color: #667eea;">
                                        <?php echo $num++; ?>
                                    </td>
                                    <td>
                                        <div class="question-preview">
                                            <?php echo clean($q['question_text']); ?>
                                        </div>
                                        <div class="answer-preview">
                                            <span class="answer-badge <?php echo $q['correct_answer'] == 'A' ? 'correct' : ''; ?>">
                                                A. <?php echo clean($q['option_a']); ?>
                                            </span>
                                            <span class="answer-badge <?php echo $q['correct_answer'] == 'B' ? 'correct' : ''; ?>">
                                                B. <?php echo clean($q['option_b']); ?>
                                            </span>
                                            <span class="answer-badge <?php echo $q['correct_answer'] == 'C' ? 'correct' : ''; ?>">
                                                C. <?php echo clean($q['option_c']); ?>
                                            </span>
                                            <span class="answer-badge <?php echo $q['correct_answer'] == 'D' ? 'correct' : ''; ?>">
                                                D. <?php echo clean($q['option_d']); ?>
                                            </span>
                                        </div>
                                        <?php if ($q['explanation']): ?>
                                            <div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-radius: 6px; font-size: 0.9em;">
                                                💡 <strong>คำอธิบาย:</strong> <?php echo clean($q['explanation']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="background: #4CAF50; color: white; padding: 10px; border-radius: 8px; font-weight: bold; font-size: 1.2em;">
                                            <?php echo $q['correct_answer']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?quiz_id=<?php echo $quiz_id; ?>&edit=<?php echo $q['id']; ?>" class="btn-sm btn-edit">
                                                ✏️ แก้ไข
                                            </a>
                                            <a href="?quiz_id=<?php echo $quiz_id; ?>&delete=<?php echo $q['id']; ?>" class="btn-sm btn-delete" onclick="return confirm('ต้องการลบคำถามนี้?')">
                                                🗑️ ลบ
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">❓</div>
                        <p style="font-size: 1.2em;">ยังไม่มีคำถามในชุดข้อสอบนี้</p>
                        <p style="margin-top: 10px; color: #666;">เพิ่มคำถามแรกของคุณด้านบน</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" style="background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div class="empty-icon">📚</div>
                <p style="font-size: 1.2em;">กรุณาเลือกชุดข้อสอบก่อน</p>
                <p style="margin-top: 10px; color: #666;">เลือกชุดข้อสอบจากด้านบนเพื่อเริ่มจัดการคำถาม</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>