<?php
session_start();
require_once('config/db.php');
require_once('utils/sms_sender.php');

$step    = $_SESSION['otp_step'] ?? 1;
$error   = '';
$success = '';

// ─── STEP 1: Send OTP to phone ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_otp'])) {
    $phone_input = trim($_POST['phone']);

    // Look up user by phone number (try multiple formats)
    $phone_clean = preg_replace('/\D/', '', $phone_input);
    $variants    = [];
    if (strlen($phone_clean) === 10) {
        $variants = [$phone_clean, '0'.$phone_clean, '+91'.$phone_clean, '91'.$phone_clean, '+91 '.$phone_clean];
    } elseif (strlen($phone_clean) === 12 && str_starts_with($phone_clean, '91')) {
        $ten = substr($phone_clean, 2);
        $variants = [$ten, '+91'.$ten, '91'.$ten, '+91 '.$ten, '0'.$ten, $phone_clean];
    } else {
        $variants = [$phone_input, $phone_clean];
    }

    $user = null;
    foreach ($variants as $v) {
        $stmt = $pdo->prepare("SELECT id, name, phone, email FROM users WHERE phone = ? OR REPLACE(REPLACE(REPLACE(phone,' ',''),'+',''),'-','') = ?");
        $stmt->execute([$v, $phone_clean]);
        $user = $stmt->fetch();
        if ($user) break;
    }

    if (!$user) {
        $error = "No account found with that phone number.";
    } else {
        // Generate 6-digit OTP
        $otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Save OTP in DB using MySQL time (no timezone mismatch)
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$user['email']]);
        $pdo->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))")
            ->execute([$user['email'], $otp]);

        // Send SMS
        $result = sendOTPSms($user['phone'], $otp);

        if ($result === true) {
            $_SESSION['reset_email'] = $user['email'];
            $_SESSION['reset_phone'] = $user['phone'];
            $_SESSION['otp_step']    = 2;
            $step    = 2;
            // Show masked phone
            $masked  = substr(preg_replace('/\D/', '', $user['phone']), -4);
            $success = "OTP sent via SMS to ******$masked. Check your messages.";
        } elseif ($result === 'NOT_CONFIGURED') {
            // Test mode — show OTP on screen
            $_SESSION['reset_email'] = $user['email'];
            $_SESSION['reset_phone'] = $user['phone'];
            $_SESSION['otp_step']    = 2;
            $_SESSION['test_otp']    = $otp;
            $step    = 2;
            $success = "SMS not configured yet — using test mode.";
        } elseif ($result === 'QUOTA_EXCEEDED') {
            // Daily free SMS limit hit — show OTP on screen as fallback
            $_SESSION['reset_email'] = $user['email'];
            $_SESSION['reset_phone'] = $user['phone'];
            $_SESSION['otp_step']    = 2;
            $_SESSION['test_otp']    = $otp;
            $step    = 2;
            $success = "Daily free SMS limit reached. OTP shown below for testing.";
        } else {
            $error = "Could not send SMS: $result";
        }
    }
}

// ─── STEP 2: Verify OTP ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $otp   = trim($_POST['otp']);
    $email = $_SESSION['reset_email'] ?? '';
    $step  = 2;

    if (!$email) {
        $error = "Session expired. Please start again.";
        $step  = 1;
        unset($_SESSION['otp_step']);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND TRIM(otp) = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$email, $otp]);
        $record = $stmt->fetch();

        if ($record) {
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
            $_SESSION['otp_step']     = 3;
            $_SESSION['otp_verified'] = true;
            unset($_SESSION['test_otp']);
            $step    = 3;
            $success = "Phone verified! Set your new password.";
        } else {
            $error = "Incorrect OTP or it has expired. Please check the code and try again.";
        }
    }
}

// ─── STEP 3: Reset Password ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $step  = 3;
    $email = $_SESSION['reset_email'] ?? '';

    if (!($_SESSION['otp_verified'] ?? false)) {
        $error = "Not authorised. Please verify OTP first.";
        $step  = 1;
    } elseif (strlen($_POST['new_password']) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
        $error = "Passwords do not match.";
    } else {
        $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$hash, $email]);
        unset($_SESSION['otp_step'], $_SESSION['otp_verified'], $_SESSION['reset_email'], $_SESSION['reset_phone'], $_SESSION['test_otp']);
        header("Location: login.php?msg=password_reset");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $step = $_SESSION['otp_step'] ?? 1;
}
$test_otp = $_SESSION['test_otp'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PurpleHeart ERP | Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Inter',sans-serif; background:#F8F7FF;
            min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px;
        }
        .card {
            width:100%; max-width:420px; background:#fff;
            border-radius:22px; padding:40px;
            border:1.5px solid #EDE9FE;
            box-shadow:0 16px 48px rgba(124,58,237,0.12);
        }
        .logo {
            width:52px; height:52px;
            background:linear-gradient(135deg,#7C3AED,#A78BFA);
            border-radius:14px; display:flex; align-items:center; justify-content:center;
            color:#fff; font-size:22px; margin-bottom:22px;
            box-shadow:0 6px 18px rgba(124,58,237,0.3);
        }
        .card-title { font-size:1.5rem; font-weight:800; color:#1E1B4B; margin-bottom:5px; }
        .card-sub   { font-size:13px; color:#9CA3AF; font-weight:500; margin-bottom:26px; line-height:1.6; }

        /* Progress */
        .steps { display:flex; gap:6px; margin-bottom:26px; }
        .step-dot {
            flex:1; height:5px; border-radius:9999px; background:#EDE9FE; transition:all 0.3s;
        }
        .step-dot.done    { background:#7C3AED; }
        .step-dot.current { background:linear-gradient(90deg,#7C3AED,#A78BFA); }

        /* Phone input */
        .phone-wrap {
            display:flex; align-items:center;
            border:1.5px solid #EDE9FE; border-radius:10px;
            background:#F8F7FF; overflow:hidden;
            margin-bottom:20px; transition:all 0.2s;
        }
        .phone-wrap:focus-within {
            border-color:#7C3AED; background:#fff;
            box-shadow:0 0 0 3px rgba(124,58,237,0.12);
        }
        .phone-prefix {
            padding:12px 14px;
            font-size:14px; font-weight:700; color:#7C3AED;
            background:#F5F3FF; border-right:1.5px solid #EDE9FE;
            white-space:nowrap; display:flex; align-items:center; gap:6px;
        }
        .phone-prefix img { width:20px; height:14px; border-radius:2px; }
        .phone-field {
            flex:1; padding:12px 14px;
            border:none; background:transparent;
            font-size:15px; font-weight:600; color:#1E1B4B;
            font-family:'Inter',sans-serif; outline:none;
            letter-spacing:1px;
        }
        .phone-field::placeholder { color:#C4B5FD; font-weight:500; letter-spacing:0; }

        /* OTP boxes */
        .otp-boxes {
            display:flex; gap:10px; margin-bottom:20px; justify-content:center;
        }
        .otp-box-digit {
            width:50px; height:58px;
            border:1.5px solid #EDE9FE; border-radius:12px;
            background:#F8F7FF; text-align:center;
            font-size:22px; font-weight:800; color:#1E1B4B;
            font-family:'Inter',sans-serif; outline:none;
            transition:all 0.2s; caret-color: #7C3AED;
        }
        .otp-box-digit:focus {
            border-color:#7C3AED; background:#fff;
            box-shadow:0 0 0 3px rgba(124,58,237,0.12);
        }
        .otp-box-digit.filled { border-color:#7C3AED; background:#F5F3FF; color:#7C3AED; }
        /* Hidden input that holds the combined value */
        #otpHidden { display:none; }

        /* Label */
        .field-label {
            display:block; font-size:11.5px; font-weight:700;
            text-transform:uppercase; letter-spacing:0.6px;
            color:#1E1B4B; margin-bottom:10px;
        }
        .field-input {
            width:100%; padding:12px 14px;
            border:1.5px solid #EDE9FE; border-radius:10px;
            background:#F8F7FF; font-size:14px; font-weight:500; color:#1E1B4B;
            transition:all 0.2s; margin-bottom:20px;
            font-family:'Inter',sans-serif;
        }
        .field-input:focus {
            outline:none; border-color:#7C3AED;
            background:#fff; box-shadow:0 0 0 3px rgba(124,58,237,0.12);
        }

        /* Button */
        .btn-submit {
            width:100%; padding:14px;
            background:linear-gradient(135deg,#7C3AED,#6D28D9);
            color:#fff; border:none; border-radius:10px;
            font-size:15px; font-weight:700; cursor:pointer;
            box-shadow:0 6px 18px rgba(124,58,237,0.35);
            transition:all 0.2s;
            display:flex; align-items:center; justify-content:center; gap:8px;
        }
        .btn-submit:hover { transform:translateY(-1px); box-shadow:0 10px 25px rgba(124,58,237,0.45); }
        .btn-submit:disabled { opacity:0.6; cursor:not-allowed; transform:none !important; }

        /* Messages */
        .msg-box {
            border-radius:10px; padding:12px 14px; font-size:13px; font-weight:600;
            display:flex; align-items:flex-start; gap:8px; margin-bottom:20px; line-height:1.5;
        }
        .msg-error   { background:#FEF2F2; border:1.5px solid #FCA5A5; color:#DC2626; }
        .msg-success { background:#F0FDF4; border:1.5px solid #86EFAC; color:#166534; }
        .msg-test    { background:#FFFBEB; border:1.5px solid #FCD34D; color:#92400E; }

        .timer-row { text-align:center; font-size:12px; color:#9CA3AF; margin:-4px 0 18px; }
        #countdown { font-weight:700; color:#7C3AED; }

        .back-link   { text-align:center; margin-top:18px; }
        .back-link a { color:#7C3AED; font-size:13px; font-weight:600; text-decoration:none; }
        .resend-row  { text-align:center; margin-top:14px; font-size:13px; color:#9CA3AF; }
        .resend-row a { color:#7C3AED; font-weight:700; text-decoration:none; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo"><i class="fas fa-mobile-alt"></i></div>
    <h2 class="card-title">Reset Password</h2>

    <!-- Step progress -->
    <div class="steps">
        <div class="step-dot <?php echo $step == 1 ? 'current' : ($step > 1 ? 'done' : ''); ?>"></div>
        <div class="step-dot <?php echo $step == 2 ? 'current' : ($step > 2 ? 'done' : ''); ?>"></div>
        <div class="step-dot <?php echo $step == 3 ? 'current' : ''; ?>"></div>
    </div>

    <!-- Alerts -->
    <?php if($error): ?>
        <div class="msg-box msg-error"><i class="fas fa-exclamation-circle" style="flex-shrink:0;margin-top:2px;"></i><span><?php echo $error; ?></span></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="msg-box msg-success"><i class="fas fa-check-circle" style="flex-shrink:0;margin-top:2px;"></i><span><?php echo $success; ?></span></div>
    <?php endif; ?>
    <?php if($test_otp): ?>
        <div class="msg-box msg-test" style="justify-content:center; flex-direction:column; align-items:center; text-align:center;">
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;"><i class="fas fa-flask me-1"></i> Test Mode — SMS Not Configured</div>
            <div style="font-size:28px; font-weight:900; letter-spacing:10px; color:#92400E;"><?php echo $test_otp; ?></div>
            <div style="font-size:11px; opacity:0.7; margin-top:4px;">Enter this OTP below</div>
        </div>
    <?php endif; ?>

    <!-- ─── STEP 1: Phone number ─── -->
    <?php if ($step == 1): ?>
        <p class="card-sub">Enter your registered mobile number. We'll send a 6-digit OTP via SMS.</p>
        <form method="POST">
            <label class="field-label"><i class="fas fa-phone me-1"></i> Mobile Number</label>
            <div class="phone-wrap">
                <div class="phone-prefix">
                    🇮🇳 +91
                </div>
                <input type="tel" name="phone" class="phone-field"
                       placeholder="98765 43210" maxlength="10"
                       inputmode="numeric" pattern="\d{10}"
                       autocomplete="tel-national" required autofocus>
            </div>
            <button type="submit" name="request_otp" class="btn-submit">
                <i class="fas fa-sms"></i> Send OTP via SMS
            </button>
        </form>
        <div class="back-link">
            <a href="login.php"><i class="fas fa-arrow-left" style="font-size:11px;"></i> Back to Login</a>
        </div>

    <!-- ─── STEP 2: OTP entry ─── -->
    <?php elseif ($step == 2): ?>
        <p class="card-sub">Enter the 6-digit OTP sent to your phone via SMS.</p>
        <form method="POST" onsubmit="combineOTP()">
            <label class="field-label" style="text-align:center; display:block;"><i class="fas fa-key me-1"></i> One-Time Password</label>
            <div class="otp-boxes" id="otpBoxes">
                <input class="otp-box-digit" maxlength="1" inputmode="numeric" pattern="\d" autofocus>
                <input class="otp-box-digit" maxlength="1" inputmode="numeric" pattern="\d">
                <input class="otp-box-digit" maxlength="1" inputmode="numeric" pattern="\d">
                <input class="otp-box-digit" maxlength="1" inputmode="numeric" pattern="\d">
                <input class="otp-box-digit" maxlength="1" inputmode="numeric" pattern="\d">
                <input class="otp-box-digit" maxlength="1" inputmode="numeric" pattern="\d">
            </div>
            <input type="hidden" name="otp" id="otpHidden">
            <div class="timer-row">
                OTP expires in <span id="countdown">15:00</span>
            </div>
            <button type="submit" name="verify_otp" id="verifyBtn" class="btn-submit">
                <i class="fas fa-shield-check"></i> Verify OTP
            </button>
        </form>
        <div class="resend-row">
            Didn't receive it?
            <a href="forgot_password.php" onclick="clearOTPSession()">Resend SMS</a>
        </div>

    <!-- ─── STEP 3: New password ─── -->
    <?php elseif ($step == 3): ?>
        <p class="card-sub">Phone verified. Choose a strong new password for your account.</p>
        <form method="POST">
            <label class="field-label">New Password</label>
            <input type="password" name="new_password" class="field-input"
                   placeholder="At least 8 characters" minlength="8" required autofocus>
            <label class="field-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="field-input"
                   placeholder="Repeat new password" required>
            <button type="submit" name="reset_password" class="btn-submit">
                <i class="fas fa-key"></i> Update Password
            </button>
        </form>
    <?php endif; ?>
</div>

<script>
// ── OTP digit boxes auto-advance ──────────────────────────
(function() {
    var boxes = document.querySelectorAll('.otp-box-digit');
    if (!boxes.length) return;

    boxes.forEach(function(box, i) {
        box.addEventListener('input', function(e) {
            var val = e.target.value.replace(/\D/g, '');
            e.target.value = val ? val[0] : '';
            if (val && i < boxes.length - 1) boxes[i + 1].focus();
            e.target.classList.toggle('filled', !!val);
        });

        box.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !e.target.value && i > 0) {
                boxes[i - 1].value = '';
                boxes[i - 1].classList.remove('filled');
                boxes[i - 1].focus();
            }
        });

        box.addEventListener('paste', function(e) {
            e.preventDefault();
            var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'');
            pasted.split('').forEach(function(ch, j) {
                if (boxes[i + j]) {
                    boxes[i + j].value = ch;
                    boxes[i + j].classList.add('filled');
                }
            });
            var next = Math.min(i + pasted.length, boxes.length - 1);
            boxes[next].focus();
        });
    });
})();

function combineOTP() {
    var boxes = document.querySelectorAll('.otp-box-digit');
    var val   = '';
    boxes.forEach(function(b) { val += b.value; });
    document.getElementById('otpHidden').value = val;
}

// ── Countdown timer ───────────────────────────────────────
<?php if ($step == 2): ?>
(function() {
    var secs = 15 * 60;
    var el   = document.getElementById('countdown');
    var btn  = document.getElementById('verifyBtn');
    var t = setInterval(function() {
        secs--;
        if (secs <= 0) {
            clearInterval(t);
            if (el)  { el.innerText = 'Expired'; el.style.color = '#EF4444'; }
            if (btn) { btn.disabled = true; btn.textContent = 'OTP Expired — Resend'; }
        } else {
            var m = Math.floor(secs / 60), s = secs % 60;
            if (el) el.innerText = m + ':' + (s < 10 ? '0' : '') + s;
        }
    }, 1000);
})();
<?php endif; ?>

function clearOTPSession() {
    // Navigation to fresh forgot_password.php clears the session step server-side
}
</script>
</body>
</html>
<?php
// Clear session step on fresh GET visit (no step in session = step 1)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_SESSION['otp_step'])) {
    unset($_SESSION['reset_email'], $_SESSION['reset_phone'], $_SESSION['otp_verified'], $_SESSION['test_otp']);
}
?>
