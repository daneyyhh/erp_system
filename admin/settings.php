<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$role = $_SESSION['user_role'];
$page_title = "Account Settings | Scholarly";
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
            <div class="content-area" style="max-width: 800px; margin: 0 auto;">
                <h2 class="fw-800 mb-2">Profile Settings</h2>
                <p class="text-muted mb-5">Update your personal information and security preferences.</p>

                <div class="card border-0 shadow-sm p-5 mb-4" style="border-radius: 30px;">
                    <div class="d-flex align-items-center gap-4 mb-5">
                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $_SESSION['user_name']; ?>" width="100" class="rounded-4">
                        <div>
                            <button class="btn btn-primary btn-sm px-4">Change Photo</button>
                            <button class="btn btn-outline-danger btn-sm px-4 ms-2">Remove</button>
                        </div>
                    </div>

                    <form>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="smallest fw-800 text-muted uppercase">Full Name</label>
                                <input type="text" class="form-control py-3" value="<?php echo $_SESSION['user_name']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="smallest fw-800 text-muted uppercase">Role</label>
                                <input type="text" class="form-control py-3" value="<?php echo ucfirst($role); ?>" disabled>
                            </div>
                            <div class="col-md-12">
                                <label class="smallest fw-800 text-muted uppercase">Email Address</label>
                                <input type="email" class="form-control py-3" placeholder="email@example.com">
                            </div>
                        </div>
                        <div class="mt-5">
                            <button class="btn btn-primary px-5 py-3 fw-bold rounded-4 shadow-sm">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
