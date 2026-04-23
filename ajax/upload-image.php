<?php
// admin/ajax/upload-image.php
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

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['image'];
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
$max_size = 5 * 1024 * 1024; // 5MB

// Validate file type
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG and PNG allowed']);
    exit;
}

// Validate file size
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max 5MB']);
    exit;
}

// Create upload directory if not exists
$upload_dir = '../../uploads/products/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'product_' . $product_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
$upload_path = $upload_dir . $filename;
$db_path = 'uploads/products/' . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

// Save to database
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if this is the first image (set as primary)
    $query = "SELECT COUNT(*) as count FROM product_images WHERE product_id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $result = $stmt->fetch();
    $is_primary = ($result['count'] == 0) ? 1 : 0;
    
    // Get next display order
    $query = "SELECT COALESCE(MAX(display_order), 0) + 1 as next_order FROM product_images WHERE product_id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $result = $stmt->fetch();
    $display_order = $result['next_order'];
    
    // Insert image record
    $query = "INSERT INTO product_images (product_id, image_path, is_primary, display_order) 
              VALUES (:product_id, :image_path, :is_primary, :display_order)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->bindParam(':image_path', $db_path);
    $stmt->bindParam(':is_primary', $is_primary);
    $stmt->bindParam(':display_order', $display_order);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Image uploaded successfully']);
} catch (PDOException $e) {
    // Delete file if DB insert fails
    unlink($upload_path);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>