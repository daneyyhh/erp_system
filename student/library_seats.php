<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php"); exit();
}
require_once('../config/db.php');

$page_title = "Library Floor Plan | ia Academy";
$success_msg = ''; $error_msg = '';

$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student_id = $stmt->fetchColumn();

// Params
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_slot = $_GET['slot'] ?? 'Morning (08AM-12PM)';

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'book_seat') {
        $seat_id = $_POST['seat_id'];
        $chk = $pdo->prepare("SELECT id FROM library_seat_bookings WHERE seat_id = ? AND booking_date = ? AND time_slot = ? AND status = 'active'");
        $chk->execute([$seat_id, $selected_date, $selected_slot]);
        if (!$chk->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO library_seat_bookings (seat_id, student_id, booking_date, time_slot) VALUES (?, ?, ?, ?)");
            $stmt->execute([$seat_id, $student_id, $selected_date, $selected_slot]);
            $success_msg = "Academic Pod Secured.";
        } else { $error_msg = "Pod occupied during this window."; }
    } elseif ($_POST['action'] === 'release_seat') {
        $booking_id = $_POST['booking_id'];
        $pdo->prepare("UPDATE library_seat_bookings SET status = 'cancelled' WHERE id = ? AND student_id = ?")->execute([$booking_id, $student_id]);
        $success_msg = "Reservation Released.";
    }
}

// Fetch seats
$seats_raw = $pdo->query("SELECT * FROM library_seats ORDER BY CAST(SUBSTRING_INDEX(seat_number, '-', -1) AS UNSIGNED) ASC")->fetchAll();
$seats_map = [];
foreach($seats_raw as $s) {
    $num = (int)str_replace('S-','',$s['seat_number']);
    $seats_map[$num] = $s;
}

$booked_stmt = $pdo->prepare("SELECT seat_id, student_id FROM library_seat_bookings WHERE booking_date = ? AND time_slot = ? AND status = 'active'");
$booked_stmt->execute([$selected_date, $selected_slot]);
$booked_data = $booked_stmt->fetchAll(PDO::FETCH_ASSOC);
$booked_seats = array_column($booked_data, 'student_id', 'seat_id');

function render_seat($num, $type = 'carrel') {
    global $seats_map, $booked_seats, $student_id, $selected_date, $selected_slot;
    $s = $seats_map[$num] ?? null;
    if(!$s) return '';
    $id = $s['id'];
    $is_booked = isset($booked_seats[$id]);
    $is_mine = $is_booked && ($booked_seats[$id] == $student_id);
    
    $cls = $is_mine ? 'seat-mine' : ($is_booked ? 'seat-bk' : 'seat-av');
    $icon = match($type) {
        'carrel' => '<i class="far fa-lightbulb smallest mb-1 d-block opacity-50"></i>',
        'comput' => '<i class="fas fa-desktop smallest mb-1 d-block opacity-50"></i>',
        'lounge' => '<i class="fas fa-couch smallest mb-1 d-block opacity-50"></i>',
        'bean'   => '<i class="fas fa-circle smallest mb-1 d-block opacity-50"></i>',
        default => ''
    };
    
    $html = '<div class="seat-wrapper">';
    if(!$is_booked) {
        $html .= '<form method="POST" style="margin:0;">
                    <input type="hidden" name="action" value="book_seat">
                    <input type="hidden" name="seat_id" value="'.$id.'">
                    <button type="submit" class="seat-map-btn '.$cls.'" title="Book Seat '.$num.'">'.$icon.$num.'</button>
                  </form>';
    } else {
        $html .= '<div class="seat-map-btn '.$cls.'">'.$icon.$num.'</div>';
    }
    $html .= '</div>';
    return $html;
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
    <style>
        .floor-plan-container { background: #f1f5f9; border-radius: 40px; padding: 60px; border: 2px solid #e2e8f0; position: relative; max-width: 1200px; margin: 0 auto; }
        .map-section { background: rgba(255,255,255,0.7); border: 2px dashed #94a3b8; border-radius: 30px; padding: 30px; position: relative; margin-bottom: 40px; }
        .map-section-title { position: absolute; top: -15px; left: 30px; background: #1e1b4b; color: white; padding: 4px 15px; border-radius: 10px; font-size: 11px; font-weight: 900; letter-spacing: 1px; }
        
        .seat-map-btn { width: 44px; height: 50px; display: flex; flex-direction: column; align-items: center; justify-content: center; font-weight: 800; border-radius: 10px; border: 1.5px solid transparent; font-size: 11px; transition: 0.3s; background: white; }
        .seat-av { border-color: #cbd5e1; color: #475569; }
        .seat-av:hover { transform: scale(1.15); border-color: var(--primary); color: var(--primary); box-shadow: 0 10px 20px rgba(124, 58, 237, 0.1); }
        .seat-bk { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; border-color: #cbd5e1; }
        .seat-mine { background: var(--primary); color: white; border-color: var(--warning); box-shadow: 0 5px 15px rgba(124, 58, 237, 0.4); }
        
        .grid-study { display: grid; grid-template-columns: repeat(8, 1fr); gap: 15px; }
        .exit-marker { text-align: center; border: 2px solid #fecaca; color: #ef4444; padding: 10px; border-radius: 15px; font-weight: 900; letter-spacing: 2px; font-size: 12px; background: #fff1f2; }
        .entrance-marker { text-align: center; border: 2px solid #bfdbfe; color: #3b82f6; padding: 10px; border-radius: 15px; font-weight: 900; letter-spacing: 2px; font-size: 12px; background: #eff6ff; margin-bottom: 30px; }
        
        .round-table { width: 140px; height: 140px; border-radius: 50%; border: 4px solid #cbd5e1; background: white; display: flex; align-items: center; justify-content: center; font-weight: 900; color: #94a3b8; font-size: 12px; position: relative; margin: 40px; }
        .round-table-container { display: flex; justify-content: space-around; flex-wrap: wrap; }
        
        /* Absolute Positioning for Round Table Seats */
        .ts-1 { position: absolute; top: -55px; left: 50%; transform: translateX(-50%); }
        .ts-2 { position: absolute; top: -15px; right: -55px; }
        .ts-3 { position: absolute; bottom: -15px; right: -55px; }
        .ts-4 { position: absolute; bottom: -55px; left: 50%; transform: translateX(-50%); }
        .ts-5 { position: absolute; bottom: -15px; left: -55px; }
        .ts-6 { position: absolute; top: -15px; left: -55px; }
        
        .lounge-row { display: flex; gap: 40px; justify-content: center; }
        .bean-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .comp-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }

        .amenity { font-size: 10px; font-weight: 800; color: #64748b; background: white; padding: 10px 15px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body style="background: #f8fafc;">
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                
                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-950 mb-1">Architectural Floor Plan</h2>
                        <p class="text-muted small fw-600">Spatial visualization of the reading wings for precision pod selection.</p>
                    </div>
                </div>

                <!-- Control Bar -->
                <div class="card-premium p-4 mb-5 border-0 shadow-sm d-flex justify-content-center gap-2 animate-up">
                    <?php for($i=0; $i<5; $i++): 
                        $d = date('Y-m-d', strtotime("+$i days"));
                        $active = ($d == $selected_date) ? 'btn-primary' : 'btn-light-premium';
                    ?>
                        <a href="?date=<?php echo $d; ?>&slot=<?php echo urlencode($selected_slot); ?>" class="btn <?php echo $active; ?> fw-800 rounded-pill px-3 py-2" style="font-size:11px;">
                            <?php echo $i==0 ? 'TODAY' : date('D, M d', strtotime($d)); ?>
                        </a>
                    <?php endfor; ?>
                </div>

                <?php if($success_msg): ?>
                    <div class="alert alert-success animate-up mb-4 py-2 px-3 fw-bold small rounded-4"><i class="fas fa-check-circle me-1"></i> <?php echo $success_msg; ?></div>
                <?php endif; ?>

                <div class="floor-plan-container animate-up">
                    
                    <div class="entrance-marker">MAIN ENTRANCE / TURNSTILE GATE</div>

                    <div class="d-flex gap-3 mb-5 justify-content-center">
                        <div class="amenity"><i class="fas fa-book-reader text-primary"></i> CIRCULATION DESK</div>
                        <div class="amenity"><i class="fas fa-faucet text-info"></i> WATER FOUNTAIN</div>
                        <div class="amenity"><i class="fas fa-clipboard-list text-warning"></i> NOTICE BOARD</div>
                    </div>

                    <!-- ZONE 1: SILENT ZONE -->
                    <div class="map-section">
                        <div class="map-section-title">ZONE 1: SILENT STUDY AREA (SILENT ZONE ⚠️)</div>
                        <div class="grid-study mb-4">
                            <?php for($i=1; $i<=8; $i++) echo render_seat($i, 'carrel'); ?>
                        </div>
                        <div class="text-center smallest fw-900 text-muted opacity-25 uppercase ls-1 mb-3">Main Study Aisle (4ft Wide)</div>
                        <div class="grid-study mb-5">
                            <?php for($i=9; $i<=16; $i++) echo render_seat($i, 'carrel'); ?>
                        </div>
                        <div class="d-flex justify-content-around mt-4 pt-4 border-top">
                            <div class="text-center">
                                <span class="smallest fw-901 text-muted d-block mb-2">WINDOW WALL SEATING</span>
                                <div class="d-flex gap-3">
                                    <?php for($i=17; $i<=20; $i++) echo render_seat($i, 'carrel'); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- ZONE 2: GROUP STUDY -->
                        <div class="col-lg-7">
                            <div class="map-section" style="height: calc(100% - 40px);">
                                <div class="map-section-title">ZONE 2: GROUP STUDY (QUIET DISCUSSION 💬)</div>
                                <div class="round-table-container">
                                    <div class="round-table">
                                        TABLE A
                                        <div class="ts-1"><?php echo render_seat(21); ?></div>
                                        <div class="ts-2"><?php echo render_seat(22); ?></div>
                                        <div class="ts-3"><?php echo render_seat(23); ?></div>
                                        <div class="ts-4"><?php echo render_seat(24); ?></div>
                                        <div class="ts-5"><?php echo render_seat(25); ?></div>
                                        <div class="ts-6"><?php echo render_seat(26); ?></div>
                                    </div>
                                    <div class="round-table">
                                        TABLE B
                                        <div class="ts-1"><?php echo render_seat(27); ?></div>
                                        <div class="ts-2"><?php echo render_seat(28); ?></div>
                                        <div class="ts-3"><?php echo render_seat(29); ?></div>
                                        <div class="ts-4"><?php echo render_seat(30); ?></div>
                                        <div class="ts-5"><?php echo render_seat(31); ?></div>
                                        <div class="ts-6"><?php echo render_seat(32); ?></div>
                                    </div>
                                </div>
                                <div class="text-center mt-3"><div class="amenity d-inline-block"><i class="fas fa-chalkboard"></i> WHITEBOARD AREA</div></div>
                            </div>
                        </div>

                        <!-- ZONE 3: LOUNGE -->
                        <div class="col-lg-5">
                            <div class="map-section" style="height: calc(100% - 40px);">
                                <div class="map-section-title">ZONE 3: READING LOUNGE (LEISURE 📖)</div>
                                <div class="lounge-row mb-4 pt-3">
                                    <?php for($i=33; $i<=38; $i++) echo render_seat($i, 'lounge'); ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-5">
                                    <div class="bean-grid">
                                        <?php for($i=39; $i<=42; $i++) echo render_seat($i, 'bean'); ?>
                                    </div>
                                    <div class="text-end">
                                        <div class="amenity mb-2"><i class="fas fa-newspaper"></i> NEWSPAPERS</div>
                                        <div class="amenity"><i class="fas fa-leaf text-success"></i> DECOR PLANT</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ZONE 4: COMPUTER STATION -->
                    <div class="map-section mt-4">
                        <div class="map-section-title">ZONE 4: COMPUTER STATION (DIGITAL RESOURCES 💻)</div>
                        <div class="comp-grid mb-4 pt-3">
                            <?php for($i=43; $i<=50; $i++) echo render_seat($i, 'comput'); ?>
                        </div>
                        <div class="d-flex gap-3 justify-content-center mt-4 pt-3 border-top">
                            <div class="amenity"><i class="fas fa-print"></i> PRINTER</div>
                            <div class="amenity"><i class="fas fa-expand"></i> SCANNER</div>
                            <div class="amenity"><i class="fas fa-bolt text-warning"></i> CHARGING HUB</div>
                        </div>
                    </div>

                    <div class="exit-marker mt-5">EMERGENCY EXIT / FIRE EXIT 🔥</div>

                </div>

            </div>
        </div>
    </div>
</body>
</html>
