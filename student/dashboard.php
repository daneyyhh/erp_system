<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php"); exit();
}
require_once('../config/db.php');

$user_name = $_SESSION['user_name'] ?? 'Student';

// Fetch student profile
try {
    $stmt = $pdo->prepare("SELECT s.*, u.name, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();

    // Pending fees
    $pending_fees = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM fees WHERE student_id = ? AND status='pending'");
    $pending_fees->execute([$student['id'] ?? 0]);
    $pending_fees = $pending_fees->fetchColumn() ?: 0;

    // Attendance %
    $att = $pdo->prepare("SELECT COUNT(*) as total, SUM(status='present') as present FROM attendance WHERE student_id=?");
    $att->execute([$student['id'] ?? 0]);
    $attRow = $att->fetch();
    $att_pct = $attRow['total'] > 0 ? round($attRow['present']/$attRow['total']*100) : 94;

    // Marks
    $marks_stmt = $pdo->prepare("SELECT subject, marks_obtained, total_marks FROM marks WHERE student_id=? LIMIT 6");
    $marks_stmt->execute([$student['id'] ?? 0]);
    $marks_list = $marks_stmt->fetchAll();
    // Real Subjects for current semester
    $curriculum_stmt = $pdo->prepare("SELECT name, code FROM subjects WHERE department = ? AND semester = ?");
    $curriculum_stmt->execute([$student['class'], $student['semester']]);
    $real_subjects = $curriculum_stmt->fetchAll();

} catch (Exception $e) {
    $student = []; $pending_fees = 0; $att_pct = 94; $marks_list = []; $real_subjects = [];
}

$subjects_chart = $marks_list ? array_column($marks_list,'subject') : ($real_subjects ? array_column($real_subjects,'name') : ['Math','Physics','CS','English','DB','Web']);
$marks_chart    = $marks_list ? array_map(fn($m)=>$m['marks_obtained'], $marks_list) : [85,92,78,88,95,90];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | ia Academy</title>
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
                    <div class="page-title">Student Dashboard</div>
                    <div class="page-sub">Welcome back, <?php echo htmlspecialchars($user_name); ?>! Here's your academic overview.</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="timetable.php" class="btn btn-outline btn-sm"><i class="fas fa-calendar-alt"></i> Timetable</a>
                    <a href="exams.php" class="btn btn-primary btn-sm"><i class="fas fa-file-alt"></i> My Exams</a>
                </div>
            </div>

            <!-- KPI Row -->
            <div class="row g-3 mb-4 animate-up">
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon purple"><i class="fas fa-chart-line"></i></div>
                        <div>
                            <div class="kpi-title">Current GPA</div>
                            <div class="kpi-value">3.8<span style="font-size:13px;font-weight:500;color:var(--text-muted)">/4.0</span></div>
                            <div class="kpi-badge trend-up"><i class="fas fa-arrow-up"></i> Good standing</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon green"><i class="fas fa-clipboard-check"></i></div>
                        <div>
                            <div class="kpi-title">Attendance</div>
                            <div class="kpi-value"><?php echo $att_pct; ?>%</div>
                            <div class="kpi-badge <?php echo $att_pct >= 75 ? 'trend-up' : 'trend-down'; ?>">
                                <i class="fas fa-<?php echo $att_pct >= 75 ? 'check' : 'exclamation-triangle'; ?>"></i>
                                <?php echo $att_pct >= 75 ? 'Good' : 'Low — Act now'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon orange"><i class="fas fa-wallet"></i></div>
                        <div>
                            <div class="kpi-title">Pending Fees</div>
                            <div class="kpi-value">₹<?php echo number_format($pending_fees/1000,1); ?>K</div>
                            <div class="kpi-badge <?php echo $pending_fees > 0 ? 'trend-down' : 'trend-up'; ?>">
                                <?php echo $pending_fees > 0 ? 'Due soon' : 'All clear'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon blue"><i class="fas fa-star"></i></div>
                        <div>
                            <div class="kpi-title">Credits Earned</div>
                            <div class="kpi-value">124<span style="font-size:13px;font-weight:500;color:var(--text-muted)">/160</span></div>
                            <div class="kpi-badge trend-up"><i class="fas fa-arrow-up"></i> On track</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-3 mb-4 animate-up">
                <!-- Subject Marks Bar Chart -->
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="card-title">Subject Performance</div>
                                <div class="card-sub">Marks scored per subject this semester</div>
                            </div>
                            <a href="marks.php" class="btn btn-outline btn-sm">View Transcript</a>
                        </div>
                        <div class="chart-container" style="height:210px;">
                            <canvas id="marksChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- GPA Trend Donut -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-title mb-1">Semester Progress</div>
                        <div class="card-sub mb-3">GPA across semesters</div>
                        <div class="chart-container" style="height:160px;">
                            <canvas id="gpaLine"></canvas>
                        </div>
                        <div style="text-align:center;margin-top:12px;">
                            <div style="font-size:28px;font-weight:800;color:var(--text);">3.82</div>
                            <div style="font-size:12px;color:var(--text-muted);">Cumulative GPA</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row -->
            <div class="row g-3 animate-up">
                <!-- Academic Curriculum -->
                <div class="col-md-5">
                    <div class="card h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title">Course Curriculum</div>
                            <span class="badge bg-primary bg-opacity-10 text-primary fw-900 border-0">SEM <?php echo $student['semester']; ?></span>
                        </div>
                        <div class="pe-2" style="max-height: 280px; overflow-y: auto;">
                        <?php
                        if (empty($real_subjects)): ?>
                            <div class="text-center py-4 text-muted small">No subjects mapped for <?php echo htmlspecialchars($student['class']); ?>.</div>
                        <?php else: 
                            $colors = ['purple','green','orange','blue','red'];
                            foreach ($real_subjects as $index => $sub): 
                                $color = $colors[$index % count($colors)];
                            ?>
                            <div style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border-light);">
                                <div style="font-size:10px;font-weight:800;color:var(--text-muted);min-width:45px;text-align:right;"><?php echo $sub['code']; ?></div>
                                <div style="width:3px;height:32px;background:var(--<?php echo $color==='orange'?'secondary':$color; ?>);border-radius:9999px;flex-shrink:0;"></div>
                                <div>
                                    <div style="font-size:13px;font-weight:700;color:var(--text);"><?php echo htmlspecialchars($sub['name']); ?></div>
                                    <div style="font-size:11px;color:var(--text-muted);">Core Academic Module</div>
                                </div>
                            </div>
                            <?php endforeach; 
                        endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-md-7">
                    <div class="card h-100">
                        <div class="card-title mb-3">Quick Access</div>
                        <div class="row g-3">
                            <?php $links = [
                                ['attendance.php','fa-clipboard-check','purple','Attendance','Check record'],
                                ['marks.php','fa-chart-bar','green','My Marks','View results'],
                                ['transport.php','fa-bus','orange','Transport','Live GPS tracking'],
                                ['library_seats.php','fa-book','blue','Library','Book a seat'],
                                ['hostel.php','fa-building','red','Hostel','Allocation'],
                                ['exams.php','fa-file-alt','purple','Exams','Take tests'],
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
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Subject Marks Bar Chart
new Chart(document.getElementById('marksChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($subjects_chart); ?>,
        datasets: [{
            label: 'Marks',
            data: <?php echo json_encode($marks_chart); ?>,
            backgroundColor: '#7C3AED',
            borderRadius: 6,
            borderSkipped: false,
            barPercentage: 0.55,
        },{
            label: 'Max',
            data: Array(<?php echo count($subjects_chart); ?>).fill(100),
            backgroundColor: '#EDE9FE',
            borderRadius: 6,
            borderSkipped: false,
            barPercentage: 0.55,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, stacked: false, ticks: { font: { size: 11, family: 'Inter' }, color: '#9CA3AF' } },
            y: { max: 100, grid: { color: '#F3F4F6' }, ticks: { font: { size: 11, family: 'Inter' }, color: '#9CA3AF' } }
        }
    }
});

// GPA Line Chart
new Chart(document.getElementById('gpaLine'), {
    type: 'line',
    data: {
        labels: ['Sem 1','Sem 2','Sem 3','Sem 4'],
        datasets: [{
            data: [3.42, 3.65, 3.58, 3.82],
            borderColor: '#7C3AED',
            backgroundColor: 'rgba(124,58,237,0.08)',
            borderWidth: 3,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#7C3AED',
            pointRadius: 5,
            tension: 0.4, fill: true,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10, family: 'Inter' }, color: '#9CA3AF' } },
            y: { min: 3, max: 4, grid: { color: '#F3F4F6' }, ticks: { font: { size: 10, family: 'Inter' }, color: '#9CA3AF' } }
        }
    }
});
</script>
</body>
</html>
