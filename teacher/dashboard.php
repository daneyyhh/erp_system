<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php"); exit();
}
require_once('../config/db.php');

$user_name = $_SESSION['user_name'] ?? 'Teacher';

// Stats
try {
    $teacher = $pdo->prepare("SELECT * FROM teachers WHERE user_id=?");
    $teacher->execute([$_SESSION['user_id']]);
    $teacher = $teacher->fetch();

    $total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn() ?: 0;
    $pending_certs  = $pdo->query("SELECT COUNT(*) FROM certificates WHERE status='pending'")->fetchColumn() ?: 0;
    $subjects_count = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn() ?: 0;
} catch (Exception $e) {
    $teacher = []; $total_students = 0; $pending_certs = 0; $subjects_count = 0;
}

$schedule = [
    ['09:00 - 10:30','BCA 1st Year','Lab 01','Core Programming'],
    ['11:00 - 12:30','MCA 2nd Year','Room 302','Data Structures'],
    ['14:00 - 15:30','BCA 2nd Year','Room 201','Web Technology'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard | ia Academy</title>
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

            <!-- Header -->
            <div class="page-header animate-up">
                <div>
                    <div class="page-title">Teacher Dashboard</div>
                    <div class="page-sub">Welcome, <?php echo htmlspecialchars($user_name); ?>! Manage your classes and students.</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="mark_attendance.php" class="btn btn-outline btn-sm"><i class="fas fa-clipboard-check"></i> Mark Attendance</a>
                    <a href="marks.php" class="btn btn-primary btn-sm"><i class="fas fa-chart-bar"></i> Enter Marks</a>
                </div>
            </div>

            <!-- KPI Row -->
            <div class="row g-3 mb-4 animate-up">
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon purple"><i class="fas fa-user-graduate"></i></div>
                        <div>
                            <div class="kpi-title">Total Students</div>
                            <div class="kpi-value"><?php echo number_format($total_students); ?></div>
                            <div class="kpi-badge trend-up"><i class="fas fa-users"></i> Enrolled</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon orange"><i class="fas fa-book-open"></i></div>
                        <div>
                            <div class="kpi-title">Subjects</div>
                            <div class="kpi-value"><?php echo $subjects_count; ?></div>
                            <div class="kpi-badge trend-up"><i class="fas fa-check"></i> Active</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon green"><i class="fas fa-check-double"></i></div>
                        <div>
                            <div class="kpi-title">Attendance Rate</div>
                            <div class="kpi-value">91%</div>
                            <div class="kpi-badge trend-up"><i class="fas fa-arrow-up"></i> This month</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon red"><i class="fas fa-stamp"></i></div>
                        <div>
                            <div class="kpi-title">Pending Certs</div>
                            <div class="kpi-value"><?php echo $pending_certs; ?></div>
                            <div class="kpi-badge trend-down"><i class="fas fa-clock"></i> Awaiting</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-3 mb-4 animate-up">
                <!-- Batch Performance Chart -->
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="card-title">Batch Performance</div>
                                <div class="card-sub">Average scores across all assigned batches</div>
                            </div>
                            <div class="d-flex gap-3" style="font-size:12px;font-weight:600;">
                                <span style="color:var(--primary);"><i class="fas fa-circle" style="font-size:8px;"></i> Theory</span>
                                <span style="color:var(--secondary);"><i class="fas fa-circle" style="font-size:8px;"></i> Practical</span>
                            </div>
                        </div>
                        <div class="chart-container" style="height:220px;">
                            <canvas id="batchChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Attendance Donut -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-title mb-1">Class Attendance</div>
                        <div class="card-sub mb-3">Present vs Absent today</div>
                        <div class="chart-container" style="height:155px;">
                            <canvas id="attDonut"></canvas>
                        </div>
                        <div style="text-align:center;margin-top:12px;">
                            <div style="font-size:28px;font-weight:800;color:var(--text);">91%</div>
                            <div style="font-size:12px;color:var(--text-muted);">Present Today</div>
                        </div>
                        <div class="d-flex justify-content-center gap-4 mt-3" style="font-size:12px;font-weight:600;">
                            <span style="color:var(--green);"><i class="fas fa-circle" style="font-size:8px;"></i> Present</span>
                            <span style="color:var(--red);"><i class="fas fa-circle" style="font-size:8px;"></i> Absent</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row -->
            <div class="row g-3 animate-up">
                <!-- Today's Schedule -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title">Teaching Schedule</div>
                            <a href="calendar.php" style="font-size:12px;font-weight:600;color:var(--primary);text-decoration:none;">Full Planner</a>
                        </div>
                        <?php foreach ($schedule as $s): ?>
                        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-light);">
                            <div style="font-size:11px;font-weight:700;color:var(--text-muted);min-width:48px;line-height:1.3;"><?php echo explode(' - ',$s[0])[0]; ?></div>
                            <div style="width:3px;height:36px;background:var(--primary);border-radius:9999px;flex-shrink:0;"></div>
                            <div>
                                <div style="font-size:13px;font-weight:600;color:var(--text);"><?php echo $s[3]; ?></div>
                                <div style="font-size:11.5px;color:var(--text-muted);"><?php echo $s[2]; ?> • <?php echo $s[1]; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Pending certs + quick access -->
                <div class="col-md-7">
                    <div class="card h-100">
                        <div class="card-title mb-3">Quick Access</div>
                        <div class="row g-3">
                            <?php $links = [
                                ['students_list.php','fa-user-graduate','purple','Students','View all students'],
                                ['mark_attendance.php','fa-clipboard-check','green','Attendance','Mark today\'s attendance'],
                                ['marks.php','fa-chart-bar','orange','Enter Marks','Update student scores'],
                                ['exams.php','fa-file-alt','blue','Exams','Manage exam questions'],
                                ['certificates.php','fa-certificate','red','Certificates','Review & approve'],
                                ['timetable.php','fa-calendar-alt','purple','Timetable','View weekly schedule'],
                                ['transport.php','fa-bus','green','Transport','Live tracking'],
                            ];
                            foreach ($links as $l): ?>
                            <div class="col-6 col-md-4">
                                <a href="<?php echo $l[0]; ?>" style="text-decoration:none;">
                                    <div style="padding:14px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;transition:all 0.15s;display:flex;align-items:center;gap:10px;"
                                         onmouseover="this.style.borderColor='var(--primary)';this.style.background='var(--primary-soft)'"
                                         onmouseout="this.style.borderColor='var(--border)';this.style.background='#fff'">
                                        <div class="kpi-icon <?php echo $l[2]; ?>" style="width:34px;height:34px;border-radius:8px;font-size:14px;flex-shrink:0;">
                                            <i class="fas <?php echo $l[1]; ?>"></i>
                                        </div>
                                        <div>
                                            <div style="font-size:12.5px;font-weight:700;color:var(--text);"><?php echo $l[3]; ?></div>
                                            <div style="font-size:11px;color:var(--text-muted);"><?php echo $l[4]; ?></div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pending cert alert -->
                        <?php if ($pending_certs > 0): ?>
                        <div class="alert alert-warning mt-3" style="margin-bottom:0;">
                            <i class="fas fa-stamp"></i>
                            <strong><?php echo $pending_certs; ?> certificate request<?php echo $pending_certs > 1 ? 's' : ''; ?></strong> awaiting your review.
                            <a href="certificates.php" style="color:inherit;font-weight:700;margin-left:8px;">Review →</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
new Chart(document.getElementById('batchChart'), {
    type: 'bar',
    data: {
        labels: ['BCA 1','BCA 2','MCA 1','MCA 2','BCA 3','MCA 3'],
        datasets: [{
            label: 'Theory',
            data: [78, 82, 75, 88, 71, 85],
            backgroundColor: '#7C3AED', borderRadius: 6, borderSkipped: false, barPercentage: 0.4,
        },{
            label: 'Practical',
            data: [85, 79, 82, 91, 77, 88],
            backgroundColor: '#F59E0B', borderRadius: 6, borderSkipped: false, barPercentage: 0.4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11, family: 'Inter' }, color: '#9CA3AF' } },
            y: { max: 100, grid: { color: '#F3F4F6' }, ticks: { font: { size: 11, family: 'Inter' }, color: '#9CA3AF' } }
        }
    }
});

new Chart(document.getElementById('attDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Present','Absent'],
        datasets: [{ data: [91,9], backgroundColor: ['#10B981','#FEE2E2'], borderWidth: 0, hoverOffset: 4 }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false } } }
});
</script>
</body>
</html>
