<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$page_title = "Transport & Logistics | Enlight";
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
<body>
    <div class="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div class="main-content">
            <?php include('../includes/topbar.php'); ?>
            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-5 animate-up">
                    <div>
                        <h2 class="fw-800 mb-1">Official Transport</h2>
                        <p class="text-muted small fw-600">Track campus shuttle services and routes</p>
                    </div>
                </div>

                <div class="card-premium animate-up">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <h4 class="fw-800 mb-3">Bus Route: R-104 (Downtown)</h4>
                            <p class="text-muted fw-600 mb-4">Your assigned vehicle is currently in operation. Track your pick-up points and timing below.</p>
                            <div class="lecturer-item border shadow-sm mb-3">
                                <div class="bg-primary-soft p-3 rounded-4 me-3">
                                    <i class="fas fa-bus text-primary fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-800 mb-1">Morning Pickup: 07:30 AM</h6>
                                    <p class="smallest text-muted mb-0 fw-bold uppercase">Location: Main Square Park</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5 text-center">
                            <i class="fas fa-map-location-dot fa-8x opacity-10 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
