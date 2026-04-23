<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false]);
    exit;
}

require_once '../../config/database.php';
$database = new Database();
$db = $database->getConnection();

$category_id = intval($_GET['category_id'] ?? 0);

if (!$category_id) {
    echo json_encode(['success' => false]);
    exit;
}

$query = "SELECT COALESCE(MAX(display_order), 0) + 1 as next_order 
          FROM brands 
          WHERE category_id = :category_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':category_id', $category_id);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'next_order' => $result['next_order']]);