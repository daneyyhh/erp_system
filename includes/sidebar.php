<?php
$current_role = $_SESSION['user_role'] ?? 'student';
?>
<div class="sidebar">
    <div class="brand-area mb-5 px-3">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary p-2 rounded-3 text-white">
                <i class="fas fa-graduation-cap fs-4"></i>
            </div>
            <span class="fw-800 fs-4 text-dark mb-0">Project ERP</span>
        </div>
    </div>

    <div class="nav-links flex-grow-1 overflow-auto">
        <!-- GLOBAL -->
        <span class="nav-group-label">General</span>
        <a href="../<?php echo $current_role; ?>/dashboard.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false ? 'active' : ''; ?>">
            <i class="fas fa-grid-2"></i> Dashboard
        </a>

        <?php if ($current_role == 'student'): ?>
            <span class="nav-group-label">Academic</span>
            <a href="my_class.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'my_class.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users-rectangle"></i> My Class
            </a>
            <a href="timetable.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'timetable.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-week"></i> Timetable
            </a>
            <a href="calendar.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'calendar.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-days"></i> Academic Calendar
            </a>
            <a href="../admin/students_list.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'students_list.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users-viewfinder"></i> People Directory
            </a>
            
            <span class="nav-group-label">Logistics</span>
            <a href="exams.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'exams.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-file-signature"></i> Examination Desk
            </a>
            <a href="payments.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'payments.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-wallet"></i> Tuition Fees
            </a>
            
            <span class="nav-group-label">Documents</span>
            <a href="certificates.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'certificates.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice"></i> Cert Requests
            </a>
        <?php endif; ?>

        <?php if ($current_role == 'teacher'): ?>
            <span class="nav-group-label">Academic Support</span>
            <a href="timetable.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'timetable.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-week"></i> My Schedule
            </a>
            <a href="calendar.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'calendar.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-days"></i> Academic Calendar
            </a>
            <a href="attendance.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'attendance.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-user"></i> Roll Call
            </a>
            <a href="../admin/students_list.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'students_list.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users-viewfinder"></i> People Directory
            </a>
            <span class="nav-group-label">Management</span>
            <a href="certificates.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'certificates.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-stamp"></i> Document Approvals
            </a>
            <a href="fees_overview.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'fees_overview.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar"></i> Students Fees
            </a>
        <?php endif; ?>

        <?php if ($current_role == 'admin'): ?>
            <span class="nav-group-label">Administration</span>
            <a href="students_list.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'students_list.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users-viewfinder"></i> Identity Manager
            </a>
            <a href="certificates.php" class="nav-link">
                <i class="fas fa-check-double"></i> Approvals
            </a>
            <a href="fees_overall.php" class="nav-link">
                <i class="fas fa-money-check-dollar"></i> Fees Overall
            </a>
            <a href="logs.php" class="nav-link">
                <i class="fas fa-terminal"></i> System Logs
            </a>
        <?php endif; ?>

        <?php if ($current_role == 'parent'): ?>
            <span class="nav-group-label">Ward Tracking</span>
            <a href="timetable.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'timetable.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-week"></i> Ward Timetable
            </a>
            <a href="attendance.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'attendance.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user-check"></i> Attendance
            </a>
            <a href="marks.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'marks.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-award"></i> Exam Marks
            </a>
            <a href="../admin/students_list.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'students_list.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users-viewfinder"></i> People Directory
            </a>
        <?php endif; ?>

        <span class="nav-group-label">Preferences</span>
        <a href="../student/settings.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'settings.php') !== false ? 'active' : ''; ?>">
            <i class="fas fa-gear"></i> Settings
        </a>
    </div>

    <div class="sidebar-footer mt-auto pt-4 border-top">
        <a href="../auth/logout.php" class="nav-link text-danger">
            <i class="fas fa-power-off"></i> Sign Out
        </a>
    </div>
</div>
