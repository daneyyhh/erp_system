<?php
/**
 * SMS OTP Sender — Vonage (formerly Nexmo)
 *
 * FREE SETUP (2 minutes — €2 free credit ≈ 25 SMS to India):
 *  1. Go to https://dashboard.nexmo.com/sign-up
 *  2. Sign up with email (no credit card needed)
 *  3. Verify your phone number
 *  4. On the dashboard you'll see:
 *       • API key     (e.g. "a1b2c3d4")
 *       • API secret  (e.g. "abcdefghijklmnop")
 *  5. Paste both below
 *
 * OR — Fast2SMS (₹100 one-time, ~500 SMS, best for India):
 *  1. Login to fast2sms.com → Recharge ₹100
 *  2. Dashboard → Dev API → copy your API key
 *  3. Switch PROVIDER below to 'fast2sms' and fill FAST2SMS_KEY
 */

// ── Choose your provider ──────────────────────────────────────
define('PROVIDER', getenv('SMS_PROVIDER') ?: 'vonage');   // 'vonage' | 'fast2sms'

// Vonage credentials (loaded from environment variables)
define('VONAGE_KEY',    getenv('VONAGE_KEY')    ?: 'YOUR_VONAGE_API_KEY');
define('VONAGE_SECRET', getenv('VONAGE_SECRET') ?: 'YOUR_VONAGE_API_SECRET');

// Fast2SMS credential (loaded from environment variables)
define('FAST2SMS_KEY',  getenv('FAST2SMS_KEY')  ?: 'YOUR_FAST2SMS_KEY');

// ─────────────────────────────────────────────────────────────

function sendOTPSms(string $phone, string $otp): bool|string {
    return PROVIDER === 'fast2sms'
        ? _sendViaFast2SMS($phone, $otp)
        : _sendViaVonage($phone, $otp);
}

// ── Vonage ────────────────────────────────────────────────────
function _sendViaVonage(string $phone, string $otp): bool|string {
    if (VONAGE_KEY === 'YOUR_VONAGE_API_KEY') return 'NOT_CONFIGURED';

    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) === 10)                                       $to = '91' . $digits;
    elseif (strlen($digits) === 12 && str_starts_with($digits, '91')) $to = $digits;
    elseif (strlen($digits) === 11 && str_starts_with($digits, '0'))  $to = '91' . substr($digits, 1);
    else                                                               $to = $digits;

    // Use form-encoded (not JSON) so special chars in secret are URL-encoded safely
    $fields = http_build_query([
        'api_key'    => VONAGE_KEY,
        'api_secret' => VONAGE_SECRET,
        'to'         => $to,
        'from'       => 'Vonage',
        'text'       => "Your PurpleHeart ERP OTP is: $otp  Valid 15 min. Do not share.",
    ]);

    $ch = curl_init('https://rest.nexmo.com/sms/json');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $fields,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $raw  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("[Vonage] To=$to HTTP=$code Raw=" . ($raw ?: 'NULL') . " cURL=" . ($err ?: 'none'));

    if ($err)  return "Network error: $err";
    if (!$raw) return "No response from Vonage.";

    $result  = json_decode($raw, true);
    $message = $result['messages'][0] ?? [];
    $status  = (string)($message['status'] ?? '');

    if ($status === '0') return true;  // ✅ Delivered

    $errText = $message['error-text'] ?? ($result['error-text'] ?? ('Raw: ' . substr($raw, 0, 300)));
    return "Vonage: $errText";
}

// ── Fast2SMS (after ₹100 recharge) ───────────────────────────
function _sendViaFast2SMS(string $phone, string $otp): bool|string {
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) === 12 && str_starts_with($digits, '91')) $digits = substr($digits, 2);
    if (strlen($digits) === 11 && str_starts_with($digits, '0'))  $digits = substr($digits, 1);
    if (strlen($digits) !== 10) return "Invalid phone number.";

    $payload = json_encode([
        'route'    => 'q',
        'message'  => "Your PurpleHeart ERP OTP is $otp. Valid 15 min. Do not share.",
        'language' => 'english',
        'flash'    => 0,
        'numbers'  => $digits,
    ]);

    $ch = curl_init('https://www.fast2sms.com/dev/bulkV2');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['authorization: ' . FAST2SMS_KEY, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    error_log("[Fast2SMS] To=$digits Raw=" . ($raw ?: 'NULL'));

    if ($err)  return "Network error: $err";
    if (!$raw) return "No response from Fast2SMS.";

    $result = json_decode($raw, true);
    if (($result['return'] ?? false) === true) return true;

    $msg = $result['message'] ?? 'Unknown error';
    return "Fast2SMS: " . (is_array($msg) ? implode(' ', $msg) : $msg);
}
