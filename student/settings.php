<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$page_title = "Account Settings | Enlight";
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
                        <h2 class="fw-800 mb-1">Account & Preferences</h2>
                        <p class="text-muted small fw-600">Update your security and profile settings</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card-premium animate-up">
                            <h5 class="fw-800 mb-4">Security Settings</h5>
                            <form>
                                <div class="mb-4">
                                    <label class="smallest fw-800 text-muted uppercase ls-1 mb-2">Current Password</label>
                                    <input type="password" class="form-control" placeholder="••••••••">
                                </div>
                                <div class="mb-4">
                                    <label class="smallest fw-800 text-muted uppercase ls-1 mb-2">New Password</label>
                                    <input type="password" class="form-control" placeholder="Enter new password">
                                </div>
                                <button type="button" class="btn btn-premium px-5">Update Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
