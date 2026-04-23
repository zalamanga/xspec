<?php
// admin/ajax/delete-video.php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$input      = json_decode(file_get_contents('php://input'), true);
$product_id = intval($input['product_id'] ?? 0);

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Product ID tidak valid']);
    exit;
}

try {
    // Ambil data video yang ada
    $query = "SELECT video_file, video_url, video_type FROM products WHERE id = :id";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product tidak ditemukan']);
        exit;
    }

    // Hapus file video lokal kalau ada
    if (!empty($product['video_file'])) {
        $file_path = '../../' . $product['video_file'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Clear kolom video di database
    $query = "UPDATE products SET 
                video_file = NULL, 
                video_url  = NULL, 
                video_type = NULL 
              WHERE id = :id";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Video berhasil dihapus']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>