<?php
require_once(__DIR__.'/config/db.php');

try {
    $pdo->exec("ALTER TABLE library_seats ADD COLUMN booking_date DATE DEFAULT NULL");
    $pdo->exec("ALTER TABLE library_seats ADD COLUMN time_slot VARCHAR(100) DEFAULT NULL");
    echo "Done updating structure.";
} catch(Exception $e) {
    if(strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "Already exists.";
    } else {
        echo "Error: ". $e->getMessage();
    }
}
