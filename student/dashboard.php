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
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
</head>
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area" style="padding: 0 40px 40px; background: #fdfcff;">
                
                <div class="row g-4 mb-5 position-relative">
                    <div class="col-12 z-1">
                        <div class="card border-0 p-5 overflow-hidden" style="background: linear-gradient(135deg, #8e24aa, #6a1b9a); border-radius: 32px; box-shadow: 0 20px 50px rgba(142,36,170,0.2);">
                            <div class="position-absolute" style="top: -50%; right: -10%; width: 400px; height: 400px; border-radius: 50%; border: 30px solid rgba(255,255,255,0.05);"></div>
                            <div class="position-absolute" style="bottom: -20%; right: 10%; width: 200px; height: 200px; border-radius: 50%; border: 15px solid rgba(255,255,255,0.1);"></div>
                            <div class="row align-items-center position-relative z-2">
                                <div class="col-md-8">
                                    <h4 class="text-white fw-bold opacity-75 mb-2">Academic Overview</h4>
                                    <h1 class="text-white fw-900 mb-4" style="font-size: 3rem; letter-spacing: -1px;">You are excelling!</h1>
                                    <p class="text-white opacity-75 fw-500 mb-0" style="max-width: 500px; font-size: 1.1rem; line-height: 1.6;">Your attendance is perfect and your latest assessments are grading above the cohort average. Keep up the momentum.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="card border-0 p-4 d-flex flex-row align-items-center gap-4" style="background: #ffffff; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                            <div class="p-3 rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(94, 107, 192, 0.1); color: #5C6BC0; width: 64px; height: 64px;">
                                <i class="fas fa-chart-line fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted fw-bold uppercase ls-1 mb-1" style="font-size: 0.75rem;">Current GPA</h6>
                                <h2 class="fw-900 mb-0" style="color: #1e1b4b;">3.8 <span class="text-muted fs-6 fw-bold">/4.0</span></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 p-4 d-flex flex-row align-items-center gap-4" style="background: #ffffff; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                            <div class="p-3 rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(102, 187, 106, 0.1); color: #66BB6A; width: 64px; height: 64px;">
                                <i class="fas fa-calendar-check fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted fw-bold uppercase ls-1 mb-1" style="font-size: 0.75rem;">Attendance</h6>
                                <h2 class="fw-900 mb-0" style="color: #1e1b4b;">94% <span class="text-success fs-6 fw-bold"><i class="fas fa-arrow-up"></i></span></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 p-4 d-flex flex-row align-items-center gap-4" style="background: #ffffff; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                            <div class="p-3 rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(255, 167, 38, 0.1); color: #FFA726; width: 64px; height: 64px;">
                                <i class="fas fa-wallet fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted fw-bold uppercase ls-1 mb-1" style="font-size: 0.75rem;">Pending Dues</h6>
                                <h2 class="fw-900 mb-0" style="color: #1e1b4b;">₹<?php echo number_format($pending_fees); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-5">
                    <div class="col-lg-8">
                        <div class="d-flex justify-content-between align-items-end mb-4">
                            <h4 class="fw-800 mb-0" style="color: #1e1b4b;">Mid-Term Results</h4>
                            <a href="marks.php" class="text-decoration-none fw-bold" style="color: #8e24aa;">View Full Transcript</a>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card border-0 p-4" style="border-radius: 24px; background: rgba(94, 107, 192, 0.05); border: 1px solid rgba(94, 107, 192, 0.1) !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="p-2 rounded-3" style="background: #5C6BC0; color: white;"><i class="fas fa-database"></i></div>
                                        <span class="badge bg-white text-dark shadow-sm">BCA-301</span>
                                    </div>
                                    <h5 class="fw-800" style="color: #1e1b4b;">Database Systems</h5>
                                    <div class="d-flex justify-content-between align-items-end mt-4">
                                        <h2 class="fw-900 mb-0" style="color: #5C6BC0;">85%</h2>
                                        <span class="fw-bold text-muted">Grade A</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 p-4" style="border-radius: 24px; background: rgba(102, 187, 106, 0.05); border: 1px solid rgba(102, 187, 106, 0.1) !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="p-2 rounded-3" style="background: #66BB6A; color: white;"><i class="fas fa-code"></i></div>
                                        <span class="badge bg-white text-dark shadow-sm">BCA-302</span>
                                    </div>
                                    <h5 class="fw-800" style="color: #1e1b4b;">Web Technology</h5>
                                    <div class="d-flex justify-content-between align-items-end mt-4">
                                        <h2 class="fw-900 mb-0" style="color: #66BB6A;">92%</h2>
                                        <span class="fw-bold text-muted">Grade A+</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border-0 h-100 p-4" style="border-radius: 24px; background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-800 mb-0" style="color: #1e1b4b;">Next Classes</h5>
                                <div class="p-2 bg-light rounded-circle text-muted"><i class="fas fa-calendar-alt"></i></div>
                            </div>
                            
                            <div class="d-flex gap-3 mb-4">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="fw-bold text-muted small">09:00</div>
                                    <div style="width: 2px; height: 40px; background: #ede9fe; margin: 5px 0;"></div>
                                </div>
                                <div>
                                    <div class="p-3 rounded-4 mb-2" style="background: #fdfcff; border: 1px solid #ede9fe;">
                                        <h6 class="fw-800 mb-1" style="color: #1e1b4b;">Advanced Web Tech</h6>
                                        <p class="mb-0 text-muted small fw-500"><i class="fas fa-map-marker-alt me-1 text-primary"></i> Lab 01 • Prof. Smith</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-3">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="fw-bold text-muted small">11:00</div>
                                    <div style="width: 2px; height: 40px; background: #ede9fe; margin: 5px 0;"></div>
                                </div>
                                <div>
                                    <div class="p-3 rounded-4 mb-2" style="background: #fdfcff; border: 1px solid #ede9fe;">
                                        <h6 class="fw-800 mb-1" style="color: #1e1b4b;">Network Security</h6>
                                        <p class="mb-0 text-muted small fw-500"><i class="fas fa-map-marker-alt me-1 text-warning"></i> Room 302 • Prof. Davis</p>
                                    </div>
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
