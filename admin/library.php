<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = strtoupper($_SESSION['user_role']) . " | Library Command";
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_book') {
        $title = $_POST['title']; $author = $_POST['author']; $isbn = $_POST['isbn']; $copies = $_POST['copies'];
        try {
            $pdo->prepare("INSERT INTO library_books (title, author, isbn, total_copies, available_copies) VALUES (?, ?, ?, ?, ?)")->execute([$title, $author, $isbn, $copies, $copies]);
            $success_msg = "Book efficiently added to campus repository.";
        } catch (Exception $e) { $error_msg = "ISBN already logged."; }
    } elseif ($_POST['action'] === 'issue_book') {
        $book_id = $_POST['book_id']; $student_id = $_POST['student_id']; $due_date = $_POST['due_date'];
        try {
            $pdo->beginTransaction();
            $ch = $pdo->prepare("SELECT available_copies FROM library_books WHERE id = ? FOR UPDATE"); $ch->execute([$book_id]);
            if ($ch->fetchColumn() > 0) {
                $pdo->prepare("INSERT INTO library_issued (book_id, student_id, issue_date, due_date) VALUES (?, ?, CURDATE(), ?)")->execute([$book_id, $student_id, $due_date]);
                $pdo->prepare("UPDATE library_books SET available_copies = available_copies - 1 WHERE id = ?")->execute([$book_id]);
                $pdo->commit(); $success_msg = "Book physically issued.";
            } else { $pdo->rollBack(); $error_msg = "Zero copies accessible."; }
        } catch(Exception $e) { $pdo->rollBack(); }
    } elseif ($_POST['action'] === 'return_book') {
        $issue_id = $_POST['issue_id']; $book_id = $_POST['book_id'];
        
        // Calculate fines (Assume 10 Rs per day late)
        $chk = $pdo->prepare("SELECT due_date FROM library_issued WHERE id = ?"); $chk->execute([$issue_id]);
        $due = strtotime($chk->fetchColumn());
        $now = time();
        $fine = 0;
        if($now > $due) {
            $diff = floor(($now - $due) / (60 * 60 * 24));
            $fine = $diff * 10;
        }

        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE library_issued SET status = 'returned', return_date = CURDATE(), fine_amount = ? WHERE id = ?")->execute([$fine, $issue_id]);
            $pdo->prepare("UPDATE library_books SET available_copies = available_copies + 1 WHERE id = ?")->execute([$book_id]);
            $pdo->commit();
            $success_msg = "Return successful. Fine Processed: ₹$fine";
        } catch (Exception $e) { $pdo->rollBack(); }
    } elseif ($_POST['action'] === 'clear_seat') {
        $seat_id = $_POST['seat_id'];
        $pdo->prepare("UPDATE library_seats SET status = 'available', booked_by = NULL WHERE id = ?")->execute([$seat_id]);
        $success_msg = "Seat force cleared successfully.";
    }
}

$books = [];
$active_issues = [];
$students = [];
$seats = [];

try {
    $books = $pdo->query("SELECT * FROM library_books ORDER BY title ASC")->fetchAll();
    $active_issues = $pdo->query("SELECT li.*, b.title, u.name as student_name, s.roll_no FROM library_issued li JOIN library_books b ON li.book_id = b.id JOIN students s ON li.student_id = s.id JOIN users u ON s.user_id = u.id WHERE li.status = 'issued' ORDER BY li.due_date ASC")->fetchAll();
    $students = $pdo->query("SELECT s.id, s.roll_no, u.name FROM students s JOIN users u ON s.user_id = u.id ORDER BY s.roll_no ASC")->fetchAll();
    $seats = $pdo->query("SELECT ls.*, u.name as student_name FROM library_seats ls LEFT JOIN students s ON ls.booked_by = s.id LEFT JOIN users u ON s.user_id = u.id ORDER BY CAST(SUBSTRING_INDEX(ls.seat_number, '-', -1) AS UNSIGNED) ASC")->fetchAll();
} catch (Exception $e) {
    if(empty($error_msg)) $error_msg = $e->getMessage();
}
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
        .seat-btn { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; font-weight: 800; border-radius: 12px; margin: 4px; border: 2px solid transparent; }
        .seat-av { background: var(--success); color: white; opacity: 0.5; }
        .seat-bk { background: var(--danger); color: white; cursor: pointer; }
        .seat-bk:hover { box-shadow: 0 0 10px var(--danger); opacity: 0.8; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                
                <?php if($success_msg): ?><div class="alert alert-success fw-bold mb-4"><i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?></div><?php endif; ?>
                <?php if($error_msg): ?><div class="alert alert-danger fw-bold mb-4"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Library Central Control</h2>
                        <p class="text-muted small fw-600">Access enabled for <?php echo ucfirst($_SESSION['user_role']); ?> permissions</p>
                    </div>
                    <div class="d-flex gap-3">
                        <button class="btn btn-warning px-4 shadow-sm fw-800 text-white" data-bs-toggle="modal" data-bs-target="#issueModal"><i class="fas fa-hand-holding-hand me-2"></i> Issue Book Manually</button>
                        <button class="btn btn-premium px-4 shadow-sm fw-800" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-swatchbook me-2"></i> Add Book Inventory</button>
                    </div>
                </div>

                <!-- Seat Monitoring Map -->
                <div class="card-premium border-0 shadow-sm animate-up mb-4 p-4">
                    <h5 class="fw-800 mb-4 px-2">Seat Surveillance & Enforcement Tracker</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach($seats as $s): ?>
                            <?php if($s['status'] == 'booked'): ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="clear_seat">
                                    <input type="hidden" name="seat_id" value="<?php echo $s['id']; ?>">
                                    <button type="submit" class="seat-btn seat-bk" onclick="return confirm('Force clear seat for <?php echo htmlspecialchars($s['student_name']); ?>?');" title="Occupied by <?php echo htmlspecialchars($s['student_name']); ?>. Click to EVICT."><?php echo str_replace('S-','',$s['seat_number']); ?></button>
                                </form>
                            <?php else: ?>
                                <div class="seat-btn seat-av" title="Unoccupied"><?php echo str_replace('S-','',$s['seat_number']); ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-muted small fw-bold mt-3 text-center">Click a Red (Occupied) seat to forcefully evict the student booking and clear the desk.</div>
                </div>

                <div class="row g-4">
                    <!-- Issue & Returns tracking with fine generator -->
                    <div class="col-lg-12">
                        <div class="card-premium border-0 shadow-sm animate-up">
                            <h5 class="fw-800 mb-4 px-2">Active Circulation & Fine Processing</h5>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead><tr><th>Book Title</th><th>Student Bound</th><th>Timeline & Warning</th><th class="text-end">Processing</th></tr></thead>
                                    <tbody>
                                        <?php if(empty($active_issues)): ?><tr><td colspan="4" class="text-center text-muted">No external circulation detected.</td></tr><?php endif; ?>
                                        <?php foreach ($active_issues as $ai): 
                                            $overdue = strtotime($ai['due_date']) < time();
                                            $diff = $overdue ? floor((time() - strtotime($ai['due_date'])) / (60 * 60 * 24)) : 0;
                                            $est_fine = $diff * 10;
                                        ?>
                                        <tr>
                                            <td class="fw-800 text-main"><?php echo htmlspecialchars($ai['title']); ?></td>
                                            <td class="fw-800"><?php echo htmlspecialchars($ai['student_name']); ?> <code class="ms-1"><?php echo $ai['roll_no']; ?></code></td>
                                            <td>
                                                <?php if($overdue): ?>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger fw-900 border border-danger p-2"><i class="fas fa-exclamation-circle"></i> OVERDUE BY <?php echo $diff; ?> DAYS (Est Fine: ₹<?php echo $est_fine; ?>)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-page border text-main fw-bold p-2">Due on <?php echo date('d M Y', strtotime($ai['due_date'])); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="return_book">
                                                    <input type="hidden" name="issue_id" value="<?php echo $ai['id']; ?>">
                                                    <input type="hidden" name="book_id" value="<?php echo $ai['book_id']; ?>">
                                                    <button class="btn btn-sm btn-success fw-bold px-3 py-2">Process Return</button>
                                                </form>
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
        </div>
    </div>
    
    <!-- MODALS for Issue and Add omitted here for succinctness but included functionally in form architecture above -->
    <!-- Issue Modal -->
    <div class="modal fade" id="issueModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><div class="modal-header border-0"><h5 class="fw-900">Issue Book to Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST"><input type="hidden" name="action" value="issue_book">
            <div class="mb-3"><select name="book_id" class="form-select fw-bold" required><option value="">Select a book...</option><?php foreach($books as $b): if($b['available_copies']>0): ?><option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['title']); ?></option><?php endif; endforeach; ?></select></div>
            <div class="mb-3"><select name="student_id" class="form-select fw-bold" required><option value="">Select a student...</option><?php foreach($students as $s): ?><option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option><?php endforeach; ?></select></div>
            <div class="mb-4"><label class="form-label">Due Date</label><input type="date" name="due_date" class="form-control fw-bold" required></div>
            <button type="submit" class="btn btn-primary w-100 fw-900 shadow-sm py-3">Authorize Dispatch</button>
        </form></div></div></div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><div class="modal-header border-0"><h5 class="fw-900">Add Title Record</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="POST"><input type="hidden" name="action" value="add_book">
            <div class="mb-3"><input type="text" name="title" class="form-control fw-bold" placeholder="Title" required></div>
            <div class="mb-3"><input type="text" name="author" class="form-control fw-bold" placeholder="Author" required></div>
            <div class="mb-3"><input type="text" name="isbn" class="form-control fw-bold" placeholder="ISBN" required></div>
            <div class="mb-4"><input type="number" name="copies" class="form-control fw-bold" placeholder="Total Copies" required></div>
            <button type="submit" class="btn btn-premium w-100 fw-900 shadow-sm py-3">Push to Database</button>
        </form></div></div></div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
