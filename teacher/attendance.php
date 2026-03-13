<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Class Selection | Scholarly";
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
            <div class="content-area" style="max-width: 900px; margin: 0 auto;">
                <h2 class="fw-800 mb-2">Initialize Attendance</h2>
                <p class="text-muted mb-5">Select the cohort and session you wish to mark.</p>

                <div class="card border-0 shadow-sm p-5" style="border-radius: 30px;">
                    <form action="mark_attendance.php" method="GET">
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="small fw-800 text-muted mb-2 uppercase">SELECT YEAR</label>
                                <select name="class" class="form-select py-3 border-0 bg-light" style="border-radius: 15px;">
                                    <option>BCA 1st Year</option>
                                    <option>BCA 2nd Year</option>
                                    <option>BCA 3rd Year</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="small fw-800 text-muted mb-2 uppercase">SELECT SUBJECT</label>
                                <select name="subject_id" class="form-select py-3 border-0 bg-light" style="border-radius: 15px;">
                                    <option value="1">Basics of User Research</option>
                                    <option value="2">Advanced Data Structures</option>
                                    <option value="3">Software Architecture</option>
                                </select>
                            </div>
                            <div class="col-md-12 mt-5">
                                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm" style="border-radius: 15px;">
                                    Open Attendance Ledger <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
