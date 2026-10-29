<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: " . $_SESSION['user_role'] . "/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PurpleHeart College | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#F8F7FF; display:flex; min-height:100vh; }

        .left-panel {
            flex: 1;
            background: linear-gradient(145deg, #4C1D95 0%, #7C3AED 50%, #A78BFA 100%);
            display: flex; flex-direction: column;
            justify-content: center; align-items: flex-start;
            padding: 80px;
            position: relative; overflow: hidden;
        }
        .left-panel::before {
            content: '';
            position: absolute; top: -120px; right: -120px;
            width: 400px; height: 400px; border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }
        .left-panel::after {
            content: '';
            position: absolute; bottom: -80px; left: -80px;
            width: 300px; height: 300px; border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .brand-badge {
            display: inline-flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 9999px;
            padding: 8px 18px;
            color: #fff; font-size: 13px; font-weight: 600;
            margin-bottom: 40px;
        }
        .left-heading {
            font-size: 3.2rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.15;
            letter-spacing: -1.5px;
            margin-bottom: 24px;
        }
        .left-sub {
            font-size: 1.05rem;
            color: rgba(255,255,255,0.75);
            font-weight: 400;
            line-height: 1.7;
            max-width: 460px;
        }
        .stats-row {
            display: flex; gap: 32px;
            margin-top: 48px;
        }
        .stat-item { color: rgba(255,255,255,0.9); }
        .stat-num { font-size: 1.8rem; font-weight: 800; line-height: 1; }
        .stat-label { font-size: 12px; opacity: 0.7; margin-top: 4px; }

        .right-panel {
            width: 480px;
            background: #fff;
            display: flex; align-items: center; justify-content: center;
            padding: 48px 40px;
        }
        .form-card { width: 100%; max-width: 360px; }

        .form-logo {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, #7C3AED, #A78BFA);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 20px;
            box-shadow: 0 8px 20px rgba(124,58,237,0.3);
            margin-bottom: 28px;
        }
        .form-title { font-size: 1.6rem; font-weight: 800; color: #1E1B4B; margin-bottom: 6px; }
        .form-sub { font-size: 13px; color: #9CA3AF; font-weight: 500; margin-bottom: 32px; }

        .field-label {
            display: block; font-size: 12px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.6px;
            color: #1E1B4B; margin-bottom: 8px;
        }
        .field-input {
            width: 100%; padding: 12px 16px;
            border: 1.5px solid #EDE9FE;
            border-radius: 10px;
            background: #F8F7FF;
            font-size: 14px; font-weight: 500; color: #1E1B4B;
            transition: all 0.2s; margin-bottom: 20px;
            font-family: 'Inter', sans-serif;
        }
        .field-input:focus {
            outline: none; border-color: #7C3AED;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(124,58,237,0.12);
        }
        .forgot-link {
            display: block; text-align: right;
            font-size: 12.5px; font-weight: 600;
            color: #7C3AED; text-decoration: none;
            margin-top: -12px; margin-bottom: 24px;
        }
        .forgot-link:hover { color: #6D28D9; }

        .btn-login {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #7C3AED, #6D28D9);
            color: #fff; border: none;
            border-radius: 10px;
            font-size: 15px; font-weight: 700;
            cursor: pointer;
            box-shadow: 0 6px 18px rgba(124,58,237,0.35);
            transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(124,58,237,0.45);
        }

        .register-link {
            text-align: center; margin-top: 24px;
            font-size: 13px; color: #9CA3AF; font-weight: 500;
        }
        .register-link a { color: #7C3AED; font-weight: 700; text-decoration: none; }

        .error-box {
            background: #FEF2F2; border: 1.5px solid #FCA5A5;
            color: #DC2626; border-radius: 10px;
            padding: 12px 16px; font-size: 13px; font-weight: 600;
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 24px;
        }
        .success-box {
            background: #F0FDF4; border: 1.5px solid #86EFAC;
            color: #166534; border-radius: 10px;
            padding: 12px 16px; font-size: 13px; font-weight: 600;
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 24px;
        }

        @media (max-width: 900px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="left-panel">
        <div class="brand-badge"><i class="fas fa-heart"></i> PurpleHeart College</div>
        <h1 class="left-heading">The smart way to manage your academic life.</h1>
        <p class="left-sub">Access attendance records, fee tracking, exam results, certificate requests and more — all in one unified portal designed for your institution.</p>
        <div class="stats-row">
            <div class="stat-item"><div class="stat-num">15K+</div><div class="stat-label">Students</div></div>
            <div class="stat-item"><div class="stat-num">2K+</div><div class="stat-label">Teachers</div></div>
            <div class="stat-item"><div class="stat-num">98%</div><div class="stat-label">Satisfaction</div></div>
        </div>
    </div>

    <div class="right-panel">
        <div class="form-card">
            <div class="form-logo"><i class="fas fa-heart"></i></div>
            <h2 class="form-title">Welcome back</h2>
            <p class="form-sub">Sign in to access your portal dashboard.</p>

            <?php if (isset($_GET['error'])): ?>
                <div class="error-box"><i class="fas fa-exclamation-circle"></i> Invalid email or password.</div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'password_reset'): ?>
                <div class="success-box"><i class="fas fa-check-circle"></i> Password reset successfully. You can now log in.</div>
            <?php endif; ?>

            <form action="auth/process_login.php" method="POST">
                <label class="field-label">Email Address</label>
                <input type="email" name="email" class="field-input" placeholder="your@email.com" required>

                <label class="field-label">Password</label>
                <input type="password" name="password" class="field-input" placeholder="••••••••" required>

                <a href="forgot_password.php" class="forgot-link">Forgot password?</a>

                <button type="submit" class="btn-login">
                    Sign In <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="register-link">
                New here? <a href="register.php">Create an account</a>
            </div>
        </div>
    </div>
</body>
</html>
