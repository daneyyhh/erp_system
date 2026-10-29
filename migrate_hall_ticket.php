<?php
require_once(__DIR__.'/config/db.php');

try {
    $pdo->exec("ALTER TABLE certificates MODIFY type ENUM('Bonafide', 'TC', 'NOC', 'Migration', 'Hall Ticket') NOT NULL");
    echo "Database enum updated.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
