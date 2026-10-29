<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Academic Calendar | Project ERP";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <style>
        .fc-theme-standard .fc-scrollgrid { border-radius: 16px; overflow: hidden; border: 1px solid var(--border); }
        .fc .fc-toolbar-title { font-weight: 800; font-family: 'Playfair Display', serif; color: var(--primary); font-size: 2rem; letter-spacing: -1px; }
        .fc .fc-button-primary { background-color: var(--primary); border-color: var(--primary); border-radius: 12px; font-weight: 800; padding: 0.5rem 1rem; text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; }
        .fc .fc-button-primary:not(:disabled):active, .fc .fc-button-primary:not(:disabled).fc-button-active { background-color: var(--primary-hover); border-color: var(--primary-hover); }
        .fc .fc-daygrid-day.fc-day-today { background-color: var(--primary-soft); }
        .fc .fc-col-header-cell-cushion { font-weight: 900; color: var(--text-main); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px; }
        .fc .fc-daygrid-day-number { font-weight: 800; color: var(--text-muted); padding: 10px; font-size: 1.1rem; }
        .fc-event { border: none !important; border-radius: 8px !important; font-weight: 700; padding: 4px 8px !important; cursor: pointer; transition: 0.2s; box-shadow: var(--shadow-sm); }
        .fc-event:hover { transform: translateY(-2px); box-shadow: var(--shadow); filter: brightness(1.1); }
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
                        <h1 class="fw-800 mb-1" style="letter-spacing: -2px;">Interactive Academic Calendar</h1>
                        <p class="text-muted small fw-600">Drag, drop, switch views, and click on events for deep Academic insights.</p>
                    </div>
                </div>

                <div class="card-premium p-4 animate-up">
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                themeSystem: 'standard',
                height: 800,
                navLinks: true, // can click day/week names to navigate views
                editable: true, // Interactive drag/drop!
                dayMaxEvents: true, // allow "more" link when too many events
                events: [
                    {
                        title: 'Mid-term Exams Start',
                        start: new Date(new Date().getFullYear(), new Date().getMonth(), 15),
                        end: new Date(new Date().getFullYear(), new Date().getMonth(), 18),
                        backgroundColor: '#dc2626', // Danger red
                    },
                    {
                        title: 'Board Meeting',
                        start: new Date(new Date().getFullYear(), new Date().getMonth(), 12, 10, 30, 0),
                        backgroundColor: '#d97706', // Warning orange
                    },
                    {
                        title: 'Festival Break',
                        start: new Date(new Date().getFullYear(), new Date().getMonth(), 25),
                        end: new Date(new Date().getFullYear(), new Date().getMonth(), 27),
                        backgroundColor: '#059669', // Success green
                    },
                    {
                        title: 'Thesis Submission',
                        start: new Date(new Date().getFullYear(), new Date().getMonth() + 1, 5),
                        backgroundColor: '#4338ca', // Primary indigo
                    }
                ],
                eventClick: function(info) {
                    alert('Event: ' + info.event.title + '\nStart: ' + info.event.start);
                }
            });
            
            calendar.render();
        });
    </script>
</body>
</html>
