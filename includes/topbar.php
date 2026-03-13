<?php
$user_name = $_SESSION['user_name'] ?? 'User';
$role = $_SESSION['user_role'] ?? 'Guest';
require_once(__DIR__.'/../config/db.php');

// Fetch notifications
$notif_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY sent_at DESC LIMIT 5");
$notif_stmt->execute([$_SESSION['user_id']]);
$notifications = $notif_stmt->fetchAll();

$notif_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$notif_count_stmt->execute([$_SESSION['user_id']]);
$notif_count = $notif_count_stmt->fetchColumn();
?>
<script>
    (function() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    })();
</script>

<div class="top-header" style="padding: 2rem 3rem; display: flex; justify-content: space-between; align-items: center; position: relative;">
    <div class="animate-up">
        <h2 class="fw-800 mb-1" style="letter-spacing: -2px; font-size: 2.2rem;">Hello, <?php echo explode(' ', $user_name)[0]; ?>! 👋</h2>
        <p class="text-muted smallest mb-0 fw-800 uppercase ls-2">Project ERP Core • <?php echo strtoupper($role); ?></p>
    </div>
    
    <div class="d-flex align-items-center gap-4">
        <!-- Theme Toggle -->
        <div class="theme-switch" id="themeToggle" onclick="toggleTheme()" title="Switch Appearance">
            <div class="toggle-ball shadow-lg">
                <i class="fas fa-sun" id="themeIcon"></i>
            </div>
        </div>

        <!-- User Profile -->
        <div class="d-flex align-items-center gap-3 ps-3 border-start">
            <div class="text-end d-none d-sm-block">
                <div class="fw-800 fs-6"><?php echo $user_name; ?></div>
                <div class="smallest text-muted fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;"><?php echo $role; ?></div>
            </div>
            <div class="position-relative">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $user_name; ?>" 
                     width="56" height="56" class="rounded-4 shadow-sm border" style="object-fit:cover; background: #fff; border-color: var(--border);">
            </div>
        </div>

        <!-- Notification Bell - MOVED TO TOP RIGHT CORNER -->
        <div class="position-relative ms-2">
            <div class="rounded-circle p-3 shadow-sm border theme-sensitive-bg" id="bellIcon" onclick="toggleNotifications()" style="cursor:pointer; background: var(--bg-card); border-color: var(--border); width: 56px; height: 56px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-bell text-muted fs-5"></i>
                <?php if($notif_count > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger p-2 border-2 border-white" style="font-size: 0.6rem;">
                    <?php echo $notif_count; ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Dropdown -->
            <div class="notif-dropdown shadow-lg" id="notifDropdown" style="right: 0; left: auto;">
                <div class="notif-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-800 mb-0">Alert Center</h6>
                    <span class="smallest text-primary fw-900 uppercase ls-1"><?php echo $notif_count; ?> NEW</span>
                </div>
                <div class="notif-body">
                    <?php if(empty($notifications)): ?>
                        <div class="p-5 text-center text-muted small">All caught up!</div>
                    <?php else: ?>
                        <?php foreach($notifications as $n): ?>
                            <?php 
                                $link = "#";
                                if($role == 'teacher' && $n['type'] == 'Fee Update') $link = "fees_overview.php";
                                elseif($role == 'student' && ($n['type'] == 'Fee Payment' || $n['type'] == 'Fee Reminder')) $link = "payments.php";
                                elseif($n['type'] == 'Doc Approval') $link = "certificates.php";
                            ?>
                            <a href="<?php echo $link; ?>" class="text-decoration-none">
                                <div class="notif-item">
                                    <div class="notif-icon" style="background: <?php echo strpos($n['type'], 'Fee') !== false ? '#fffbeb' : 'var(--primary-soft)'; ?>; color: <?php echo strpos($n['type'], 'Fee') !== false ? '#b45309' : 'var(--primary)'; ?>;">
                                        <i class="fas <?php echo strpos($n['type'], 'Fee') !== false ? 'fa-wallet' : 'fa-bell'; ?>"></i>
                                    </div>
                                    <div class="notif-content">
                                        <div class="small fw-800 text-main mb-0"><?php echo $n['type']; ?></div>
                                        <div class="smallest text-muted fw-600"><?php echo $n['message']; ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const applyThemeLogic = (theme) => {
        const icon = document.getElementById('themeIcon');
        const themeSensitives = document.querySelectorAll('.theme-sensitive-bg');
        if(!icon) return;
        if(theme === 'dark') {
            icon.className = 'fas fa-moon animate-up';
            themeSensitives.forEach(el => el.style.backgroundColor = 'var(--bg-card)');
        } else {
            icon.className = 'fas fa-sun animate-up';
            themeSensitives.forEach(el => el.style.backgroundColor = '#fff');
        }
    };

    const toggleTheme = () => {
        const next = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        applyThemeLogic(next);
    };

    const toggleNotifications = () => {
        document.getElementById('notifDropdown').classList.toggle('show');
    };

    window.onclick = function(e) {
        if (!e.target.closest('#bellIcon') && !e.target.closest('#notifDropdown')) {
            const d = document.getElementById('notifDropdown');
            if (d) d.classList.remove('show');
        }
    };

    applyThemeLogic(document.documentElement.getAttribute('data-theme'));
</script>
