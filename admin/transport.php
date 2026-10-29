<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php"); exit();
}
require_once('../config/db.php');

// â”€â”€ Handle POST actions (Admin only) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$msg = ''; $msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_driver') {
        $name   = trim($_POST['driver_name'] ?? '');
        $phone  = trim($_POST['driver_phone'] ?? '');
        $bus    = trim($_POST['bus_id'] ?? '');
        $route  = trim($_POST['route'] ?? '');
        if ($name && $phone && $bus) {
            $pdo->prepare("INSERT INTO transport_drivers (driver_name, driver_phone, bus_id, route_name, status) VALUES (?,?,?,?,'active')")
                ->execute([$name, $phone, $bus, $route]);
            $msg = "Driver '$name' added successfully.";
        } else { $msg = "All required fields must be filled."; $msg_type = 'danger'; }
    }

    if ($action === 'delete_driver') {
        $id = intval($_POST['driver_id']);
        $pdo->prepare("DELETE FROM transport_drivers WHERE id = ?")->execute([$id]);
        $msg = "Driver record deleted.";
    }

    if ($action === 'toggle_status') {
        $id = intval($_POST['driver_id']);
        $pdo->prepare("UPDATE transport_drivers SET status = CASE WHEN status='active' THEN 'inactive' ELSE 'active' END WHERE id = ?")->execute([$id]);
        $msg = "Driver status updated.";
    }
}

// â”€â”€ Ensure transport_drivers table exists â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
if ($cnt == 0) {
    $pdo->exec("INSERT INTO transport_drivers (driver_name,driver_phone,bus_id,route_name,status) VALUES
        ('Rajan Kumar','+91 98765 43210','BUS-1','Alappuzha â†’ Kochi (NH-66)','active'),
        ('Thomas George','+91 90876 54321','BUS-2','Alappuzha â†’ Kochi (NH-66)','active')");
}
$drivers = $pdo->query("SELECT * FROM transport_drivers ORDER BY created_at DESC")->fetchAll();
$active  = array_filter($drivers, fn($d) => $d['status'] === 'active');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Management | ia Academy Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #busMap { height:400px;border-radius:12px;width:100%;border:1.5px solid var(--border);z-index:1; }
        .gps-dot{width:12px;height:12px;border-radius:50%;display:inline-block;margin-right:6px;}
        .gps-dot.live{background:#10B981;animation:gping 1.5s infinite;}
        .gps-dot.demo{background:#F59E0B;}
        .gps-dot.off{background:#9CA3AF;}
        @keyframes gping{0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.4);}50%{box-shadow:0 0 0 8px rgba(16,185,129,0);}}
        @keyframes busping{0%{box-shadow:0 0 0 0 rgba(124,58,237,.55);}70%{box-shadow:0 0 0 16px rgba(124,58,237,0);}100%{box-shadow:0 0 0 0 rgba(124,58,237,0);}}
        .stop-card{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-light);transition:all .3s;}
        .stop-icon{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;font-weight:700;}
        .stop-icon.passed{background:#D1FAE5;color:#059669;}
        .stop-icon.current{background:#7C3AED;color:#fff;box-shadow:0 0 0 4px rgba(124,58,237,.2);animation:gping 1.5s infinite;}
        .stop-icon.upcoming{background:#F3F4F6;color:#9CA3AF;}
        .stop-name.passed{color:#6B7280;text-decoration:line-through;}
        .stop-name.current{color:var(--primary);font-weight:700;}
        .stop-name.upcoming{color:var(--text-muted);}
        .route-progress{height:4px;background:#EDE9FE;border-radius:9999px;overflow:hidden;margin:0 0 16px;}
        .route-progress-fill{height:100%;background:linear-gradient(90deg,#7C3AED,#A78BFA);border-radius:9999px;transition:width 1s linear;}
        .call-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;background:linear-gradient(135deg,#10B981,#059669);color:#fff;font-size:12px;font-weight:700;text-decoration:none;border:none;cursor:pointer;transition:all .2s;}
        .call-btn:hover{background:linear-gradient(135deg,#059669,#047857);color:#fff;transform:scale(1.03);}
        .driver-card{background:var(--card-bg);border:1.5px solid var(--border);border-radius:14px;padding:16px;transition:all .3s;}
        .driver-card:hover{border-color:var(--primary);box-shadow:0 4px 20px rgba(124,58,237,.15);}
        .modal-content{border-radius:20px;border:none;}
        .form-label{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);}
    </style>
</head>
<body>
<div class="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <div class="main-content">
        <?php include('../includes/topbar.php'); ?>
        <div class="content-area">

            <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show animate-up" role="alert">
                <?php echo htmlspecialchars($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="page-header animate-up">
                <div>
                    <div class="page-title">Transport Management</div>
                    <div class="page-sub">Alappuzha â†’ Kochi Â· Route NH-66 Â· Full Admin Control</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="gps-dot demo" id="gpsStatusDot"></span>
                    <span id="gpsStatusText" style="font-size:13px;font-weight:600;color:var(--text-muted);">Active Simulation</span>
                    <button class="btn btn-primary btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#addDriverModal">
                        <i class="fas fa-plus me-1"></i> Add Driver
                    </button>
                </div>
            </div>

            <!-- KPI Row -->
            <div class="row g-3 mb-4 animate-up">
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon purple"><i class="fas fa-bus"></i></div>
                        <div><div class="kpi-title">Total Buses</div><div class="kpi-value">3</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon green"><i class="fas fa-user-check"></i></div>
                        <div><div class="kpi-title">Active Drivers</div><div class="kpi-value" id="activeDriverCount"><?php echo count($active); ?></div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon orange"><i class="fas fa-map-marker-alt"></i></div>
                        <div><div class="kpi-title">Current Stop</div><div class="kpi-value" style="font-size:13px;" id="currentStopDisplay">Alappuzha</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <?php if (!empty($active)): ?>
                    <a href="tel:<?php echo htmlspecialchars(reset($active)['driver_phone']); ?>" class="call-btn h-100">
                        <i class="fas fa-phone-alt"></i> Call Driver
                    </a>
                    <?php else: ?>
                    <div class="kpi-card">
                        <div class="kpi-icon gray"><i class="fas fa-phone-slash"></i></div>
                        <div><div class="kpi-title">Driver</div><div class="kpi-value">Offline</div></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Map + Stops -->
            <div class="row g-3 animate-up mb-4">
                <div class="col-md-8">
                    <div class="card" style="padding:16px;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="card-title">Live Bus Location</div>
                            <div style="font-size:12px;color:var(--text-muted);">Auto-refreshes every 10s</div>
                        </div>
                        <div id="busMap"></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-title mb-1">Route Progress</div>
                        <div class="route-progress mb-3">
                            <div class="route-progress-fill" id="routeProgressFill" style="width:0%"></div>
                        </div>
                        <div id="stopList"></div>
                        <div class="alert alert-info mt-3" style="margin-bottom:0;font-size:12px;">
                            <i class="fas fa-shield-alt"></i> <strong>Admin GPS Control</strong><br><br>
                            Open driver page to broadcast real GPS:<br>
                            <a href="../transport_driver.php" target="_blank" style="color:var(--blue);font-weight:700;"><i class="fas fa-bus me-1"></i> Driver GPS Page &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Driver Contact Cards (Call Option) -->
            <div class="card animate-up mb-4">
                <div class="card-title mb-3">
                    <i class="fas fa-phone-alt me-2" style="color:#10B981;"></i>Contact Your Bus Driver
                </div>
                <?php if (empty($active)): ?>
                <div class="text-center py-4" style="color:var(--text-muted);">
                    <i class="fas fa-user-slash fa-2x mb-2" style="opacity:.3;"></i>
                    <p style="font-size:13px;">No active drivers currently available for calling.</p>
                </div>
                <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($active as $d): ?>
                    <div class="col-md-4">
                        <div class="driver-card">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#7C3AED,#A78BFA);display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;flex-shrink:0;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-weight:700;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($d['driver_name']); ?></div>
                                    <div style="font-size:11px;color:var(--text-muted);"><?php echo htmlspecialchars($d['bus_id']); ?> · <?php echo htmlspecialchars($d['route_name'] ?: 'Main Route'); ?></div>
                                </div>
                            </div>
                            <a href="tel:<?php echo htmlspecialchars($d['driver_phone']); ?>" class="call-btn w-100 justify-content-center">
                                <i class="fas fa-phone-alt"></i>
                                Call <?php echo htmlspecialchars($d['driver_phone']); ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Driver Management Table -->

            <div class="card animate-up">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="card-title">Driver & Bus Registry</div>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDriverModal">
                        <i class="fas fa-plus me-1"></i> Add Driver
                    </button>
                </div>

                <?php if (empty($drivers)): ?>
                <div class="text-center py-5" style="color:var(--text-muted);">
                    <i class="fas fa-bus fa-3x mb-3" style="opacity:.3;"></i>
                    <p>No drivers registered yet. Click "Add Driver" to get started.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table" id="driverTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Driver Name</th>
                                <th>Phone</th>
                                <th>Bus ID</th>
                                <th>Route</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($drivers as $i => $d): ?>
                            <tr>
                                <td><?php echo $i+1; ?></td>
                                <td><strong><?php echo htmlspecialchars($d['driver_name']); ?></strong></td>
                                <td>
                                    <a href="tel:<?php echo htmlspecialchars($d['driver_phone']); ?>" class="call-btn">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($d['driver_phone']); ?>
                                    </a>
                                </td>
                                <td><span class="badge badge-purple"><?php echo htmlspecialchars($d['bus_id']); ?></span></td>
                                <td style="font-size:13px;"><?php echo htmlspecialchars($d['route_name'] ?: 'â€”'); ?></td>
                                <td>
                                    <?php if ($d['status'] === 'active'): ?>
                                    <span class="badge badge-green">Active</span>
                                    <?php else: ?>
                                    <span class="badge badge-red">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="driver_id" value="<?php echo $d['id']; ?>">
                                            <button type="submit" class="btn btn-outline btn-sm" title="Toggle Status">
                                                <i class="fas fa-toggle-<?php echo $d['status']==='active' ? 'on' : 'off'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Delete this driver record?');" style="margin:0;">
                                            <input type="hidden" name="action" value="delete_driver">
                                            <input type="hidden" name="driver_id" value="<?php echo $d['id']; ?>">
                                            <button type="submit" class="btn btn-sm" style="background:#FEE2E2;color:#DC2626;border:none;" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Fee Overview -->
            <div class="card mt-3 animate-up">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="card-title">Transport Fees &middot; Alappuzha&ndash;Kochi</div>
                        <div class="card-sub">Annual route fee &mdash; Admin managed</div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div style="font-size:22px;font-weight:800;color:var(--text);">₹12,500<span style="font-size:12px;font-weight:500;color:var(--text-muted);">/yr</span></div>
                        <span class="badge badge-green" style="font-size:13px;padding:8px 16px;">ACTIVE</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add Driver Modal -->
<div class="modal fade" id="addDriverModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-2">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-700"><i class="fas fa-user-plus me-2" style="color:var(--primary);"></i>Register New Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addDriverForm">
                    <input type="hidden" name="action" value="add_driver">
                    <div class="mb-3">
                        <label class="form-label">Driver Full Name *</label>
                        <input type="text" name="driver_name" class="form-control" placeholder="e.g. Rajan Kumar" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number *</label>
                        <input type="tel" name="driver_phone" class="form-control" placeholder="e.g. +91 9876543210" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bus ID *</label>
                        <select name="bus_id" class="form-select" required>
                            <option value="">Select Bus</option>
                            <option value="BUS-1">BUS-1</option>
                            <option value="BUS-2">BUS-2</option>
                            <option value="BUS-3">BUS-3</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Route</label>
                        <select name="route" class="form-select">
                            <option value="Alappuzha â†’ Kochi (NH-66)">Alappuzha â†’ Kochi (NH-66)</option>
                            <option value="Route R-104 (Downtown)">Route R-104 â€” Downtown</option>
                            <option value="Route R-105 (North Campus)">Route R-105 â€” North Campus</option>
                            <option value="Route R-106 (Railway Station)">Route R-106 â€” Railway Station</option>
                            <option value="Route R-107 (South Zone)">Route R-107 â€” South Zone</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-700">
                        <i class="fas fa-save me-2"></i> Save Driver
                    </button>
                </form>
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
const SEG_MS = 12000;

const map = L.map('busMap').setView([9.7200, 76.3050], 9);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap',maxZoom:19}).addTo(map);
L.polyline(WAYPOINTS,{color:'#7C3AED',weight:5,opacity:0.3,dashArray:'10,10'}).addTo(map);

// Add realistic markers for all 20 stops
STOPS.forEach(s => {
    const isOrigin = s.name.includes('Station');
    const isDest   = s.name.includes('MG Road');
    if(isOrigin || isDest) {
        L.marker([s.lat, s.lng]).addTo(map).bindPopup('<b>' + s.name + '</b><br>' + (isOrigin ?'Origin' :'Destination'));
    } else {
        L.circleMarker([s.lat, s.lng], {radius: 4, color: '#7C3AED', fillColor: '#fff', fillOpacity: 1, weight: 2}).addTo(map).bindPopup(s.name);
    }
});

var busCore=null,pulseRing=null,pulseR=20,pulseDir=1;
function initBus(lat,lng,popup){
    pulseRing=L.circleMarker([lat,lng],{radius:20,color:'#7C3AED',weight:2,fillColor:'#7C3AED',fillOpacity:0.1,opacity:0.6,pane:'overlayPane'}).addTo(map);
    busCore=L.circleMarker([lat,lng],{radius:10,fillColor:'#7C3AED',color:'#fff',weight:3,fillOpacity:1,opacity:1,pane:'overlayPane'}).addTo(map).bindPopup(popup||'',{maxWidth:220});
    setInterval(function(){
        pulseR += pulseDir * 1.5;
        if(pulseR>=45) pulseDir=-1;
        if(pulseR<=15) pulseDir=1;
        var fade=Math.max(0, 0.6-(pulseR/45)*0.5);
        pulseRing.setRadius(pulseR);
        pulseRing.setStyle({opacity:fade,fillOpacity:fade*0.2});
    },50);
}
function moveBus(lat,lng,popup){
    if(!busCore){initBus(lat,lng,popup);return;}
    busCore.setLatLng([lat,lng]);pulseRing.setLatLng([lat,lng]);
    if(popup!==null)busCore.getPopup().setContent(popup);
}
var animFrame=null;
function animateTo(fLat,fLng,tLat,tLng,ms){
    if(animFrame)cancelAnimationFrame(animFrame);
    var t0=performance.now();
    (function step(now){
        var t=Math.min((now-t0)/ms,1),e=1-Math.pow(1-t,3);
        moveBus(fLat+(tLat-fLat)*e,fLng+(tLng-fLng)*e,null);
        if(t<1)animFrame=requestAnimationFrame(step);
    })(performance.now());
}
function setEl(id,val){var el=document.getElementById(id);if(el)el.textContent=val;}
function setBar(pct){var el=document.getElementById('routeProgressFill');if(el)el.style.width=Math.min(100,Math.max(0,pct))+'%';}
function renderStops(idx){
    var list=document.getElementById('stopList');if(!list)return;
    list.innerHTML=STOPS.map(function(s,i){
        var cls=i<idx?'passed':i===idx?'current':'upcoming';
        var ico=i<idx?'<i class="fas fa-check"></i>':i===idx?'<i class="fas fa-bus"></i>':String(i+1);
        return '<div class="stop-card"><div class="stop-icon '+cls+'">'+ico+'</div><div style="flex:1;min-width:0;"><div class="stop-name '+cls+'" style="font-size:13px;">'+s.name+(i===STOPS.length-1?' 🏁':'')+'</div><div style="font-size:11px;color:var(--text-muted);">'+s.time+'</div></div>'+(i===idx?'<span class="badge badge-purple" style="font-size:10px;">Ongoing</span>':'')+(i<idx?'<span class="badge badge-green" style="font-size:10px;">Passed</span>':'')+'</div>';
    }).join('');
}

function addLog(msg) {
    const logs = document.getElementById('simLogs');
    if (!logs) return;
    const time = new Date().toLocaleTimeString();
    const entry = document.createElement('div');
    entry.style.cssText = "padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.1); font-size:11px; color:#9CA3AF;";
    entry.innerHTML = `<span style="color:#7C3AED; font-weight:800; margin-right:8px;">[${time}]</span> ${msg}`;
    logs.prepend(entry);
    if (logs.children.length > 20) logs.removeChild(logs.lastChild);
}

function setGPS(type){
    var dot=document.getElementById('gpsStatusDot'),txt=document.getElementById('gpsStatusText');
    if(!dot||!txt)return;
    if(type==='live'){dot.className='gps-dot live';txt.textContent='Live Tracking';txt.style.color='#10B981';}
    else if(type==='off'){dot.className='gps-dot off';txt.textContent='System Standby';txt.style.color='#9CA3AF';}
    else{dot.className='gps-dot demo';txt.textContent='Active Simulation';txt.style.color='#F59E0B';}
}
var segTimer=null,isLiveGPS=false;
function runSegment(idx){
    if(isLiveGPS)return;
    if(idx>=STOPS.length-1){
        renderStops(STOPS.length-1);setBar(100);
        setEl('currentStopDisplay',STOPS[STOPS.length-1].name);
        setEl('nextStopDisplay','🏁 Arrived!');setEl('etaDisplay','—');setGPS('demo');
        moveBus(STOPS[STOPS.length-1].lat,STOPS[STOPS.length-1].lng,'<b>🏁 Kochi</b><br>Trip Completed');
        addLog("Arrived at final destination: Kochi MG Road");
        segTimer=setTimeout(function(){
            addLog("Restarting daily route simulation...");
            moveBus(STOPS[0].lat,STOPS[0].lng,'<b>🚩 Alappuzha</b><br>Daily Restart');
            runSegment(0);
        },8000);
        return;
    }
    var from=STOPS[idx],to=STOPS[idx+1];
    var basePct=(idx/(STOPS.length-1))*100,nextPct=((idx+1)/(STOPS.length-1))*100;
    setGPS('demo');renderStops(idx);setBar(basePct);
    setEl('currentStopDisplay',from.name);setEl('nextStopDisplay',to.name);
    setEl('etaDisplay','Calculated...');setEl('busIdDisplay','BUS-1');
    moveBus(from.lat,from.lng,'<b>'+from.name+'</b><br>Currently Boarding');
    addLog(`Departing from ${from.name} towards ${to.name}`);
    
    animateTo(from.lat,from.lng,to.lat,to.lng,SEG_MS);
    var elapsed=0;
    var tick=setInterval(function(){
        if(isLiveGPS){clearInterval(tick);return;}
        elapsed++;
        var rem=(SEG_MS/1000)-elapsed;
        if(rem<=0){clearInterval(tick);return;}
        setEl('etaDisplay',rem<60?rem+'s':Math.ceil(rem/60)+' min');
        setBar(basePct+(elapsed/(SEG_MS/1000))*(nextPct-basePct));
    },1000);
    segTimer=setTimeout(function(){clearInterval(tick);runSegment(idx+1);},SEG_MS);
}
function pollGPS(){
    fetch('../api/gps_location.php?bus_id=BUS-1').then(function(r){return r.json();}).then(function(res){
        if(!res.ok)return;
        if(res.data.age_sec>120)return; 
        if(!isLiveGPS){
            isLiveGPS=true;
            if(segTimer)clearTimeout(segTimer);
            if(animFrame)cancelAnimationFrame(animFrame);
            setGPS('live');
            addLog("Switching to real-time satellite GPS data...");
        }
        var d=res.data;
        moveBus(parseFloat(d.lat),parseFloat(d.lng),'<b>LIVE</b><br>Driver: '+d.driver_name+'<br>Speed: '+parseFloat(d.speed||0).toFixed(0)+' km/h');
        setEl('currentStopDisplay',d.driver_name);setEl('nextStopDisplay',d.route_name);
        setEl('etaDisplay',d.age_sec<60?d.age_sec+'s ago':Math.floor(d.age_sec/60)+'m ago');
    }).catch(function(){});
}
initBus(STOPS[0].lat, STOPS[0].lng, '<b>🚍 Bus Location</b><br>Initializing route...');
renderStops(0);
runSegment(0);
</script>
</body>
</html>
