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
    <title>PurpleHeart College | Portal Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #F8F9FA;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .split-layout {
            display: flex;
            width: 100%;
            height: 100vh;
        }

        /* LEFT SIDE - BRANDING */
        .brand-side {
            flex: 1;
            background: #FDFDFD;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 80px;
            overflow: hidden;
        }

        .brand-text-container {
            position: relative;
            z-index: 10;
            max-width: 600px;
        }

        .purple-heading {
            font-size: 4rem;
            font-weight: 800;
            color: #8E24AA;
            line-height: 1.1;
            margin-bottom: 24px;
            letter-spacing: -1.5px;
        }

        .purple-subtext {
            font-size: 1.25rem;
            color: #636E72;
            font-weight: 500;
            line-height: 1.6;
        }

        /* Abstract purple curves from Behance UI */
        .abstract-curve-top {
            position: absolute;
            top: -100px;
            right: -100px;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            border: 40px solid #F3E5F5;
            z-index: 1;
            opacity: 0.6;
        }
        
        .abstract-curve-top::after {
            content: '';
            position: absolute;
            top: 40px;
            right: 40px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            border: 20px solid rgba(142, 36, 170, 0.1);
        }

        .abstract-curve-bottom {
            position: absolute;
            bottom: -150px;
            left: -150px;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            border: 2px solid #8E24AA;
            z-index: 1;
            box-shadow: inset 0 0 0 40px transparent, inset 0 0 0 42px #8E24AA, inset 0 0 0 60px transparent, inset 0 0 0 62px #8E24AA, inset 0 0 0 80px transparent, inset 0 0 0 82px #8E24AA;
            opacity: 0.15;
            transform: rotate(45deg);
        }

        /* RIGHT SIDE - LOGIN */
        .login-side {
            width: 550px;
            background: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            box-shadow: -20px 0 50px rgba(0,0,0,0.03);
            z-index: 20;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #FFFFFF;
        }

        .login-header {
            margin-bottom: 40px;
        }

        .college-logo-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: center;
        }

        .college-logo {
            width: 56px;
            height: 56px;
            background: #8E24AA;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 24px;
            box-shadow: 0 10px 20px rgba(142, 36, 170, 0.2);
        }

        .college-name {
            font-size: 1.5rem;
            font-weight: 800;
            color: #2D3436;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .welcome-back {
            font-size: 2rem;
            font-weight: 800;
            color: #2D3436;
            margin-bottom: 8px;
        }

        .instruction-text {
            color: #636E72;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: #2D3436;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .form-control-custom {
            width: 100%;
            padding: 16px 20px;
            border-radius: 12px;
            border: 2px solid #E2E8F0;
            background: #F8FAFC;
            font-size: 1rem;
            font-weight: 500;
            color: #2D3436;
            transition: 0.3s;
            margin-bottom: 24px;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: #8E24AA;
            background: #FFFFFF;
            box-shadow: 0 0 0 4px #F3E5F5;
        }

        .btn-purple {
            width: 100%;
            padding: 16px;
            background: #8E24AA;
            color: #FFFFFF;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 10px 25px rgba(142, 36, 170, 0.25);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .btn-purple:hover {
            background: #7B1FA2;
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(142, 36, 170, 0.35);
        }

        .error-card {
            background: #FEF2F2;
            border: 1px solid #FCA5A5;
            color: #991B1B;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        @media (max-width: 992px) {
            .brand-side { display: none; }
            .login-side { width: 100%; box-shadow: none; }
        }
    </style>
</head>
<body>

    <div class="split-layout">
        <!-- Behance Featured UI Branding Side -->
        <div class="brand-side">
            <div class="abstract-curve-top"></div>
            <div class="abstract-curve-bottom"></div>
            
            <div class="brand-text-container animate-up">
                <h1 class="purple-heading">The all-new school portal fixes that, and more...</h1>
                <p class="purple-subtext">Designed for an effortless experience. Log in to track attendance, clear fees, apply for official certificates, and trace your academic progress in real-time right from your dashboard.</p>
            </div>
        </div>

        <!-- Login Card Side -->
        <div class="login-side">
            <div class="login-card animate-up" style="animation-delay: 0.2s;">
                <div class="login-header text-center">
                    <div class="college-logo-wrapper">
                        <div class="college-logo"><i class="fas fa-heart"></i></div>
                        <h2 class="college-name">PurpleHeart <span style="font-weight:400;">College</span></h2>
                    </div>
                    <div style="text-align: left; margin-top: 50px;">
                        <h3 class="welcome-back">Sign In</h3>
                        <p class="instruction-text">Enter your academic credentials to access the portal.</p>
                    </div>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="error-card">
                        <i class="fas fa-exclamation-circle fs-5"></i>
                        <div>
                            <div>Authentication Failed</div>
                            <div style="font-size: 0.8rem; opacity: 0.8; margin-top: 2px;">Check your email or password.</div>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="auth/process_login.php" method="POST">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control-custom" placeholder="e.g. admin@scholarly.com" required>

                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control-custom" placeholder="••••••••" required>

                    <div style="text-align: right; margin-top: -15px; margin-bottom: 25px;">
                        <a href="#" style="color: #8E24AA; font-size: 0.85rem; font-weight: 700; text-decoration: none;">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn-purple">
                        Secure Authentication <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
