-- Clean Reset (Safe for re-runs)
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS timetable;
DROP TABLE IF EXISTS certificates;
DROP TABLE IF EXISTS fees;
DROP TABLE IF EXISTS marks;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS teachers;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS logs;

CREATE DATABASE IF NOT EXISTS smart_college_erp;
USE smart_college_erp;

-- 1. Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student', 'parent') NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Students Table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    roll_no VARCHAR(50) UNIQUE NOT NULL,
    class VARCHAR(50),
    semester INT,
    parent_phone VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Teachers Table
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255),
    department VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Subjects Table
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    department VARCHAR(255),
    semester INT
);

-- 5. Attendance Table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent') NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- 6. Marks Table
CREATE TABLE marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    internal INT DEFAULT 0,
    external INT DEFAULT 0,
    total INT GENERATED ALWAYS AS (internal + external) STORED,
    grade VARCHAR(5),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- 7. Fees Table
CREATE TABLE fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    due_date DATE NOT NULL,
    paid_date DATE,
    status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
    receipt_no VARCHAR(100) UNIQUE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- 8. Certificates Table
CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    type ENUM('Bonafide', 'TC', 'NOC', 'Migration') NOT NULL,
    applied_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'downloaded') DEFAULT 'pending',
    pdf_path VARCHAR(255),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- 9. Timetable Table
CREATE TABLE timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class VARCHAR(50),
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    time_slot VARCHAR(50),
    room VARCHAR(50),
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

-- 10. Notifications Table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50),
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 11. Logs Table (Cloud-Ready Persistent Logging)
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    action VARCHAR(255),
    user_id INT,
    user_name VARCHAR(255),
    details TEXT
);

-- Insert Default Admin (Password: admin123)
-- Using bcrypt hash for 'admin123'
INSERT INTO users (name, email, password, role) VALUES 
('Admin User', 'admin@scholarly.com', '$2y$10$wLidw/uU8yq8XoOUP6.6O.6xJZ8Xj6GvGkG1X/T7p.X9WvFm/eUeO', 'admin'),
('John Teacher', 'teacher@scholarly.com', '$2y$10$wLidw/uU8yq8XoOUP6.6O.6xJZ8Xj6GvGkG1X/T7p.X9WvFm/eUeO', 'teacher'),
('Alice Student', 'student@scholarly.com', '$2y$10$wLidw/uU8yq8XoOUP6.6O.6xJZ8Xj6GvGkG1X/T7p.X9WvFm/eUeO', 'student'),
('David Parent', 'parent@scholarly.com', '$2y$10$wLidw/uU8yq8XoOUP6.6O.6xJZ8Xj6GvGkG1X/T7p.X9WvFm/eUeO', 'parent');

-- Link Teacher
INSERT INTO teachers (user_id, subject, department) VALUES (2, 'Web Development', 'Computer Science');

-- Link Student
INSERT INTO students (user_id, roll_no, class, semester) VALUES (3, 'S1001', 'BCA 3rd Year', 5);

-- Link Initial Fee
INSERT INTO fees (student_id, amount, due_date, status) VALUES (1, 25000.00, '2024-12-30', 'pending');
