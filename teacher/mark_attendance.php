<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');
require_once('../utils/logger.php');

$class = $_GET['class'] ?? 'BCA 1st Year';
$subject_id = $_GET['subject_id'] ?? '1';
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Fetch Subject Details
$stmt_sub = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt_sub->execute([$subject_id]);
$subject = $stmt_sub->fetch();

// Handle Save Attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $attendance_data = $_POST['attendance'] ?? [];
    foreach ($attendance_data as $student_id => $status) {
        // Check if record exists
        $check = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND subject_id = ? AND date = ?");
        $check->execute([$student_id, $subject_id, $selected_date]);
        $existing = $check->fetch();

        if ($existing) {
            $update = $pdo->prepare("UPDATE attendance SET status = ? WHERE id = ?");
            $update->execute([$status, $existing['id']]);
        } else {
            $insert = $pdo->prepare("INSERT INTO attendance (student_id, subject_id, date, status) VALUES (?, ?, ?, ?)");
            $insert->execute([$student_id, $subject_id, $selected_date, $status]);
        }
    }
    logToExcel('Attendance', $_SESSION['user_id'], $_SESSION['user_name'], "Saved attendance for $class - " . ($subject['name'] ?? '') . " on $selected_date");
    $success_msg = "Attendance saved and synced successfully!";
}

// Fetch Students and their existing attendance for the selected date
$stmt = $pdo->prepare("SELECT s.id as student_id, s.roll_no, u.name, a.status as current_status
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       LEFT JOIN attendance a ON s.id = a.student_id AND a.subject_id = ? AND a.date = ?
                       WHERE s.class = ? 
                       ORDER BY u.name ASC");
$stmt->execute([$subject_id, $selected_date, $class]);
$students = $stmt->fetchAll();

// Calendar Logic
$month = date('m', strtotime($selected_date));
$year = date('Y', strtotime($selected_date));
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day_of_month = date('w', strtotime("$year-$month-01"));
$month_name = date('F', strtotime($selected_date));

$page_title = "Attendance Portal | Scholarly";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .attendance-v2-container { display: flex; gap: 30px; }
        .attendance-sidebar { width: 320px; flex-shrink: 0; }
        .attendance-main { flex: 1; }
        
        .calendar-card {
            background: #fff;
            border-radius: 24px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }
        
        .status-btn-group {
            display: flex;
            gap: 8px;
            background: #f8fafc;
            padding: 4px;
            border-radius: 12px;
        }
        
        .status-btn {
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            gap: 6px;
            background: transparent;
            color: #64748b;
            transition: 0.2s;
        }
        
        .status-btn.present.active { background: #dcfce7; color: #15803d; }
        .status-btn.absent.active { background: #fee2e2; color: #b91c1c; }
        .status-btn.late.active { background: #fef3c7; color: #b45309; }
        
        .student-row td { padding: 1.5rem 1rem !important; }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            text-align: center;
        }
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            color: #64748b;
        }
        .calendar-day:hover { background: #f1f5f9; }
        .calendar-day.active { background: #4f46e5 !important; color: white !important; }
        
        .action-icon {
            cursor: pointer;
            transition: 0.2s;
            padding: 8px;
            border-radius: 8px;
        }
        .action-icon:hover {
            background: #f1f5f9;
            color: #4f46e5;
        }
    </style>
</head>
<body style="background: #f8fafc;">
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <div class="content-area">
                
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <h2 class="fw-800 mb-0">Attendance Marking</h2>
                    <div class="d-flex gap-3 align-items-center">
                        <div class="bg-white rounded-circle p-2 shadow-sm"><i class="fas fa-bell"></i></div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="text-end">
                                <div class="fw-bold small"><?php echo $_SESSION['user_name']; ?></div>
                                <div class="smallest text-muted">Faculty ID: 5501234</div>
                            </div>
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Archie" width="35" class="rounded-circle">
                        </div>
                    </div>
                </div>

                <?php if(isset($success_msg)): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius: 15px;">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?>
                </div>
                <?php endif; ?>

                <div class="attendance-v2-container">
                    <!-- LEFT SIDEBAR -->
                    <div class="attendance-sidebar">
                        <div class="calendar-card shadow-sm border">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <b class="small"><?php echo $month_name . " " . $year; ?></b>
                                <div>
                                    <a href="?class=<?php echo urlencode($class); ?>&subject_id=<?php echo $subject_id; ?>&date=<?php echo date('Y-m-d', strtotime('-1 month', strtotime($selected_date))); ?>" class="text-muted"><i class="fas fa-chevron-left small mx-2"></i></a>
                                    <a href="?class=<?php echo urlencode($class); ?>&subject_id=<?php echo $subject_id; ?>&date=<?php echo date('Y-m-d', strtotime('+1 month', strtotime($selected_date))); ?>" class="text-muted"><i class="fas fa-chevron-right small"></i></a>
                                </div>
                            </div>
                            <div class="calendar-grid text-muted smallest mb-2">
                                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                            </div>
                            <div class="calendar-grid">
                                <?php 
                                // Empty slots for first day
                                for($i=0; $i<$first_day_of_month; $i++) echo '<div></div>';
                                
                                for($i=1; $i<=$days_in_month; $i++): 
                                    $day_date = "$year-$month-" . str_pad($i, 2, '0', STR_PAD_LEFT);
                                    $is_active = ($day_date == $selected_date) ? 'active' : '';
                                ?>
                                    <a href="?class=<?php echo urlencode($class); ?>&subject_id=<?php echo $subject_id; ?>&date=<?php echo $day_date; ?>" 
                                       class="calendar-day <?php echo $is_active; ?>">
                                       <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="card border shadow-sm p-4" style="border-radius: 24px;">
                            <div class="mb-4">
                                <label class="smallest fw-bold text-muted uppercase">Course</label>
                                <select class="form-select border-0 shadow-sm mt-1" disabled>
                                    <option><?php echo $class; ?></option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="smallest fw-bold text-muted uppercase">Subject</label>
                                <select class="form-select border-0 shadow-sm mt-1" disabled>
                                    <option><?php echo $subject['name'] ?? 'Basics of User Research'; ?></option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="smallest fw-bold text-muted uppercase">Semester</label>
                                <select class="form-select border-0 shadow-sm mt-1" disabled>
                                    <option>Semester <?php echo $subject['semester'] ?? '1'; ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- MAIN ATTENDANCE AREA -->
                    <div class="attendance-main">
                        <form method="POST">
                            <div class="card border-0 shadow-sm p-5" style="border-radius: 30px;">
                                <div class="d-flex justify-content-between align-items-start mb-5">
                                    <div>
                                        <h5 class="fw-800 mb-1"><a href="attendance.php" class="text-decoration-none"><i class="fas fa-arrow-left me-3 text-muted"></i></a> Mark Attendance</h5>
                                        <div class="mt-4">
                                            <h4 class="fw-800 mb-0"><?php echo $subject['name'] ?? 'Basics of User Research'; ?> (<?php echo $subject['code'] ?? 'BB5012'; ?>)</h4>
                                            <p class="text-muted small"><?php echo $class; ?> • <?php echo date('l, d M Y', strtotime($selected_date)); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="mb-3">
                                            <a href="../logs/system_logs.csv" class="btn btn-outline-primary btn-sm me-2 border-0 shadow-sm" style="border-radius:10px;"><i class="fas fa-download me-2"></i> Download Exl</a>
                                            <span class="badge bg-<?php echo count($students) > 0 ? 'success' : 'warning'; ?> bg-opacity-10 text-<?php echo count($students) > 0 ? 'success' : 'warning'; ?> px-3 py-2 fw-bold">
                                                <?php 
                                                    $marked_count = 0;
                                                    foreach($students as $s) if($s['current_status']) $marked_count++;
                                                    echo ($marked_count == count($students)) ? 'Completed' : 'Partial';
                                                ?>
                                            </span>
                                        </div>
                                        <button type="submit" name="save_attendance" class="btn btn-primary px-4 py-2 shadow-sm fw-bold" style="border-radius:12px;">Save Changes</button>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr class="text-muted small">
                                                <th>STUDENT ID</th>
                                                <th>STUDENT NAME</th>
                                                <th>STATUS</th>
                                                <th class="text-end">ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($students as $s): 
                                                $status = $s['current_status'] ?? 'present';
                                            ?>
                                            <tr class="student-row">
                                                <td><b class="text-primary"><?php echo $s['roll_no']; ?></b></td>
                                                <td class="fw-bold"><?php echo $s['name']; ?></td>
                                                <td>
                                                    <div class="status-btn-group">
                                                        <input type="hidden" name="attendance[<?php echo $s['student_id']; ?>]" value="<?php echo $status; ?>" id="input-<?php echo $s['student_id']; ?>">
                                                        <button type="button" class="status-btn present <?php echo ($status=='present')?'active':''; ?>" onclick="setStatus(<?php echo $s['student_id']; ?>, 'present', this)">
                                                            <i class="fas fa-check-circle"></i> Present
                                                        </button>
                                                        <button type="button" class="status-btn absent <?php echo ($status=='absent')?'active':''; ?>" onclick="setStatus(<?php echo $s['student_id']; ?>, 'absent', this)">
                                                            <i class="fas fa-times-circle"></i> Absent
                                                        </button>
                                                        <button type="button" class="status-btn late <?php echo ($status=='late')?'active':''; ?>" onclick="setStatus(<?php echo $s['student_id']; ?>, 'late', this)">
                                                            <i class="fas fa-clock"></i> Late
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <i class="fas fa-edit text-muted me-3 action-icon" onclick="editRemarks('<?php echo $s['name']; ?>')"></i>
                                                    <i class="fas fa-chart-bar text-muted action-icon" onclick="viewHistory('<?php echo $s['name']; ?>')"></i>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
    function setStatus(studentId, status, el) {
        document.getElementById('input-' + studentId).value = status;
        const group = el.parentElement;
        group.querySelectorAll('.status-btn').forEach(btn => btn.classList.remove('active'));
        el.classList.add('active');
    }

    function editRemarks(name) {
        alert("Opening quick remarks editor for " + name);
    }

    function viewHistory(name) {
        alert("Viewing attendance performance trends for " + name);
    }
    </script>
</body>
</html>
