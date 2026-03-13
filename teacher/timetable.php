<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "My Academic Schedule | Scholarly";

// Static Teacher Timetable (Classes assigned to this teacher)
$full_timetable = [
    'Monday' => [
        ['09:00 - 10:30', 'BCA 1st Year', 'Lab 01', 'Core Programming', 'lecture'],
        ['11:00 - 12:30', 'MCA 2nd Year', 'Room 302', 'Data Structures', 'lecture'],
        ['01:30 - 03:00', 'BCA 3rd Year', 'Seminar Hall', 'System Design', 'seminar']
    ],
    'Tuesday' => [
        ['09:00 - 10:30', 'BCA 2nd Year', 'Room 202', 'Web Development', 'lab'],
        ['11:00 - 12:30', 'BCA 1st Year', 'Lab 01', 'Core Programming', 'lecture']
    ],
    'Wednesday' => [
        ['09:00 - 10:30', 'MCA 1st Year', 'Room 405', 'Cloud Architecture', 'lecture'],
        ['01:30 - 03:00', 'BCA 3rd Year', 'Seminar Hall', 'System Design', 'seminar']
    ],
    'Thursday' => [
        ['11:00 - 12:30', 'BCA 2nd Year', 'Room 202', 'Web Development', 'lab'],
        ['01:30 - 03:00', 'MCA 2nd Year', 'Room 302', 'Data Structures', 'lecture']
    ],
    'Friday' => [
        ['09:00 - 10:30', 'BCA 1st Year', 'Lab 01', 'Core Programming', 'lecture'],
        ['11:00 - 12:30', 'Department Meeting', 'Conf. Room', 'Academic Audit', 'meeting']
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
        .type-meeting { background: #fee2e2; color: #b91c1c; }
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
                        <h2 class="fw-800 mb-1">Faculty Schedule</h2>
                        <p class="text-muted small">Viewing assigned lecture hours (09:00 AM - 03:00 PM)</p>
                    </div>
                </div>

                <!-- Day Selector -->
                <div class="row g-3 mb-5">
                    <?php 
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                    // Robust Anchor: Get Monday of current week
                    $today_ts = time();
                    $day_offset = date('N', $today_ts) - 1; 
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
                    <h5 class="fw-800 mb-4 px-2">Lectures for <?php echo $selected_day; ?></h5>
                    
                    <?php if(empty($day_classes)): ?>
                        <div class="card border-0 p-5 text-center shadow-sm" style="border-radius: 30px;">
                            <i class="fas fa-calendar-check text-success fs-1 mb-3"></i>
                            <p class="text-muted fw-bold">No lectures assigned for today. (Free Slot)</p>
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
                                    <div>
                                        <h5 class="fw-800 mb-0 text-primary"><?php echo $class[3]; ?></h5>
                                        <p class="small fw-bold mb-0"><?php echo $class[1]; ?></p>
                                    </div>
                                    <span class="type-badge type-<?php echo $class[4]; ?>"><?php echo $class[4]; ?></span>
                                </div>
                                <div class="d-flex gap-4">
                                    <span class="smallest text-muted fw-bold"><i class="fas fa-map-marker-alt me-2 text-primary"></i> <?php echo $class[2]; ?></span>
                                    <span class="smallest text-muted fw-bold"><i class="fas fa-clock me-2 text-primary"></i> Duration: 1.5 Hrs</span>
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
