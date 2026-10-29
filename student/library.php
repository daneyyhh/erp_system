<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php"); exit();
}
require_once('../config/db.php');

$page_title = "Library Central Hub | ia Academy";

$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student_id = $stmt->fetchColumn();

// KPI Stats
try {
    $active_stmt = $pdo->prepare("SELECT COUNT(*) FROM library_issued WHERE student_id = ? AND status='issued'");
    $active_stmt->execute([$student_id]);
    $active_count = $active_stmt->fetchColumn();

    $fine_stmt = $pdo->prepare("SELECT SUM(fine_amount) FROM library_issued WHERE student_id = ?");
    $fine_stmt->execute([$student_id]);
    $total_fines = $fine_stmt->fetchColumn() ?: 0;

    $my_seat_stmt = $pdo->prepare("SELECT seat_number FROM library_seats WHERE booked_by = ?");
    $my_seat_stmt->execute([$student_id]);
    $my_seat = $my_seat_stmt->fetchColumn();
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .hub-card { border-radius: 24px; padding: 35px; background: white; border: 1.5px solid var(--border-light); transition: 0.3s; height: 100%; display: flex; flex-direction: column; justify-content: space-between; }
        .hub-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.06); border-color: var(--primary-soft); }
        .hub-icon { width: 60px; height: 60px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 25px; }
    </style>
</head>
<body style="background: #f8fafc;">
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Library Central Hub</h2>
                        <p class="text-muted small fw-600">Your gateways to academic literature, research spaces, and circulation management.</p>
                    </div>
                </div>

                <!-- KPI Row -->
                <div class="row g-3 mb-5 animate-up">
                    <div class="col-md-4">
                        <div class="card-premium p-4 shadow-sm border-0 bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="smallest fw-800 text-muted uppercase mb-1">Active Checkouts</div>
                                    <div class="h2 fw-900 mb-0"><?php echo $active_count; ?></div>
                                </div>
                                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4"><i class="fas fa-book"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-premium p-4 shadow-sm border-0 bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="smallest fw-800 text-muted uppercase mb-1">Seating Status</div>
                                    <div class="h3 fw-900 mb-0"><?php echo $my_seat ? $my_seat : 'Not Seated'; ?></div>
                                </div>
                                <div class="p-3 bg-success bg-opacity-10 text-success rounded-4"><i class="fas fa-chair"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-premium p-4 shadow-sm border-0 bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="smallest fw-800 text-muted uppercase mb-1">Total Fine Dues</div>
                                    <div class="h2 fw-900 mb-0">₹<?php echo number_format($total_fines); ?></div>
                                </div>
                                <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-4"><i class="fas fa-credit-card"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 animate-up">
                    <div class="col-md-4">
                        <div class="hub-card">
                            <div>
                                <div class="hub-icon" style="background: #ede9fe; color: #7c3aed;"><i class="fas fa-search"></i></div>
                                <h5 class="fw-800 mb-2">Book Catalog</h5>
                                <p class="text-muted font-sm">Browse 500+ books, check availability, and perform self-checkouts anywhere.</p>
                            </div>
                            <a href="library_books.php" class="btn btn-premium w-100 fw-800 py-3 mt-4" style="border-radius:15px;">OPEN CATALOG</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="hub-card">
                            <div>
                                <div class="hub-icon" style="background: #dcfce7; color: #16a34a;"><i class="fas fa-chair"></i></div>
                                <h5 class="fw-800 mb-2">Reading Pods</h5>
                                <p class="text-muted font-sm">Explore interactive floor plans and reserve a quiet study space for your research.</p>
                            </div>
                            <a href="library_seats.php" class="btn btn-success w-100 fw-800 py-3 mt-4 text-white" style="border-radius:15px; background:#16a34a;">RESERVE SEAT</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="hub-card">
                            <div>
                                <div class="hub-icon" style="background: #fef3c7; color: #d97706;"><i class="fas fa-history"></i></div>
                                <h5 class="fw-800 mb-2">Borrowing History</h5>
                                <p class="text-muted font-sm">View your past library activity, track due dates, and monitor your fine history.</p>
                            </div>
                            <a href="library_issues.php" class="btn btn-warning w-100 fw-800 py-3 mt-4 text-white" style="border-radius:15px; background:#d97706;">VIEW RECORDS</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
