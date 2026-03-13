<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');
require_once('../utils/logger.php');

$student_id = $_GET['id'] ?? '';
if (!$student_id) { die("No student ID provided."); }

// Fetch student info
$stmt = $pdo->prepare("SELECT s.*, u.name, u.email, u.phone 
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       WHERE s.roll_no = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) { die("Student not found."); }

// Handle Remarks Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remarks'])) {
    $remarks = filter_var($_POST['remarks'], FILTER_SANITIZE_STRING);
    try {
        $pdo->query("ALTER TABLE students ADD COLUMN IF NOT EXISTS remarks TEXT");
        $update = $pdo->prepare("UPDATE students SET remarks = ? WHERE roll_no = ?");
        $update->execute([$remarks, $student_id]);
        logToExcel('Remarks Update', $_SESSION['user_id'], $_SESSION['user_name'], "Added remarks for $student_id");
        $success = "Remarks updated successfully!";
        $student['remarks'] = $remarks;
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile: <?php echo $student['name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-container { display: flex; gap: 40px; }
        .profile-left { width: 350px; flex-shrink: 0; }
        .profile-right { flex: 1; }
        
        .avatar-frame {
            width: 120px; height: 120px;
            background: #fff;
            padding: 5px;
            border-radius: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
    </style>
</head>
<body style="background: #f8fafc;">
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <div class="content-area">
                
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <h2 class="fw-800 mb-0">Student Insights</h2>
                    <div class="d-flex gap-3 align-items-center">
                        <button class="btn btn-outline-primary border-0 shadow-sm" style="border-radius:12px;"> <i class="fas fa-phone"></i> </button>
                        <button class="btn btn-primary px-4 py-2 shadow-sm fw-bold" style="border-radius:12px;">Message Student</button>
                    </div>
                </div>

                <div class="profile-container">
                    <!-- LEFT COLUMN -->
                    <div class="profile-left">
                        <div class="card border-0 shadow-sm p-4 text-center" style="border-radius: 30px;">
                            <div class="avatar-frame mx-auto">
                                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $student['name']; ?>" width="100%" class="rounded-3">
                            </div>
                            <h4 class="fw-800 mb-1"><?php echo $student['name']; ?></h4>
                            <p class="text-muted small">ID: <?php echo $student['roll_no']; ?></p>
                            
                            <hr class="my-4" style="opacity: 0.05">
                            
                            <div class="text-start mb-4">
                                <div class="mb-3">
                                    <label class="smallest fw-bold text-muted uppercase">Phone</label>
                                    <div class="fw-bold">+88 01632534125</div>
                                </div>
                                <div class="mb-3">
                                    <label class="smallest fw-bold text-muted uppercase">Email</label>
                                    <div class="fw-bold"><?php echo $student['email']; ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="smallest fw-bold text-muted uppercase">Address</label>
                                    <div class="fw-bold">245 Delo Street, NY</div>
                                </div>
                            </div>
                            
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="bg-primary bg-opacity-10 p-3 rounded-4 text-center">
                                        <b class="d-block text-primary">25 Days</b>
                                        <span class="smallest text-muted">Attendance</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-danger bg-opacity-10 p-3 rounded-4 text-center">
                                        <b class="d-block text-danger">2 Days</b>
                                        <span class="smallest text-muted">Absent</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="profile-right">
                        <div class="card border-0 shadow-sm p-5 mb-4" style="border-radius: 30px;">
                            <h5 class="fw-800 mb-4">Grades & Assignments Section</h5>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr class="text-muted smallest uppercase">
                                            <th>Subject</th><th>Last Grade</th><th>Avg Grade</th><th>Improvement</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold">Mathematics</td><td>A</td><td>B+</td><td class="text-success fw-bold">Improved</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">English</td><td>B+</td><td>B</td><td class="text-danger fw-bold">Stable</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm p-5" style="border-radius: 30px;">
                            <h5 class="fw-800 mb-4">Academic Performance & Remarks</h5>
                            <form method="POST">
                                <textarea name="remarks" class="form-control border-0 bg-light py-3 px-4 mb-4" rows="4" style="border-radius: 20px;" placeholder="Add private faculty remarks..."><?php echo $student['remarks'] ?? ''; ?></textarea>
                                <button type="submit" class="btn btn-primary px-5 py-3 fw-bold shadow-sm" style="border-radius: 16px;">Save Performance Update</button>
                            </form>
                            <?php if(isset($success)): ?>
                                <div class="text-success fw-bold mt-3 small"><i class="fas fa-check-circle me-1"></i> <?php echo $success; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
