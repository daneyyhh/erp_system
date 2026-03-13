<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

// Get student ID
$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student_id = $stmt->fetchColumn();

// Handle New Request
$success = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_cert'])) {
    $type = $_POST['cert_type']; // ENUM: 'Bonafide', 'TC', 'NOC', 'Migration'
    try {
        $stmt = $pdo->prepare("INSERT INTO certificates (student_id, type, applied_date, status) VALUES (?, ?, CURDATE(), 'pending')");
        $stmt->execute([$student_id, $type]);
        $success = "Your request for $type has been submitted!";
    } catch (Exception $e) {
        $error = "Failed to submit: " . $e->getMessage();
    }
}

// Fetch Previous Requests
$stmt = $pdo->prepare("SELECT * FROM certificates WHERE student_id = ? ORDER BY applied_date DESC");
$stmt->execute([$student_id]);
$my_certs = $stmt->fetchAll();

$page_title = "My Documents | Enlight";
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
                        <h2 class="fw-800 mb-1">Official Documents</h2>
                        <p class="text-muted small fw-600">Track and download your verified academic certificates</p>
                    </div>
                    <button class="btn btn-premium px-4 shadow-lg" data-bs-toggle="modal" data-bs-target="#requestModal">
                        <i class="fas fa-plus me-2"></i> Request Document
                    </button>
                </div>

                <?php if($success): ?>
                    <div class="alert alert-success border-0 rounded-4 mb-4 shadow-sm animate-up">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <div class="card-premium animate-up">
                    <h5 class="fw-800 mb-4">Application History</h5>
                    <div class="table-responsive">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>Date Applied</th>
                                    <th>Document Type</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($my_certs)): ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted small">No document requests found.</td></tr>
                                <?php endif; ?>
                                <?php foreach($my_certs as $c): ?>
                                <tr>
                                    <td class="text-muted fw-bold"><?php echo $c['applied_date']; ?></td>
                                    <td class="fw-800"><?php echo $c['type']; ?> Certificate</td>
                                    <td>
                                        <?php if($c['status'] == 'pending'): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning fw-extrabold p-2 small px-3">PENDING</span>
                                        <?php elseif($c['status'] == 'approved'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success fw-extrabold p-2 small px-3">READY</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger fw-extrabold p-2 small px-3">REJECTED</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if($c['status'] == 'approved'): ?>
                                            <a href="../download.php?type=certificate&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary fw-800 px-3">
                                                <i class="fas fa-download me-2"></i> Download
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-light border text-muted fw-bold px-3" disabled>
                                                <i class="fas fa-clock me-2"></i> Reviewing
                                            </button>
                                        <?php endif; ?>
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

    <!-- Request Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg p-3" style="border-radius: 32px; background: var(--bg-card);">
                <div class="modal-header border-0">
                    <h5 class="fw-800 mb-0">Apply for Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <label class="smallest fw-800 text-muted uppercase ls-1 mb-2">Select Certificate Type</label>
                        <select name="cert_type" class="form-select border-0 bg-light py-3 rounded-4 fw-bold mb-4">
                            <option value="Bonafide">Bonafide Certificate</option>
                            <option value="TC">Transfer Certificate (TC)</option>
                            <option value="NOC">No Objection Certificate (NOC)</option>
                            <option value="Migration">Migration Certificate</option>
                        </select>
                        <p class="smallest text-muted"><i class="fas fa-info-circle me-1 text-primary"></i> Documents are usually processed within 3-5 working days by the registrar's office.</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" name="request_cert" class="btn btn-premium w-100 py-3 rounded-4">Submit Application</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
