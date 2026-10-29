<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Take Exam | Enlight";

$exam_id = $_GET['exam_id'] ?? null;

if (!$exam_id) {
    header("Location: exams.php");
    exit();
}

$stmt = $pdo->prepare("SELECT s.id FROM students s WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student_id = $stmt->fetchColumn();

$exam = $pdo->prepare("SELECT e.*, s.name as subject_name FROM exams e LEFT JOIN subjects s ON e.subject_id = s.id WHERE e.id = ? AND e.status = 'active'");
$exam->execute([$exam_id]);
$exam = $exam->fetch();

if (!$exam) {
    header("Location: exams.php");
    exit();
}

$now = date('Y-m-d H:i:s');
if ($exam['end_time'] && strtotime($exam['end_time']) < strtotime($now)) {
    echo "<script>alert('Exam has ended'); window.location='exams.php';</script>";
    exit();
}

$existing_attempt = $pdo->prepare("SELECT * FROM exam_attempts WHERE exam_id = ? AND student_id = ? ORDER BY id DESC LIMIT 1");
$existing_attempt->execute([$exam_id, $student_id]);
$attempt = $existing_attempt->fetch();

if (!$attempt) {
    $stmt = $pdo->prepare("INSERT INTO exam_attempts (exam_id, student_id, status) VALUES (?, ?, 'in_progress')");
    $stmt->execute([$exam_id, $student_id]);
    $attempt_id = $pdo->lastInsertId();
    $attempt = ['id' => $attempt_id, 'status' => 'in_progress', 'start_time' => date('Y-m-d H:i:s')];
} else {
    $attempt_id = $attempt['id'];
}

$questions = $pdo->prepare("SELECT * FROM exam_questions WHERE exam_id = ?");
$questions->execute([$exam_id]);
$questions = $questions->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
    $score = 0;
    $total_marks = 0;
    
    $pdo->prepare("DELETE FROM exam_answers WHERE attempt_id = ?")->execute([$attempt_id]);
    
    $answer_stmt = $pdo->prepare("INSERT INTO exam_answers (attempt_id, question_id, answer, is_correct, marks_obtained) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($questions as $q) {
        $total_marks += $q['marks'];
        $answer = $_POST['answer'][$q['id']] ?? '';
        $is_correct = false;
        $marks_obtained = 0;
        
        if ($answer) {
            if (strcasecmp(trim($answer), trim($q['correct_answer'])) === 0) {
                $is_correct = true;
                $marks_obtained = $q['marks'];
                $score += $q['marks'];
            }
        }
        
        $answer_stmt->execute([$attempt_id, $q['id'], $answer, $is_correct, $marks_obtained]);
    }
    
    $pdo->prepare("UPDATE exam_attempts SET score = ?, status = 'completed', end_time = NOW() WHERE id = ?")
        ->execute([$score, $attempt_id]);
    
    $percent = $total_marks > 0 ? round(($score / $total_marks) * 100, 1) : 0;
    header("Location: exam_result.php?attempt_id=$attempt_id&score=$score&total=$total_marks&percent=$percent");
    exit();
}

if (isset($_POST['save_progress'])) {
    foreach ($questions as $q) {
        $answer = $_POST['answer'][$q['id']] ?? '';
        $existing = $pdo->prepare("SELECT id FROM exam_answers WHERE attempt_id = ? AND question_id = ?");
        $existing->execute([$attempt_id, $q['id']]);
        
        if ($existing->fetch()) {
            $pdo->prepare("UPDATE exam_answers SET answer = ? WHERE attempt_id = ? AND question_id = ?")
                ->execute([$answer, $attempt_id, $q['id']]);
        } else {
            $pdo->prepare("INSERT INTO exam_answers (attempt_id, question_id, answer) VALUES (?, ?, ?)")
                ->execute([$attempt_id, $q['id'], $answer]);
        }
    }
    $saved = true;
}

$saved_answers = [];
$existing_answers = $pdo->prepare("SELECT question_id, answer FROM exam_answers WHERE attempt_id = ?");
$existing_answers->execute([$attempt_id]);
foreach ($existing_answers->fetchAll() as $a) {
    $saved_answers[$a['question_id']] = $a['answer'];
}

$start_time = strtotime($attempt['start_time']);
$duration_seconds = $exam['duration_minutes'] * 60;
$end_time = $start_time + $duration_seconds;
$remaining = $end_time - time();
if ($remaining < 0) $remaining = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .question-nav { position: sticky; top: 20px; z-index: 100; }
        .q-num { width: 40px; height: 40px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin: 3px; font-weight: bold; cursor: pointer; border: 2px solid #e2e8f0; background: white; transition: all 0.2s; }
        .q-num.answered { background: #10b981; color: white; border-color: #10b981; }
        .q-num.current { border-color: #8e24aa; background: #8e24aa; color: white; }
    </style>
</head>
<body style="background: #f8fafc;">
    <div class="sticky-top bg-white border-bottom shadow-sm py-2 px-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-800 mb-0"><?php echo htmlspecialchars($exam['title']); ?></h5>
            <small class="text-muted"><?php echo $exam['subject_name'] ?? ''; ?> | <?php echo count($questions); ?> Questions</small>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <small class="text-muted d-block">Time Remaining</small>
                <span class="fw-bold fs-4 text-danger" id="timer"><?php echo floor($remaining / 60); ?>:<?php echo str_pad($remaining % 60, 2, '0'); ?></span>
            </div>
            <button type="button" class="btn btn-success fw-bold rounded-4 px-4" data-bs-toggle="modal" data-bs-target="#submitModal">
                <i class="fas fa-check me-2"></i> Submit
            </button>
        </div>
    </div>

    <div class="container py-4">
        <?php if(isset($saved)): ?>
            <div class="alert alert-info border-0 rounded-4 mb-4">
                <i class="fas fa-save me-2"></i> Progress saved! You can continue or submit when ready.
            </div>
        <?php endif; ?>

        <form method="POST" id="examForm">
            <div class="row g-4">
                <div class="col-lg-9">
                    <?php if(empty($questions)): ?>
                        <div class="alert alert-warning">No questions in this exam yet.</div>
                    <?php else: ?>
                        <?php foreach($questions as $index => $q): 
                            $options = json_decode($q['options'], true) ?? [];
                        ?>
                        <div class="card border-0 shadow-sm mb-4 question-card" id="q<?php echo $q['id']; ?>" data-qid="<?php echo $q['id']; ?>">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2 rounded-3">
                                        Question <?php echo $index + 1; ?>
                                    </span>
                                    <span class="badge bg-warning bg-opacity-10 text-warning fw-bold px-3 py-2 rounded-3">
                                        <?php echo $q['marks']; ?> Mark<?php echo $q['marks'] > 1 ? 's' : ''; ?>
                                    </span>
                                </div>
                                <h5 class="fw-800 mb-4"><?php echo htmlspecialchars($q['question_text']); ?></h5>
                                
                                <?php if($q['question_type'] === 'multiple_choice'): ?>
                                    <?php foreach($options as $i => $opt): 
                                        $opt_letter = chr(65 + $i);
                                    ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="answer[<?php echo $q['id']; ?>]" 
                                               id="q<?php echo $q['id']; ?>_opt<?php echo $i; ?>" 
                                               value="<?php echo $opt_letter; ?>"
                                               <?php echo ($saved_answers[$q['id']] ?? '') === $opt_letter ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="q<?php echo $q['id']; ?>_opt<?php echo $i; ?>">
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold me-2"><?php echo $opt_letter; ?></span>
                                            <?php echo htmlspecialchars($opt); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                <?php elseif($q['question_type'] === 'true_false'): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="answer[<?php echo $q['id']; ?>]" 
                                               id="q<?php echo $q['id']; ?>_true" value="True"
                                               <?php echo ($saved_answers[$q['id']] ?? '') === 'True' ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="q<?php echo $q['id']; ?>_true">
                                            <span class="badge bg-success bg-opacity-10 text-success me-2"><i class="fas fa-check"></i></span> True
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="answer[<?php echo $q['id']; ?>]" 
                                               id="q<?php echo $q['id']; ?>_false" value="False"
                                               <?php echo ($saved_answers[$q['id']] ?? '') === 'False' ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="q<?php echo $q['id']; ?>_false">
                                            <span class="badge bg-danger bg-opacity-10 text-danger me-2"><i class="fas fa-times"></i></span> False
                                        </label>
                                    </div>
                                    
                                <?php else: ?>
                                    <textarea name="answer[<?php echo $q['id']; ?>]" class="form-control" rows="3" placeholder="Type your answer here..."><?php echo $saved_answers[$q['id']] ?? ''; ?></textarea>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-3">
                    <div class="card border-0 shadow-sm question-nav">
                        <div class="card-body">
                            <h6 class="fw-800 mb-3">Question Navigator</h6>
                            <div class="d-flex flex-wrap">
                                <?php foreach($questions as $index => $q): ?>
                                <a href="#q<?php echo $q['id']; ?>" class="q-num <?php echo isset($saved_answers[$q['id']]) ? 'answered' : ''; ?>" data-qid="<?php echo $q['id']; ?>">
                                    <?php echo $index + 1; ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3 small">
                                <span class="q-num answered" style="width:20px;height:20px;margin-right:5px;"></span> Answered
                            </div>
                            <hr>
                            <button type="submit" name="save_progress" class="btn btn-outline-primary w-100 rounded-3">
                                <i class="fas fa-save me-1"></i> Save Progress
                            </button>
                        </div>
                    </div>
                </div>
                <input type="submit" name="submit_exam" id="submitInput" class="d-none">
            </div>
        </form>
    </div>

    <div class="modal fade" id="submitModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-800">Submit Exam?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to submit your exam? This action cannot be undone.</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> 
                        Make sure you have answered all questions before submitting.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-4" data-bs-dismiss="modal">Review Answers</button>
                    <button type="button" class="btn btn-success rounded-4 fw-800" onclick="document.getElementById('submitInput').click();">
                        <i class="fas fa-check me-2"></i> Submit Exam
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let remainingTime = <?php echo $remaining; ?>;
        const timerEl = document.getElementById('timer');
        
        const timer = setInterval(() => {
            remainingTime--;
            if (remainingTime <= 0) {
                clearInterval(timer);
                document.getElementById('examForm').submit();
                return;
            }
            const mins = Math.floor(remainingTime / 60);
            const secs = remainingTime % 60;
            timerEl.textContent = mins + ':' + (secs < 10 ? '0' : '') + secs;
            if (remainingTime <= 300) timerEl.classList.add('blink');
        }, 1000);

        document.querySelectorAll('input[type="radio"], textarea').forEach(el => {
            el.addEventListener('change', function() {
                const qid = this.name.match(/\[(\d+)\]/)[1];
                document.querySelector(`.q-num[data-qid="${qid}"]`).classList.add('answered');
            });
        });
    </script>
    <style>
        .blink { animation: blinker 1s linear infinite; }
        @keyframes blinker { 50% { opacity: 0.5; } }
    </style>
</body>
</html>
