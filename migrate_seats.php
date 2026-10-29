<?php
require_once(__DIR__.'/config/db.php');

try {
    $pdo->exec("ALTER TABLE library_seats ADD COLUMN booked_by INT DEFAULT NULL");
    echo "Column booked_by added successfully.";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
