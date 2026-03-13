<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Student Command | Project ERP";
$_SESSION['user_name'] = $_SESSION['user_name'] ?? 'John Doe';
$user_name = $_SESSION['user_name'];

// Fetch Student Profile
$stmt = $pdo->prepare("SELECT s.*, u.name, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Fetch Pending Fees
$fee_stmt = $pdo->prepare("SELECT SUM(amount) FROM fees WHERE student_id = ? AND status = 'pending'");
$fee_stmt->execute([$student['id'] ?? 0]);
$pending_fees = $fee_stmt->fetchColumn() ?: 0;

$kpis = [
    ['title' => 'Credits Earned', 'value' => '124', 'total' => '160', 'icon' => 'fa-star'],
    ['title' => 'Current GPA', 'value' => '3.8', 'total' => '4.0', 'icon' => 'fa-chart-line'],
    ['title' => 'Attendance', 'value' => '94%', 'total' => '100%', 'icon' => 'fa-user-check']
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
                                <span class="badge bg-light text-muted fw-bold p-2 small px-3">Session 2024</span>
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

                <div class="row g-5"> <!-- Increased row gap -->
                    <div class="col-lg-8">
                        <div class="card-premium mb-5 animate-up shadow-lg border-0">
                            <div class="d-flex justify-content-between align-items-center mb-5">
                                <div>
                                    <h4 class="fw-900 mb-1" style="letter-spacing: -1px;">Academic Trajectory</h4>
                                    <p class="text-muted small fw-600">Overview of GPA progress across 4 semesters</p>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-light rounded-4 px-3 border shadow-sm dropdown-toggle fw-800" type="button" data-bs-toggle="dropdown">
                                        All Semesters
                                    </button>
                                </div>
                            </div>
                            <canvas id="studentGpaChart" height="280"></canvas>
                        </div>

                        <!-- PREMIUM PAYMENT CALL-TO-ACTION -->
                        <div class="card-premium animate-up shadow-lg border-0" style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; padding: 3rem;">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h3 class="fw-900 text-white mb-2" style="letter-spacing: -1px;">Outstanding Tuition</h3>
                                    <h1 class="fw-900 text-white mb-4" style="font-size: 3.5rem;">₹<?php echo number_format($pending_fees, 2); ?></h1>
                                    <p class="opacity-75 fw-500 mb-5" style="max-width: 400px; font-size: 1.1rem;">Your next payment is due on 15th October. Please clear it today to avoid late fees.</p>
                                    <a href="payments.php" class="btn btn-light fw-900 px-5 py-3 shadow-lg" style="border-radius: 18px; color: #4f46e5; border: none;">
                                        <i class="fas fa-credit-card me-2"></i> PAY VIA ENLIGHT SECURE
                                    </a>
                                </div>
                                <div class="col-md-4 text-center d-none d-md-block">
                                    <i class="fas fa-wallet fa-9x opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card-premium h-100 animate-up shadow-lg border-0" style="padding: 2.5rem;">
                            <div class="d-flex justify-content-between align-items-center mb-5">
                                <h4 class="fw-900 mb-0" style="letter-spacing: -1px;">Live Schedule</h4>
                                <a href="calendar.php" class="smallest text-primary fw-900 uppercase ls-1 text-decoration-none">Full Calendar <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                            <div class="schedule-rail">
                                <div class="lecturer-item border-start border-4 border-primary shadow-sm mb-4 ps-4 py-3 bg-white" style="border-radius: 0 20px 20px 0;">
                                    <h6 class="fw-900 mb-1">09:00 AM - 10:30 AM</h6>
                                    <div class="fw-800 text-primary small">Advanced Web Tech</div>
                                    <div class="smallest text-muted fw-bold">Lab 01 • Prof. Smith</div>
                                </div>
                                <div class="lecturer-item border-start border-4 border-warning shadow-sm mb-4 ps-4 py-3 bg-white" style="border-radius: 0 20px 20px 0;">
                                    <h6 class="fw-900 mb-1">11:00 AM - 12:30 PM</h6>
                                    <div class="fw-800 text-warning small">Network Security</div>
                                    <div class="smallest text-muted fw-bold">Room 302 • Prof. Davis</div>
                                </div>
                                <div class="lecturer-item border-start border-4 border-success shadow-sm mb-4 ps-4 py-3 bg-white" style="border-radius: 0 20px 20px 0;">
                                    <h6 class="fw-900 mb-1">01:30 PM - 03:00 PM</h6>
                                    <div class="fw-800 text-success small">Mathematics IV</div>
                                    <div class="smallest text-muted fw-bold">Room 101 • Prof. Wilson</div>
                                </div>
                            </div>
                            
                            <div class="mt-5 p-4 rounded-4 bg-primary-soft border border-primary border-opacity-10 text-center">
                                <p class="smallest text-muted fw-800 uppercase ls-1 mb-2">Next Holiday</p>
                                <h5 class="fw-900 text-primary mb-0">Holi Festival</h5>
                                <p class="smallest text-muted fw-600 mb-0">25th March, 2024</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('studentGpaChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
                datasets: [{
                    label: 'My GPA Outcome',
                    data: [3.42, 3.65, 3.58, 3.82],
                    borderColor: '#6366f1',
                    borderWidth: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#6366f1',
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(99, 102, 241, 0.08)'
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: false, min: 3, max: 4, grid: { color: 'rgba(0,0,0,0.02)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
