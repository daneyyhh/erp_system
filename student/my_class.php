<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "My Class | Enlight";

// Fetch classmates
$stmt = $pdo->prepare("SELECT u.name, s.roll_no, s.class 
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       WHERE s.class = (SELECT class FROM students WHERE user_id = ?)
                       ORDER BY u.name ASC");
$stmt->execute([$_SESSION['user_id']]);
$classmates = $stmt->fetchAll();
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
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">My Class</h2>
                        <p class="text-muted small fw-600">Overview of your academic batch and classmates</p>
                    </div>
                </div>

                <div class="card-premium animate-up">
                    <h5 class="fw-800 mb-4 px-2">Batch Strength: <?php echo count($classmates); ?> Students</h5>
                    <div class="table-responsive">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                    <th>Class Section</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($classmates as $c): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?php echo $c['roll_no']; ?></td>
                                    <td class="fw-800"><?php echo $c['name']; ?></td>
                                    <td><span class="badge-category cat-user-login"><?php echo $c['class']; ?></span></td>
                                    <td class="text-end"><span class="badge bg-success bg-opacity-10 text-success fw-bold p-2 small">Active</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
