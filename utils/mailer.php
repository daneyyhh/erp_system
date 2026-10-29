<?php
/**
 * MinimalMailer — lightweight SMTP client for Gmail
 * No Composer, no extensions beyond openssl (bundled with XAMPP)
 */
class MinimalMailer {

    private string $host;
    private int    $port;
    private string $user;
    private string $pass;

    public function __construct(string $host, int $port, string $user, string $pass) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function send(string $to, string $subject, string $htmlBody): bool {
        $socket = fsockopen("ssl://{$this->host}", $this->port, $errno, $errstr, 15);
        if (!$socket) return false;

        $read = fn() => fgets($socket, 512);
        $send = fn($cmd) => fputs($socket, $cmd . "\r\n");

        $read(); // 220 greeting
        $send("EHLO localhost");
        while (($line = $read()) && substr($line, 3, 1) !== ' ') {} // read all EHLO lines

        $send("AUTH LOGIN");
        $read();
        $send(base64_encode($this->user));
        $read();
        $send(base64_encode($this->pass));
        $resp = $read();
        if (strpos($resp, '235') === false) { fclose($socket); return false; } // auth failed

        $boundary = md5(uniqid());
        $send("MAIL FROM:<{$this->user}>");  $read();
        $send("RCPT TO:<{$to}>");             $read();
        $send("DATA");                        $read();

        $headers  = "From: \"PurpleHeart ERP\" <{$this->user}>\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $body .= strip_tags($htmlBody) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $body .= $htmlBody . "\r\n";
        $body .= "--{$boundary}--\r\n";

        $send($headers . "\r\n" . $body . "\r\n.");
        $resp = $read();
        $send("QUIT");
        fclose($socket);

        return strpos($resp, '250') !== false;
    }
}

/**
 * Sends an OTP email via Gmail SMTP.
 * Returns true on success, error string on failure.
 */
function sendOTPEmail(string $toEmail, string $otp): bool|string {
    // ── GMAIL SMTP CREDENTIALS (via Environment Variables) ──────
    $gmailUser = getenv('SMTP_USER') ?: 'YOUR_GMAIL@gmail.com';
    $gmailPass = getenv('SMTP_PASS') ?: 'YOUR_APP_PASSWORD';
    // ───────────────────────────────────────────────────────────

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body{font-family:Arial,sans-serif;background:#F5F3FF;margin:0;padding:30px;}
  .card{background:#fff;max-width:480px;margin:0 auto;border-radius:20px;overflow:hidden;box-shadow:0 8px 32px rgba(109,40,217,0.12);}
  .header{background:linear-gradient(135deg,#4C1D95,#7C3AED);padding:36px;text-align:center;}
  .header h1{color:#fff;font-size:22px;margin:0;font-weight:700;}
  .header p{color:rgba(255,255,255,0.7);font-size:13px;margin:8px 0 0;}
  .body{padding:36px;}
  .body p{color:#374151;font-size:14px;line-height:1.75;margin:0 0 16px;}
  .otp-box{background:#F5F3FF;border:2px dashed #A78BFA;border-radius:14px;padding:24px;text-align:center;margin:20px 0;}
  .otp-code{font-family:'Courier New',monospace;font-size:42px;font-weight:800;color:#5B21B6;letter-spacing:12px;}
  .otp-label{font-size:12px;color:#6B7280;margin-top:8px;}
  .warn{background:#FFF7ED;border-left:4px solid #F59E0B;padding:12px 16px;border-radius:0 10px 10px 0;font-size:12.5px;color:#92400E;margin:18px 0;}
  .footer{background:#F9FAFB;padding:18px 36px;border-top:1px solid #EDE9FE;font-size:11px;color:#9CA3AF;text-align:center;line-height:1.7;}
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
    <p>Someone requested a password reset for the account linked to <strong>{$toEmail}</strong>. Use the OTP below:</p>
    <div class="otp-box">
      <div class="otp-code">{$otp}</div>
      <div class="otp-label">Valid for <strong>15 minutes</strong> &nbsp;·&nbsp; Do not share this code</div>
    </div>
    <div class="warn">⚠️ If you did not request this, you can safely ignore this email.</div>
    <p>This code expires in <strong>15 minutes</strong> and can only be used once.</p>
  </div>
  <div class="footer">
    Automated message from PurpleHeart ERP System.<br>Please do not reply to this email.
  </div>
</div>
</body>
</html>
HTML;

    try {
        $mailer = new MinimalMailer('smtp.gmail.com', 465, $gmailUser, $gmailPass);
        $ok = $mailer->send($toEmail, '💜 Your ERP Password Reset OTP', $html);
        return $ok ? true : 'Failed to send email. Check your Gmail App Password.';
    } catch (Throwable $e) {
        return 'Email error: ' . $e->getMessage();
    }
}
