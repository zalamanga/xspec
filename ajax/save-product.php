<?php
// admin/ajax/save-product.php - NO SIZE LIMIT + VIDEO SUPPORT
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
$brand_id          = intval($_POST['brand_id']);
$name              = trim($_POST['name']);
$slug              = trim($_POST['slug']);
$subtitle          = trim($_POST['subtitle']);
$short_description = trim($_POST['short_description'] ?? '');
$description       = trim($_POST['description']);
$display_order     = intval($_POST['display_order']);
$is_active         = intval($_POST['is_active']);
$country_codes     = $_POST['country_codes'] ?? [];

if (!is_array($country_codes) || empty($country_codes)) {
    echo json_encode(['success' => false, 'message' => 'Minimal 1 negara harus dipilih']);
    exit;
}

try {
    // =====================
    // Handle brochure upload - NO SIZE LIMIT
    // =====================
    $brochure_path = null;
    if (isset($_FILES['brochure']) && $_FILES['brochure']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/brochures/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['brochure']['name'], PATHINFO_EXTENSION));
        
        if ($file_ext !== 'pdf') {
            echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed for brochures']);
            exit;
        }

        $file_name     = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['brochure']['name']);
        $brochure_path = 'uploads/brochures/' . $file_name;

        if (!move_uploaded_file($_FILES['brochure']['tmp_name'], $upload_dir . $file_name)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload brochure']);
            exit;
        }
    }

    // =====================
    // Handle Video Upload / URL
    // =====================
    $video_file = null;
    $video_url  = null;
    $video_type = null;

    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {

        $upload_dir = '../../uploads/videos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $allowed_mime = ['video/mp4', 'video/webm', 'video/ogg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['video']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_mime)) {
            echo json_encode(['success' => false, 'message' => 'Format video tidak didukung. Gunakan MP4 atau WebM.']);
            exit;
        }

        $file_name  = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['video']['name']);
        $video_file = 'uploads/videos/' . $file_name;

        if (!move_uploaded_file($_FILES['video']['tmp_name'], $upload_dir . $file_name)) {
            echo json_encode(['success' => false, 'message' => 'Gagal upload video.']);
            exit;
        }

        $video_type = 'upload';

    } elseif (!empty($_POST['video_url'])) {

        $raw_url = trim($_POST['video_url']);

        // YouTube
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/', $raw_url, $m)) {
            $video_url  = 'https://www.youtube.com/embed/' . $m[1];
            $video_type = 'youtube';

        // Vimeo
        } elseif (preg_match('/vimeo\.com\/(\d+)/', $raw_url, $m)) {
            $video_url  = 'https://player.vimeo.com/video/' . $m[1];
            $video_type = 'vimeo';

        } else {
            echo json_encode(['success' => false, 'message' => 'URL video tidak valid. Gunakan YouTube atau Vimeo.']);
            exit;
        }
    }

    // =====================
    // INSERT / UPDATE Product
    // =====================
    if ($id) {
        // UPDATE
        if ($brochure_path) {
            $query = "UPDATE products SET 
                        brand_id = :brand_id, 
                        name = :name, 
                        slug = :slug, 
                        subtitle = :subtitle, 
                        short_description = :short_description, 
                        description = :description,
                        brochure_file = :brochure_file, 
                        video_file = :video_file, 
                        video_url = :video_url, 
                        video_type = :video_type,
                        display_order = :display_order, 
                        is_active = :is_active 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':brochure_file', $brochure_path);
        } else {
            $query = "UPDATE products SET 
                        brand_id = :brand_id, 
                        name = :name, 
                        slug = :slug, 
                        subtitle = :subtitle, 
                        short_description = :short_description, 
                        description = :description,
                        video_file = :video_file, 
                        video_url = :video_url, 
                        video_type = :video_type,
                        display_order = :display_order, 
                        is_active = :is_active 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
        }

        $stmt->bindParam(':id', $id);
        $product_id = $id;

    } else {
        // INSERT
        $query = "INSERT INTO products 
                    (brand_id, name, slug, subtitle, short_description, description, 
                     brochure_file, video_file, video_url, video_type, display_order, is_active) 
                  VALUES 
                    (:brand_id, :name, :slug, :subtitle, :short_description, :description, 
                     :brochure_file, :video_file, :video_url, :video_type, :display_order, :is_active)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':brochure_file', $brochure_path);

        $product_id = null;
    }

    // Bind semua params
    $stmt->bindParam(':brand_id',          $brand_id);
    $stmt->bindParam(':name',              $name);
    $stmt->bindParam(':slug',              $slug);
    $stmt->bindParam(':subtitle',          $subtitle);
    $stmt->bindParam(':short_description', $short_description);
    $stmt->bindParam(':description',       $description);
    $stmt->bindParam(':display_order',     $display_order);
    $stmt->bindParam(':is_active',         $is_active);
    $stmt->bindParam(':video_file',        $video_file);
    $stmt->bindParam(':video_url',         $video_url);
    $stmt->bindParam(':video_type',        $video_type);
    $stmt->execute();

    if (!$id) {
        $product_id = $db->lastInsertId();
    }

    // =====================
    // Sync country pivot
    // =====================
    sync_entity_countries($db, 'product_countries', 'product_id', $product_id, $country_codes);

    // =====================
    // Handle image uploads with AUTO RESIZE to 500x500
    // =====================
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $upload_dir = '../../uploads/products/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Get current max display_order
        $query = "SELECT COALESCE(MAX(display_order), 0) as max_order FROM product_images WHERE product_id = :product_id";
        $stmt  = $db->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        $max_order = $stmt->fetch(PDO::FETCH_ASSOC)['max_order'];

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                
                if (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    continue;
                }

                $file_name = time() . '_' . $key . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['images']['name'][$key]);
                
                // ===== AUTO RESIZE TO 500x500 =====
                list($width, $height) = getimagesize($tmp_name);
                
                switch ($file_ext) {
                    case 'jpg':
                    case 'jpeg':
                        $source = imagecreatefromjpeg($tmp_name);
                        break;
                    case 'png':
                        $source = imagecreatefrompng($tmp_name);
                        break;
                    case 'gif':
                        $source = imagecreatefromgif($tmp_name);
                        break;
                    case 'webp':
                        $source = imagecreatefromwebp($tmp_name);
                        break;
                }
                
                $target_size = 500;
                $canvas      = imagecreatetruecolor($target_size, $target_size);
                
                // Preserve transparency for PNG/GIF
                if ($file_ext == 'png' || $file_ext == 'gif') {
                    imagealphablending($canvas, false);
                    imagesavealpha($canvas, true);
                    $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
                    imagefilledrectangle($canvas, 0, 0, $target_size, $target_size, $transparent);
                }
                
                // Center crop to square
                if ($width > $height) {
                    $crop_size = $height;
                    $src_x     = ($width - $height) / 2;
                    $src_y     = 0;
                } else {
                    $crop_size = $width;
                    $src_x     = 0;
                    $src_y     = ($height - $width) / 2;
                }
                
                imagecopyresampled(
                    $canvas, $source,
                    0, 0,
                    $src_x, $src_y,
                    $target_size, $target_size,
                    $crop_size, $crop_size
                );
                
                $target_path = $upload_dir . $file_name;
                switch ($file_ext) {
                    case 'jpg':
                    case 'jpeg':
                        imagejpeg($canvas, $target_path, 90);
                        break;
                    case 'png':
                        imagepng($canvas, $target_path, 8);
                        break;
                    case 'gif':
                        imagegif($canvas, $target_path);
                        break;
                    case 'webp':
                        imagewebp($canvas, $target_path, 90);
                        break;
                }
                
                imagedestroy($source);
                imagedestroy($canvas);
                // ===== END RESIZE =====
                
                $image_path = 'uploads/products/' . $file_name;
                $max_order++;
                
                $query = "INSERT INTO product_images (product_id, image_path, display_order) 
                          VALUES (:product_id, :image_path, :display_order)";
                $stmt  = $db->prepare($query);
                $stmt->bindParam(':product_id',    $product_id);
                $stmt->bindParam(':image_path',    $image_path);
                $stmt->bindParam(':display_order', $max_order);
                $stmt->execute();
            }
        }
    }

    echo json_encode([
        'success' => true, 
        'message' => $id ? 'Product updated successfully' : 'Product added successfully'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>