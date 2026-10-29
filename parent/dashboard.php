<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
require_once('../config/db.php');

$user_name = $_SESSION['user_name'] ?? 'Parent';

// Try to find ward (child) linked to parent
try {
    $ward = $pdo->prepare("SELECT s.*, u.name FROM students s JOIN users u ON s.user_id = u.id WHERE s.parent_id = ? LIMIT 1");
    $ward->execute([$_SESSION['user_id']]);
    $ward = $ward->fetch();

    if ($ward) {
        $att = $pdo->prepare("SELECT COUNT(*) as total, SUM(status='present') as present FROM attendance WHERE student_id=?");
        $att->execute([$ward['id']]);
        $attRow = $att->fetch();
        $att_pct = $attRow['total'] > 0 ? round($attRow['present']/$attRow['total']*100) : 94;

        $fees_due = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM fees WHERE student_id=? AND status='pending'");
        $fees_due->execute([$ward['id']]);
        $fees_due = $fees_due->fetchColumn() ?: 0;

        $marks_stmt = $pdo->prepare("SELECT subject, marks_obtained, total_marks FROM marks WHERE student_id=? LIMIT 6");
        $marks_stmt->execute([$ward['id']]);
        $marks_list = $marks_stmt->fetchAll();
    } else {
        $att_pct = 94; $fees_due = 15000; $marks_list = [];
    }
} catch (Exception $e) {
    $ward = null; $att_pct = 94; $fees_due = 15000; $marks_list = [];
}

$ward_name      = $ward['name'] ?? 'Your Ward';
$subjects_chart = $marks_list ? array_column($marks_list,'subject') : ['Math','Physics','CS','English','DB'];
$marks_chart    = $marks_list ? array_map(fn($m)=>$m['marks_obtained'], $marks_list) : [85,92,78,88,95];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard | ia Academy</title>
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
                    <div class="page-title">Parent Dashboard</div>
                    <div class="page-sub">Tracking <strong><?php echo htmlspecialchars($ward_name); ?></strong>'s academic progress.</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="attendance.php" class="btn btn-outline btn-sm"><i class="fas fa-clipboard-check"></i> Attendance</a>
                    <a href="marks.php" class="btn btn-primary btn-sm"><i class="fas fa-chart-bar"></i> View Marks</a>
                </div>
            </div>

            <!-- KPI Row -->
            <div class="row g-3 mb-4 animate-up">
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon green"><i class="fas fa-clipboard-check"></i></div>
                        <div>
                            <div class="kpi-title">Attendance</div>
                            <div class="kpi-value"><?php echo $att_pct; ?>%</div>
                            <div class="kpi-badge <?php echo $att_pct >= 75 ? 'trend-up' : 'trend-down'; ?>">
                                <?php echo $att_pct >= 75 ? 'Excellent' : 'Needs improvement'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon purple"><i class="fas fa-chart-line"></i></div>
                        <div>
                            <div class="kpi-title">Current CGPA</div>
                            <div class="kpi-value">3.82</div>
                            <div class="kpi-badge trend-up"><i class="fas fa-arrow-up"></i> Top 5%</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon orange"><i class="fas fa-wallet"></i></div>
                        <div>
                            <div class="kpi-title">Fee Due</div>
                            <div class="kpi-value">₹<?php echo number_format($fees_due/1000,1); ?>K</div>
                            <div class="kpi-badge <?php echo $fees_due > 0 ? 'trend-down' : 'trend-up'; ?>">
                                <?php echo $fees_due > 0 ? 'Pay before deadline' : 'All paid'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon blue"><i class="fas fa-star"></i></div>
                        <div>
                            <div class="kpi-title">Class Rank</div>
                            <div class="kpi-value">#5</div>
                            <div class="kpi-badge trend-up"><i class="fas fa-trophy"></i> Top performer</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-3 mb-4 animate-up">
                <!-- Marks Bar Chart -->
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="card-title">Academic Performance</div>
                                <div class="card-sub">Subject-wise marks of <?php echo htmlspecialchars($ward_name); ?></div>
                            </div>
                            <a href="marks.php" class="btn btn-outline btn-sm">Full Report</a>
                        </div>
                        <div class="chart-container" style="height:210px;">
                            <canvas id="marksChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Progress donut -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-title mb-1">Semester Progress</div>
                        <div class="card-sub mb-3">CGPA trend overview</div>
                        <div class="chart-container" style="height:160px;">
                            <canvas id="progressDonut"></canvas>
                        </div>
                        <div style="text-align:center;margin-top:12px;">
                            <div style="font-size:28px;font-weight:800;color:var(--text);">3.82</div>
                            <div style="font-size:12px;color:var(--text-muted);">Cumulative GPA</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts + Quick Links -->
            <div class="row g-3 animate-up">
                <!-- Alerts -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title">Alerts & Notices</div>
                        </div>
                        <?php
                        $alerts = [
                            ['Fee Reminder','Semester fees are due by 15th Oct.','orange','fa-credit-card'],
                            ['Attendance Warning','Attendance dropped below 80% in Physics.','red','fa-exclamation-triangle'],
                            ['Exam Schedule','Mid-term exams begin 20th October.','purple','fa-file-alt'],
                            ['Good Performance','Ranked in top 5% this semester!','green','fa-trophy'],
                        ];
                        foreach ($alerts as $a): ?>
                        <div style="display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-light);">
                            <div style="width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;
                                background:var(--<?php echo $a[2]==='orange'?'secondary':$a[2]; ?>-light);
                                color:var(--<?php echo $a[2]==='orange'?'secondary':$a[2]; ?>);
                                font-size:14px;flex-shrink:0;margin-top:2px;">
                                <i class="fas <?php echo $a[3]; ?>"></i>
                            </div>
                            <div>
                                <div style="font-size:13px;font-weight:600;color:var(--text);"><?php echo $a[0]; ?></div>
                                <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px;"><?php echo $a[1]; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick Access -->
                <div class="col-md-7">
                    <div class="card h-100">
                        <div class="card-title mb-3">Quick Access</div>
                        <div class="row g-3">
                            <?php $links = [
                                ['attendance.php','fa-clipboard-check','green','Attendance','Monitor presence'],
                                ['marks.php','fa-chart-bar','purple','Marks','Subject scores'],
                                ['timetable.php','fa-calendar-alt','orange','Timetable','Class schedule'],
                                ['transport.php','fa-bus','green','Transport','Live GPS tracking'],
                                ['settings.php','fa-cog','blue','Settings','Profile & account'],
                            ];
                            foreach ($links as $l): ?>
                            <div class="col-6">
                                <a href="<?php echo $l[0]; ?>" style="text-decoration:none;">
                                    <div style="padding:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;transition:all 0.15s;display:flex;align-items:center;gap:12px;"
                                         onmouseover="this.style.borderColor='var(--primary)';this.style.background='var(--primary-soft)'"
                                         onmouseout="this.style.borderColor='var(--border)';this.style.background='#fff'">
                                        <div class="kpi-icon <?php echo $l[2]; ?>" style="width:40px;height:40px;border-radius:10px;font-size:16px;flex-shrink:0;">
                                            <i class="fas <?php echo $l[1]; ?>"></i>
                                        </div>
                                        <div>
                                            <div style="font-size:13.5px;font-weight:700;color:var(--text);"><?php echo $l[3]; ?></div>
                                            <div style="font-size:11.5px;color:var(--text-muted);"><?php echo $l[4]; ?></div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($fees_due > 0): ?>
                        <div class="alert alert-warning mt-3" style="margin-bottom:0;">
                            <i class="fas fa-wallet"></i>
                            Outstanding fee of <strong>₹<?php echo number_format($fees_due); ?></strong> is pending.
                            <a href="settings.php" style="color:inherit;font-weight:700;margin-left:8px;">Pay Now →</a>
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
new Chart(document.getElementById('marksChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($subjects_chart); ?>,
        datasets: [{
            label: 'Marks',
            data: <?php echo json_encode($marks_chart); ?>,
            backgroundColor: '#7C3AED', borderRadius: 6, borderSkipped: false, barPercentage: 0.5,
        },{
            label: 'Max',
            data: Array(<?php echo count($subjects_chart); ?>).fill(100),
            backgroundColor: '#EDE9FE', borderRadius: 6, borderSkipped: false, barPercentage: 0.5,
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

new Chart(document.getElementById('progressDonut'), {
    type: 'doughnut',
    data: {
        labels: ['CGPA Earned','Remaining'],
        datasets: [{ data: [95.5, 4.5], backgroundColor: ['#7C3AED','#EDE9FE'], borderWidth: 0, hoverOffset: 4 }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false } } }
});
</script>
</body>
</html>
