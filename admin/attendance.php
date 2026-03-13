<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Institutional Attendance | Scholarly";

// Fetch classes and status
$classes = ["BCA 1st Year", "BCA 2nd Year", "BCA 3rd Year"];
$reports = [];

foreach ($classes as $class) {
    $stmt = $pdo->prepare("SELECT 
        COUNT(a.id) as total,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE s.class = ?");
    $stmt->execute([$class]);
    $res = $stmt->fetch();
    $reports[$class] = $res;
}
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
<body style="background: #f8fafc;">
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-800 mb-1">Global Attendance Tracker</h2>
                        <p class="text-muted small">Consolidated departmental records and performance indices</p>
                    </div>
                </div>
                
                <div class="row g-4 mb-5">
                    <?php foreach ($reports as $class => $data): 
                        $pct = ($data['total'] > 0) ? round(($data['present'] / $data['total']) * 100) : 0;
                        $color = ($pct < 75) ? 'danger' : 'success';
                    ?>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm p-4" style="border-radius: 30px;">
                            <h5 class="fw-800 mb-3 text-primary"><?php echo $class; ?></h5>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted smallest fw-bold uppercase">AGGREGATE RATIO</span>
                                <span class="fw-900 text-<?php echo $color; ?>"><?php echo $pct; ?>%</span>
                            </div>
                            <div class="progress mb-4" style="height: 6px; background: #f1f5f9;">
                                <div class="progress-bar bg-<?php echo $color; ?>" style="width: <?php echo $pct; ?>%"></div>
                            </div>
                            <a href="students_list.php?year=<?php echo urlencode($class); ?>" class="btn btn-outline-primary w-100 py-2 fw-bold" style="border-radius: 12px;">Detailed Roster</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="card border-0 shadow-sm p-5" style="border-radius: 30px;">
                    <h5 class="fw-800 mb-4">Operational Summary (Today)</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr class="text-muted small">
                                    <th>SECTION</th><th>REPORTING FACULTY</th><th>SESSION STATUS</th><th>TALLY</th><th class="text-end">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($classes as $class): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $class; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-light rounded-circle" style="width: 24px; height: 24px;"></div>
                                            <span class="small fw-bold">Prof. Xavier</span>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-1">Marked</span></td>
                                    <td class="fw-bold"><?php echo $reports[$class]['present']; ?> / <?php echo $reports[$class]['total']; ?></td>
                                    <td class="text-end"><button class="btn btn-link py-0 text-muted"><i class="fas fa-ellipsis-h"></i></button></td>
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
