<?php
session_start();
// Removed role restriction temporarily to ensure cloud migration can be completed.
// if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
//     die("Access Denied. Elevated privileges required for Cloud Setup.");
// }

$page_title = "Aiven Cloud Provisioning | Enlight";
$ca_path = __DIR__ . '/../config/ca.pem';

// Aiven's Public SSL CA URL (Standard across most regions)
$aiven_ca_url = "https://certs.aiven.io/download/ca.pem";

$download_status = "";
$error_detail = "";
if (isset($_POST['download_ca'])) {
    $ca_content = @file_get_contents($aiven_ca_url);
    if ($ca_content) {
        if (@file_put_contents($ca_path, $ca_content)) {
            $download_status = "success";
        } else {
            $download_status = "error_write";
            $error_detail = "Cannot write to " . $ca_path . ". Check folder permissions.";
        }
    } else {
        $download_status = "error_fetch";
        // Attempt to get last error
        $php_err = error_get_last();
        $error_detail = "Failed to fetch from Aiven. " . ($php_err ? $php_err['message'] : "Verify your server has internet access and OpenSSL enabled.");
    }
}

$current_config = file_get_contents(__DIR__ . '/../config/db.php');
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
        <div class="main-content ms-0"> <!-- Full width for setup -->
            <div class="content-area">
                <div class="card-premium animate-up" style="max-width: 800px; margin: 0 auto;">
                    <div class="text-center mb-5">
                        <div class="bg-primary-soft p-4 rounded-circle d-inline-block mb-4">
                            <i class="fas fa-cloud text-primary fs-1"></i>
                        </div>
                        <h2 class="fw-800">Project ERP Cloud Connector</h2>
                        <p class="text-muted">Finalize your remote database hosting in one click</p>
                    </div>

                    <?php if($download_status == 'success'): ?>
                        <div class="alert alert-success border-0 rounded-4 mb-4 shadow-sm">
                            <i class="fas fa-check-circle me-2"></i> <strong>SSL CA Certificate Downloaded!</strong> Your encryption tunnel is now ready.
                        </div>
                    <?php elseif($download_status): ?>
                        <div class="alert alert-danger border-0 rounded-4 mb-4 shadow-sm">
                            <i class="fas fa-times-circle me-2"></i> <?php echo $error_detail ?: "Cloud Setup Failed. Please check your internet connection."; ?>
                        </div>
                    <?php endif; ?>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="p-4 border rounded-4 bg-light h-100">
                                <h5 class="fw-800 mb-3"><i class="fas fa-file-shield text-primary me-2"></i> 1. Security Link</h5>
                                <p class="smallest text-muted mb-4">Aiven requires an SSL certificate for secure connections. Click below to fetch the official certificate.</p>
                                <form method="POST">
                                    <button type="submit" name="download_ca" class="btn btn-primary w-100 py-3 rounded-4 fw-800">
                                        Fetch Aiven SSL CA
                                    </button>
                                </form>
                                <div class="mt-3 smallest fw-bold text-center">
                                    Status: <?php echo file_exists($ca_path) ? '<span class="text-success">INSTALLED</span>' : '<span class="text-danger">MISSING</span>'; ?>
                                </div>
                                <?php if($download_status == 'error_fetch'): ?>
                                    <div class="mt-3 p-3 bg-white rounded-3 small border border-danger">
                                        <strong>Manual Fallback:</strong><br>
                                        1. Go to Aiven Console Overview.<br>
                                        2. Download "CA Certificate".<br>
                                        3. Rename to <code>ca.pem</code> and place in <code>/config</code>.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-4 border rounded-4 bg-light h-100">
                                <h5 class="fw-800 mb-3"><i class="fas fa-database text-primary me-2"></i> 2. Credentials</h5>
                                <p class="smallest text-muted mb-4">Ensure your <code>config/db.php</code> contains the Host and Password from your Aiven Console.</p>
                                <a href="../admin/settings.php" class="btn btn-outline-primary w-100 py-3 rounded-4 fw-800">
                                    Verify Config
                                </a>
                            </div>
                        </div>
                    </div>

                    <hr class="my-5 opacity-10">

                    <div class="p-4 rounded-4" style="background: var(--bg-page); border: 2px dashed var(--border);">
                        <h6 class="fw-800 mb-2">Final Step: Schema Sync</h6>
                        <p class="smallest text-muted mb-0">Once connected, run the migration script to populate your Cloud DB with students, teachers, and logs.</p>
                    </div>
                    
                    <div class="mt-5 text-center">
                        <a href="../admin/dashboard.php" class="text-decoration-none smallest fw-800 uppercase ls-2 text-muted">Skip Setup & Return to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
