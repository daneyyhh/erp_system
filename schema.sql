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
DROP TABLE IF EXISTS settings;

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
    status ENUM('pending', 'teacher_approved', 'approved', 'rejected', 'downloaded') DEFAULT 'pending',
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

-- 12. Online Exams Table
CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    subject_id INT,
    class VARCHAR(50),
    duration_minutes INT DEFAULT 30,
    start_time DATETIME,
    end_time DATETIME,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('draft', 'active', 'closed') DEFAULT 'draft',
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- 13. Exam Questions Table
CREATE TABLE IF NOT EXISTS exam_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'short_answer') DEFAULT 'multiple_choice',
    options JSON,
    correct_answer TEXT NOT NULL,
    marks INT DEFAULT 1,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- 14. Exam Attempts Table
CREATE TABLE IF NOT EXISTS exam_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    score DECIMAL(5,2) DEFAULT 0,
    status ENUM('in_progress', 'completed') DEFAULT 'in_progress',
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- 15. Exam Answers Table
CREATE TABLE IF NOT EXISTS exam_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    answer TEXT,
    is_correct BOOLEAN DEFAULT FALSE,
    marks_obtained INT DEFAULT 0,
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES exam_questions(id) ON DELETE CASCADE
);

-- 16. Library Books Table
CREATE TABLE IF NOT EXISTS library_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255),
    isbn VARCHAR(100) UNIQUE,
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    status ENUM('active', 'archived') DEFAULT 'active'
);

-- 17. Library Issued Records Table
CREATE TABLE IF NOT EXISTS library_issued (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    student_id INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE,
    status ENUM('issued', 'returned', 'overdue') DEFAULT 'issued',
    FOREIGN KEY (book_id) REFERENCES library_books(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- 18. Hostel Rooms Table
CREATE TABLE IF NOT EXISTS hostel_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(50) UNIQUE NOT NULL,
    capacity INT DEFAULT 2,
    occupancy INT DEFAULT 0,
    type ENUM('ac', 'non-ac') DEFAULT 'non-ac',
    fee_per_semester DECIMAL(10, 2) NOT NULL
);

-- 19. Hostel Allocations Table
CREATE TABLE IF NOT EXISTS hostel_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    student_id INT NOT NULL,
    allocated_from DATE NOT NULL,
    allocated_to DATE,
    status ENUM('active', 'vacated') DEFAULT 'active',
    FOREIGN KEY (room_id) REFERENCES hostel_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- 20. Settings Table
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
);

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
('institution_name', 'Smart College ERP'),
('tagline', 'Excellence in Education'),
('email', 'admin@scholarly.com'),
('phone', '1234567890'),
('address', '123 Smart Campus, Tech City'),
('late_fine_rate', '10');
