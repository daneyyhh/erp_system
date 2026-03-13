<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Examination Desk | Enlight";

// Handle Examination Apply
$apply_success = false;
if (isset($_POST['apply_exam'])) {
    // In a real app, logic would go here to record the application
    $apply_success = true;
    $msg = "Examination application for 'End Semester Dec 2024' submitted successfully.";
    $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'Exam Alert')")->execute([$_SESSION['user_id'], $msg]);
}

$results = [
    ['code' => 'BCA-301', 'subject' => 'Database Systems', 'marks' => '85/100', 'status' => 'Pass'],
    ['code' => 'BCA-302', 'subject' => 'Web Tech', 'marks' => '92/100', 'status' => 'Pass'],
    ['code' => 'BCA-303', 'subject' => 'DevOps', 'marks' => '78/100', 'status' => 'Pass']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Examination & Assessment</h2>
                        <p class="text-muted small fw-600">Register for upcoming exams and access your digital results</p>
                    </div>
                </div>

                <?php if($apply_success): ?>
                    <div class="alert alert-success border-0 rounded-4 mb-4 shadow-sm animate-up">
                        <i class="fas fa-check-circle me-2"></i> Examination application submitted successfully!
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Apply Section -->
                    <div class="col-lg-6">
                        <div class="card-premium animate-up h-100">
                            <div class="bg-primary-soft p-3 rounded-4 mb-4 d-inline-block">
                                <i class="fas fa-file-signature text-primary fs-4"></i>
                            </div>
                            <h4 class="fw-800 mb-3">Apply for Examination</h4>
                            <p class="text-muted mb-4 small fw-600">Register for the upcoming End-Semester Examination. Ensure all dues are cleared before applying.</p>
                            
                            <div class="p-3 border rounded-4 mb-4" style="background: var(--bg-page);">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="smallest fw-800 text-muted uppercase">Exam Cycle</span>
                                    <span class="smallest fw-800 text-primary">DECEMBER 2024</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="smallest fw-800 text-muted uppercase">Deadline</span>
                                    <span class="smallest fw-800 text-danger">30 OCT, 2024</span>
                                </div>
                            </div>
                            
                            <form method="POST">
                                <button type="submit" name="apply_exam" class="btn btn-premium w-100 py-3 rounded-4 fw-800">
                                    <i class="fas fa-paper-plane me-2"></i> Submit Application
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Results Section -->
                    <div class="col-lg-6">
                        <div class="card-premium animate-up h-100">
                            <div class="bg-primary-soft p-3 rounded-4 mb-4 d-inline-block">
                                <i class="fas fa-download text-primary fs-4"></i>
                            </div>
                            <h4 class="fw-800 mb-3">Download Results</h4>
                            <p class="text-muted mb-4 small fw-600">Access your digital marksheet for previous examination cycles.</p>
                            
                            <div class="table-responsive">
                                <table class="table table-borderless align-middle mb-0">
                                    <thead>
                                        <tr class="smallest text-muted uppercase fw-800">
                                            <th>Subject</th>
                                            <th>Score</th>
                                            <th class="text-end">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($results as $r): ?>
                                        <tr class="border-bottom" style="border-color: var(--border) !important;">
                                            <td class="py-3">
                                                <div class="fw-800 small text-main"><?php echo $r['subject']; ?></div>
                                                <div class="smallest text-muted fw-bold"><?php echo $r['code']; ?></div>
                                            </td>
                                            <td class="fw-800 text-primary"><?php echo $r['marks']; ?></td>
                                            <td class="text-end">
                                                <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-3 small">PASS</span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="../download.php?type=transcript&id=<?php echo $_SESSION['user_id']; ?>" class="btn btn-outline-primary w-100 mt-4 py-3 rounded-4 fw-800 border-2 text-decoration-none">
                                <i class="fas fa-file-pdf me-2"></i> Download Full Transcript
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
