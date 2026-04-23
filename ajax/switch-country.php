<?php
// admin/ajax/switch-country.php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../includes/db.php';
require_once '../includes/country.php';

$data = json_decode(file_get_contents('php://input'), true);
$code = trim($data['code'] ?? '');

if (!$code) {
    echo json_encode(['success' => false, 'message' => 'Country code required']);
    exit;
}

if (set_active_country($db, $code)) {
    echo json_encode(['success' => true, 'code' => $code]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid country code']);
}
