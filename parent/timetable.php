<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Ward Timetable | Enlight";

// Static Timetable Data (Same as student for now, in real app fetch based on ward_id)
$full_timetable = [
    'Monday' => [
        ['09:00 - 10:30', 'Development (Core)', 'Lab 01', 'Prof. Smith', 'lecture'],
        ['11:00 - 12:30', 'Network Security', 'Room 302', 'Prof. Davis', 'seminar'],
        ['01:30 - 03:00', 'Mathematics IV', 'Room 101', 'Prof. Wilson', 'lecture']
    ],
    'Tuesday' => [
        ['09:00 - 10:30', 'Software Architecture', 'Seminar Hall', 'Ms. Clark', 'seminar'],
        ['11:00 - 12:30', 'Database Management', 'Lab 02', 'Prof. Brown', 'lecture'],
        ['01:30 - 03:00', 'User Experience Design', 'Creative Studio', 'Ms. White', 'lab']
    ],
    'Wednesday' => [
        ['09:00 - 10:30', 'Cloud Computing', 'Room 405', 'Prof. Miller', 'lecture'],
        ['11:00 - 12:30', 'Data Structures', 'Lab 01', 'Mr. Taylor', 'lab'],
        ['01:30 - 03:00', 'English Comm.', 'Room 202', 'Ms. Moore', 'seminar']
    ],
    'Thursday' => [
        ['09:00 - 10:30', 'Cyber Ethics', 'Room 101', 'Prof. Anderson', 'lecture'],
        ['11:00 - 12:30', 'Python Automation', 'Lab 03', 'Mr. Garcia', 'lab'],
        ['01:30 - 03:00', 'Cloud Computing', 'Room 405', 'Prof. Miller', 'lecture']
    ],
    'Friday' => [
        ['09:00 - 10:30', 'Final Project Review', 'Auditorium', 'Panel A', 'seminar'],
        ['11:00 - 12:30', 'Mobile App Dev', 'Lab 03', 'Mr. Martinez', 'lab'],
        ['01:30 - 03:00', 'Seminar: AI Trends', 'Seminar Hall', 'Guest Speaker', 'lecture']
    ]
];

$selected_day = $_GET['day'] ?? date('l');
if (!array_key_exists($selected_day, $full_timetable)) $selected_day = 'Monday';

$day_classes = $full_timetable[$selected_day];
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
                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Ward Academic Schedule</h2>
                        <p class="text-muted small fw-600">Monitoring class hours for John Doe</p>
                    </div>
                </div>

                <!-- Day Selector -->
                <div class="row g-3 mb-5 animate-up">
                    <?php 
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                    $monday_ts = strtotime('monday this week');
                    foreach($days as $index => $day): 
                        $is_active = ($day == $selected_day);
                        $day_ts = strtotime("+$index days", $monday_ts);
                    ?>
                    <div class="col">
                        <a href="?day=<?php echo $day; ?>" class="text-decoration-none">
                            <div class="tt-day-card shadow-sm <?php echo $is_active ? 'active' : ''; ?>" style="border-radius: 20px; padding: 20px; text-align: center; background: <?php echo $is_active ? 'var(--primary)' : 'var(--bg-card)'; ?>; color: <?php echo $is_active ? '#fff' : 'inherit'; ?>; border: 1px solid var(--border);">
                                <div class="smallest ls-1 uppercase fw-bold mb-1 <?php echo $is_active ? '' : 'text-muted'; ?>">
                                    <?php echo substr($day, 0, 3); ?>
                                </div>
                                <h5 class="fw-800 mb-0"><?php echo date('d', $day_ts); ?></h5>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="schedule-container animate-up">
                    <h5 class="fw-800 mb-4 px-2">Lectures for <?php echo $selected_day; ?></h5>
                    <?php foreach($day_classes as $class): ?>
                    <div class="card-premium mb-4 border-0 shadow-sm" style="padding: 1.5rem;">
                        <div class="d-flex align-items-center">
                            <div class="text-center pe-4 border-end" style="min-width: 120px;">
                                <h5 class="fw-800 mb-0"><?php echo explode(' ', $class[0])[0]; ?></h5>
                                <div class="smallest text-muted fw-bold">AM</div>
                            </div>
                            <div class="ps-4 flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <h5 class="fw-800 mb-1 text-primary"><?php echo $class[1]; ?></h5>
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-1 rounded-3 small"><?php echo strtoupper($class[4]); ?></span>
                                </div>
                                <div class="d-flex gap-4">
                                    <span class="smallest text-muted fw-bold"><i class="fas fa-map-marker-alt me-2 text-primary"></i> <?php echo $class[2]; ?></span>
                                    <span class="smallest text-muted fw-bold"><i class="fas fa-user-tie me-2 text-primary"></i> <?php echo $class[3]; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
