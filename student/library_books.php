<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php"); exit();
}
require_once('../config/db.php');

$page_title = "Book Catalog | ia Academy Library";
$success_msg = ''; $error_msg = '';

$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student_id = $stmt->fetchColumn();

// Self-Checkout Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'self_checkout') {
    $book_id = $_POST['book_id'];
    try {
        $pdo->beginTransaction();
        $ch = $pdo->prepare("SELECT available_copies FROM library_books WHERE id = ? FOR UPDATE");
        $ch->execute([$book_id]);
        if ($ch->fetchColumn() > 0) {
            $due_date = date('Y-m-d', strtotime('+14 days'));
            $stmt = $pdo->prepare("INSERT INTO library_issued (book_id, student_id, issue_date, due_date, status) VALUES (?, ?, CURDATE(), ?, 'issued')");
            $stmt->execute([$book_id, $student_id, $due_date]);
            $pdo->prepare("UPDATE library_books SET available_copies = available_copies - 1 WHERE id = ?")->execute([$book_id]);
            $pdo->commit();
            $success_msg = "Resource secured! Return by " . date('M d, Y', strtotime($due_date));
        } else {
            $pdo->rollBack(); $error_msg = "Out of stock.";
        }
    } catch (Exception $e) { $pdo->rollBack(); $error_msg = "Checkout failed."; }
}

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$book_sql = "SELECT * FROM library_books WHERE status = 'active'";
if($search) $book_sql .= " AND (title LIKE ".$pdo->quote("%$search%")." OR author LIKE ".$pdo->quote("%$search%").")";
if($category) $book_sql .= " AND category = ".$pdo->quote($category);
$book_sql .= " ORDER BY title ASC";
$books = $pdo->query($book_sql)->fetchAll();
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
        .book-card { border-radius: 20px; transition: 0.3s; border: 1px solid var(--border); overflow: hidden; height: 100%; display: flex; flex-direction: column; background: white; }
        .book-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); border-color: var(--primary-soft); }
        .book-cover { height: 160px; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); display: flex; align-items: center; justify-content: center; position: relative; }
        .category-pill { padding: 4px 12px; border-radius: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; position: absolute; top: 12px; right: 12px; background: rgba(255,255,255,0.9); }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-4 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Book Catalog</h2>
                        <p class="text-muted small fw-600">Browse and search through 500+ academic resources.</p>
                    </div>
                    <div class="search-pill">
                        <form method="GET" class="m-0 d-flex align-items-center">
                            <i class="fas fa-search icon"></i>
                            <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit">SEARCH</button>
                        </form>
                    </div>
                </div>

                <div class="catalog-filters mb-5 d-flex gap-2 animate-up overflow-auto no-scrollbar pb-2">
                    <a href="library_books.php" class="badge <?php echo !$category ? 'bg-primary' : 'bg-white text-muted border'; ?> py-2 px-3 fw-800 rounded-pill text-decoration-none">ALL LITERATURE</a>
                    <a href="?category=Engineering" class="badge <?php echo $category=='Engineering' ? 'bg-primary' : 'bg-white text-muted border'; ?> py-2 px-3 fw-800 rounded-pill text-decoration-none">ENGINEERING</a>
                    <a href="?category=Management" class="badge <?php echo $category=='Management' ? 'bg-primary' : 'bg-white text-muted border'; ?> py-2 px-3 fw-800 rounded-pill text-decoration-none">MANAGEMENT</a>
                    <a href="?category=Medical" class="badge <?php echo $category=='Medical' ? 'bg-primary' : 'bg-white text-muted border'; ?> py-2 px-3 fw-800 rounded-pill text-decoration-none">MEDICAL</a>
                    <a href="?category=Fiction" class="badge <?php echo $category=='Fiction' ? 'bg-primary' : 'bg-white text-muted border'; ?> py-2 px-3 fw-800 rounded-pill text-decoration-none">FICTION</a>
                    <a href="?category=Comics" class="badge <?php echo $category=='Comics' ? 'bg-primary' : 'bg-white text-muted border'; ?> py-2 px-3 fw-800 rounded-pill text-decoration-none">COMICS & MANGA</a>
                </div>

                <?php if($success_msg): ?>
                    <div class="alert alert-success animate-up mb-4 py-2 px-3 small fw-bold border-0 shadow-sm"><i class="fas fa-check-circle me-1"></i> <?php echo $success_msg; ?></div>
                <?php endif; ?>

                <div class="row g-4 animate-up">
                    <?php foreach ($books as $b): ?>
                    <div class="col-md-4 col-xl-3">
                        <div class="book-card p-0 shadow-sm h-100">
                            <div class="book-cover mb-0" style="height: 260px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                <span class="category-pill text-primary" style="z-index: 10;"><?php echo $b['category'] ?: 'General'; ?></span>
                                <img src="<?php echo $b['cover_url']; ?>" 
                                     alt="Cover" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                     style="width:100%; height:100%; object-fit: cover;">
                                <div class="fallback-icon" style="display:none; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: #e2e8f0; color: #94a3b8;">
                                    <i class="fas fa-book fa-3x mb-2"></i>
                                    <span class="smallest fw-bold">Cover Not Found</span>
                                </div>
                            </div>
                            <div class="p-3 d-flex flex-column" style="height: calc(100% - 260px);">
                                <div class="fw-800 text-main mb-1" style="font-size: 14.5px; line-height: 1.3; height: 38px; overflow: hidden;"><?php echo htmlspecialchars($b['title']); ?></div>
                                <div class="smallest text-muted fw-bold mb-3">by <?php echo htmlspecialchars($b['author']); ?></div>
                                <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                    <span class="smallest fw-900 <?php echo $b['available_copies'] > 0 ? 'text-success' : 'text-danger'; ?> uppercase ls-1"><?php echo $b['available_copies']; ?> IN STOCK</span>
                                    <?php if($b['available_copies'] > 0): ?>
                                    <form method="POST" class="m-0">
                                        <input type="hidden" name="action" value="self_checkout">
                                        <input type="hidden" name="book_id" value="<?php echo $b['id']; ?>">
                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill fw-900 px-3" style="font-size: 10.5px;">CHECKOUT</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
