<?php
require_once(__DIR__.'/config/db.php');

try {
    // 1. Library Seats
    $pdo->exec("CREATE TABLE IF NOT EXISTS library_seats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seat_number VARCHAR(20) UNIQUE NOT NULL,
        status ENUM('available', 'booked') DEFAULT 'available',
        booked_by INT DEFAULT NULL,
        FOREIGN KEY (booked_by) REFERENCES students(id) ON DELETE SET NULL
    )");

    // Pre-populate some seats if empty
    if ($pdo->query("SELECT COUNT(*) FROM library_seats")->fetchColumn() == 0) {
        for ($i=1; $i<=20; $i++) {
            $pdo->exec("INSERT INTO library_seats (seat_number, status) VALUES ('S-$i', 'available')");
        }
    }

    // 2. Fines column
    try {
        $pdo->exec("ALTER TABLE library_issued ADD COLUMN fine_amount DECIMAL(10,2) DEFAULT 0.00");
    } catch(Exception $e) {}

    // 3. Hostel Floor column
    try {
        $pdo->exec("ALTER TABLE hostel_rooms ADD COLUMN floor INT DEFAULT 1");
    } catch(Exception $e) {}

    // 4. Hostel Mess Menu
    $pdo->exec("CREATE TABLE IF NOT EXISTS hostel_mess_menu (
        id INT AUTO_INCREMENT PRIMARY KEY,
        day VARCHAR(20) NOT NULL,
        meal_type ENUM('Breakfast', 'Lunch', 'Dinner') NOT NULL,
        menu_items TEXT NOT NULL,
        UNIQUE KEY day_meal (day, meal_type)
    )");

    echo "Migration Successful!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
