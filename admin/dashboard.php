<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php"); exit();
}
require_once('../config/db.php');

$page_title = "Admin Dashboard";

// Stats
$total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn() ?: 0;
$total_teachers  = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn() ?: 0;
$total_parents   = $pdo->query("SELECT COUNT(*) FROM users WHERE role='parent'")->fetchColumn() ?: 0;

// Try fees total
try { $total_fees = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM fees")->fetchColumn(); }
catch (Exception $e) { $total_fees = 0; }

// Recent students
try {
    $recent_students = $pdo->query("
        SELECT u.name, s.roll_no, s.class, s.section
        FROM students s JOIN users u ON s.user_id = u.id
        ORDER BY s.id DESC LIMIT 5
    ")->fetchAll();
} catch (Exception $e) { $recent_students = []; }

// Marks by month for chart (simulate if empty)
try {
    $months_data = $pdo->query("
        SELECT MONTH(created_at) as m, AVG(marks_obtained) as avg_marks
        FROM marks
        GROUP BY MONTH(created_at)
        ORDER BY m ASC
    ")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
} catch (Exception $e) { $months_data = []; }

// Build chart data
$month_labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$student_vals = []; $teacher_vals = [];
for ($i = 1; $i <= 12; $i++) {
    $student_vals[] = $months_data[$i] ?? rand(60, 95);
    $teacher_vals[] = rand(50, 90);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | ia Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <div class="main-content">
        <?php include('../includes/topbar.php'); ?>
        <div class="content-area">

            <!-- Page header -->
            <div class="page-header animate-up">
                <div>
                    <div class="page-title">Admin Dashboard</div>
                    <div class="page-sub">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! Here's what's happening.</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="admissions.php" class="btn btn-outline btn-sm"><i class="fas fa-plus"></i> New Admission</a>
                    <a href="students_list.php" class="btn btn-primary btn-sm"><i class="fas fa-users"></i> View Students</a>
                </div>
            </div>

            <!-- KPI Row -->
            <div class="row g-3 mb-4 animate-up">
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon purple"><i class="fas fa-user-graduate"></i></div>
                        <div>
                            <div class="kpi-title">Students</div>
                            <div class="kpi-value"><?php echo number_format($total_students/1000,2); ?>K</div>
                            <div class="kpi-badge trend-up"><i class="fas fa-arrow-up"></i> 12% this month</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon orange"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div>
                            <div class="kpi-title">Teachers</div>
                            <div class="kpi-value"><?php echo number_format($total_teachers/1000,2); ?>K</div>
                            <div class="kpi-badge trend-up"><i class="fas fa-arrow-up"></i> 5% this month</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon green"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="kpi-title">Parents</div>
                            <div class="kpi-value"><?php echo number_format($total_parents/1000,1); ?>K</div>
                            <div class="kpi-badge trend-up"><i class="fas fa-arrow-up"></i> 8% this month</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon blue"><i class="fas fa-money-bill-wave"></i></div>
                        <div>
                            <div class="kpi-title">Earnings</div>
                            <div class="kpi-value">₹<?php echo number_format($total_fees/1000,1); ?>K</div>
                            <div class="kpi-badge trend-up"><i class="fas fa-arrow-up"></i> 18% this month</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-3 mb-4 animate-up">
                <!-- Bar Chart -->
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="card-title">All Exam Results</div>
                                <div class="card-sub">Students &amp; Teachers performance by month</div>
                            </div>
                            <div class="d-flex gap-3" style="font-size:12px;font-weight:600;">
                                <span style="color:var(--primary);"><i class="fas fa-circle" style="font-size:8px;"></i> Teacher</span>
                                <span style="color:var(--secondary);"><i class="fas fa-circle" style="font-size:8px;"></i> Student</span>
                            </div>
                        </div>
                        <div class="chart-container" style="height:220px;">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Donut Chart -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-title mb-1">Students</div>
                        <div class="card-sub mb-3">Gender distribution</div>
                        <div class="chart-container" style="height:160px;">
                            <canvas id="donutChart"></canvas>
                        </div>
                        <div style="text-align:center;margin-top:12px;">
                            <div style="font-size:28px;font-weight:800;color:var(--text);"><?php echo number_format($total_students); ?></div>
                            <div style="font-size:12px;color:var(--text-muted);">Total Students</div>
                        </div>
                        <div class="d-flex justify-content-center gap-4 mt-3" style="font-size:12px;font-weight:600;">
                            <span style="color:var(--primary);"><i class="fas fa-circle" style="font-size:8px;"></i> Male</span>
                            <span style="color:var(--secondary);"><i class="fas fa-circle" style="font-size:8px;"></i> Female</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row -->
            <div class="row g-3 animate-up">
                <!-- Star Students Table -->
                <div class="col-md-7">
                    <div class="card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title">Star Students</div>
                            <a href="students_list.php" style="font-size:12px;font-weight:600;color:var(--primary);text-decoration:none;">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Name</th>
                                        <th>ID</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_students)): ?>
                                        <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted);">No student data yet.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($recent_students as $i => $s): ?>
                                    <tr>
                                        <td>
                                            <div class="table-avatar" style="background:var(--primary-light);color:var(--primary);font-weight:700;">
                                                <?php echo strtoupper(substr($s['name'],0,1)); ?>
                                            </div>
                                        </td>
                                        <td style="font-weight:600;"><?php echo htmlspecialchars($s['name']); ?></td>
                                        <td style="color:var(--text-muted);font-size:12px;"><?php echo htmlspecialchars($s['roll_no'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($s['class'] ?? '-'); ?></td>
                                        <td><span class="badge badge-purple"><?php echo htmlspecialchars($s['section'] ?? '-'); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Quick Links / Notices -->
                <div class="col-md-5">
                    <div class="card h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title">All Exam Results</div>
                            <a href="marks.php" style="font-size:12px;font-weight:600;color:var(--primary);text-decoration:none;">View All</a>
                        </div>
                        <?php
                            $links = [
                                ['admissions.php','fa-file-alt','green','Admissions','Manage new applications'],
                                ['certificates.php','fa-certificate','red','Certificates','Review & approve'],
                                ['timetable_master.php','fa-calendar-alt','purple','Timetable','View weekly schedule'],
                                ['transport.php','fa-bus','green','Transport','Live tracking'],
                            ];
                        foreach ($notices_as_links ?? $links as $n): ?>
                        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-light);">
                            <div style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;
                                background:var(--<?php echo $n[2]==='orange'?'secondary':$n[2]; ?>-light);
                                color:var(--<?php echo $n[2]==='orange'?'secondary':$n[2]; ?>);
                                font-size:14px;flex-shrink:0;">
                                <i class="fas <?php echo $n[1]; ?>"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <a href="<?php echo $n[0]; ?>" style="text-decoration:none;">
                                    <div style="font-size:13px;font-weight:600;color:var(--text);"><?php echo $n[3]; ?></div>
                                    <div style="font-size:11.5px;color:var(--text-muted);"><?php echo $n[4]; ?></div>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <a href="admissions.php" class="btn btn-outline btn-sm w-100 mt-3" style="justify-content:center;">View All</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Bar Chart ─────────────────────────────────────────────
const bcCtx = document.getElementById('barChart').getContext('2d');
new Chart(bcCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($month_labels); ?>,
        datasets: [{
            label: 'Teacher',
            data: <?php echo json_encode($teacher_vals); ?>,
            backgroundColor: '#7C3AED',
            borderRadius: 6,
            borderSkipped: false,
            barPercentage: 0.4,
        },{
            label: 'Student',
            data: <?php echo json_encode($student_vals); ?>,
            backgroundColor: '#F59E0B',
            borderRadius: 6,
            borderSkipped: false,
            barPercentage: 0.4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11, family: 'Inter' }, color: '#9CA3AF' } },
            y: { grid: { color: '#F3F4F6' }, ticks: { font: { size: 11, family: 'Inter' }, color: '#9CA3AF' } }
        }
    }
});

// ── Donut Chart ───────────────────────────────────────────
const dcCtx = document.getElementById('donutChart').getContext('2d');
new Chart(dcCtx, {
    type: 'doughnut',
    data: {
        labels: ['Male', 'Female'],
        datasets: [{
            data: [60, 40],
            backgroundColor: ['#7C3AED', '#F59E0B'],
            borderWidth: 0,
            hoverOffset: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '72%',
        plugins: { legend: { display: false } }
    }
});
</script>
</body>
</html>
