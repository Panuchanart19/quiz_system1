<?php
require_once '../config.php';

if (!isLoggedIn() || !isCreator()) {
    redirect('../page1.php');
}

$creator_id = $_SESSION['user_id'];

// สร้างชุดข้อสอบใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $difficulty = $_POST['difficulty'];
    $time_per_question = $_POST['time_per_question'] ?? 0;
    $pass_score = $_POST['pass_score'] ?? 60;
    $shuffle = isset($_POST['shuffle_questions']) ? 1 : 0;
    $show_answers = isset($_POST['show_answers']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO quizzes (title, description, category_id, difficulty, time_per_question, pass_score, shuffle_questions, show_answers, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssissiiiii", $title, $description, $category_id, $difficulty, $time_per_question, $pass_score, $shuffle, $show_answers, $is_active, $creator_id);
    
    if ($stmt->execute()) {
        setAlert('สร้างชุดข้อสอบสำเร็จ!', 'success');
    } else {
        setAlert('เกิดข้อผิดพลาด', 'error');
    }
    redirect('manage_quizzes.php');
}

// ลบชุดข้อสอบ (เฉพาะของตัวเอง)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $check = $conn->query("SELECT created_by FROM quizzes WHERE id = $id")->fetch_assoc();
    
    if ($check && $check['created_by'] == $creator_id) {
        $conn->query("DELETE FROM quizzes WHERE id = $id");
        setAlert('ลบชุดข้อสอบสำเร็จ!', 'success');
    } else {
        setAlert('คุณไม่มีสิทธิ์ลบชุดข้อสอบนี้', 'error');
    }
    redirect('manage_quizzes.php');
}

// แก้ไขชุดข้อสอบ (เฉพาะของตัวเอง)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = $_POST['quiz_id'];
    
    // ตรวจสอบสิทธิ์
    $check = $conn->query("SELECT created_by FROM quizzes WHERE id = $id")->fetch_assoc();
    
    if ($check && $check['created_by'] == $creator_id) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $category_id = $_POST['category_id'];
        $difficulty = $_POST['difficulty'];
        $time_per_question = $_POST['time_per_question'] ?? 0;
        $pass_score = $_POST['pass_score'] ?? 60;
        $shuffle = isset($_POST['shuffle_questions']) ? 1 : 0;
        $show_answers = isset($_POST['show_answers']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE quizzes SET title=?, description=?, category_id=?, difficulty=?, time_per_question=?, pass_score=?, shuffle_questions=?, show_answers=?, is_active=? WHERE id=?");
        $stmt->bind_param("ssissiiiii", $title, $description, $category_id, $difficulty, $time_per_question, $pass_score, $shuffle, $show_answers, $is_active, $id);
        
        if ($stmt->execute()) {
            setAlert('แก้ไขชุดข้อสอบสำเร็จ!', 'success');
        }
    } else {
        setAlert('คุณไม่มีสิทธิ์แก้ไขชุดข้อสอบนี้', 'error');
    }
    redirect('manage_quizzes.php');
}

// ดึงข้อมูลชุดข้อสอบของตัวเอง
$quizzes_query = "SELECT q.*, c.name as category_name,
                  (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
                  FROM quizzes q
                  LEFT JOIN quiz_categories c ON q.category_id = c.id
                  WHERE q.created_by = $creator_id
                  ORDER BY q.created_at DESC";
$quizzes = $conn->query($quizzes_query);

// ดึงหมวดหมู่
$categories = $conn->query("SELECT * FROM quiz_categories ORDER BY name");

// สำหรับแก้ไข
$edit_quiz = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_result = $conn->query("SELECT * FROM quizzes WHERE id = $edit_id AND created_by = $creator_id");
    if ($edit_result->num_rows > 0) {
        $edit_quiz = $edit_result->fetch_assoc();
    } else {
        setAlert('คุณไม่มีสิทธิ์แก้ไขชุดข้อสอบนี้', 'error');
        redirect('manage_quizzes.php');
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการชุดข้อสอบ - <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
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
        
        .sidebar-menu { padding: 20px 0; }
        
        .menu-item {
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .menu-item:hover, .menu-item.active { background: #34495e; }
        
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
        
        .page-title { font-size: 2em; color: #333; }
        
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
            margin-bottom: 20px;
        }
        
        .form-group { margin-bottom: 20px; }
        
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
        
        textarea { resize: vertical; min-height: 100px; }
        
        .checkbox-group {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
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
        }
        
        .btn-cancel { background: #9e9e9e; }
        
        .table { width: 100%; border-collapse: collapse; }
        
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
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge-success { background: #4CAF50; color: white; }
        .badge-warning { background: #FF9800; color: white; }
        
        .btn-group { display: flex; gap: 10px; }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9em;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-edit { background: #2196F3; color: white; }
        .btn-delete { background: #f44336; color: white; }
        
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
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>📝 Creator Panel</h2>
            <p><?php echo clean($_SESSION['full_name']); ?></p>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">📊 Dashboard</a>
            <a href="manage_quizzes.php" class="menu-item active">📝 สร้างชุดข้อสอบ</a>
            <a href="manage_questions.php" class="menu-item">❓ จัดการคำถาม</a>
            <a href="../logout.php" class="menu-item">🚪 ออกจากระบบ</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title">📝 สร้างชุดข้อสอบ</h1>
        </div>
        
        <?php showAlert(); ?>
        
        <!-- Form สร้าง/แก้ไข -->
        <div class="section">
            <h2 style="margin-bottom: 20px;"><?php echo $edit_quiz ? '✏️ แก้ไขชุดข้อสอบ' : '➕ สร้างชุดข้อสอบใหม่'; ?></h2>
            
            <form method="POST">
                <?php if ($edit_quiz): ?>
                    <input type="hidden" name="quiz_id" value="<?php echo $edit_quiz['id']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>ชื่อชุดข้อสอบ *</label>
                        <input type="text" name="title" value="<?php echo $edit_quiz['title'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>หมวดหมู่ *</label>
                        <select name="category_id" required>
                            <option value="">เลือกหมวดหมู่</option>
                            <?php 
                            $categories->data_seek(0);
                            while ($cat = $categories->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($edit_quiz && $edit_quiz['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo clean($cat['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>คำอธิบาย</label>
                    <textarea name="description"><?php echo $edit_quiz['description'] ?? ''; ?></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>ระดับความยาก</label>
                        <select name="difficulty">
                            <option value="easy" <?php echo ($edit_quiz && $edit_quiz['difficulty'] == 'easy') ? 'selected' : ''; ?>>ง่าย</option>
                            <option value="medium" <?php echo (!$edit_quiz || $edit_quiz['difficulty'] == 'medium') ? 'selected' : ''; ?>>ปานกลาง</option>
                            <option value="hard" <?php echo ($edit_quiz && $edit_quiz['difficulty'] == 'hard') ? 'selected' : ''; ?>>ยาก</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>เวลาต่อข้อ (วินาที, 0 = ไม่จำกัด)</label>
                        <input type="number" name="time_per_question" value="<?php echo $edit_quiz['time_per_question'] ?? 0; ?>" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>คะแนนผ่าน (%)</label>
                        <input type="number" name="pass_score" value="<?php echo $edit_quiz['pass_score'] ?? 60; ?>" min="0" max="100">
                    </div>
                </div>
                
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="shuffle_questions" <?php echo ($edit_quiz && $edit_quiz['shuffle_questions']) ? 'checked' : ''; ?>>
                        สุ่มลำดับคำถาม
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="show_answers" <?php echo (!$edit_quiz || $edit_quiz['show_answers']) ? 'checked' : ''; ?>>
                        แสดงเฉลย
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" <?php echo (!$edit_quiz || $edit_quiz['is_active']) ? 'checked' : ''; ?>>
                        เปิดใช้งาน
                    </label>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" name="<?php echo $edit_quiz ? 'update' : 'create'; ?>" class="btn">
                        <?php echo $edit_quiz ? '💾 บันทึกการแก้ไข' : '➕ สร้างชุดข้อสอบ'; ?>
                    </button>
                    <?php if ($edit_quiz): ?>
                        <a href="manage_quizzes.php" class="btn btn-cancel">ยกเลิก</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- รายการชุดข้อสอบ -->
        <div class="section">
            <h2 style="margin-bottom: 20px;">📚 ชุดข้อสอบของฉัน</h2>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>ชื่อชุดข้อสอบ</th>
                        <th>หมวดหมู่</th>
                        <th>ความยาก</th>
                        <th>จำนวนข้อ</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo clean($quiz['title']); ?></strong></td>
                            <td><?php echo clean($quiz['category_name']); ?></td>
                            <td>
                                <span class="badge" style="background-color: <?php echo getDifficultyColor($quiz['difficulty']); ?>; color: white;">
                                    <?php echo getDifficultyText($quiz['difficulty']); ?>
                                </span>
                            </td>
                            <td><?php echo $quiz['question_count']; ?> ข้อ</td>
                            <td>
                                <span class="badge <?php echo $quiz['is_active'] ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo $quiz['is_active'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="manage_questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn-sm btn-edit">จัดการคำถาม</a>
                                    <a href="?edit=<?php echo $quiz['id']; ?>" class="btn-sm btn-edit">แก้ไข</a>
                                    <a href="?delete=<?php echo $quiz['id']; ?>" class="btn-sm btn-delete" onclick="return confirm('ต้องการลบชุดข้อสอบนี้?')">ลบ</a>
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