require('dotenv').config();
const express    = require('express');
const nodemailer = require('nodemailer');
const cors       = require('cors');
const path       = require('path');

const app = express();
app.use(express.json());
app.use(cors({ origin: 'http://localhost' })); // Allow only your ERP origin

// ─────────────────────────────────────────────
//  In-memory OTP store
//  { email: { otp, expires, attempts, lastSent } }
// ─────────────────────────────────────────────
const otpStore = {};

// ─── Nodemailer transporter ───────────────────
const transporter = nodemailer.createTransport({
  service: 'gmail',
  auth: {
    user: process.env.EMAIL_USER,
    pass: process.env.EMAIL_PASS,
  },
});

transporter.verify((err) => {
  if (err) {
    console.error('❌  SMTP connection failed:', err.message);
    console.error('    → Check EMAIL_USER and EMAIL_PASS in your .env file');
  } else {
    console.log('✅  SMTP connected — ready to send OTPs');
  }
});

// ─── Helpers ─────────────────────────────────
function generateOTP() {
  return String(Math.floor(100000 + Math.random() * 900000));
}

function buildEmailHTML(otp, email) {
  return `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body{font-family:'Segoe UI',Arial,sans-serif;background:#F5F3FF;margin:0;padding:30px;}
    .card{background:#fff;max-width:480px;margin:0 auto;border-radius:20px;overflow:hidden;box-shadow:0 8px 32px rgba(109,40,217,0.12);}
    .header{background:linear-gradient(135deg,#4C1D95,#7C3AED);padding:36px;text-align:center;}
    .header h1{color:#fff;font-size:22px;margin:0;font-weight:700;letter-spacing:-0.5px;}
    .header p{color:rgba(255,255,255,0.75);font-size:13px;margin:8px 0 0;}
    .body{padding:36px;}
    .body p{color:#374151;font-size:14px;line-height:1.75;margin:0 0 18px;}
    .otp-box{background:#F5F3FF;border:2px dashed #A78BFA;border-radius:14px;padding:24px;text-align:center;margin:24px 0;}
    .otp-code{font-family:'Courier New',monospace;font-size:42px;font-weight:800;color:#5B21B6;letter-spacing:12px;}
    .otp-label{font-size:12px;color:#6B7280;margin-top:8px;}
    .warning{background:#FFF7ED;border-left:4px solid #F59E0B;padding:14px 16px;border-radius:0 10px 10px 0;font-size:12.5px;color:#92400E;margin:20px 0;}
    .footer{background:#F9FAFB;padding:20px 36px;border-top:1px solid #EDE9FE;font-size:11px;color:#9CA3AF;text-align:center;line-height:1.7;}
  </style>
</head>
<body>
  <div class="card">
    <div class="header">
      <h1>💜 PurpleHeart ERP</h1>
      <p>Password Reset Request</p>
    </div>
    <div class="body">
      <p>Hello,</p>
      <p>We received a request to reset the password for <strong>${email}</strong>. Use the OTP below:</p>
      <div class="otp-box">
        <div class="otp-code">${otp}</div>
        <div class="otp-label">Valid for <strong>15 minutes</strong> &nbsp;·&nbsp; Do not share this code</div>
      </div>
      <div class="warning">
        ⚠️ If you did not request this reset, please ignore this email. Your password will remain unchanged.
      </div>
      <p>This code expires in <strong>15 minutes</strong> and can only be used once.</p>
    </div>
    <div class="footer">
      This is an automated message from PurpleHeart ERP System.<br>
      Please do not reply to this email.
    </div>
  </div>
</body>
</html>`;
}

// ─────────────────────────────────────────────
//  ROUTES
// ─────────────────────────────────────────────

// POST /api/send-otp  { email }
app.post('/api/send-otp', async (req, res) => {
  const { email } = req.body;

  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return res.status(400).json({ success: false, message: 'Invalid email address.' });
  }

  // Rate limiting — 60-second cooldown
  const existing = otpStore[email];
  if (existing && existing.lastSent && (Date.now() - existing.lastSent) < 60000) {
    const wait = Math.ceil((60000 - (Date.now() - existing.lastSent)) / 1000);
    return res.status(429).json({ success: false, message: `Please wait ${wait}s before requesting again.` });
  }

  const otp     = generateOTP();
  const expires = Date.now() + 15 * 60 * 1000; // 15 minutes

  otpStore[email] = { otp, expires, attempts: 0, lastSent: Date.now() };
  setTimeout(() => { delete otpStore[email]; }, 15 * 60 * 1000 + 5000);

  try {
    await transporter.sendMail({
      from:    `"PurpleHeart ERP" <${process.env.EMAIL_USER}>`,
      to:      email,
      subject: '💜 Your ERP Password Reset OTP',
      html:    buildEmailHTML(otp, email),
      text:    `Your OTP is: ${otp}\nValid for 15 minutes. Do not share.`,
    });
    console.log(`✅ OTP sent → ${email}`);
    res.json({ success: true, message: `OTP sent to ${email}` });
  } catch (err) {
    console.error('❌ Email send error:', err.message);
    delete otpStore[email];
    res.status(500).json({ success: false, message: 'Failed to send OTP email. Check server config.' });
  }
});

// POST /api/verify-otp  { email, otp }
app.post('/api/verify-otp', (req, res) => {
  const { email, otp } = req.body;
  if (!email || !otp) return res.status(400).json({ valid: false, reason: 'Missing fields.' });

  const record = otpStore[email];
  if (!record)                  return res.json({ valid: false, reason: 'No OTP found. Request a new one.' });
  if (Date.now() > record.expires) {
    delete otpStore[email];
    return res.json({ valid: false, reason: 'OTP expired. Request a new one.' });
  }

  record.attempts++;
  if (record.attempts > 3) {
    delete otpStore[email];
    return res.json({ valid: false, reason: 'Too many attempts. Request a new OTP.' });
  }

  if (record.otp !== String(otp)) {
    const left = 3 - record.attempts;
    return res.json({ valid: false, reason: `Incorrect OTP. ${left} attempt${left !== 1 ? 's' : ''} left.` });
  }

  delete otpStore[email];
  console.log(`✅ OTP verified → ${email}`);
  res.json({ valid: true });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`\n🚀  ERP OTP Server running → http://localhost:${PORT}`);
  console.log(`📧  Sending from: ${process.env.EMAIL_USER || '⚠️  Not set — check your .env file'}\n`);
});
