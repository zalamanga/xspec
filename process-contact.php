<?php
// process-contact.php
// Handle contact form submission → kirim email ke admin.
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        if (ob_get_level()) ob_clean();
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'message' => 'PHP Fatal: ' . $err['message'] . ' in ' . basename($err['file']) . ':' . $err['line']
        ]);
    }
});

require_once 'config/database.php';
require_once 'includes/country.php';

if (ob_get_level()) ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Sanitize input
$name    = trim($_POST['name']    ?? '');
$phone   = trim($_POST['phone']   ?? '');
$email   = trim($_POST['email']   ?? '');
$company = trim($_POST['company'] ?? '');
$address = trim($_POST['address'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validation
if ($name === '' || $phone === '' || $email === '' || $message === '') {
    echo json_encode(['success' => false, 'message' => 'Name, phone, email, and message are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Limit panjang supaya gak abuse
if (strlen($message) > 5000) {
    echo json_encode(['success' => false, 'message' => 'Message too long (max 5000 characters).']);
    exit;
}

// Detect country user (dari subdomain / override ?country=xx)
$active_cc = active_country();
$country_label = strtoupper($active_cc);
$host = $_SERVER['HTTP_HOST'] ?? 'xspectechnology.com';

// === Siapkan email ===
$to      = 'info@xspectechnology.com';
$subject = "[XSpec {$country_label}] New Contact Inquiry from " . $name;

// Build HTML body
$body = "
<!DOCTYPE html>
<html>
<head><meta charset='utf-8'><style>
body { font-family: Arial, Helvetica, sans-serif; background: #f5f5f7; margin: 0; padding: 20px; color: #333; }
.wrap { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
.header { background: linear-gradient(135deg, #e63946 0%, #d62839 100%); color: #fff; padding: 28px 30px; }
.header h1 { margin: 0; font-size: 20px; font-weight: 600; }
.header .meta { margin-top: 8px; font-size: 13px; opacity: 0.9; }
.body { padding: 30px; }
.field { margin-bottom: 18px; padding-bottom: 16px; border-bottom: 1px solid #f0f0f0; }
.field:last-child { border-bottom: none; }
.label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: #888; margin-bottom: 6px; font-weight: 600; }
.value { font-size: 15px; color: #222; line-height: 1.5; word-wrap: break-word; }
.value a { color: #e63946; text-decoration: none; }
.msg-box { background: #fafafa; border-left: 4px solid #e63946; padding: 16px 18px; border-radius: 6px; font-size: 15px; line-height: 1.6; white-space: pre-wrap; }
.footer { background: #fafafa; padding: 16px 30px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; }
</style></head>
<body>
  <div class='wrap'>
    <div class='header'>
      <h1>📬 New Contact Inquiry</h1>
      <div class='meta'>From XSpec {$country_label} website &nbsp;•&nbsp; " . date('d M Y, H:i') . "</div>
    </div>
    <div class='body'>
      <div class='field'>
        <div class='label'>Full Name</div>
        <div class='value'>" . htmlspecialchars($name) . "</div>
      </div>
      <div class='field'>
        <div class='label'>Mobile / WhatsApp</div>
        <div class='value'><a href='https://wa.me/" . preg_replace('/[^0-9]/', '', $phone) . "'>" . htmlspecialchars($phone) . "</a></div>
      </div>
      <div class='field'>
        <div class='label'>Email</div>
        <div class='value'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></div>
      </div>";

if ($company !== '') {
    $body .= "
      <div class='field'>
        <div class='label'>Company</div>
        <div class='value'>" . htmlspecialchars($company) . "</div>
      </div>";
}

if ($address !== '') {
    $body .= "
      <div class='field'>
        <div class='label'>Company Address</div>
        <div class='value'>" . nl2br(htmlspecialchars($address)) . "</div>
      </div>";
}

$body .= "
      <div class='field'>
        <div class='label'>Message</div>
        <div class='msg-box'>" . htmlspecialchars($message) . "</div>
      </div>
      <div class='field'>
        <div class='label'>Submitted From</div>
        <div class='value' style='color:#999; font-size:13px;'>
          {$host} &nbsp;•&nbsp; IP " . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '-') . "
        </div>
      </div>
    </div>
    <div class='footer'>XSpec {$country_label} – Automated Notification &nbsp;|&nbsp; Reply directly to respond to the sender</div>
  </div>
</body>
</html>
";

// Email headers — Reply-To diset ke email user, jadi kalau admin reply langsung ke user
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: XSpec Website <no-replys@xspectechnology.com>\r\n";
$headers .= "Reply-To: " . htmlspecialchars($name) . " <" . $email . ">\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

// Kirim
$sent = @mail($to, $subject, $body, $headers);

if ($sent) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your message has been sent. We will get back to you soon.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Sorry, we could not send your message right now. Please try again later or contact us directly.'
    ]);
}
