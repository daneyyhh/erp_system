<?php
require_once(__DIR__.'/config/db.php');

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS library_books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(255) NOT NULL,
        isbn VARCHAR(50) UNIQUE,
        total_copies INT DEFAULT 1,
        available_copies INT DEFAULT 1,
        status ENUM('active', 'inactive') DEFAULT 'active'
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS library_issued (
        id INT AUTO_INCREMENT PRIMARY KEY,
        book_id INT NOT NULL,
        student_id INT NOT NULL,
        issue_date DATE NOT NULL,
        due_date DATE NOT NULL,
        return_date DATE,
        fine_amount DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('issued', 'returned', 'overdue') DEFAULT 'issued',
        FOREIGN KEY (book_id) REFERENCES library_books(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS hostel_rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_number VARCHAR(50) UNIQUE NOT NULL,
        capacity INT DEFAULT 2,
        occupancy INT DEFAULT 0,
        floor INT DEFAULT 1,
        type ENUM('ac', 'non-ac') DEFAULT 'non-ac',
        fee_per_semester DECIMAL(10,2) NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS hostel_allocations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id INT NOT NULL,
        student_id INT NOT NULL,
        allocated_from DATE NOT NULL,
        allocated_to DATE,
        status ENUM('active', 'vacated') DEFAULT 'active',
        FOREIGN KEY (room_id) REFERENCES hostel_rooms(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    )");

    // Rerun previous specific updates
    // Seats
    $pdo->exec("CREATE TABLE IF NOT EXISTS library_seats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seat_number VARCHAR(20) UNIQUE NOT NULL,
        status ENUM('available', 'booked') DEFAULT 'available',
        booked_by INT DEFAULT NULL,
        FOREIGN KEY (booked_by) REFERENCES students(id) ON DELETE SET NULL
    )");
    if ($pdo->query("SELECT COUNT(*) FROM library_seats")->fetchColumn() == 0) {
        for ($i=1; $i<=20; $i++) {
            $pdo->exec("INSERT INTO library_seats (seat_number, status) VALUES ('S-$i', 'available')");
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS hostel_mess_menu (
        id INT AUTO_INCREMENT PRIMARY KEY,
        day VARCHAR(20) NOT NULL,
        meal_type ENUM('Breakfast', 'Lunch', 'Dinner') NOT NULL,
        menu_items TEXT NOT NULL,
        UNIQUE KEY day_meal (day, meal_type)
    )");

    // Fix missing columns if the tables existed but missing cols
    try { $pdo->exec("ALTER TABLE library_issued ADD COLUMN fine_amount DECIMAL(10,2) DEFAULT 0.00"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE hostel_rooms ADD COLUMN floor INT DEFAULT 1"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE hostel_rooms ADD COLUMN occupancy INT DEFAULT 0"); } catch (Exception $e) {}

    echo "Fully Created Everything!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
