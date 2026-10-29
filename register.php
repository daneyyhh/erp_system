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
    <title>PurpleHeart College | Register</title>
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
            padding: 80px; position: relative; overflow: hidden;
        }
        .left-panel::before {
            content:''; position:absolute; top:-120px; right:-120px;
            width:400px; height:400px; border-radius:50%;
            background:rgba(255,255,255,0.06);
        }
        .left-panel::after {
            content:''; position:absolute; bottom:-80px; left:-80px;
            width:300px; height:300px; border-radius:50%;
            background:rgba(255,255,255,0.04);
        }
        .brand-badge {
            display:inline-flex; align-items:center; gap:10px;
            background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.2);
            border-radius:9999px; padding:8px 18px;
            color:#fff; font-size:13px; font-weight:600; margin-bottom:40px;
        }
        .left-heading { font-size:3rem; font-weight:800; color:#fff; line-height:1.15; letter-spacing:-1.5px; margin-bottom:20px; }
        .left-sub { font-size:1rem; color:rgba(255,255,255,0.75); font-weight:400; line-height:1.7; max-width:460px; }

        .right-panel {
            width: 520px;
            background: #fff;
            display: flex; align-items: center; justify-content: center;
            padding: 40px; overflow-y: auto;
        }
        .form-card { width:100%; max-width:400px; }

        .form-logo {
            width:44px; height:44px;
            background:linear-gradient(135deg,#7C3AED,#A78BFA);
            border-radius:12px; display:flex; align-items:center; justify-content:center;
            color:#fff; font-size:18px;
            box-shadow:0 6px 16px rgba(124,58,237,0.3); margin-bottom:24px;
        }
        .form-title { font-size:1.5rem; font-weight:800; color:#1E1B4B; margin-bottom:4px; }
        .form-sub { font-size:13px; color:#9CA3AF; font-weight:500; margin-bottom:28px; }

        .field-label {
            display:block; font-size:11.5px; font-weight:700;
            text-transform:uppercase; letter-spacing:0.6px;
            color:#1E1B4B; margin-bottom:6px;
        }
        .field-input {
            width:100%; padding:11px 14px;
            border:1.5px solid #EDE9FE; border-radius:10px;
            background:#F8F7FF; font-size:14px; font-weight:500; color:#1E1B4B;
            transition:all 0.2s; margin-bottom:16px;
            font-family:'Inter',sans-serif; -webkit-appearance:none;
        }
        .field-input:focus {
            outline:none; border-color:#7C3AED;
            background:#fff; box-shadow:0 0 0 3px rgba(124,58,237,0.12);
        }
        select.field-input {
            cursor:pointer;
            background-image:url('data:image/svg+xml;utf8,<svg fill="%237C3AED" height="16" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat:no-repeat; background-position:right 12px top 50%; padding-right:36px;
        }
        .row-gap { display:flex; gap:12px; }
        .row-gap > div { flex:1; }

        .btn-submit {
            width:100%; padding:13px;
            background:linear-gradient(135deg,#7C3AED,#6D28D9);
            color:#fff; border:none; border-radius:10px;
            font-size:14px; font-weight:700; cursor:pointer;
            box-shadow:0 6px 18px rgba(124,58,237,0.35);
            transition:all 0.2s; margin-top:8px;
            display:flex; align-items:center; justify-content:center; gap:8px;
        }
        .btn-submit:hover { transform:translateY(-1px); box-shadow:0 10px 25px rgba(124,58,237,0.45); }

        .login-link { text-align:center; margin-top:20px; font-size:13px; color:#9CA3AF; font-weight:500; }
        .login-link a { color:#7C3AED; font-weight:700; text-decoration:none; }

        .error-box {
            background:#FEF2F2; border:1.5px solid #FCA5A5; color:#DC2626;
            border-radius:10px; padding:12px 16px; font-size:13px; font-weight:600;
            display:flex; align-items:center; gap:10px; margin-bottom:20px;
        }
        .success-box {
            background:#F0FDF4; border:1.5px solid #86EFAC; color:#166534;
            border-radius:10px; padding:12px 16px; font-size:13px; font-weight:600;
            display:flex; align-items:center; gap:10px; margin-bottom:20px;
        }
        @media (max-width:900px) {
            .left-panel { display:none; }
            .right-panel { width:100%; }
        }
    </style>
</head>
<body>
    <div class="left-panel">
        <div class="brand-badge"><i class="fas fa-heart"></i> PurpleHeart College</div>
        <h1 class="left-heading">Start your digital journey with us.</h1>
        <p class="left-sub">Register today to access your secure portal for tracking attendance, managing fees, and obtaining official documents directly from your personal dashboard.</p>
    </div>

    <div class="right-panel">
        <div class="form-card">
            <div class="form-logo"><i class="fas fa-heart"></i></div>
            <h2 class="form-title">Create Account</h2>
            <p class="form-sub">Register your access credentials below.</p>

            <?php if (isset($_GET['error'])): ?>
                <div class="error-box">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php
                        if($_GET['error'] == 'email_exists') echo 'Email is already registered.';
                        else if($_GET['error'] == 'roll_exists') echo 'Roll Number is already taken.';
                        else if($_GET['error'] == 'invalid_role') echo 'Invalid account type.';
                        else if($_GET['error'] == 'missing_fields') echo 'Please fill all required fields.';
                        else echo 'An error occurred. Please try again.';
                    ?>
                </div>
            <?php elseif (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                <div class="success-box">
                    <i class="fas fa-check-circle"></i> Registration successful! You can now log in.
                </div>
            <?php endif; ?>

            <form action="auth/process_register.php" method="POST">
                <label class="field-label">Account Type</label>
                <select name="role" id="roleSelect" class="field-input" onchange="toggleFields()" required>
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                    <option value="parent">Parent</option>
                </select>

                <label class="field-label">Full Name</label>
                <input type="text" name="name" class="field-input" placeholder="John Doe" required>

                <label class="field-label">Email Address</label>
                <input type="email" name="email" class="field-input" placeholder="your@email.com" required>

                <label class="field-label">Phone Number</label>
                <input type="tel" name="phone" class="field-input" placeholder="+91 6235985969" required>

                <label class="field-label">Password</label>
                <input type="password" name="password" class="field-input" placeholder="••••••••" required>

                <div id="studentFields">
                    <div class="row-gap">
                        <div>
                            <label class="field-label">Roll Number</label>
                            <input type="text" name="roll_no" id="rollNoInput" class="field-input" placeholder="S1002">
                        </div>
                        <div>
                            <label class="field-label">Class</label>
                            <input type="text" name="class" class="field-input" placeholder="BCA 1st Year">
                        </div>
                    </div>
                </div>

                <div id="teacherFields" style="display:none;">
                    <div class="row-gap">
                        <div>
                            <label class="field-label">Subject</label>
                            <input type="text" name="subject" id="subjectInput" class="field-input" placeholder="Mathematics">
                        </div>
                        <div>
                            <label class="field-label">Department</label>
                            <input type="text" name="department" class="field-input" placeholder="Science">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    Create Account <i class="fas fa-user-plus"></i>
                </button>
            </form>

            <div class="login-link">
                Already registered? <a href="login.php">Sign in here</a>
            </div>
        </div>
    </div>

    <script>
        function toggleFields() {
            var role = document.getElementById('roleSelect').value;
            var sf = document.getElementById('studentFields');
            var tf = document.getElementById('teacherFields');
            var rn = document.getElementById('rollNoInput');
            var si = document.getElementById('subjectInput');
            if (role === 'student') {
                sf.style.display='block'; tf.style.display='none';
                rn.required=true; si.required=false;
            } else if (role === 'teacher') {
                sf.style.display='none'; tf.style.display='block';
                rn.required=false; si.required=true;
            } else {
                sf.style.display='none'; tf.style.display='none';
                rn.required=false; si.required=false;
            }
        }
        window.onload = toggleFields;
    </script>
</body>
</html>
