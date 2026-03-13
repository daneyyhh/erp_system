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
    <title>Enlight Portal | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Inline script to prevent white flash during initial load -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <style>
        body {
            background: var(--bg-page);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
        }
        .login-card {
            width: 100%;
            max-width: 480px;
            background: var(--bg-card);
            border-radius: 40px;
            padding: 60px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            position: relative;
            z-index: 2;
        }
        .role-selector {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 40px;
        }
        .role-option {
            padding: 18px;
            border: 2px solid var(--border);
            border-radius: 20px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            background: var(--bg-card);
        }
        .role-option i {
            display: block;
            font-size: 1.3rem;
            margin-bottom: 8px;
            color: var(--text-muted);
        }
        .role-option span {
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--text-body);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .role-option:hover {
            border-color: var(--primary);
            background: var(--primary-soft);
        }
        .role-option.active {
            border-color: var(--primary);
            background: var(--primary);
        }
        .role-option.active i, .role-option.active span {
            color: white !important;
        }
        .form-control {
            border-radius: 18px;
            padding: 16px 24px;
            border: 2px solid var(--border);
            background: var(--input-bg);
            color: var(--text-main);
            font-weight: 500;
        }
        .form-control:focus {
            background: var(--bg-card);
            border-color: var(--primary);
            box-shadow: 0 0 0 5px rgba(94, 90, 219, 0.1);
            color: var(--text-main);
        }
        .btn-login {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 20px;
            padding: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
            box-shadow: 0 15px 30px -5px rgba(94, 90, 219, 0.3);
        }
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px -5px rgba(94, 90, 219, 0.4);
            color: white;
        }
        .mode-toggle-float {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>

    <div class="mode-toggle-float">
        <div class="theme-switch" id="themeToggle" onclick="toggleTheme()" title="Switch Appearance">
            <div class="toggle-ball shadow-lg">
                <i class="fas fa-sun" id="themeIcon"></i>
            </div>
        </div>
    </div>

    <div class="login-card animate-up">
        <div class="text-center mb-5">
            <div class="d-inline-block p-3 rounded-4 mb-4" style="background: var(--primary-soft);">
                <i class="fas fa-graduation-cap fs-1 text-primary"></i>
            </div>
            <h2 class="fw-800 mb-1" style="letter-spacing: -1px;">Project ERP</h2>
            <p class="text-muted small fw-500">Intelligent Academic Management System</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger border-0 small py-3 text-center mb-4" style="border-radius: 16px; background: #fee2e2; color: #b91c1c;">
                <i class="fas fa-exclamation-circle me-2"></i> Invalid login path or credentials.
                <div class="mt-2 smallest opacity-75">Hint: <b>admin@scholarly.com</b> / <b>admin123</b></div>
            </div>
        <?php endif; ?>

        <form action="auth/process_login.php" method="POST">
            <div class="mb-4">
                <label class="smallest fw-800 text-muted mb-2 uppercase ls-1">Academic Email</label>
                <input type="email" name="email" class="form-control" placeholder="admin@scholarly.com" required>
            </div>

            <div class="mb-5">
                <label class="smallest fw-800 text-muted mb-2 uppercase ls-1">Secure Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-login w-100">
                Log In to Project ERP <i class="fas fa-arrow-right ms-2 small"></i>
            </button>
        </form>
    </div>

    <script>
        const applyThemeLogic = (theme) => {
            const icon = document.getElementById('themeIcon');
            if(!icon) return;
            if(theme === 'dark') {
                icon.className = 'fas fa-moon animate-up';
            } else {
                icon.className = 'fas fa-sun animate-up';
            }
        };

        const toggleTheme = () => {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            applyThemeLogic(next);
        };

        // Initialize icon state
        applyThemeLogic(document.documentElement.getAttribute('data-theme'));
    </script>
</body>
</html>
