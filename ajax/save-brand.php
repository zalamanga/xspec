<?php
// admin/ajax/save-brand.php - NO SIZE LIMIT
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';
require_once '../includes/country.php';

$database = new Database();
$db = $database->getConnection();

$id                = $_POST['id'] ?? null;
$category_id       = intval($_POST['category_id']);
$name              = trim($_POST['name']);
$slug              = trim($_POST['slug']);
$short_description = trim($_POST['short_description']);
$display_order     = intval($_POST['display_order']);
$is_active         = intval($_POST['is_active']);
$existing_logo     = $_POST['existing_logo'] ?? '';
$country_codes     = $_POST['country_codes'] ?? [];

if (!is_array($country_codes) || empty($country_codes)) {
    echo json_encode(['success' => false, 'message' => 'Minimal 1 negara harus dipilih']);
    exit;
}

try {
    // Handle logo upload
    $logo_path = null;
    
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/brands/';
        
        // Create directory if not exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
        
        // ONLY CHECK FILE TYPE - NO SIZE LIMIT
        if (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
            echo json_encode(['success' => false, 'message' => 'Only image files are allowed (JPG, PNG, GIF, WEBP, SVG)']);
            exit;
        }

        // Delete old logo if exists
        if ($existing_logo && $existing_logo !== 'REMOVE') {
            $old_file = '../../' . $existing_logo;
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }

        $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['logo_file']['name']);
        $logo_path = 'uploads/brands/' . $file_name;

        if (!move_uploaded_file($_FILES['logo_file']['tmp_name'], $upload_dir . $file_name)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload logo']);
            exit;
        }
    } elseif ($existing_logo === 'REMOVE') {
        // User wants to remove logo
        if ($id) {
            // Get current logo to delete
            $query = "SELECT logo FROM brands WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current && $current['logo']) {
                $old_file = '../../' . $current['logo'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
        }
        $logo_path = ''; // Set to empty
    } else {
        // Keep existing logo
        $logo_path = $existing_logo;
    }

    if ($id) {
        // Update
        if ($logo_path !== null) {
            $query = "UPDATE brands SET category_id = :category_id, name = :name, slug = :slug, logo = :logo, 
                      short_description = :short_description, display_order = :display_order, is_active = :is_active WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':logo', $logo_path);
        } else {
            $query = "UPDATE brands SET category_id = :category_id, name = :name, slug = :slug, 
                      short_description = :short_description, display_order = :display_order, is_active = :is_active WHERE id = :id";
            $stmt = $db->prepare($query);
        }
        $stmt->bindParam(':id', $id);
    } else {
        // Insert
        $query = "INSERT INTO brands (category_id, name, slug, logo, short_description, display_order, is_active) 
                  VALUES (:category_id, :name, :slug, :logo, :short_description, :display_order, :is_active)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':logo', $logo_path);
    }

    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':slug', $slug);
    $stmt->bindParam(':short_description', $short_description);
    $stmt->bindParam(':display_order', $display_order);
    $stmt->bindParam(':is_active', $is_active);
    $stmt->execute();

    $brand_id = $id ?: $db->lastInsertId();

    // Sync country pivot
    sync_entity_countries($db, 'brand_countries', 'brand_id', $brand_id, $country_codes);

    echo json_encode(['success' => true, 'message' => $id ? 'Brand updated successfully' : 'Brand added successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>