<?php
// PROJECT ERP: ONE-CLICK LOCAL INITIALIZER
// Use this once after deploying to your localhost to set up the entire system.

session_start();
require_once(__DIR__ . '/config/db.php');

$page_title = "Local System Initialization | Project ERP";
$step = $_GET['step'] ?? 'verify';
$output = [];

if ($step === 'migrate') {
    try {
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $stmt) {
            if (empty($stmt)) continue;
            if (stripos($stmt, 'CREATE DATABASE') !== false || stripos($stmt, 'USE ') !== false) continue;
            $pdo->exec($stmt);
        }
        $output[] = "✅ Schema created successfully.";
        $output[] = "✅ Default Admin created: admin@scholarly.com";
        $output[] = "✅ Demo Students, Teachers & Parents added.";
    } catch (Exception $e) {
        $output[] = "❌ Error: " . $e->getMessage();
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
    <link rel="stylesheet" href="assets/css/style.css?v=2">
</head>
<body style="background: var(--bg-page); height: 100vh; display: flex; align-items: center; justify-content: center;">
    <div class="card-premium animate-up" style="max-width: 600px; text-align: center;">
        <div class="bg-primary-soft p-4 rounded-circle d-inline-block mb-4">
            <i class="fas fa-database text-primary fs-1"></i>
        </div>
        <h2 class="fw-800 mb-3">Project ERP Local Setup</h2>
        <p class="text-muted mb-5">Finalizing your local XAMPP environment. This will populate your local database with all required tables and demo data.</p>

        <?php if (!empty($output)): ?>
            <div class="text-start p-4 rounded-4 mb-4" style="background: var(--bg-page); border: 2px solid var(--border);">
                <?php foreach($output as $line): ?>
                    <div class="fw-bold mb-2"><?php echo $line; ?></div>
                <?php endforeach; ?>
                <div class="mt-4">
                    <a href="login.php" class="btn btn-primary w-100 py-3 rounded-4 fw-800">Launch Portal</a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-success border-0 rounded-4 mb-5 shadow-sm">
                <i class="fas fa-link me-2"></i> <strong>LOCAL DATABASE CONNECTED</strong>
            </div>
            <a href="?step=migrate" class="btn btn-premium w-100 py-3 rounded-4 fw-800 fs-5 shadow-lg">
                Populate Database & Finish Setup
            </a>
            <div class="mt-4 smallest text-muted fw-bold uppercase ls-2">Warning: This will overwrite existing tables in the database</div>
        <?php endif; ?>
    </div>
</body>
</html>
