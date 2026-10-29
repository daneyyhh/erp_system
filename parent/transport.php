<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') { header("Location: ../login.php"); exit(); }
require_once('../config/db.php');

// Ensure table exists & auto-seed demo drivers
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS transport_drivers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        driver_name VARCHAR(255) NOT NULL,
        driver_phone VARCHAR(20) NOT NULL,
        bus_id VARCHAR(20) NOT NULL,
        route_name VARCHAR(255),
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $cnt = $pdo->query("SELECT COUNT(*) FROM transport_drivers")->fetchColumn();
    if ($cnt == 0 || true) {
        $pdo->exec("DELETE FROM transport_drivers");
        $pdo->exec("INSERT INTO transport_drivers (driver_name,driver_phone,bus_id,route_name,status) VALUES
            ('Rajan Kumar','+91 98765 43210','BUS-1','Alappuzha → Kochi (NH-66)','active')");
    }
    $drivers = $pdo->query("SELECT * FROM transport_drivers WHERE status='active' ORDER BY bus_id ASC")->fetchAll();
} catch (Exception $e) { $drivers = []; }

$last_update = date('H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PARENT: BUS TRACKER | ia Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body{background:#f0f2f5 !important;}
        #busMap{height:500px;border-radius:20px;width:100%;border:4px solid #7C3AED;background:#fff;box-shadow:0 15px 40px rgba(0,0,0,0.15);}
        .mega-header{background:linear-gradient(135deg,#059669,#10B981);color:#fff;padding:35px;border-radius:0 0 40px 40px;margin-bottom:30px;box-shadow:0 12px 35px rgba(16,185,129,0.3);}
        .call-now-btn{background:#7C3AED;color:#fff !important;padding:22px 45px;border-radius:60px;font-size:26px;font-weight:900;text-decoration:none !important;display:inline-flex;align-items:center;gap:18px;box-shadow:0 18px 40px rgba(124,58,237,0.45);transition:all .3s;}
        .call-now-btn:hover{transform:translateY(-6px) scale(1.04);box-shadow:0 22px 50px rgba(124,58,237,0.55);}
        .bus-marker{width:55px;height:55px;background:#7C3AED;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;border:4px solid #fff;box-shadow:0 8px 20px rgba(0,0,0,0.35);}
        .pulse{position:absolute;width:55px;height:55px;border:4px solid #7C3AED;border-radius:50%;animation:pulse-anim 1.5s infinite;}
        @keyframes pulse-anim{0%{transform:scale(1);opacity:1;}100%{transform:scale(2.6);opacity:0;}}
        .stop-item{background:#fff;margin-bottom:12px;padding:18px;border-radius:20px;border:2px solid #eee;display:flex;align-items:center;gap:18px;transition:all .3s;}
        .stop-item.active{border-color:#10B981;background:#ecfdf5;box-shadow:0 5px 15px rgba(16,185,129,0.1);}
        .stop-dot{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:15px;}
        .stop-dot.done{background:#D1FAE5;color:#059669;}
        .stop-dot.now{background:#10B981;color:#fff;animation:shadow-pulse 1.5s infinite;}
        @keyframes shadow-pulse{0%{box-shadow:0 0 0 0 rgba(16,185,129,0.4);}100%{box-shadow:0 0 0 12px rgba(16,185,129,0);}}
    </style>
</head>
<body>
<div class="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <div class="main-content">
        <div class="mega-header animate-up">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <h6 style="color:rgba(255,255,255,0.8); font-weight:700; text-transform:uppercase; letter-spacing:1px;">Parent Portal</h6>
                        <h1 style="font-weight:900; font-size:48px; margin:0; line-height:1;">LIVE TRACKER</h1>
                        <p style="font-size:19px; opacity:0.9; margin-top:8px;">Tracking Ward's Bus · Route: Kerala Coastal NH-66</p>
                        <div class="mt-4">
                            <a href="tel:<?php echo $drivers[0]['driver_phone'] ?? ''; ?>" class="call-now-btn">
                                <i class="fas fa-phone-alt"></i> CALL DRIVER NOW
                            </a>
                        </div>
                    </div>
                    <div class="col-md-5 text-md-end d-none d-md-block">
                        <div style="background:rgba(255,255,255,0.2); padding:25px; border-radius:30px; display:inline-block; backdrop-filter:blur(12px); border:1px solid rgba(255,255,255,0.3);">
                            <div style="font-size:15px; font-weight:700; text-transform:uppercase; margin-bottom:5px;">Connection Status</div>
                            <div style="font-size:26px; font-weight:800; color:#fff;"><i class="fas fa-signal me-2"></i> SIMULATION LIVE</div>
                            <div style="font-size:12px; margin-top:10px; opacity:0.8; font-family:monospace;">STAMP: <?php echo $last_update; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-area" style="padding:0 30px 50px;">
            <div class="row g-4 animate-up">
                <div class="col-lg-8">
                    <div id="busMap"></div>
                </div>
                <div class="col-lg-4">
                    <div style="font-weight:900; font-size:22px; margin-bottom:20px; color:#1f2937; display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-route" style="color:#10B981;"></i> STOPS SCHEDULE
                    </div>
                    <div id="stopList"></div>
                    <div class="card mt-4 shadow-lg" style="background:linear-gradient(135deg,#1f2937,#111827); color:#fff; border-radius:25px; padding:30px; border:none;">
                       <div style="font-size:13px; text-transform:uppercase; opacity:0.5; font-weight:700;">Destination Station</div>
                       <div style="font-size:26px; font-weight:900; color:#10B981;">KOCHI MG ROAD</div>
                       <div style="font-size:14px; margin-top:15px; background:rgba(16,185,129,0.1); display:inline-block; padding:8px 15px; border-radius:10px; border:1px solid rgba(16,185,129,0.3);">
                           <i class="fas fa-clock me-2"></i> On Schedule
                       </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const STOPS = [
    {name:'Alappuzha Station', lat:9.4981, lng:76.3388, time:'07:15 AM'},
    {name:'Kalarcode Junction',lat:9.5050, lng:76.3380, time:'07:22 AM'},
    {name:'Paravur Market',    lat:9.5280, lng:76.3370, time:'07:30 AM'},
    {name:'Punnapra North',   lat:9.5520, lng:76.3360, time:'07:38 AM'},
    {name:'Ambalappuzha',     lat:9.5850, lng:76.3350, time:'07:46 AM'},
    {name:'Purakkad',         lat:9.6100, lng:76.3340, time:'07:54 AM'},
    {name:'Thottappally',     lat:9.6420, lng:76.3330, time:'08:02 AM'},
    {name:'Karuvatta',        lat:9.6650, lng:76.3320, time:'08:10 AM'},
    {name:'Haripad Town',     lat:9.6950, lng:76.3310, time:'08:18 AM'},
    {name:'Nangiar kulangara',lat:9.7250, lng:76.3250, time:'08:26 AM'},
    {name:'Cherthala Bus Stn',lat:9.7550, lng:76.3150, time:'08:34 AM'},
    {name:'Arthunkal Gate',   lat:9.7850, lng:76.3050, time:'08:42 AM'},
    {name:'Kuthiathode',      lat:9.8150, lng:76.2950, time:'08:50 AM'},
    {name:'Aroor Junction',   lat:9.8450, lng:76.2850, time:'08:58 AM'},
    {name:'Kumbalam North',   lat:9.8750, lng:76.2750, time:'09:06 AM'},
    {name:'Kundannoor',       lat:9.9050, lng:76.2650, time:'09:14 AM'},
    {name:'Vyttila Hub',      lat:9.9350, lng:76.2550, time:'09:22 AM'},
    {name:'Kadavanthra',      lat:9.9650, lng:76.2450, time:'09:30 AM'},
    {name:'Marine Drive',     lat:9.9850, lng:76.2350, time:'09:38 AM'},
    {name:'Kochi MG Road',    lat:9.9912, lng:76.2273, time:'09:45 AM'},
];
const SEG_MS = 10000;
const map = L.map('busMap').setView([9.72, 76.30], 9);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

var busMarker = null;
function initBus(lat, lng) {
    var icon = L.divIcon({
        className: 'bus-icon-div',
        html: '<div class="pulse"></div><div class="bus-marker"><i class="fas fa-bus"></i></div>',
        iconSize:[55,55], iconAnchor:[27,27]
    });
    busMarker = L.marker([lat, lng], {icon: icon}).addTo(map).bindPopup('<b>🚍 WARD\'S BUS</b><br>Simulation Live').openPopup();
}

function moveBus(lat, lng) {
    if (!busMarker) { initBus(lat, lng); return; }
    busMarker.setLatLng([lat, lng]);
}

function run(idx) {
    if (idx >= STOPS.length - 1) { setTimeout(() => run(0), 4000); return; }
    var from = STOPS[idx], to = STOPS[idx+1];
    
    document.getElementById('stopList').innerHTML = STOPS.map((s, i) => {
        var cls = i < idx ? 'done' : i === idx ? 'now' : 'wait';
        return `<div class="stop-item ${i===idx?'active':''}">
            <div class="stop-dot ${cls}">${i < idx ? '✓' : i + 1}</div>
            <div class="stop-name ${i<idx?'passed':''}">${s.name}</div>
        </div>`;
    }).join('');

    var start = performance.now();
    function step(now) {
        var t = Math.min((now - start) / SEG_MS, 1);
        var e = 1 - Math.pow(1 - t, 3);
        moveBus(from.lat + (to.lat - from.lat) * e, from.lng + (to.lng - from.lng) * e);
        if (t < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
    
    setTimeout(() => run(idx + 1), SEG_MS);
}

initBus(STOPS[0].lat, STOPS[0].lng);
run(0);
</script>
</body>
</html>
