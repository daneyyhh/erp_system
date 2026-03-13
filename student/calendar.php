<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Academic Calendar | Project ERP";

// Calendar Logic
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$numberDays = date('t', $firstDayOfMonth);
$dateComponents = getdate($firstDayOfMonth);
$monthName = $dateComponents['month'];
$dayOfWeek = $dateComponents['wday'];

// Adjust for actual week start
$prevMonth = date('m', strtotime('-1 month', $firstDayOfMonth));
$prevYear = date('Y', strtotime('-1 month', $firstDayOfMonth));
$nextMonth = date('m', strtotime('+1 month', $firstDayOfMonth));
$nextYear = date('Y', strtotime('+1 month', $firstDayOfMonth));
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
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 15px;
        }
        .calendar-day-header {
            text-align: center;
            font-weight: 800;
            padding: 20px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 2px;
        }
        .calendar-day-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .calendar-day {
            min-height: 140px;
            background: var(--bg-card);
            border-radius: 24px;
            padding: 18px;
            border: 1px solid var(--border);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        .calendar-day:hover {
            border-color: var(--primary);
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            background: var(--bg-card);
        }
        .calendar-day.today {
            border-color: var(--primary);
            background: var(--primary-soft);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.15);
        }
        .calendar-day.weekend {
            background: var(--bg-page);
            opacity: 0.6;
        }
        .day-number {
            font-weight: 900;
            font-size: 1.4rem;
            margin-bottom: 12px;
            display: block;
            color: var(--text-main);
        }
        .calendar-event {
            font-size: 0.75rem;
            padding: 6px 10px;
            border-radius: 10px;
            background: var(--primary);
            color: white;
            margin-top: 6px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .holiday {
            background: #fee2e2;
            color: #b91c1c;
        }
        .timetable-hint {
            position: absolute;
            bottom: 10px;
            right: 15px;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--primary);
            opacity: 0;
            transition: 0.3s;
        }
        .calendar-day:hover .timetable-hint { opacity: 1; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h1 class="fw-800 mb-1" style="letter-spacing: -2px;"><?php echo $monthName . ' ' . $year; ?></h1>
                        <p class="text-muted small fw-600">Plan your academic activities and track holidays. Click any day to view timetable.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-outline-primary shadow-sm rounded-4 px-4 py-2 border-2">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-outline-primary shadow-sm rounded-4 px-4 py-2 border-2">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>

                <div class="calendar-grid animate-up">
                    <?php
                    $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    $full_days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    foreach($days as $day) echo "<div class='calendar-day-header'>$day</div>";

                    for($i = 0; $i < $dayOfWeek; $i++) echo "<div></div>";

                    $today = date('Y-m-d');

                    for($day = 1; $day <= $numberDays; $day++) {
                        $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $isToday = ($currentDate == $today) ? 'today' : '';
                        $currentDayOfWeek = ($dayOfWeek + $day - 1) % 7;
                        $isWeekend = ($currentDayOfWeek == 0 || $currentDayOfWeek == 6) ? 'weekend' : '';
                        $dayName = $full_days[$currentDayOfWeek];
                        
                        echo "<a href='timetable.php?day=$dayName' class='calendar-day-link'>";
                        echo "<div class='calendar-day $isToday $isWeekend'>";
                        echo "<span class='day-number'>$day</span>";
                        
                        if($isWeekend) {
                            echo "<div class='calendar-event holiday'>Campus Holiday</div>";
                        } else {
                            echo "<div class='calendar-event' style='background: #e0e7ff; color: #4338ca;'>Academic Session</div>";
                        }
                        
                        // Example events
                        if($day == 15) echo "<div class='calendar-event' style='background: #fef3c7; color: #b45309;'>Board Meeting</div>";
                        if($day == 25) echo "<div class='calendar-event' style='background: #dcfce7; color: #15803d;'>Festival Break</div>";
                        
                        echo "<div class='timetable-hint'>View Schedule <i class='fas fa-arrow-right ms-1'></i></div>";
                        echo "</div>";
                        echo "</a>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
