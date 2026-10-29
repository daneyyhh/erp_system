<?php
require_once(__DIR__.'/config/db.php');

try {
    $pdo->exec("ALTER TABLE hostel_rooms ADD COLUMN occupancy INT DEFAULT 0");
    
    // Recalculate occupancy
    $pdo->exec("UPDATE hostel_rooms r SET occupancy = (SELECT COUNT(*) FROM hostel_allocations WHERE room_id = r.id AND status = 'active')");
    
    echo "Occupancy column added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
