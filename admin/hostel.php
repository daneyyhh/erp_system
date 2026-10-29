<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = strtoupper($_SESSION['user_role']) . " | Hostel & Mess";
$success_msg = ''; $error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_room') {
        $room_number = $_POST['room_number']; $capacity = $_POST['capacity']; $type = $_POST['type']; $fee = $_POST['fee']; $floor = $_POST['floor'];
        try {
            $pdo->prepare("INSERT INTO hostel_rooms (room_number, capacity, type, fee_per_semester, floor) VALUES (?, ?, ?, ?, ?)")->execute([$room_number, $capacity, $type, $fee, $floor]);
            $success_msg = "Room $room_number added to floor $floor.";
        } catch (Exception $e) { $error_msg = "Room ID clash detected."; }
    } elseif ($_POST['action'] === 'allocate') {
        $room_id = $_POST['room_id']; $student_ids = $_POST['student_ids'];
        try {
            $pdo->beginTransaction();
            $ch = $pdo->prepare("SELECT capacity, occupancy FROM hostel_rooms WHERE id = ? FOR UPDATE"); $ch->execute([$room_id]); $rm = $ch->fetch();
            if ($rm['occupancy'] + count($student_ids) <= $rm['capacity']) {
                foreach($student_ids as $sid) {
                    $exist = $pdo->prepare("SELECT * FROM hostel_allocations WHERE student_id = ? AND status = 'active'");
                    $exist->execute([$sid]);
                    if($exist->fetch()) continue;
                    
                    $pdo->prepare("INSERT INTO hostel_allocations (room_id, student_id, allocated_from) VALUES (?, ?, CURDATE())")->execute([$room_id, $sid]);
                    $pdo->prepare("UPDATE hostel_rooms SET occupancy = occupancy + 1 WHERE id = ?")->execute([$room_id]);
                }
                $pdo->commit(); $success_msg = "Resident(s)/Roommate(s) Assigned Successfully.";
            } else { $pdo->rollBack(); $error_msg = "Capacity limit reached. Not enough beds."; }
        } catch(Exception $e) { $pdo->rollBack(); $error_msg = $e->getMessage(); }
    } elseif ($_POST['action'] === 'revoke') {
        $alloc_id = $_POST['alloc_id']; $room_id = $_POST['room_id'];
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE hostel_allocations SET status = 'vacated', allocated_to = CURDATE() WHERE id = ?")->execute([$alloc_id]);
            $pdo->prepare("UPDATE hostel_rooms SET occupancy = occupancy - 1 WHERE id = ?")->execute([$room_id]);
            $pdo->commit(); $success_msg = "Allocation cleared.";
        } catch(Exception $e) { $pdo->rollBack(); }
    } elseif ($_POST['action'] === 'add_menu') {
        $day = $_POST['day']; $meal_type = $_POST['meal_type']; $menu_items = $_POST['menu_items'];
        try {
            // Insert or update on duplicate
            $pdo->prepare("INSERT INTO hostel_mess_menu (day, meal_type, menu_items) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE menu_items = ?")
                ->execute([$day, $meal_type, $menu_items, $menu_items]);
            $success_msg = "Mess Menu Updated!";
        } catch (Exception $e) { $error_msg = "Menu update failed."; }
    }
}

$rooms = [];
$allocs = [];
$students = [];
$menus = [];

try {
    $rooms = $pdo->query("SELECT * FROM hostel_rooms ORDER BY floor ASC, room_number ASC")->fetchAll();
    $allocs = $pdo->query("SELECT a.*, r.room_number, r.type, r.floor, u.name as student_name, s.roll_no FROM hostel_allocations a JOIN hostel_rooms r ON a.room_id = r.id JOIN students s ON a.student_id = s.id JOIN users u ON s.user_id = u.id WHERE a.status = 'active' ORDER BY r.room_number ASC")->fetchAll();
    $students = $pdo->query("SELECT s.id, s.roll_no, u.name FROM students s JOIN users u ON s.user_id = u.id ORDER BY s.roll_no ASC")->fetchAll();
    
    // Fetch menu
    $menu_raw = $pdo->query("SELECT * FROM hostel_mess_menu ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), FIELD(meal_type, 'Breakfast', 'Lunch', 'Dinner')")->fetchAll();
    foreach($menu_raw as $m) { $menus[$m['day']][$m['meal_type']] = $m['menu_items']; }
} catch(Exception $e) { 
    if(empty($error_msg)) $error_msg = $e->getMessage(); 
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
                
                <?php if($success_msg): ?><div class="alert alert-success fw-bold mb-4"><i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?></div><?php endif; ?>
                <?php if($error_msg): ?><div class="alert alert-danger fw-bold mb-4"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Hostel & Mess Operations</h2>
                        <p class="text-muted small fw-600">Global accommodation control, floor layout and mess dietary assignments</p>
                    </div>
                    <div class="d-flex gap-3">
                        <button class="btn btn-warning text-white px-4 py-2 shadow-sm fw-800 rounded-3" data-bs-toggle="modal" data-bs-target="#allocateModal"><i class="fas fa-key me-2"></i> Assign Resident</button>
                        <button class="btn btn-premium px-4 py-2 shadow-sm fw-800 rounded-3" data-bs-toggle="modal" data-bs-target="#addRoomModal"><i class="fas fa-hammer me-2"></i> Build Room</button>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Mess Menu Updater -->
                    <div class="col-lg-12">
                        <div class="card-premium animate-up border-0 shadow-sm p-4" style="background: var(--bg-card); border-radius: var(--radius-xl);">
                            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                                <h4 class="fw-900 mb-0 text-main"><i class="fas fa-utensils me-2 text-primary"></i> Campus Mess Menu Matrix</h4>
                                <button class="btn btn-sm btn-light border fw-800 text-muted shadow-sm" data-bs-toggle="modal" data-bs-target="#editMenuModal"><i class="fas fa-pen me-2"></i> Edit Menu Roster</button>
                            </div>
                            
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

                    <!-- Room Analytics With Floor -->
                    <div class="col-lg-6 mb-2 animate-up">
                        <h5 class="fw-800 mb-4 px-1">Floor & Room Analytics</h5>
                        <div class="card-premium border-0 shadow-sm p-3">
                            <div class="table-responsive" style="max-height: 400px; overflow-y:auto;">
                                <table class="table align-middle">
                                    <thead class="sticky-top bg-white"><tr><th>Room & Floor</th><th>Tier</th><th>Occupancy Check</th></tr></thead>
                                    <tbody>
                                        <?php if(empty($rooms)): ?><tr><td colspan="3" class="text-muted">No rooms established.</td></tr><?php endif; ?>
                                        <?php foreach ($rooms as $r): 
                                            $pct = ($r['capacity'] > 0) ? ($r['occupancy'] / $r['capacity']) * 100 : 0;
                                            $color = $pct >= 100 ? 'danger' : ($pct >= 50 ? 'warning' : 'success');
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-900 text-main fs-6"><?php echo htmlspecialchars($r['room_number']); ?></div>
                                                <div class="smallest text-muted fw-bold">Floor <?php echo $r['floor'] ?? '1'; ?></div>
                                            </td>
                                            <td><span class="badge border bg-page text-muted"><?php echo $r['type'] == 'ac' ? 'AC' : 'Non-AC'; ?></span></td>
                                            <td style="width:40%;">
                                                <div class="d-flex justify-content-between mb-1 smallest text-muted fw-900">
                                                    <span><?php echo $r['occupancy']; ?>/<?php echo $r['capacity']; ?> Beds</span><span><?php echo round($pct); ?>%</span>
                                                </div>
                                                <div class="progress" style="height: 6px; border-radius: 4px;"><div class="progress-bar bg-<?php echo $color; ?>" style="width: <?php echo $pct; ?>%;"></div></div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Active Residents Grid -->
                    <div class="col-lg-6 mb-2 animate-up">
                        <h5 class="fw-800 mb-4 px-1">Active Residence Allocations</h5>
                        <div class="card-premium border-0 shadow-sm p-3">
                            <div class="table-responsive" style="max-height: 400px; overflow-y:auto;">
                                <table class="table align-middle">
                                    <thead class="sticky-top bg-white"><tr><th>Resident Profile</th><th>Allocation Spec</th><th class="text-end">Revoke</th></tr></thead>
                                    <tbody>
                                        <?php if(empty($allocs)): ?><tr><td colspan="3" class="text-muted">No active residents.</td></tr><?php endif; ?>
                                        <?php foreach ($allocs as $a): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-800 text-main"><?php echo htmlspecialchars($a['student_name']); ?></div>
                                                <div class="smallest text-muted border d-inline-block px-1 rounded"><?php echo htmlspecialchars($a['roll_no']); ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-900 text-primary fs-6"><?php echo htmlspecialchars($a['room_number']); ?></div>
                                                <div class="smallest text-muted fw-bold uppercase">Floor <?php echo $a['floor'] ?? '1'; ?> • <?php echo $a['type']; ?></div>
                                            </td>
                                            <td class="text-end">
                                                <form method="POST"><input type="hidden" name="action" value="revoke"><input type="hidden" name="alloc_id" value="<?php echo $a['id']; ?>"><input type="hidden" name="room_id" value="<?php echo $a['room_id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger fw-bold shadow-sm" onclick="return confirm('Revoke bed?');"><i class="fas fa-eject"></i></button></form>
                                            </td>
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
    
    <!-- MODALS -->
    <!-- Edit Menu -->
    <div class="modal fade" id="editMenuModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><div class="modal-header border-0"><h5 class="fw-900">Update Mess Roster</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST"><input type="hidden" name="action" value="add_menu">
            <div class="mb-3"><select name="day" class="form-select fw-bold" required><option value="Monday">Monday</option><option value="Tuesday">Tuesday</option><option value="Wednesday">Wednesday</option><option value="Thursday">Thursday</option><option value="Friday">Friday</option><option value="Saturday">Saturday</option><option value="Sunday">Sunday</option></select></div>
            <div class="mb-3"><select name="meal_type" class="form-select fw-bold" required><option value="Breakfast">Breakfast</option><option value="Lunch">Lunch</option><option value="Dinner">Dinner</option></select></div>
            <div class="mb-4"><textarea name="menu_items" class="form-control fw-bold" rows="3" placeholder="Aloo Paratha, Curd, Tea..." required></textarea></div>
            <button type="submit" class="btn btn-primary w-100 py-3 fw-900 rounded-3 shadow-sm">Save Day Plan</button>
        </form></div></div></div>
    </div>
    
    <!-- Add Room Modal & Allocate kept brief for space -->
    <div class="modal fade" id="addRoomModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><div class="modal-header border-0"><h5 class="fw-900">Build Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST"><input type="hidden" name="action" value="add_room">
        <div class="mb-3"><input type="text" name="room_number" class="form-control fw-bold" placeholder="Identifiers (ex: B-Floor1-05)" required></div>
        <div class="row"><div class="col-6 mb-3"><input type="number" name="floor" class="form-control fw-bold" placeholder="Floor Level" value="1" required></div><div class="col-6 mb-3"><select name="type" class="form-select fw-bold"><option value="non-ac">Non-AC Tier</option><option value="ac">AC Tier</option></select></div></div>
        <div class="row"><div class="col-6 mb-4"><input type="number" name="capacity" class="form-control fw-bold" placeholder="Total Beds" value="2" required></div><div class="col-6 mb-4"><input type="number" name="fee" class="form-control fw-bold" placeholder="Semester Fee" value="15000" required></div></div>
        <button type="submit" class="btn btn-premium w-100 py-3 fw-900 shadow-sm rounded-3">Construct & Map</button>
    </form></div></div></div></div>

    <div class="modal fade" id="allocateModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><div class="modal-header border-0"><h5 class="fw-900">Select Assignee(s)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST"><input type="hidden" name="action" value="allocate">
        <div class="mb-3"><select name="room_id" class="form-select fw-bold" required><option value="">Select a vacant room...</option><?php foreach($rooms as $r): if($r['occupancy']<$r['capacity']): ?><option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['room_number'] . ' - F' . ($r['floor']??'1') . ' ('.($r['capacity']-$r['occupancy']).' free)'); ?></option><?php endif; endforeach; ?></select></div>
        <div class="mb-4">
            <select name="student_ids[]" class="form-select fw-bold" required multiple size="6">
                <option disabled value="">-- Hold CTRL/CMD to select multiple roomates --</option>
                <?php foreach($students as $s): ?>
                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?> (<?php echo $s['roll_no']; ?>)</option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted mt-1 d-block"><i class="fas fa-info-circle text-primary"></i> You can select up to the remaining capacity of the room.</small>
        </div>
        <button type="submit" class="btn btn-warning text-white w-100 py-3 fw-900 shadow-sm rounded-3">Assign Roommate(s)</button>
    </form></div></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
