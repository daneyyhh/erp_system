<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Parent Portal | Project ERP";
$student_name = "John Doe"; // In real app, fetch from DB via session/ward_id
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
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="card-premium kpi-card animate-up">
                            <div class="kpi-title">Ward Attendance</div>
                            <div class="kpi-value text-success">94%</div>
                            <div class="mt-2 smallest text-muted fw-bold uppercase">Status: Excellent</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-premium kpi-card animate-up" style="--delay: 0.1s">
                            <div class="kpi-title">Current CGPA</div>
                            <div class="kpi-value text-primary">3.82</div>
                            <div class="mt-2 smallest text-muted fw-bold uppercase">Rank: top 5%</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-premium kpi-card animate-up" style="--delay: 0.2s">
                            <div class="kpi-title">Next Fee Due</div>
                            <div class="kpi-value">₹15k</div>
                            <div class="mt-2 smallest text-muted fw-bold uppercase">Due Date: 15 Oct</div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card-premium animate-up">
                            <h5 class="fw-800 mb-4">Academic Progress Tracker</h5>
                            <canvas id="parentChart" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card-premium h-100 animate-up">
                            <h5 class="fw-800 mb-4">Quick Alerts</h5>
                            <div class="lecturer-item border shadow-sm mb-3">
                                <div class="bg-primary-soft p-3 rounded-4 me-3">
                                    <i class="fas fa-bullhorn text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-800 mb-1">Fee Reminder</h6>
                                    <p class="smallest text-muted mb-0">Semester fees are due by next week.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('parentChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Math', 'Physics', 'CS', 'English', 'DB'],
                datasets: [{
                    label: 'Marks Scored',
                    data: [85, 92, 78, 88, 95],
                    backgroundColor: '#6366f1',
                    borderRadius: 10
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>
