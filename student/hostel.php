<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "My Hostel Setup | Project ERP";

$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student_id = $stmt->fetchColumn();

// Fetch allocation
try {
    $stmt = $pdo->prepare("SELECT a.*, r.room_number, r.type, r.fee_per_semester, r.floor 
                           FROM hostel_allocations a 
                           JOIN hostel_rooms r ON a.room_id = r.id 
                           WHERE a.student_id = ? AND a.status = 'active'
                           ORDER BY a.allocated_from DESC LIMIT 1");
    $stmt->execute([$student_id]);
    $allocation = $stmt->fetch();
    
    // Fetch menu
    $menu_raw = $pdo->query("SELECT * FROM hostel_mess_menu ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), FIELD(meal_type, 'Breakfast', 'Lunch', 'Dinner')")->fetchAll();
    $menus = [];
    foreach($menu_raw as $m) { $menus[$m['day']][$m['meal_type']] = $m['menu_items']; }
    
} catch (Exception $e) {
    $allocation = false;
    $menus = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                
                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Dormitory & Mess Viewer</h2>
                        <p class="text-muted small fw-600">Your campus accommodation profile and active menus</p>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Allocation -->
                    <div class="col-lg-5">
                        <?php if(!$allocation): ?>
                            <div class="card-premium text-center py-5 animate-up border-0 shadow-sm">
                                <div class="d-inline-block bg-warning bg-opacity-10 p-4 rounded-circle mb-3 text-warning">
                                    <i class="fas fa-bed fa-2x"></i>
                                </div>
                                <h5 class="fw-800">No Active Allocation</h5>
                                <p class="text-muted small mb-0">Please report to Campus Administration.</p>
                            </div>
                        <?php else: ?>
                            <div class="card-premium animate-up border-0 shadow-sm" style="background: var(--bg-card); padding: 40px;">
                                <div class="d-flex justify-content-between align-items-start mb-4">
                                    <div class="bg-primary bg-opacity-10 p-3 rounded-4">
                                        <i class="fas fa-home fs-3 text-primary"></i>
                                    </div>
                                    <span class="badge bg-success bg-opacity-10 text-success fw-900 px-3 py-2 rounded-3">ACTIVE WARD</span>
                                </div>
                                <h3 class="fw-900 mb-0 text-main" style="font-size: 2.5rem;">Unit <?php echo htmlspecialchars($allocation['room_number']); ?></h3>
                                <div class="mb-4 pb-4 border-bottom">
                                    <span class="badge bg-light text-muted border mt-2 p-2 px-3 fw-900 fs-6">FLOOR <?php echo htmlspecialchars($allocation['floor'] ?? '1'); ?></span>
                                </div>
                                <div class="row g-4">
                                    <div class="col-6">
                                        <div class="smallest text-uppercase ls-1 text-muted fw-900 mb-1">Tier Details</div>
                                        <div class="fw-800 fs-5 text-main"><?php echo $allocation['type'] == 'ac' ? 'AC Suite' : 'Non-AC'; ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="smallest text-uppercase ls-1 text-muted fw-900 mb-1">Term Fee</div>
                                        <div class="fw-800 fs-5 text-success"><i class="fas fa-rupee-sign me-1"></i><?php echo number_format($allocation['fee_per_semester']); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Mess Menu Viewer -->
                    <div class="col-lg-7">
                        <div class="card-premium animate-up border-0 shadow-sm p-4" style="background: var(--bg-card); border-radius: var(--radius-xl);">
                            <h4 class="fw-900 mb-4 border-bottom pb-3 text-main"><i class="fas fa-utensils me-2 text-primary"></i> Campus Mess Disclosures</h4>
                            <div class="table-responsive">
                                <table class="table mb-0 align-middle glass-table">
                                    <thead><tr><th class="border-bottom">Day</th><th class="border-bottom">Breakfast</th><th class="border-bottom">Lunch</th><th class="border-bottom">Dinner</th></tr></thead>
                                    <tbody>
                                        <?php $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']; ?>
                                        <?php foreach($days as $day): ?>
                                        <tr>
                                            <td class="fw-900 text-main border-bottom"><?php echo $day; ?></td>
                                            <td class="border-bottom text-muted small fw-bold"><?php echo htmlspecialchars($menus[$day]['Breakfast'] ?? '---'); ?></td>
                                            <td class="border-bottom text-muted small fw-bold"><?php echo htmlspecialchars($menus[$day]['Lunch'] ?? '---'); ?></td>
                                            <td class="border-bottom text-muted small fw-bold"><?php echo htmlspecialchars($menus[$day]['Dinner'] ?? '---'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
