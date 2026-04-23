<?php
// process-download.php
require_once 'config/database.php';
require_once 'includes/country.php';

header('Content-Type: application/json');

$database  = new Database();
$db        = $database->getConnection();
$active_cc = active_country();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'submit_download') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// ── Sanitize input ──────────────────────────────────────────────────────────
$product_id = intval($_POST['product_id'] ?? 0);
$name       = trim($_POST['name']       ?? '');
$email      = trim($_POST['email']      ?? '');
$phone      = trim($_POST['phone']      ?? '');
$company    = trim($_POST['company']    ?? '');
$remarks    = trim($_POST['remarks']    ?? '');

// Validation
if (!$product_id || empty($name) || empty($email) || empty($phone) || empty($company)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// ── Get product + brochure (must be available in active country) ──────────
$query = "SELECT p.id, p.name, p.brochure_file, b.name as brand_name
          FROM products p
          LEFT JOIN brands b ON p.brand_id = b.id
          JOIN product_countries pc ON pc.product_id = p.id
          WHERE p.id = :id AND p.is_active = 1 AND pc.country_code = :cc
          LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $product_id, ':cc' => $active_cc]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product || empty($product['brochure_file'])) {
    echo json_encode(['success' => false, 'message' => 'Product or brochure not found.']);
    exit;
}

// ── Log to database ─────────────────────────────────────────────────────────
try {
    $query = "INSERT INTO download_logs 
                (product_id, visitor_name, visitor_email, visitor_phone, visitor_company, remarks, ip_address, user_agent, created_at)
              VALUES 
                (:product_id, :name, :email, :phone, :company, :remarks, :ip, :ua, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->bindParam(':name',       $name);
    $stmt->bindParam(':email',      $email);
    $stmt->bindParam(':phone',      $phone);
    $stmt->bindParam(':company',    $company);
    $stmt->bindParam(':remarks',    $remarks);
    $stmt->bindParam(':ip',         $_SERVER['REMOTE_ADDR'] ?? '');
    $stmt->bindParam(':ua',         $_SERVER['HTTP_USER_AGENT'] ?? '');
    $stmt->execute();
} catch (PDOException $e) {
    error_log('download_logs insert error: ' . $e->getMessage());
}

// ── Send email notification ─────────────────────────────────────────────────
$to      = 'info@xspectechnology.com';
$subject = '[XSpec] Brochure Request – ' . $product['brand_name'] . ' ' . $product['name'];

$body = "
<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<style>
  body        { font-family: Arial, sans-serif; background:#f5f5f5; margin:0; padding:20px; }
  .container  { max-width:560px; margin:0 auto; background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.1); }
  .header     { background:#C0392B; color:#fff; padding:24px 28px; }
  .header h2  { margin:0; font-size:20px; }
  .header p   { margin:4px 0 0; font-size:13px; opacity:.85; }
  .body       { padding:24px 28px; }
  .field      { margin-bottom:14px; }
  .label      { font-size:11px; font-weight:700; text-transform:uppercase; color:#888; letter-spacing:.5px; }
  .value      { font-size:15px; color:#222; margin-top:2px; }
  .product-box{ background:#fef2f2; border-left:4px solid #C0392B; padding:12px 16px; border-radius:4px; margin-bottom:20px; }
  .product-box .pname { font-size:16px; font-weight:700; color:#C0392B; }
  .footer     { background:#f0f0f0; padding:14px 28px; font-size:11px; color:#999; text-align:center; }
</style>
</head>
<body>
<div class='container'>
  <div class='header'>
    <h2>📄 New Brochure Request</h2>
    <p>" . date('d M Y, H:i') . " WIB</p>
  </div>
  <div class='body'>
    <div class='product-box'>
      <div class='label'>Product</div>
      <div class='pname'>" . htmlspecialchars($product['brand_name']) . " – " . htmlspecialchars($product['name']) . "</div>
    </div>

    <div class='field'>
      <div class='label'>Full Name</div>
      <div class='value'>" . htmlspecialchars($name) . "</div>
    </div>
    <div class='field'>
      <div class='label'>Email</div>
      <div class='value'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></div>
    </div>
    <div class='field'>
      <div class='label'>Phone / WhatsApp</div>
      <div class='value'><a href='https://wa.me/" . preg_replace('/[^0-9]/', '', $phone) . "'>" . htmlspecialchars($phone) . "</a></div>
    </div>
    <div class='field'>
      <div class='label'>Company</div>
      <div class='value'>" . htmlspecialchars($company) . "</div>
    </div>
    <div class='field'>
      <div class='label'>Remarks</div>
      <div class='value'>" . (!empty($remarks) ? htmlspecialchars($remarks) : '<span style=\"color:#bbb;\">—</span>') . "</div>
    </div>

    <div class='field' style='margin-top:18px; padding-top:16px; border-top:1px solid #eee;'>
      <div class='label'>IP Address</div>
      <div class='value' style='color:#999; font-size:13px;'>" . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '-') . "</div>
    </div>
  </div>
  <div class='footer'>XSpec " . strtoupper($active_cc) . " – Automated Notification &nbsp;|&nbsp; Do not reply to this email</div>
</div>
</body>
</html>
";

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: XSpec Website <no-reply@xspectechnology.com>\r\n";
$headers .= "Reply-To: " . $email . "\r\n";

mail($to, $subject, $body, $headers);

// ── Build PDF viewer URL ────────────────────────────────────────────────────
$brochure_path = $product['brochure_file'];

// Option A: Buka langsung di browser (PDF viewer bawaan)
$pdf_url = '/' . ltrim($brochure_path, '/');

// Option B: Google Docs Viewer (uncomment kalau mau pakai ini)
// $pdf_url = 'https://docs.google.com/viewer?url=' . urlencode('https://xspecmalaysia.com/' . ltrim($brochure_path, '/')) . '&embedded=true';

echo json_encode([
    'success'      => true,
    'brochure_url' => $pdf_url,
    'message'      => 'Success'
]);
exit;