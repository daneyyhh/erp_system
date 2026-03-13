<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Admission Management";

// Mock Applications
$applications = [
    ['id' => 1, 'name' => 'Rahul Sharma', 'course' => 'BCA', 'date' => '2026-03-12', 'status' => 'Under Review'],
    ['id' => 2, 'name' => 'Priya Patel', 'course' => 'BSc CS', 'date' => '2026-03-11', 'status' => 'Applied'],
    ['id' => 3, 'name' => 'Amit Verma', 'course' => 'BCom', 'date' => '2026-03-10', 'status' => 'Admitted'],
    ['id' => 4, 'name' => 'Sneha Gupta', 'course' => 'BBA', 'date' => '2026-03-09', 'status' => 'Rejected'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | ERP Lite</title>
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
                <div class="row g-4 mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0">Online Applications</h5>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-dark border border-secondary"><i class="fas fa-filter me-2"></i> Filter</button>
                                    <button class="btn btn-sm btn-primary"><i class="fas fa-plus me-2"></i> New Application</button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover text-white">
                                    <thead class="text-muted">
                                        <tr>
                                            <th>App ID</th>
                                            <th>Student Name</th>
                                            <th>Desired Course</th>
                                            <th>Applied Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applications as $app): 
                                            $status_class = match($app['status']) {
                                                'Applied' => 'bg-info',
                                                'Under Review' => 'bg-warning',
                                                'Admitted' => 'bg-success',
                                                'Rejected' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        ?>
                                        <tr>
                                            <td>#APP-00<?php echo $app['id']; ?></td>
                                            <td class="fw-bold"><?php echo $app['name']; ?></td>
                                            <td><?php echo $app['course']; ?></td>
                                            <td><?php echo $app['date']; ?></td>
                                            <td><span class="badge <?php echo $status_class; ?> bg-opacity-10 text-<?php echo str_replace('bg-', '', $status_class); ?> px-3"><?php echo $app['status']; ?></span></td>
                                            <td>
                                                <button class="btn btn-sm btn-dark me-1" title="View Documents"><i class="fas fa-file-alt"></i></button>
                                                <?php if($app['status'] !== 'Admitted'): ?>
                                                <button class="btn btn-sm btn-success me-1" title="Approve"><i class="fas fa-check"></i></button>
                                                <button class="btn btn-sm btn-danger" title="Reject"><i class="fas fa-times"></i></button>
                                                <?php endif; ?>
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
        </div>
    </div>
</body>
</html>
