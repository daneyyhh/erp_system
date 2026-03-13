<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Command Centre | Project ERP";

// Real Stats
$count_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$count_faculty = $pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
$count_pending_certs = $pdo->query("SELECT COUNT(*) FROM certificates WHERE status='pending'")->fetchColumn();

// KPI Mock Data for Admin
$kpis = [
    ['title' => 'Student Enrollment', 'value' => $count_students, 'total' => '500', 'trend' => '+12 Monthly', 'trend_up' => true],
    ['title' => 'Average Attendance', 'value' => '84', 'total' => '100', 'trend' => '+2.5% Today', 'trend_up' => true],
    ['title' => 'Faculty Strength', 'value' => $count_faculty, 'total' => '40', 'trend' => 'Stable', 'trend_up' => true]
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            
            <div class="content-area">
                <div class="row g-4 mb-5">
                    <?php foreach($kpis as $k): ?>
                    <div class="col-md-4">
                        <div class="card-premium kpi-card animate-fade">
                            <div class="kpi-title">
                                <?php echo $k['title']; ?>
                                <i class="fas fa-ellipsis-h text-muted smallest"></i>
                            </div>
                            <div class="d-flex align-items-baseline gap-2">
                                <span class="kpi-value"><?php echo $k['value']; ?></span>
                                <span class="text-muted fw-bold">/<?php echo $k['total']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="smallest text-muted">Real-time DB Metric</div>
                                <span class="trend-badge <?php echo $k['trend_up'] ? 'trend-up' : 'trend-down'; ?>">
                                    <?php echo $k['trend']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <!-- Growth Chart -->
                        <div class="card-premium mb-4 animate-fade">
                            <div class="d-flex justify-content-between align-items-center mb-5">
                                <div>
                                    <h5 class="fw-800 mb-1">Institutional Performance</h5>
                                    <p class="smallest text-muted">Tracking student growth and academic outcomes</p>
                                </div>
                                <div class="btn-group shadow-sm" style="border-radius: 12px; overflow: hidden;">
                                    <button class="btn btn-light btn-sm fw-bold active">Daily</button>
                                    <button class="btn btn-light btn-sm fw-bold">Weekly</button>
                                </div>
                            </div>
                            <canvas id="growthChart" height="200"></canvas>
                        </div>

                        <!-- System Logs Export Card -->
                        <div class="card-premium animate-fade" style="background: linear-gradient(135deg, #5e5adb, #8a84ff); color: white;">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h4 class="fw-800 text-white mb-2">Live System Audit Logs</h4>
                                    <p class="opacity-75 small mb-4">Export all historical student interactions and faculty actions to a live Excel ledger for compliance and review.</p>
                                    <a href="../logs/system_logs.csv" class="btn btn-light fw-bold px-4 py-2" style="border-radius: 12px; color: #5e5adb;">
                                        <i class="fas fa-file-excel me-2"></i> Download Audit Ledger
                                    </a>
                                </div>
                                <div class="col-md-4 text-center d-none d-md-block">
                                    <i class="fas fa-database fa-5x opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card-premium h-100 animate-fade">
                            <h5 class="fw-800 mb-4">Urgent Operations</h5>
                            
                            <!-- Pending Approvals -->
                            <div class="lecturer-item border shadow-sm mb-4" style="background: #fffafa;">
                                <div class="bg-danger bg-opacity-10 p-3 rounded-4 me-3">
                                    <i class="fas fa-certificate text-danger fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-800 mb-1"><?php echo $count_pending_certs; ?> Pending Requests</h6>
                                    <div class="smallest text-muted fw-bold">Awaiting Final Issuance</div>
                                    <a href="certificates.php" class="text-decoration-none smallest fw-800 text-danger mt-1 d-block">TAKE ACTION <i class="fas fa-arrow-right ms-1"></i></a>
                                </div>
                            </div>

                            <h5 class="fw-800 mb-4 pt-3 border-top">Quick Links</h5>
                            <div class="d-grid gap-2">
                                <a href="students_list.php" class="btn btn-light text-start py-3 px-4 border shadow-sm" style="border-radius: 18px;">
                                    <i class="fas fa-user-plus text-primary me-3"></i> <b>Enroll Student</b>
                                </a>
                                <a href="attendance.php" class="btn btn-light text-start py-3 px-4 border shadow-sm" style="border-radius: 18px;">
                                    <i class="fas fa-calendar-check text-success me-3"></i> <b>Mark Daily Status</b>
                                </a>
                                <a href="marks.php" class="btn btn-light text-start py-3 px-4 border shadow-sm" style="border-radius: 18px;">
                                    <i class="fas fa-file-invoice-dollar text-warning me-3"></i> <b>Academic Fees</b>
                                </a>
                            </div>

                            <div class="bg-light p-4 rounded-4 mt-5">
                                <p class="smallest text-muted fw-bold mb-0 text-center"><i class="fas fa-shield-check me-2"></i> System Secured via Cloud Security</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('growthChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                datasets: [{
                    label: 'Campus Activity',
                    data: [40, 65, 55, 90, 85, 95],
                    borderColor: '#5e5adb',
                    backgroundColor: 'rgba(94, 90, 219, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 5,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#5e5adb'
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false } },
                    x: { grid: { display: false }, border: { display: false } }
                }
            }
        });
    </script>
</body>
</html>
