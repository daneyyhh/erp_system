<?php
// Determine active page for highlight
$current = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

function is_active($pages, $cur = null) {
    global $current;
    $cur = $cur ?? $current;
    return in_array($cur, (array)$pages) ? 'active' : '';
}
$role = strtolower($_SESSION['user_role'] ?? 'student');
$base = $role === 'admin' ? '../' : ($role === 'teacher' ? '../' : ($role === 'parent' ? '../' : '../'));
?>
<div class="sidebar">

    <!-- Logo -->
    <a href="<?php echo $base; ?>index.php" class="sidebar-logo">
        <div class="sidebar-logo-icon"><i class="fas fa-graduation-cap"></i></div>
        <span class="sidebar-logo-text">ia Academy</span>
    </a>

    <nav class="sidebar-nav">

        <?php if ($role === 'admin'): ?>
        <!-- ADMIN NAVIGATION -->
        <div class="sidebar-section-title">Main</div>

        <a href="../admin/dashboard.php" class="sidebar-item <?php echo is_active('dashboard.php'); ?>">
            <?php if (is_active('dashboard.php')) echo '<div class="sidebar-indicator"></div>'; ?>
            <span class="si-icon"><i class="fas fa-home"></i></span>
            <span class="si-label">Home Dashboard</span>
        </a>
        <a href="../admin/transport.php" class="sidebar-item <?php echo is_active('transport.php'); ?>">
            <?php if (is_active('transport.php')) echo '<div class="sidebar-indicator"></div>'; ?>
            <span class="si-icon"><i class="fas fa-bus"></i></span>
            <span class="si-label">Transport & GPS</span>
        </a>

        <div class="sidebar-section-title">Management</div>

        <div class="sidebar-submenu-wrapper">
            <div class="sidebar-submenu-line"></div>
            <a href="../admin/students_list.php" class="sidebar-item <?php echo is_active(['students_list.php','student_profile.php','student_attendance.php']); ?>">
                <span class="si-icon"><i class="fas fa-graduation-cap"></i></span>
                <span class="si-label">Students</span>
            </a>
            <a href="../admin/teachers.php" class="sidebar-item <?php echo is_active('teachers.php'); ?>">
                <span class="si-icon"><i class="fas fa-users"></i></span>
                <span class="si-label">Teachers</span>
            </a>
        </div>

        <a href="../admin/admissions.php" class="sidebar-item <?php echo is_active('admissions.php'); ?>">
            <span class="si-icon"><i class="fas fa-file-alt"></i></span>
            <span class="si-label">Admissions</span>
        </a>
        <a href="../admin/fees_overall.php" class="sidebar-item <?php echo is_active(['fees_overall.php','payments.php']); ?>">
            <span class="si-icon"><i class="fas fa-credit-card"></i></span>
            <span class="si-label">Fees</span>
        </a>
        <a href="../admin/attendance.php" class="sidebar-item <?php echo is_active('attendance.php'); ?>">
            <span class="si-icon"><i class="fas fa-check-square"></i></span>
            <span class="si-label">Attendance</span>
        </a>

        <div class="sidebar-section-title">Academics</div>

        <div class="sidebar-submenu-wrapper">
            <div class="sidebar-submenu-line"></div>
            <a href="../admin/subjects.php" class="sidebar-item <?php echo is_active('subjects.php'); ?>">
                <span class="si-icon"><i class="fas fa-book"></i></span>
                <span class="si-label">Subjects</span>
            </a>
            <a href="../admin/timetable_master.php" class="sidebar-item <?php echo is_active('timetable_master.php'); ?>">
                <span class="si-icon"><i class="fas fa-calendar-alt"></i></span>
                <span class="si-label">Timetable</span>
            </a>
            <a href="../admin/marks.php" class="sidebar-item <?php echo is_active('marks.php'); ?>">
                <span class="si-icon"><i class="fas fa-chart-bar"></i></span>
                <span class="si-label">Marks</span>
            </a>
            <a href="../admin/calendar.php" class="sidebar-item <?php echo is_active('calendar.php'); ?>">
                <span class="si-icon"><i class="fas fa-calendar"></i></span>
                <span class="si-label">Calendar</span>
            </a>
            <a href="../admin/certificates.php" class="sidebar-item <?php echo is_active('certificates.php'); ?>">
                <span class="si-icon"><i class="fas fa-id-badge"></i></span>
                <span class="si-label">Certificates</span>
            </a>
        </div>

        <div class="sidebar-section-title">System</div>

        <a href="../admin/logs.php" class="sidebar-item <?php echo is_active('logs.php'); ?>">
            <span class="si-icon"><i class="fas fa-history"></i></span>
            <span class="si-label">Audit Logs</span>
        </a>
        <a href="../admin/settings.php" class="sidebar-item <?php echo is_active('settings.php'); ?>">
            <span class="si-icon"><i class="fas fa-cog"></i></span>
            <span class="si-label">Settings</span>
        </a>

        <?php elseif ($role === 'teacher'): ?>
        <!-- TEACHER NAVIGATION -->
        <div class="sidebar-section-title">Main</div>
        <a href="../teacher/dashboard.php" class="sidebar-item <?php echo is_active('dashboard.php'); ?>">
            <?php if (is_active('dashboard.php')) echo '<div class="sidebar-indicator"></div>'; ?>
            <span class="si-icon"><i class="far fa-compass"></i></span>
            <span class="si-label">Home Dashboard</span>
        </a>
        <a href="../teacher/transport.php" class="sidebar-item <?php echo is_active('transport.php'); ?>">
            <?php if (is_active('transport.php')) echo '<div class="sidebar-indicator"></div>'; ?>
            <span class="si-icon"><i class="fas fa-bus-alt"></i></span>
            <span class="si-label">Transport & GPS</span>
        </a>

        <div class="sidebar-section-title">Teaching</div>
        <div class="sidebar-submenu-wrapper">
            <div class="sidebar-submenu-line"></div>
            <a href="../teacher/students_list.php" class="sidebar-item <?php echo is_active('students_list.php'); ?>">
                <span class="si-icon"><i class="far fa-user"></i></span>
                <span class="si-label">Students</span>
            </a>
            <a href="../teacher/mark_attendance.php" class="sidebar-item <?php echo is_active(['mark_attendance.php','attendance.php']); ?>">
                <span class="si-icon"><i class="far fa-check-square"></i></span>
                <span class="si-label">Attendance</span>
            </a>
            <a href="../teacher/marks.php" class="sidebar-item <?php echo is_active('marks.php'); ?>">
                <span class="si-icon"><i class="far fa-chart-bar"></i></span>
                <span class="si-label">Marks</span>
            </a>
            <a href="../teacher/exams.php" class="sidebar-item <?php echo is_active(['exams.php','exam_results.php']); ?>">
                <span class="si-icon"><i class="far fa-file-alt"></i></span>
                <span class="si-label">Exams</span>
            </a>
            <a href="../teacher/timetable.php" class="sidebar-item <?php echo is_active('timetable.php'); ?>">
                <span class="si-icon"><i class="far fa-calendar-alt"></i></span>
                <span class="si-label">Timetable</span>
            </a>
            <a href="../teacher/certificates.php" class="sidebar-item <?php echo is_active('certificates.php'); ?>">
                <span class="si-icon"><i class="far fa-id-badge"></i></span>
                <span class="si-label">Certificates</span>
            </a>
            <a href="../teacher/fees_overview.php" class="sidebar-item <?php echo is_active('fees_overview.php'); ?>">
                <span class="si-icon"><i class="far fa-credit-card"></i></span>
                <span class="si-label">Fees &amp; Reminder</span>
            </a>
            <a href="../teacher/library.php" class="sidebar-item <?php echo is_active('library.php'); ?>">
                <span class="si-icon"><i class="far fa-book"></i></span>
                <span class="si-label">Library</span>
            </a>
            <a href="../teacher/hostel.php" class="sidebar-item <?php echo is_active('hostel.php'); ?>">
                <span class="si-icon"><i class="far fa-building"></i></span>
                <span class="si-label">Hostel</span>
            </a>
            <a href="../teacher/calendar.php" class="sidebar-item <?php echo is_active('calendar.php'); ?>">
                <span class="si-icon"><i class="far fa-calendar"></i></span>
                <span class="si-label">Calendar</span>
            </a>
            <a href="../teacher/settings.php" class="sidebar-item <?php echo is_active('settings.php'); ?>">
                <span class="si-icon"><i class="far fa-sun"></i></span>
                <span class="si-label">Settings</span>
            </a>
        </div>

        <?php elseif ($role === 'student'): ?>
        <!-- STUDENT NAVIGATION -->
        <div class="sidebar-section-title">Main</div>
        <a href="../student/dashboard.php" class="sidebar-item <?php echo is_active('dashboard.php'); ?>">
            <?php if (is_active('dashboard.php')) echo '<div class="sidebar-indicator"></div>'; ?>
            <span class="si-icon"><i class="far fa-compass"></i></span>
            <span class="si-label">Home Dashboard</span>
        </a>
        <a href="../student/transport.php" class="sidebar-item <?php echo is_active('transport.php'); ?>">
            <?php if (is_active('transport.php')) echo '<div class="sidebar-indicator"></div>'; ?>
            <span class="si-icon"><i class="fas fa-bus-alt"></i></span>
            <span class="si-label">Transport & GPS</span>
        </a>

        <div class="sidebar-section-title">Academics</div>
        <div class="sidebar-submenu-wrapper">
            <div class="sidebar-submenu-line"></div>
            <a href="../student/my_class.php" class="sidebar-item <?php echo is_active('my_class.php'); ?>">
                <span class="si-icon"><i class="far fa-user-circle"></i></span>
                <span class="si-label">My Class</span>
            </a>
            <a href="../student/attendance.php" class="sidebar-item <?php echo is_active('attendance.php'); ?>">
                <span class="si-icon"><i class="far fa-check-square"></i></span>
                <span class="si-label">Attendance</span>
            </a>
            <a href="../student/marks.php" class="sidebar-item <?php echo is_active('marks.php'); ?>">
                <span class="si-icon"><i class="far fa-chart-bar"></i></span>
                <span class="si-label">Marks</span>
            </a>
            <a href="../student/exams.php" class="sidebar-item <?php echo is_active(['exams.php','take_exam.php','exam_result.php']); ?>">
                <span class="si-icon"><i class="far fa-file-alt"></i></span>
                <span class="si-label">Exams</span>
            </a>
            <a href="../student/timetable.php" class="sidebar-item <?php echo is_active('timetable.php'); ?>">
                <span class="si-icon"><i class="far fa-calendar-alt"></i></span>
                <span class="si-label">Timetable</span>
            </a>
            <a href="../student/calendar.php" class="sidebar-item <?php echo is_active('calendar.php'); ?>">
                <span class="si-icon"><i class="far fa-calendar"></i></span>
                <span class="si-label">Calendar</span>
            </a>
        </div>

        <div class="sidebar-section-title">Digital Library</div>
        <div class="sidebar-submenu-wrapper">
            <div class="sidebar-submenu-line"></div>
            <a href="../student/library.php" class="sidebar-item <?php echo is_active('library.php'); ?>">
                <span class="si-icon"><i class="fas fa-layer-group"></i></span>
                <span class="si-label">Library Hub</span>
            </a>
            <a href="../student/library_books.php" class="sidebar-item <?php echo is_active('library_books.php'); ?>">
                <span class="si-icon"><i class="fas fa-search"></i></span>
                <span class="si-label">Book Catalog</span>
            </a>
            <a href="../student/library_seats.php" class="sidebar-item <?php echo is_active('library_seats.php'); ?>">
                <span class="si-icon"><i class="fas fa-chair"></i></span>
                <span class="si-label">Reading Pods</span>
            </a>
            <a href="../student/library_issues.php" class="sidebar-item <?php echo is_active('library_issues.php'); ?>">
                <span class="si-icon"><i class="fas fa-history"></i></span>
                <span class="si-label">Borrowing History</span>
            </a>
        </div>

        <div class="sidebar-section-title">Services</div>
        <div class="sidebar-submenu-wrapper">
            <div class="sidebar-submenu-line"></div>
            <a href="../student/payments.php" class="sidebar-item <?php echo is_active('payments.php'); ?>">
                <span class="si-icon"><i class="far fa-credit-card"></i></span>
                <span class="si-label">Fees</span>
            </a>
            <a href="../student/hostel.php" class="sidebar-item <?php echo is_active('hostel.php'); ?>">
                <span class="si-icon"><i class="far fa-building"></i></span>
                <span class="si-label">Hostel</span>
            </a>
            <a href="../student/alumni.php" class="sidebar-item <?php echo is_active('alumni.php'); ?>">
                <span class="si-icon"><i class="far fa-id-card"></i></span>
                <span class="si-label">Alumni</span>
            </a>
            <a href="../student/certificates.php" class="sidebar-item <?php echo is_active('certificates.php'); ?>">
                <span class="si-icon"><i class="far fa-id-badge"></i></span>
                <span class="si-label">Certificates</span>
            </a>
            <a href="../student/settings.php" class="sidebar-item <?php echo is_active('settings.php'); ?>">
                <span class="si-icon"><i class="far fa-sun"></i></span>
                <span class="si-label">Settings</span>
            </a>
        </div>

        <?php elseif ($role === 'parent'): ?>
        <!-- PARENT NAVIGATION -->
        <div class="sidebar-section-title">Main</div>
        <a href="../parent/dashboard.php" class="sidebar-item <?php echo is_active('dashboard.php'); ?>">
            <?php if (is_active('dashboard.php')) echo '<div class="sidebar-indicator"></div>'; ?>
            <span class="si-icon"><i class="far fa-compass"></i></span>
            <span class="si-label">Home Dashboard</span>
        </a>
        <a href="../parent/transport.php" class="sidebar-item <?php echo is_active('transport.php'); ?>">
            <?php if (is_active('transport.php')) echo '<div class="sidebar-indicator"></div>'; ?>
            <span class="si-icon"><i class="fas fa-bus-alt"></i></span>
            <span class="si-label">Transport & GPS</span>
        </a>
        <div class="sidebar-section-title">Child's Progress</div>
        <div class="sidebar-submenu-wrapper">
            <div class="sidebar-submenu-line"></div>
            <a href="../parent/attendance.php" class="sidebar-item <?php echo is_active('attendance.php'); ?>">
                <span class="si-icon"><i class="far fa-check-square"></i></span>
                <span class="si-label">Attendance</span>
            </a>
            <a href="../parent/marks.php" class="sidebar-item <?php echo is_active('marks.php'); ?>">
                <span class="si-icon"><i class="far fa-chart-bar"></i></span>
                <span class="si-label">Marks</span>
            </a>
            <a href="../parent/timetable.php" class="sidebar-item <?php echo is_active('timetable.php'); ?>">
                <span class="si-icon"><i class="far fa-calendar-alt"></i></span>
                <span class="si-label">Timetable</span>
            </a>
            <a href="../parent/settings.php" class="sidebar-item <?php echo is_active('settings.php'); ?>">
                <span class="si-icon"><i class="far fa-sun"></i></span>
                <span class="si-label">Settings</span>
            </a>
        </div>
        <?php endif; ?>

    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
        <i class="fas fa-arrow-up-right-from-square"></i>
        <a href="../login.php" style="color:inherit;text-decoration:none;">Visit site</a>
    </div>
</div>
