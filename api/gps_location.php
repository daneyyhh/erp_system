<?php
/**
 * GPS Location API — receives and serves bus coordinates
 * Called by driver page (POST) and student map (GET)
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once('../config/db.php');

// Auto-create table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS bus_locations (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    bus_id      VARCHAR(20) NOT NULL DEFAULT 'BUS-1',
    route_name  VARCHAR(100) DEFAULT 'Route R-104',
    driver_name VARCHAR(100) DEFAULT 'Driver',
    lat         DECIMAL(10,7) NOT NULL,
    lng         DECIMAL(10,7) NOT NULL,
    speed       DECIMAL(5,1)  DEFAULT 0,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY bus_id (bus_id)
)");

$method = $_SERVER['REQUEST_METHOD'];

// POST — driver updates position
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $lat  = (float)($data['lat'] ?? 0);
    $lng  = (float)($data['lng'] ?? 0);
    $bus  = htmlspecialchars($data['bus_id']      ?? 'BUS-1');
    $drv  = htmlspecialchars($data['driver_name'] ?? 'Driver');
    $rte  = htmlspecialchars($data['route_name']  ?? 'Route R-104');
    $spd  = (float)($data['speed'] ?? 0);

    if ($lat == 0 && $lng == 0) { echo json_encode(['ok'=>false,'error'=>'Invalid coordinates']); exit; }

    $pdo->prepare("INSERT INTO bus_locations (bus_id, route_name, driver_name, lat, lng, speed)
                   VALUES (?,?,?,?,?,?)
                   ON DUPLICATE KEY UPDATE lat=?, lng=?, speed=?, driver_name=?, route_name=?, updated_at=NOW()")
        ->execute([$bus,$rte,$drv,$lat,$lng,$spd,  $lat,$lng,$spd,$drv,$rte]);

    echo json_encode(['ok'=>true,'lat'=>$lat,'lng'=>$lng]);
    exit;
}

// GET — student poll for latest position
$bus_id = $_GET['bus_id'] ?? 'BUS-1';
$row = $pdo->prepare("SELECT *, TIMESTAMPDIFF(SECOND, updated_at, NOW()) as age_sec FROM bus_locations WHERE bus_id=?");
$row->execute([$bus_id]);
$loc = $row->fetch(PDO::FETCH_ASSOC);

if ($loc) {
    echo json_encode(['ok'=>true, 'data'=>$loc]);
} else {
    echo json_encode(['ok'=>false, 'error'=>'Bus location not available yet']);
}
