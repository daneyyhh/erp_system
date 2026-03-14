<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');
require_once('../utils/logger.php');

$page_title = "Post Academic Marks";

// Fetch classes and subjects
$classes = ["BCA 1st Year", "BCA 2nd Year", "BCA 3rd Year"];
$subjects = $pdo->query("SELECT * FROM subjects")->fetchAll();

$selected_class = $_GET['class'] ?? '';
$selected_subject = $_GET['subject_id'] ?? '';
$students = [];

if ($selected_class && $selected_subject) {
    $stmt = $pdo->prepare("SELECT s.id as student_id, u.name, s.roll_no, m.internal, m.external 
                           FROM students s 
                           JOIN users u ON s.user_id = u.id 
                           LEFT JOIN marks m ON s.id = m.student_id AND m.subject_id = ?
                           WHERE s.class = ?");
    $stmt->execute([$selected_subject, $selected_class]);
    $students = $stmt->fetchAll();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO marks (student_id, subject_id, internal, external, grade) 
                               VALUES (?, ?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE internal = VALUES(internal), external = VALUES(external)");
        
        foreach ($_POST['marks'] as $sid => $data) {
            $internal = (int)$data['internal'];
            $external = (int)$data['external'];
            $total = $internal + $external;
            $grade = ($total >= 90) ? 'A+' : (($total >= 80) ? 'A' : (($total >= 60) ? 'B' : 'C'));
            $stmt->execute([$sid, $selected_subject, $internal, $external, $grade]);
        }
        $pdo->commit();
        logToExcel('Marks Posted', $_SESSION['user_id'], $_SESSION['user_name'], "Posted marks for $selected_class");
        $success = "Marks updated and logged successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <h2 class="text-white fw-bold mb-4">Post Student Marks</h2>
                
                <div class="card glass mb-4">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <select name="class" class="form-select" required>
                                <option value="">Select Class...</option>
                                <?php foreach($classes as $c) echo "<option value='$c' " . ($selected_class==$c?'selected':'') . ">$c</option>"; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <select name="subject_id" class="form-select" required>
                                <option value="">Select Subject...</option>
                                <?php foreach($subjects as $s) echo "<option value='".$s['id']."' " . ($selected_subject==$s['id']?'selected':'') . ">".$s['name']."</option>"; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Load</button>
                        </div>
                    </form>
                </div>

                <?php if ($selected_class && $selected_subject): ?>
                <form method="POST">
                    <div class="card glass">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Roll No</th>
                                        <th>Student</th>
                                        <th>Internal (Max 40)</th>
                                        <th>External (Max 60)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($students as $s): ?>
                                    <tr>
                                        <td><code><?php echo $s['roll_no']; ?></code></td>
                                        <td class="fw-bold"><?php echo $s['name']; ?></td>
                                        <td><input type="number" name="marks[<?php echo $s['student_id']; ?>][internal]" class="form-control form-control-sm" value="<?php echo $s['internal']; ?>" max="40"></td>
                                        <td><input type="number" name="marks[<?php echo $s['student_id']; ?>][external]" class="form-control form-control-sm" value="<?php echo $s['external']; ?>" max="60"></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary px-5">Publish Marks & Log to Excel</button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
