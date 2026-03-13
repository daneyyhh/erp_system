<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Academic Timetable | Project ERP";

// Static Timetable Data based on BCA Semester
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
// If Weekend, default to Monday
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
    <style>
        .tt-day-card {
            cursor: pointer;
            transition: 0.3s;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            background: #fff;
            border: 2px solid transparent;
        }
        .tt-day-card:hover { transform: translateY(-5px); border-color: #4f46e5; }
        .tt-day-card.active { background: #4f46e5; color: white; border-color: #4f46e5; box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2); }
        .tt-day-card.active .text-muted { color: rgba(255,255,255,0.8) !important; }
        
        .class-slot {
            background: #fff;
            border-radius: 25px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            transition: 0.2s;
        }
        .class-slot:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .slot-time { width: 150px; flex-shrink: 0; }
        .slot-details { flex: 1; border-left: 2px solid #f1f5f9; padding-left: 30px; margin-left: 30px; }
        
        .type-badge {
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 800;
        }
        .type-lecture { background: #e0e7ff; color: #4338ca; }
        .type-lab { background: #dcfce7; color: #15803d; }
        .type-seminar { background: #fef3c7; color: #b45309; }
    </style>
</head>
<body style="background: #f8fafc;">
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-800 mb-1">Academic Calendar</h2>
                        <p class="text-muted small">Standard class hours: 09:00 AM - 03:00 PM</p>
                    </div>
                </div>

                <!-- Day Selector -->
                <div class="row g-3 mb-5">
                    <?php 
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                    // Robust Anchor: Get Monday of current week regardless of today's day
                    $today_ts = time();
                    $day_offset = date('N', $today_ts) - 1; // 0 for Mon, 4 for Fri
                    $monday_ts = $today_ts - ($day_offset * 86400);

                    foreach($days as $index => $day): 
                        $is_active = ($day == $selected_day);
                        $day_ts = $monday_ts + ($index * 86400);
                    ?>
                    <div class="col">
                        <a href="?day=<?php echo $day; ?>" class="text-decoration-none">
                            <div class="tt-day-card shadow-sm <?php echo $is_active ? 'active' : ''; ?>">
                                <div class="smallest ls-1 uppercase fw-bold mb-1 <?php echo $is_active ? '' : 'text-muted'; ?>">
                                    <?php echo substr($day, 0, 3); ?>
                                </div>
                                <h5 class="fw-800 mb-0"><?php echo date('d', $day_ts); ?></h5>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Selected Day Schedule -->
                <div class="schedule-container">
                    <h5 class="fw-800 mb-4 px-2">Schedule for <?php echo $selected_day; ?></h5>
                    
                    <?php if(empty($day_classes)): ?>
                        <div class="card border-0 p-5 text-center shadow-sm" style="border-radius: 30px;">
                            <i class="fas fa-calendar-times text-muted fs-1 mb-3"></i>
                            <p class="text-muted">No classes scheduled for today.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($day_classes as $class): ?>
                        <div class="class-slot shadow-sm">
                            <div class="slot-time text-center">
                                <h5 class="fw-800 mb-1"><?php echo explode(' ', $class[0])[0]; ?></h5>
                                <div class="smallest text-muted fw-bold"><?php echo explode(' ', $class[0])[2]; ?> PM</div>
                            </div>
                            <div class="slot-details">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="fw-800 mb-0"><?php echo $class[1]; ?></h5>
                                    <span class="type-badge type-<?php echo $class[4]; ?>"><?php echo $class[4]; ?></span>
                                </div>
                                <div class="d-flex gap-4">
                                    <span class="smallest text-muted fw-bold"><i class="fas fa-map-marker-alt me-2 text-primary"></i> <?php echo $class[2]; ?></span>
                                    <span class="smallest text-muted fw-bold"><i class="fas fa-user-tie me-2 text-primary"></i> <?php echo $class[3]; ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
