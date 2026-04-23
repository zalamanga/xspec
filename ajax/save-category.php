<?php
// admin/ajax/save-category.php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';
require_once '../includes/country.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

$id            = $data['id'] ?? null;
$name          = trim($data['name']);
$slug          = trim($data['slug']);
$display_order = intval($data['display_order']);
$is_active     = intval($data['is_active']);
$country_codes = $data['country_codes'] ?? [];

if (!is_array($country_codes) || empty($country_codes)) {
    echo json_encode(['success' => false, 'message' => 'Minimal 1 negara harus dipilih']);
    exit;
}

try {
    if ($id) {
        $query = "UPDATE categories SET name = :name, slug = :slug, display_order = :display_order, is_active = :is_active WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':display_order', $display_order);
        $stmt->bindParam(':is_active', $is_active);
        $stmt->execute();
        $entity_id = $id;
        $msg = 'Category updated successfully';
    } else {
        $query = "INSERT INTO categories (name, slug, display_order, is_active) VALUES (:name, :slug, :display_order, :is_active)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':display_order', $display_order);
        $stmt->bindParam(':is_active', $is_active);
        $stmt->execute();
        $entity_id = $db->lastInsertId();
        $msg = 'Category added successfully';
    }

    // Sync country pivot
    sync_entity_countries($db, 'category_countries', 'category_id', $entity_id, $country_codes);

    echo json_encode(['success' => true, 'message' => $msg]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
