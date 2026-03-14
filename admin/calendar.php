<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Academic Calendar | Project ERP";

$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$numberDays = date('t', $firstDayOfMonth);
$dateComponents = getdate($firstDayOfMonth);
$monthName = $dateComponents['month'];
$dayOfWeek = $dateComponents['wday'];

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
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
    <style>
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 15px; }
        .calendar-day-header { text-align: center; font-weight: 800; padding: 20px; color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 2px; }
        .calendar-day { min-height: 140px; background: #fff; border-radius: 24px; padding: 18px; border: 1px solid var(--border); position: relative; }
        .calendar-day.today { border-color: #8e24aa; background: #f3e5f5; }
        .calendar-day.weekend { background: #f8fafc; opacity: 0.6; }
        .day-number { font-weight: 900; font-size: 1.4rem; margin-bottom: 12px; display: block; color: var(--text-main); }
        .calendar-event { font-size: 0.75rem; padding: 6px 10px; border-radius: 10px; background: #8e24aa; color: white; margin-top: 6px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .holiday { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body style="background: #fdfcff;">
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h1 class="fw-800 mb-1" style="color: #1e1b4b;"><?php echo $monthName . ' ' . $year; ?></h1>
                        <p class="text-muted small fw-600">Institutional Calendar View.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn shadow-sm rounded-4 px-4 py-2" style="background: #fff; border: 1px solid #ede9fe; color: #8e24aa;"><i class="fas fa-chevron-left"></i></a>
                        <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn shadow-sm rounded-4 px-4 py-2" style="background: #fff; border: 1px solid #ede9fe; color: #8e24aa;"><i class="fas fa-chevron-right"></i></a>
                    </div>
                </div>
                <div class="calendar-grid">
                    <?php
                    $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    foreach($days as $day) echo "<div class='calendar-day-header'>$day</div>";
                    for($i = 0; $i < $dayOfWeek; $i++) echo "<div></div>";
                    $today = date('Y-m-d');
                    for($day = 1; $day <= $numberDays; $day++) {
                        $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $isToday = ($currentDate == $today) ? 'today' : '';
                        $currentDayOfWeek = ($dayOfWeek + $day - 1) % 7;
                        $isWeekend = ($currentDayOfWeek == 0 || $currentDayOfWeek == 6) ? 'weekend' : '';
                        
                        echo "<div class='calendar-day $isToday $isWeekend'>";
                        echo "<span class='day-number' style='color: #1e1b4b;'>$day</span>";
                        if($isWeekend) {
                            echo "<div class='calendar-event holiday'>Campus Holiday</div>";
                        }
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
