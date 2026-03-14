<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "My Students | Scholarly";

// Filtering
$year_filter = $_GET['year'] ?? '';
$sql = "SELECT s.roll_no as campus_id, u.name, u.email, s.class, s.semester 
        FROM students s 
        JOIN users u ON s.user_id = u.id";

if ($year_filter) {
    $sql .= " WHERE s.class = " . $pdo->quote($year_filter);
}

$students = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
</head>
<body style="background: #fdfdfd;">
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-800 mb-1">My Students</h2>
                        <p class="text-muted">Manage performance and remarks for your classes</p>
                    </div>
                    <select onchange="window.location.href='?year='+this.value" class="form-select border-0 shadow-sm" style="width: 200px; border-radius: 12px;">
                        <option value="">All Taught Classes</option>
                        <option value="BCA 1st Year" <?php echo $year_filter == 'BCA 1st Year' ? 'selected' : ''; ?>>BCA 1st Year</option>
                        <option value="BCA 2nd Year" <?php echo $year_filter == 'BCA 2nd Year' ? 'selected' : ''; ?>>BCA 2nd Year</option>
                        <option value="BCA 3rd Year" <?php echo $year_filter == 'BCA 3rd Year' ? 'selected' : ''; ?>>BCA 3rd Year</option>
                    </select>
                </div>

                <div class="card border-0 shadow-sm" style="border-radius: 30px;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Campus ID</th>
                                    <th>Student Name</th>
                                    <th>Course & Year</th>
                                    <th class="text-end">Academic Records</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $s): ?>
                                <tr>
                                    <td><code class="fw-800 text-primary"><?php echo $s['campus_id']; ?></code></td>
                                    <td><span class="fw-bold"><?php echo $s['name']; ?></span></td>
                                    <td><span class="badge-soft badge-soft-primary"><?php echo $s['class']; ?></span></td>
                                    <td class="text-end">
                                        <a href="../admin/student_profile.php?id=<?php echo $s['campus_id']; ?>" class="btn btn-primary btn-sm px-4">
                                            <i class="fas fa-edit me-2"></i> Add Remarks/View
                                        </a>
                                    </td>
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
