<?php
require_once(__DIR__.'/config/db.php');

try {
    $pdo->exec("ALTER TABLE hostel_rooms ADD COLUMN type ENUM('ac', 'non-ac') DEFAULT 'non-ac'");
    echo "Added type. ";
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE hostel_rooms ADD COLUMN fee_per_semester DECIMAL(10,2) NOT NULL DEFAULT 10000");
    echo "Added fee. ";
} catch (Exception $e) {}
