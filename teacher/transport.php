<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') { header("Location: ../login.php"); exit(); }
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
    <title>Teacher Transport Preview | ia Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #busMap{height:460px;border-radius:12px;width:100%;border:2px solid var(--border);background:#f8f9fa;}
        .bus-icon-div{background:none;border:none;}
        .bus-marker-icon{width:42px;height:42px;background:#7C3AED;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 4px 12px rgba(124,58,237,0.4);border:2px solid #fff;transition:all 0.1s linear;}
        .pulse-ring{position:absolute;width:42px;height:42px;background:rgba(124,58,237,0.3);border-radius:50%;animation:pulse 1.5s infinite;}
        @keyframes pulse{0%{transform:scale(1);opacity:1;}100%{transform:scale(2.5);opacity:0;}}
        .call-btn-mega{background:linear-gradient(135deg,#7C3AED,#A78BFA);color:#fff;padding:22px;border-radius:18px;text-decoration:none !important;display:flex;align-items:center;justify-content:center;gap:14px;font-size:20px;font-weight:800;box-shadow:0 12px 30px rgba(124,58,237,0.4);transition:all .3s;}
        .call-btn-mega:hover{transform:translateY(-4px);box-shadow:0 18px 40px rgba(124,58,237,0.5);color:#fff;}
        .stop-card{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border-light);}
        .stop-icon{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;font-weight:700;}
        .stop-icon.passed{background:#D1FAE5;color:#059669;}
        .stop-icon.current{background:#7C3AED;color:#fff;}
    </style>
</head>
<body>
<div class="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <div class="main-content">
        <?php include('../includes/topbar.php'); ?>
        <div class="content-area">

            <!-- PAGE UPDATE DEBUG BANNER -->
            <div style="background:#000;color:#0f0;padding:4px 12px;font-family:monospace;font-size:11px;border-radius:4px;margin-bottom:12px;">
                TEACHER_SYSTEM_REFRESHED: <?php echo $last_update; ?> | ROUTE: ALAPPUZHA -> KOCHI | SIMULATION: ON
            </div>

            <div class="row align-items-center mb-4 g-3 animate-up">
                <div class="col-md-7">
                    <div class="page-title" style="font-size:28px;">Transport Simulation</div>
                    <div class="page-sub">Teacher View · Alappuzha → Kochi Route</div>
                </div>
                <div class="col-md-5">
                    <a href="tel:<?php echo $drivers[0]['driver_phone'] ?? '+919876543210'; ?>" class="call-btn-mega">
                        <i class="fas fa-phone-alt animate-bounce"></i>
                        CALL BUS DRIVER NOW
                    </a>
                </div>
            </div>

            <div class="row g-3 animate-up">
                <div class="col-md-8">
                    <div class="card p-3 shadow-sm" style="border-radius:16px;">
                        <div id="busMap"></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 h-100 shadow-sm" style="border-radius:16px;">
                        <div class="card-title mb-3">Live Trip Progress</div>
                         <div class="progress mb-4" style="height:10px; border-radius:10px; background:#EDE9FE;">
                            <div id="pBar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%; background:linear-gradient(90deg,#7C3AED,#A78BFA);"></div>
                        </div>
                        <div id="stopList"></div>
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
const WAYPOINTS = STOPS.map(s => [s.lat, s.lng]);
const SEG_MS = 10000;

const map = L.map('busMap').setView([9.72, 76.30], 9);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
L.polyline(WAYPOINTS, {color:'#7C3AED', weight:5, opacity:0.3, dashArray:'10,10'}).addTo(map);

var busMarker = null;
function initBus(lat, lng) {
    var icon = L.divIcon({
        className: 'bus-icon-div',
        html: '<div class="pulse-ring"></div><div class="bus-marker-icon"><i class="fas fa-bus"></i></div>',
        iconSize: [42, 42],
        iconAnchor: [21, 21]
    });
    busMarker = L.marker([lat, lng], {icon: icon}).addTo(map).bindPopup('<b>🚍 BUS-01</b><br>Simulation Active').openPopup();
}

function moveBus(lat, lng) {
    if (!busMarker) { initBus(lat, lng); return; }
    busMarker.setLatLng([lat, lng]);
}

function run(idx) {
    if (idx >= STOPS.length - 1) { setTimeout(() => run(0), 5000); return; }
    var from = STOPS[idx], to = STOPS[idx+1];
    var basePct = (idx / (STOPS.length - 1)) * 100;
    var nextPct = ((idx + 1) / (STOPS.length - 1)) * 100;
    
    document.getElementById('stopList').innerHTML = STOPS.map((s, i) => {
        var cls = i < idx ? 'passed' : i === idx ? 'current' : '';
        return `<div class="stop-card"><div class="stop-icon ${cls}">${i < idx ? '✓' : i + 1}</div><div class="stop-name ${cls}">${s.name}</div></div>`;
    }).join('');

    var start = performance.now();
    function step(now) {
        var t = Math.min((now - start) / SEG_MS, 1);
        var e = 1 - Math.pow(1 - t, 3);
        moveBus(from.lat + (to.lat - from.lat) * e, from.lng + (to.lng - from.lng) * e);
        if (t < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
    
    var elap = 0;
    var tick = setInterval(() => {
        elap++;
        if (elap >= (SEG_MS / 1000)) clearInterval(tick);
        document.getElementById('pBar').style.width = (basePct + (elap / (SEG_MS / 1000)) * (nextPct - basePct)) + '%';
    }, 1000);
    
    setTimeout(() => { clearInterval(tick); run(idx + 1); }, SEG_MS);
}

initBus(STOPS[0].lat, STOPS[0].lng);
run(0);
</script>
</body>
</html>
