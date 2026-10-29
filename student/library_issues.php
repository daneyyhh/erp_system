<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php"); exit();
}
require_once('../config/db.php');

$page_title = "Borrowing History | ia Academy";

$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student_id = $stmt->fetchColumn();

// Return Resource Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return_resource') {
    $issue_id = $_POST['issue_id'];
    $book_id = $_POST['book_id'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE library_issued SET status = 'returned', return_date = CURDATE() WHERE id = ? AND student_id = ?")->execute([$issue_id, $student_id]);
        $pdo->prepare("UPDATE library_books SET available_copies = available_copies + 1 WHERE id = ?")->execute([$book_id]);
        $pdo->commit();
        $success_msg = "Resource returned successfully! Thank you for maintaining the catalog.";
    } catch (Exception $e) { $pdo->rollBack(); $error_msg = "Return failed."; }
}

// Fetch Issued Books
$stmt = $pdo->prepare("SELECT li.*, b.title, b.author, b.category 
                       FROM library_issued li 
                       JOIN library_books b ON li.book_id = b.id 
                       WHERE li.student_id = ? ORDER BY li.issue_date DESC");
$stmt->execute([$student_id]);
$history = $stmt->fetchAll();

$fine_stmt = $pdo->prepare("SELECT SUM(fine_amount) FROM library_issued WHERE student_id = ?");
$fine_stmt->execute([$student_id]);
$total_fines = $fine_stmt->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body style="background:#f8fafc;">
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Borrowing History</h2>
                        <p class="text-muted small fw-600">Track your current possessions and past literature returns.</p>
                    </div>
                </div>

                <div class="row g-4 mb-5 animate-up">
                    <div class="col-md-4">
                        <div class="card-premium p-4 shadow-sm border-0 bg-primary text-white">
                            <h6 class="fw-800 mb-1 opacity-75">Active Fines</h6>
                            <div class="h2 fw-900 mb-0">₹<?php echo number_format($total_fines); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-premium p-4 shadow-sm border-0 bg-white">
                            <h6 class="fw-800 mb-1 text-muted">Total Borrowed</h6>
                            <div class="h2 fw-900 text-main mb-0"><?php echo count($history); ?></div>
                        </div>
                    </div>
                </div>

                <div class="card-premium animate-up shadow-sm border-0 p-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3">Resource Details</th>
                                    <th>Status</th>
                                    <th>Issued Date</th>
                                    <th>Due Date</th>
                                    <th class="text-end pe-4">Fine</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($history)): ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted fw-bold">No circulation records found.</td></tr>
                                <?php endif; ?>
                                <?php foreach($history as $h): 
                                    $is_overdue = ($h['status'] == 'issued' && strtotime($h['due_date']) < time());
                                ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="fw-800 text-main mb-0"><?php echo htmlspecialchars($h['title']); ?></div>
                                        <div class="smallest text-muted fw-bold"><?php echo htmlspecialchars($h['author']); ?></div>
                                    </td>
                                    <td>
                                        <?php if($h['status'] == 'returned'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success fw-900">RETURNED</span>
                                        <?php elseif($is_overdue): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger fw-900">OVERDUE</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary fw-900">IN POSSESSION</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><div class="small fw-bold text-muted"><?php echo date('d M Y', strtotime($h['issue_date'])); ?></div></td>
                                    <td>
                                        <?php if($h['status'] == 'returned'): ?>
                                            <div class="small fw-800 text-success">Returned <?php echo date('d M Y', strtotime($h['return_date'])); ?></div>
                                        <?php else: ?>
                                            <div class="small fw-bold text-muted"><?php echo date('d M Y', strtotime($h['due_date'])); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php if($h['status'] !== 'returned'): ?>
                                        <form method="POST" class="m-0 d-inline-block">
                                            <input type="hidden" name="action" value="return_resource">
                                            <input type="hidden" name="issue_id" value="<?php echo $h['id']; ?>">
                                            <input type="hidden" name="book_id" value="<?php echo $h['book_id']; ?>">
                                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill fw-900 px-3" style="font-size:10px;">RETURN RESOURCE</button>
                                        </form>
                                        <?php endif; ?>
                                        <span class="ms-3 fw-900 <?php echo $h['fine_amount'] > 0 ? 'text-danger' : 'text-muted'; ?>">
                                            ₹<?php echo number_format($h['fine_amount']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
