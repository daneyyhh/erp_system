<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');
require_once('../utils/logger.php');

$page_title = "Examinations | Scholarly";

// Handle Marks Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_marks'])) {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $internal = $_POST['internal'];
    $external = $_POST['external'];
    $total = $internal + $external;
    
    // Grade logic
    if ($total >= 80) $grade = 'A';
    elseif ($total >= 60) $grade = 'B';
    elseif ($total >= 40) $grade = 'C';
    else $grade = 'F';

    try {
        $stmt = $pdo->prepare("INSERT INTO marks (student_id, subject_id, internal, external, total, grade) 
                               VALUES (?, ?, ?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE internal=?, external=?, total=?, grade=?");
        $stmt->execute([$student_id, $subject_id, $internal, $external, $total, $grade, $internal, $external, $total, $grade]);
        logToExcel('Marks Posted', $_SESSION['user_id'], $_SESSION['user_name'], "Updated Result for Student ID: $student_id");
        $success = "Examination results successfully committed to ledger!";
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
}

$students = $pdo->query("SELECT s.id, u.name, s.roll_no FROM students s JOIN users u ON s.user_id = u.id")->fetchAll();
$subjects = $pdo->query("SELECT * FROM subjects")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: #f8fafc;">
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <h2 class="fw-800 mb-2">Examination Bureau</h2>
                <p class="text-muted mb-5">Post academic results and calculate semester grades.</p>

                <?php if(isset($success)): ?>
                    <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius:12px;"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm p-5" style="border-radius: 30px;">
                    <form method="POST">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="smallest fw-800 text-muted uppercase mb-2">Select Student</label>
                                <select name="student_id" class="form-select border-0 bg-light py-3" style="border-radius:15px;" required>
                                    <?php foreach($students as $s): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?> (<?php echo $s['roll_no']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="smallest fw-800 text-muted uppercase mb-2">Subject</label>
                                <select name="subject_id" class="form-select border-0 bg-light py-3" style="border-radius:15px;" required>
                                    <?php foreach($subjects as $sub): ?>
                                        <option value="<?php echo $sub['id']; ?>"><?php echo $sub['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="smallest fw-800 text-muted uppercase mb-2">Internal (Max 40)</label>
                                <input type="number" name="internal" class="form-control border-0 bg-light py-3" style="border-radius:15px;" max="40" required>
                            </div>
                            <div class="col-md-6">
                                <label class="smallest fw-800 text-muted uppercase mb-2">External (Max 60)</label>
                                <input type="number" name="external" class="form-control border-0 bg-light py-3" style="border-radius:15px;" max="60" required>
                            </div>
                        </div>
                        <div class="mt-5">
                            <button type="submit" name="submit_marks" class="btn btn-primary w-100 py-3 fw-bold shadow-sm" style="border-radius:15px;">
                                COMMIT MARKS & UPDATE LEDGER <i class="fas fa-check-circle ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
