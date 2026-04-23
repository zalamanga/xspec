<?php
// admin/ajax/delete-brochure.php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);
$product_id = intval($data['product_id']);

try {
    // Get brochure path
    $query = "SELECT brochure_file FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product || !$product['brochure_file']) {
        echo json_encode(['success' => false, 'message' => 'Brochure not found']);
        exit;
    }

    // Delete file
    $file_path = '../../' . $product['brochure_file'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Update database
    $query = "UPDATE products SET brochure_file = NULL WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Brochure deleted successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>