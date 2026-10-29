<?php
/**
 * Quick Exam Tables Setup
 * Run this once to create exam tables
 */

require_once(__DIR__ . '/config/db.php');

$output = [];

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS exams (
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
    )");
    $output[] = "✅ exams table created";

    $pdo->exec("CREATE TABLE IF NOT EXISTS exam_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        question_text TEXT NOT NULL,
        question_type ENUM('multiple_choice', 'true_false', 'short_answer') DEFAULT 'multiple_choice',
        options JSON,
        correct_answer TEXT NOT NULL,
        marks INT DEFAULT 1,
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
    )");
    $output[] = "✅ exam_questions table created";

    $pdo->exec("CREATE TABLE IF NOT EXISTS exam_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        student_id INT NOT NULL,
        start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        end_time TIMESTAMP NULL,
        score DECIMAL(5,2) DEFAULT 0,
        status ENUM('in_progress', 'completed') DEFAULT 'in_progress',
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    )");
    $output[] = "✅ exam_attempts table created";

    $pdo->exec("CREATE TABLE IF NOT EXISTS exam_answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        attempt_id INT NOT NULL,
        question_id INT NOT NULL,
        answer TEXT,
        is_correct BOOLEAN DEFAULT FALSE,
        marks_obtained INT DEFAULT 0,
        FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES exam_questions(id) ON DELETE CASCADE
    )");
    $output[] = "✅ exam_answers table created";

    echo "<div style='font-family:sans-serif; padding:50px; text-align:center;'>
            <h2 style='color:#059669;'>✅ Exam System Ready!</h2>
            <div style='text-align:left; max-width:400px; margin:20px auto;'>";
    foreach ($output as $line) {
        echo "<div style='margin:5px 0;'>$line</div>";
    }
    echo "</div>
            <a href='login.php' style='display:inline-block; margin-top:20px; padding:15px 30px; background:#6366f1; color:white; text-decoration:none; border-radius:12px; font-weight:bold;'>Go to Login</a>
          </div>";

} catch (PDOException $e) {
    echo "<div style='font-family:sans-serif; padding:50px; text-align:center;'>
            <h2 style='color:#b91c1c;'>❌ Error</h2>
            <p>" . $e->getMessage() . "</p>
          </div>";
}
?>
