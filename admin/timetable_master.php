<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Master Timetable | Project ERP";
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $class      = $_POST['class'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $teacher_id = $_POST['teacher_id'] ?? '';
    $day        = $_POST['day'] ?? '';
    $time_slot  = $_POST['time_slot'] ?? '';
    $room       = $_POST['room'] ?? '';
    try {
        $stmt = $pdo->prepare("INSERT INTO timetable (class, subject_id, teacher_id, day, time_slot, room) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$class, $subject_id, $teacher_id, $day, $time_slot, $room]);
        $success_msg = "Class scheduled successfully!";
    } catch (PDOException $e) {
        $error_msg = "Error: " . $e->getMessage();
    }
}

if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM timetable WHERE id = ?")->execute([$_GET['delete_id']]);
    $success_msg = "Time slot removed.";
}

$classes = $pdo->query("
    SELECT tt.id, tt.class, tt.day, tt.time_slot, tt.room,
           s.name as subject_name, s.code as subject_code,
           u.name as teacher_name
    FROM timetable tt
    LEFT JOIN subjects s ON tt.subject_id = s.id
    LEFT JOIN teachers t ON tt.teacher_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY FIELD(tt.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), tt.time_slot ASC
")->fetchAll();

$available_subjects = $pdo->query("SELECT id, name, code FROM subjects ORDER BY name ASC")->fetchAll();
$available_teachers = $pdo->query("SELECT t.id, u.name, t.department FROM teachers t JOIN users u ON t.user_id = u.id ORDER BY u.name ASC")->fetchAll();

// Group by day
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$grouped = [];
foreach ($days as $d) $grouped[$d] = [];
foreach ($classes as $c) {
    $grouped[$c['day']][] = $c;
}

$day_colors = [
    'Monday'    => ['bg'=>'#F5F3FF','border'=>'#7C3AED','pill'=>'#7C3AED'],
    'Tuesday'   => ['bg'=>'#EFF6FF','border'=>'#3B82F6','pill'=>'#3B82F6'],
    'Wednesday' => ['bg'=>'#F0FDF4','border'=>'#10B981','pill'=>'#10B981'],
    'Thursday'  => ['bg'=>'#FFFBEB','border'=>'#F59E0B','pill'=>'#F59E0B'],
    'Friday'    => ['bg'=>'#FEF2F2','border'=>'#EF4444','pill'=>'#EF4444'],
    'Saturday'  => ['bg'=>'#F0F9FF','border'=>'#06B6D4','pill'=>'#06B6D4'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .day-col-header {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: 700; font-size: 13px;
            margin-bottom: 12px;
        }
        .slot-card {
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 10px;
            border-left: 4px solid transparent;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            transition: all 0.2s;
            position: relative;
        }
        .slot-card:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,0.1); }
        .slot-time {
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.5px;
            color: #6B7280; margin-bottom: 6px;
        }
        .slot-subject { font-size: 14px; font-weight: 700; color: #1E1B4B; margin-bottom: 3px; }
        .slot-meta { font-size: 12px; color: #6B7280; }
        .slot-room {
            position: absolute; top: 12px; right: 12px;
            font-size: 10.5px; font-weight: 700;
            padding: 3px 8px; border-radius: 6px;
            background: #F5F3FF; color: #7C3AED;
        }
        .delete-btn {
            position: absolute; bottom: 10px; right: 12px;
            font-size: 11px; color: #EF4444; text-decoration: none;
            font-weight: 600;
        }
        .delete-btn:hover { color: #DC2626; }
        .empty-day {
            padding: 20px;
            border-radius: 12px;
            border: 2px dashed #EDE9FE;
            text-align: center;
            color: #C4B5FD;
            font-size: 12px; font-weight: 600;
        }
        .day-col { min-width: 170px; }
        .timetable-grid { overflow-x: auto; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <div class="main-content">
        <?php include('../includes/topbar.php'); ?>
        <div class="content-area">

            <?php if($success_msg): ?>
                <div class="alert alert-success animate-up mb-4"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if($error_msg): ?>
                <div class="alert alert-danger animate-up mb-4"><i class="fas fa-exclamation-triangle"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-800 mb-1">Master Timetable</h2>
                    <p class="text-muted" style="margin:0;font-size:13px;">Weekly class schedule across all sections</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                    <i class="fas fa-calendar-plus"></i> Add Slot
                </button>
            </div>

            <!-- Stats row -->
            <div class="d-flex gap-3 mb-4 flex-wrap">
                <?php
                $total = count($classes);
                $days_active = count(array_filter($grouped, fn($g) => count($g) > 0));
                ?>
                <div style="background:#fff;border:1.5px solid #EDE9FE;border-radius:12px;padding:14px 20px;display:flex;align-items:center;gap:12px;">
                    <div style="width:36px;height:36px;background:#F5F3FF;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#7C3AED;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <div style="font-size:20px;font-weight:800;color:#1E1B4B;"><?php echo $total; ?></div>
                        <div style="font-size:12px;color:#6B7280;">Total Slots</div>
                    </div>
                </div>
                <div style="background:#fff;border:1.5px solid #EDE9FE;border-radius:12px;padding:14px 20px;display:flex;align-items:center;gap:12px;">
                    <div style="width:36px;height:36px;background:#F0FDF4;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#10B981;">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div>
                        <div style="font-size:20px;font-weight:800;color:#1E1B4B;"><?php echo $days_active; ?></div>
                        <div style="font-size:12px;color:#6B7280;">Active Days</div>
                    </div>
                </div>
                <div style="background:#fff;border:1.5px solid #EDE9FE;border-radius:12px;padding:14px 20px;display:flex;align-items:center;gap:12px;">
                    <div style="width:36px;height:36px;background:#EFF6FF;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#3B82F6;">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div>
                        <div style="font-size:20px;font-weight:800;color:#1E1B4B;"><?php echo count($available_teachers); ?></div>
                        <div style="font-size:12px;color:#6B7280;">Faculty Assigned</div>
                    </div>
                </div>
            </div>

            <?php if ($total === 0): ?>
            <!-- Empty State -->
            <div style="background:#fff;border:1.5px solid #EDE9FE;border-radius:16px;padding:60px;text-align:center;">
                <div style="font-size:48px;color:#DDD6FE;margin-bottom:16px;"><i class="fas fa-calendar-times"></i></div>
                <h5 style="font-weight:700;color:#1E1B4B;margin-bottom:8px;">No schedules mapped yet</h5>
                <p style="color:#9CA3AF;margin-bottom:24px;">Click "Add Slot" to start building the weekly timetable.</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                    <i class="fas fa-plus"></i> Add First Slot
                </button>
            </div>
            <?php else: ?>
            <!-- Day-based Grid -->
            <div class="timetable-grid">
                <div class="d-flex gap-3" style="min-width:1020px;">
                    <?php foreach ($days as $day):
                        $col = $day_colors[$day];
                        $slots = $grouped[$day];
                    ?>
                    <div class="day-col flex-fill">
                        <!-- Day Header -->
                        <div class="day-col-header" style="background:<?php echo $col['bg']; ?>; border:1.5px solid <?php echo $col['border']; ?>20; color:<?php echo $col['border']; ?>;">
                            <i class="fas fa-circle" style="font-size:8px;"></i>
                            <?php echo $day; ?>
                            <span style="margin-left:auto;background:<?php echo $col['border']; ?>;color:#fff;border-radius:9999px;padding:2px 8px;font-size:11px;font-weight:700;"><?php echo count($slots); ?></span>
                        </div>

                        <?php if (empty($slots)): ?>
                            <div class="empty-day"><i class="fas fa-plus-circle mb-1 d-block" style="font-size:20px;"></i>No classes</div>
                        <?php else: foreach ($slots as $s): ?>
                        <div class="slot-card" style="border-left-color:<?php echo $col['border']; ?>;">
                            <div class="slot-time"><i class="far fa-clock me-1"></i><?php echo htmlspecialchars($s['time_slot']); ?></div>
                            <div class="slot-subject"><?php echo htmlspecialchars($s['subject_name'] ?: 'N/A'); ?></div>
                            <div class="slot-meta">
                                <i class="fas fa-users me-1"></i><?php echo htmlspecialchars($s['class']); ?>
                            </div>
                            <div class="slot-meta mt-1">
                                <i class="fas fa-chalkboard-teacher me-1"></i><?php echo htmlspecialchars($s['teacher_name'] ?: 'TBA'); ?>
                            </div>
                            <div class="slot-room"><?php echo htmlspecialchars($s['room']); ?></div>
                            <a href="timetable_master.php?delete_id=<?php echo $s['id']; ?>"
                               class="delete-btn"
                               onclick="return confirm('Remove this slot?');">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Allocate New Class Slot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Class / Section</label>
                        <input type="text" name="class" class="form-control" required placeholder="e.g. BCA 3rd Year">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject / Course</label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">Select a Subject...</option>
                            <?php foreach($available_subjects as $sub): ?>
                                <option value="<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['code'] . ' - ' . $sub['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign Faculty</label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="">Select Instructor...</option>
                            <?php foreach($available_teachers as $tch): ?>
                                <option value="<?php echo $tch['id']; ?>"><?php echo htmlspecialchars($tch['name'] . ' (' . $tch['department'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Day</label>
                            <select name="day" class="form-select" required>
                                <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d): ?>
                                    <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" id="startTime" class="form-control" required value="09:00" oninput="updateSlotPreview()">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" id="endTime" class="form-control" required value="10:00" oninput="updateSlotPreview()">
                        </div>
                        <div class="col-12 mb-2">
                            <div id="slotPreview" style="background:#F5F3FF;border:1.5px solid #EDE9FE;border-radius:10px;padding:10px 16px;font-size:13px;font-weight:700;color:#7C3AED;display:flex;align-items:center;gap:8px;">
                                <i class="far fa-clock"></i>
                                <span id="slotPreviewText">09:00 AM – 10:00 AM</span>
                            </div>
                            <input type="hidden" name="time_slot" id="timeSlotHidden" value="09:00 AM - 10:00 AM">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Room / Location</label>
                        <input type="text" name="room" class="form-control" required placeholder="e.g. Lab 01">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> Save to Timetable
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function formatTimeTo12h(timeStr) {
    if (!timeStr) return '';
    var parts = timeStr.split(':');
    var h = parseInt(parts[0], 10);
    var m = parts[1];
    var ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return h + ':' + m + ' ' + ampm;
}
function updateSlotPreview() {
    var s = document.getElementById('startTime').value;
    var e = document.getElementById('endTime').value;
    var label = formatTimeTo12h(s) + ' – ' + formatTimeTo12h(e);
    document.getElementById('slotPreviewText').innerText = label;
    document.getElementById('timeSlotHidden').value = label;
}
document.addEventListener('DOMContentLoaded', function() { updateSlotPreview(); });
</script>
</body>
</html>
