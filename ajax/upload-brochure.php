<?php
// admin/ajax/upload-brochure.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$product_id = $_POST['product_id'] ?? '';

if (empty($product_id)) {
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

if (!isset($_FILES['brochure']) || $_FILES['brochure']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['brochure'];
$allowed_type = 'application/pdf';
$max_size = 10 * 1024 * 1024; // 10MB

// Validate file type
if ($file['type'] !== $allowed_type) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF allowed']);
    exit;
}

// Validate file size
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max 10MB']);
    exit;
}

// Create upload directory if not exists
$upload_dir = '../../uploads/brochures/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$filename = 'brochure_' . $product_id . '_' . time() . '.pdf';
$upload_path = $upload_dir . $filename;
$db_path = 'uploads/brochures/' . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

// Update database
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get old brochure to delete
    $query = "SELECT brochure_file FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch();
    
    // Delete old brochure file if exists
    if ($product && !empty($product['brochure_file'])) {
        $old_file = '../../' . $product['brochure_file'];
        if (file_exists($old_file)) {
            unlink($old_file);
        }
    }
    
    // Update product with new brochure path
    $query = "UPDATE products SET brochure_file = :brochure_file WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':brochure_file', $db_path);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Brochure uploaded successfully']);
} catch (PDOException $e) {
    // Delete file if DB update fails
    unlink($upload_path);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>