<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Faculty Command | Project ERP";
$user_name = $_SESSION['user_name'] ?? 'Prof. Aryan';

$kpis = [
    ['title' => 'Assigned Lectures', 'value' => '12', 'total' => '15', 'icon' => 'fa-book-open'],
    ['title' => 'Attendance Rate', 'value' => '91%', 'total' => '100%', 'icon' => 'fa-check-double'],
    ['title' => 'Pending Certs', 'value' => '04', 'total' => '10', 'icon' => 'fa-stamp']
];

$schedule = [
    ['09:00 - 10:30', 'BCA 1st Year', 'Lab 01', 'Core Programming'],
    ['11:00 - 12:30', 'MCA 2nd Year', 'Room 302', 'Data Structures']
];
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
            <div class="content-area" style="padding-top: 0;">
                
                <div class="row g-4 mb-5">
                    <?php foreach($kpis as $k): ?>
                    <div class="col-md-4">
                        <div class="card-premium animate-up shadow-lg border-0">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div class="bg-primary-soft p-3 rounded-4">
                                    <i class="fas <?php echo $k['icon']; ?> text-primary fs-4"></i>
                                </div>
                                <span class="badge bg-light text-muted fw-bold p-2 small px-3">Active Sem</span>
                            </div>
                            <div class="kpi-title mb-2"><?php echo $k['title']; ?></div>
                            <div class="d-flex align-items-baseline gap-2">
                                <span class="kpi-value" style="font-size: 2.8rem; font-weight: 900;"><?php echo $k['value']; ?></span>
                                <span class="text-muted fw-800 fs-5">/<?php echo $k['total']; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="row g-5">
                    <div class="col-lg-8">
                        <div class="card-premium mb-5 animate-up shadow-lg border-0">
                            <div class="d-flex justify-content-between align-items-center mb-5">
                                <div>
                                    <h4 class="fw-900 mb-1" style="letter-spacing: -1px;">Batch Performance</h4>
                                    <p class="text-muted small fw-600">Average test scores across assigned departments</p>
                                </div>
                            </div>
                            <canvas id="teacherPerformanceChart" height="280"></canvas>
                        </div>

                        <div class="card-premium animate-up shadow-lg border-0" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 3rem;">
                            <div class="row align-items-center">
                                <div class="col-md-9">
                                    <h3 class="fw-900 text-white mb-2" style="letter-spacing: -1px;">Pending Approvals</h3>
                                    <p class="opacity-75 fw-500 mb-4" style="max-width: 450px; font-size: 1.1rem;">You have 4 document requests awaiting verification. Review them to help students get their certificates on time.</p>
                                    <a href="certificates.php" class="btn btn-light fw-900 px-5 py-3 shadow-lg" style="border-radius: 18px; color: #d97706; border: none;">
                                        <i class="fas fa-stamp me-2"></i> REVIEW DOCUMENTS
                                    </a>
                                </div>
                                <div class="col-md-3 text-center d-none d-md-block">
                                    <i class="fas fa-file-invoice fa-8x opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card-premium h-100 animate-up shadow-lg border-0" style="padding: 2.5rem;">
                            <div class="d-flex justify-content-between align-items-center mb-5">
                                <h4 class="fw-900 mb-0" style="letter-spacing: -1px;">Teaching Flow</h4>
                                <a href="calendar.php" class="smallest text-primary fw-900 uppercase ls-1 text-decoration-none">Full Planner <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                            
                            <?php foreach($schedule as $s): ?>
                            <div class="lecturer-item border-start border-4 border-primary shadow-sm mb-4 ps-4 py-3 bg-white" style="border-radius: 0 20px 20px 0;">
                                <h6 class="fw-900 mb-1"><?php echo $s[0]; ?></h6>
                                <div class="fw-800 text-primary small"><?php echo $s[3]; ?></div>
                                <div class="smallest text-muted fw-bold"><?php echo $s[2]; ?> • <?php echo $s[1]; ?></div>
                            </div>
                            <?php endforeach; ?>

                            <div class="mt-5 p-4 rounded-4 bg-primary-soft border border-primary border-opacity-10 text-center">
                                <p class="smallest text-muted fw-800 uppercase ls-1 mb-2">Faculty Notification</p>
                                <h5 class="fw-900 text-primary mb-0">Grade Submission</h5>
                                <p class="smallest text-muted fw-600 mb-0">Deadline: 5th April, 2024</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('teacherPerformanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['BCA 1', 'BCA 2', 'MCA 1', 'MCA 2'],
                datasets: [{
                    label: 'Avg Score',
                    data: [78, 82, 75, 88],
                    backgroundColor: '#6366f1',
                    borderRadius: 12
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.02)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
