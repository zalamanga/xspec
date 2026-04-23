<?php
// admin/ajax/delete-product.php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id']);

try {
    // Get product images and brochure to delete files
    $query = "SELECT brochure_file FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Delete brochure file
    if ($product && $product['brochure_file']) {
        $file_path = '../../' . $product['brochure_file'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Get product images
    $query = "SELECT image_path FROM product_images WHERE product_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Delete image files
    foreach ($images as $img) {
        $file_path = '../../' . $img['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Delete product (cascade will delete images from DB)
    $query = "DELETE FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>