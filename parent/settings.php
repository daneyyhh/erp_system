<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');
require_once('../utils/logger.php');

$role = $_SESSION['user_role'];
$page_title = "Account Settings | Project ERP";
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (password_verify($current, $user['password'])) {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $_SESSION['user_id']]);
        logToExcel('Password Change', $_SESSION['user_id'], $_SESSION['user_name'], "Updated account password");
        $msg = "<div class='alert alert-success border-0 rounded-4' style='background: #ecfdf5; color: #065f46;'><i class='fas fa-check-circle me-2'></i> Password updated successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger border-0 rounded-4' style='background: #fee2e2; color: #b91c1c;'><i class='fas fa-exclamation-circle me-2'></i> Incorrect current password.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
</head>
<body style="background: #f8fafc;">
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area" style="max-width: 800px; margin: 0 auto;">
                <h2 class="fw-800 mb-2">Security Settings</h2>
                <p class="text-muted mb-5">Update your password and security preferences.</p>
                
                <?php echo $msg; ?>

                <div class="card border-0 shadow-sm p-5 mb-4 animate-up" style="border-radius: 30px;">
                    <div class="d-flex align-items-center gap-4 mb-5">
                        <div class="bg-primary p-4 rounded-circle text-white shadow-sm">
                            <i class="fas fa-lock fs-3"></i>
                        </div>
                        <div>
                            <h4 class="fw-800 mb-0">Change Password</h4>
                            <p class="text-muted small mb-0">Ensure your account is using a long, random password to stay secure.</p>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="smallest fw-800 text-muted uppercase">Current Password</label>
                                <input type="password" name="current_password" class="form-control py-3 rounded-4" required>
                            </div>
                            <div class="col-md-12">
                                <label class="smallest fw-800 text-muted uppercase">New Password</label>
                                <input type="password" name="new_password" class="form-control py-3 rounded-4" minlength="6" required>
                            </div>
                        </div>
                        <div class="mt-5 text-end">
                            <button name="change_password" type="submit" class="btn btn-primary px-5 py-3 fw-bold rounded-4 shadow-sm">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
