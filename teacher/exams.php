<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Online Exams | Project ERP";

$pdo->exec("CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    subject_id INT,
    class VARCHAR(50),
    duration_minutes INT DEFAULT 30,
    start_time DATETIME,
    end_time DATETIME,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('draft', 'active', 'closed') DEFAULT 'draft',
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS exam_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'short_answer') DEFAULT 'multiple_choice',
    options JSON,
    correct_answer TEXT NOT NULL,
    marks INT DEFAULT 1,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS exam_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    score DECIMAL(5,2) DEFAULT 0,
    status ENUM('in_progress', 'completed') DEFAULT 'in_progress',
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS exam_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    answer TEXT,
    is_correct BOOLEAN DEFAULT FALSE,
    marks_obtained INT DEFAULT 0,
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES exam_questions(id) ON DELETE CASCADE
)");

$subjects = $pdo->query("SELECT * FROM subjects")->fetchAll();
$classes = ["BCA 1st Year", "BCA 2nd Year", "BCA 3rd Year", "MCA 1st Year", "MCA 2nd Year"];

$exams = $pdo->prepare("SELECT e.*, s.name as subject_name, u.name as created_by_name 
                        FROM exams e 
                        LEFT JOIN subjects s ON e.subject_id = s.id 
                        LEFT JOIN users u ON e.created_by = u.id
                        ORDER BY e.created_at DESC");
$exams->execute();
$exam_list = $exams->fetchAll();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_exam'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $subject_id = $_POST['subject_id'];
    $class = $_POST['class'];
    $duration = (int)$_POST['duration_minutes'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    $stmt = $pdo->prepare("INSERT INTO exams (title, description, subject_id, class, duration_minutes, start_time, end_time, created_by, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$title, $description, $subject_id, $class, $duration, $start_time, $end_time, $_SESSION['user_id']]);
    $exam_id = $pdo->lastInsertId();
    
    if (!empty($_POST['questions'])) {
        $q_stmt = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_text, question_type, options, correct_answer, marks) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($_POST['questions'] as $q) {
            $options = json_encode($q['options']);
            $q_stmt->execute([$exam_id, $q['text'], $q['type'], $options, $q['correct'], (int)$q['marks']]);
        }
    }
    
    $success = "Exam created successfully!";
}

if (isset($_GET['delete_exam'])) {
    $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ? AND created_by = ?");
    $stmt->execute([$_GET['delete_exam'], $_SESSION['user_id']]);
    header("Location: exams.php");
    exit();
}

if (isset($_GET['toggle_status'])) {
    $exam_id = $_GET['toggle_status'];
    $stmt = $pdo->prepare("SELECT status FROM exams WHERE id = ? AND created_by = ?");
    $stmt->execute([$exam_id, $_SESSION['user_id']]);
    $current_status = $stmt->fetchColumn();
    
    $new_status = ($current_status === 'active') ? 'closed' : 'active';
    $stmt = $pdo->prepare("UPDATE exams SET status = ? WHERE id = ? AND created_by = ?");
    $stmt->execute([$new_status, $exam_id, $_SESSION['user_id']]);
    header("Location: exams.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Online Examinations</h2>
                        <p class="text-muted small fw-600">Create and manage online exams for your students</p>
                    </div>
                    <button class="btn btn-premium px-4 py-2 rounded-4 fw-800" data-bs-toggle="modal" data-bs-target="#createExamModal">
                        <i class="fas fa-plus me-2"></i> Create Exam
                    </button>
                </div>

                <?php if($success): ?>
                    <div class="alert alert-success border-0 rounded-4 mb-4 shadow-sm animate-up">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert alert-danger border-0 rounded-4 mb-4 shadow-sm animate-up">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <?php if(empty($exam_list)): ?>
                        <div class="col-12">
                            <div class="card-premium text-center py-5 animate-up">
                                <i class="fas fa-file-signature fa-4x text-muted mb-3"></i>
                                <h4 class="fw-800">No Exams Created Yet</h4>
                                <p class="text-muted">Create your first online exam to get started</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach($exam_list as $exam): ?>
                        <div class="col-lg-6">
                            <div class="card-premium animate-up h-100">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <span class="badge <?php echo $exam['status'] === 'active' ? 'bg-success' : ($exam['status'] === 'closed' ? 'bg-danger' : 'bg-warning'); ?> bg-opacity-10 fw-bold px-3 py-2 rounded-3">
                                            <?php echo strtoupper($exam['status']); ?>
                                        </span>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v fs-5"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="?toggle_status=<?php echo $exam['id']; ?>">
                                                <i class="fas fa-toggle-on me-2"></i> <?php echo $exam['status'] === 'active' ? 'Close' : 'Activate'; ?>
                                            </a></li>
                                            <li><a class="dropdown-item" href="exam_results.php?exam_id=<?php echo $exam['id']; ?>">
                                                <i class="fas fa-chart-bar me-2"></i> View Results
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="?delete_exam=<?php echo $exam['id']; ?>" onclick="return confirm('Delete this exam?')">
                                                <i class="fas fa-trash me-2"></i> Delete
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                                <h4 class="fw-800 mb-2"><?php echo htmlspecialchars($exam['title']); ?></h4>
                                <p class="text-muted small mb-3"><?php echo htmlspecialchars($exam['description']); ?></p>
                                <div class="d-flex flex-wrap gap-3 mb-3">
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2 rounded-3">
                                        <i class="fas fa-book me-1"></i> <?php echo $exam['subject_name'] ?? 'General'; ?>
                                    </span>
                                    <span class="badge bg-info bg-opacity-10 text-info fw-bold px-3 py-2 rounded-3">
                                        <i class="fas fa-users me-1"></i> <?php echo $exam['class']; ?>
                                    </span>
                                    <span class="badge bg-warning bg-opacity-10 text-warning fw-bold px-3 py-2 rounded-3">
                                        <i class="fas fa-clock me-1"></i> <?php echo $exam['duration_minutes']; ?> min
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i> 
                                        <?php echo $exam['start_time'] ? date('M d, H:i', strtotime($exam['start_time'])) : 'Not scheduled'; ?>
                                        - <?php echo $exam['end_time'] ? date('M d, H:i', strtotime($exam['end_time'])) : 'Open'; ?>
                                    </small>
                                    <a href="exam_results.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-outline-primary rounded-3 fw-bold">
                                        <i class="fas fa-eye me-1"></i> Results
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createExamModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-800">Create New Exam</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="examForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Exam Title</label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g., Database Systems - Mid Term">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Instructions for students..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Subject</label>
                                <select name="subject_id" class="form-select" required>
                                    <option value="">Select Subject</option>
                                    <?php foreach($subjects as $s): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Class</label>
                                <select name="class" class="form-select" required>
                                    <option value="">Select Class</option>
                                    <?php foreach($classes as $c): ?>
                                        <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Duration (minutes)</label>
                                <input type="number" name="duration_minutes" class="form-control" value="30" min="5" max="180">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Start Time</label>
                                <input type="datetime-local" name="start_time" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">End Time</label>
                                <input type="datetime-local" name="end_time" class="form-control" required>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-800 mb-0">Questions</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-3" onclick="addQuestion()">
                                <i class="fas fa-plus me-1"></i> Add Question
                            </button>
                        </div>
                        
                        <div id="questionsContainer">
                            <div class="question-item border rounded-4 p-3 mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-bold">Question 1</span>
                                    <button type="button" class="btn btn-sm text-danger" onclick="this.closest('.question-item').remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <textarea name="questions[0][text]" class="form-control mb-2" rows="2" placeholder="Enter question..." required></textarea>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <select name="questions[0][type]" class="form-select form-select-sm" onchange="toggleOptions(this)">
                                            <option value="multiple_choice">Multiple Choice</option>
                                            <option value="true_false">True/False</option>
                                            <option value="short_answer">Short Answer</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" name="questions[0][marks]" class="form-control form-select-sm" value="1" min="1" placeholder="Marks">
                                    </div>
                                </div>
                                <div class="options-container mt-2">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text">A</span>
                                        <input type="text" name="questions[0][options][]" class="form-control" placeholder="Option A">
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text">B</span>
                                        <input type="text" name="questions[0][options][]" class="form-control" placeholder="Option B">
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text">C</span>
                                        <input type="text" name="questions[0][options][]" class="form-control" placeholder="Option C">
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text">D</span>
                                        <input type="text" name="questions[0][options][]" class="form-control" placeholder="Option D">
                                    </div>
                                    <select name="questions[0][correct]" class="form-select form-select-sm">
                                        <option value="">Select Correct Answer</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary rounded-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_exam" class="btn btn-premium rounded-4 fw-800">
                            <i class="fas fa-check me-2"></i> Create Exam
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let questionCount = 1;
        
        function addQuestion() {
            const container = document.getElementById('questionsContainer');
            const html = `
                <div class="question-item border rounded-4 p-3 mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold">Question ${++questionCount}</span>
                        <button type="button" class="btn btn-sm text-danger" onclick="this.closest('.question-item').remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <textarea name="questions[${questionCount-1}][text]" class="form-control mb-2" rows="2" placeholder="Enter question..." required></textarea>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <select name="questions[${questionCount-1}][type]" class="form-select form-select-sm" onchange="toggleOptions(this)">
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                                <option value="short_answer">Short Answer</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" name="questions[${questionCount-1}][marks]" class="form-control form-select-sm" value="1" min="1" placeholder="Marks">
                        </div>
                    </div>
                    <div class="options-container mt-2">
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text">A</span>
                            <input type="text" name="questions[${questionCount-1}][options][]" class="form-control" placeholder="Option A">
                        </div>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text">B</span>
                            <input type="text" name="questions[${questionCount-1}][options][]" class="form-control" placeholder="Option B">
                        </div>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text">C</span>
                            <input type="text" name="questions[${questionCount-1}][options][]" class="form-control" placeholder="Option C">
                        </div>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text">D</span>
                            <input type="text" name="questions[${questionCount-1}][options][]" class="form-control" placeholder="Option D">
                        </div>
                        <select name="questions[${questionCount-1}][correct]" class="form-select form-select-sm">
                            <option value="">Select Correct Answer</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }
        
        function toggleOptions(select) {
            const container = select.closest('.question-item').querySelector('.options-container');
            if (select.value === 'true_false') {
                container.innerHTML = `
                    <select name="questions[${questionCount-1}][correct]" class="form-select form-select-sm">
                        <option value="">Select Correct Answer</option>
                        <option value="True">True</option>
                        <option value="False">False</option>
                    </select>
                `;
            } else if (select.value === 'short_answer') {
                container.innerHTML = `
                    <input type="text" name="questions[${questionCount-1}][correct]" class="form-control form-select-sm" placeholder="Expected answer (for auto-grading)">
                `;
            } else {
                container.innerHTML = `
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text">A</span>
                        <input type="text" name="questions[${questionCount-1}][options][]" class="form-control" placeholder="Option A">
                    </div>
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text">B</span>
                        <input type="text" name="questions[${questionCount-1}][options][]" class="form-control" placeholder="Option B">
                    </div>
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text">C</span>
                        <input type="text" name="questions[${questionCount-1}][options][]" class="form-control" placeholder="Option C">
                    </div>
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text">D</span>
                        <input type="text" name="questions[${questionCount-1}][options][]" class="form-control" placeholder="Option D">
                    </div>
                    <select name="questions[${questionCount-1}][correct]" class="form-select form-select-sm">
                        <option value="">Select Correct Answer</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                `;
            }
        }
    </script>
</body>
</html>
