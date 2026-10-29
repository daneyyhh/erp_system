<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

$page_title = "Alumni Network | Project ERP";
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
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Alumni Network</h2>
                        <p class="text-muted small fw-600">Connect with distinguished graduates</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12">
                        <div class="card-premium animate-up border-0" style="background: linear-gradient(135deg, #1e1b4b, #4c1d95); color: white; padding: 5rem 2rem; border-radius: 24px; text-align: center; box-shadow: 0 20px 40px rgba(76, 29, 149, 0.3);">
                            <i class="fas fa-network-wired fa-5x mb-4 text-white opacity-75"></i>
                            <h2 class="fw-900 text-white mb-3" style="letter-spacing: -1px; font-size: 2.5rem;">The Global Alumni Network</h2>
                            <p class="opacity-75 fs-5 mx-auto mb-5" style="max-width: 650px;">We are actively synchronizing our global database. Soon, you will be able to search for mentors, find job opportunities, and engage with thousands of past graduates.</p>
                            <button class="btn btn-light fw-900 px-5 py-3 text-primary mt-2" style="border-radius: 12px; font-size: 1.1rem; box-shadow: 0 10px 20px rgba(0,0,0,0.2);">
                                <i class="fas fa-bell me-2"></i> Join Waitlist
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
