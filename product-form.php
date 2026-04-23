<?php
// admin/product-form.php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/country.php';

$product_id = $_GET['id'] ?? null;
$is_edit    = !empty($product_id);

$page_title    = $is_edit ? 'Edit Product' : 'Add Product';
$active_cc     = get_active_country($db);
$countries_all = get_countries($db);

// Categories yang ada di country aktif (untuk scope brand picker)
$stmt = $db->prepare("SELECT c.* FROM categories c
    JOIN category_countries cc ON cc.category_id = c.id
    WHERE c.is_active = 1 AND cc.country_code = :cc
    ORDER BY c.display_order ASC");
$stmt->execute([':cc' => $active_cc]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Brands di country aktif
$stmt = $db->prepare("SELECT b.*, c.name as category_name FROM brands b
    JOIN categories c ON b.category_id = c.id
    JOIN brand_countries bc ON bc.brand_id = b.id
    WHERE b.is_active = 1 AND bc.country_code = :cc
    ORDER BY c.display_order, b.display_order ASC");
$stmt->execute([':cc' => $active_cc]);
$all_brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

$brands_by_category = [];
foreach ($all_brands as $brand) {
    $brands_by_category[$brand['category_id']][] = $brand;
}

// If editing
$product          = null;
$product_images   = [];
$product_countries_list = [];

if ($is_edit) {
    $query = "SELECT p.*, b.category_id FROM products p
              JOIN brands b ON p.brand_id = b.id
              WHERE p.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: products.php');
        exit;
    }

    $query = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY display_order ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $product_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $product_countries_list = get_entity_countries($db, 'product_countries', 'product_id', $product_id);
} else {
    // Default untuk tambah baru: centang country yang lagi aktif
    $product_countries_list = [$active_cc];
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="mb-6">
    <a href="products.php" class="text-primary hover:text-primary-dark font-semibold">
        <i class="fas fa-arrow-left mr-2"></i> Back to Products
    </a>
</div>

<form id="productForm" class="bg-white rounded-xl shadow-md p-8">
    <input type="hidden" id="productId" name="id" value="<?php echo $product['id'] ?? ''; ?>">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- Left Column -->
        <div>
            <h3 class="text-xl font-bold text-gray-800 mb-6">Product Information</h3>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Brand *</label>
                <select id="productBrand" name="brand_id" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Select Brand</option>
                    <?php foreach ($categories as $cat): ?>
                        <?php if (isset($brands_by_category[$cat['id']])): ?>
                            <optgroup label="<?php echo htmlspecialchars($cat['name']); ?>">
                                <?php foreach ($brands_by_category[$cat['id']] as $brand): ?>
                                    <option value="<?php echo $brand['id']; ?>" 
                                            <?php echo ($product && $product['brand_id'] == $brand['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Product Name *</label>
                <input type="text" id="productName" name="name" required
                       value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="e.g., intron Basic">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Slug *</label>
                <input type="text" id="productSlug" name="slug" required
                       value="<?php echo htmlspecialchars($product['slug'] ?? ''); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="e.g., intron-basic">
                <p class="text-xs text-gray-500 mt-1">URL-friendly version (lowercase, use hyphens)</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Subtitle</label>
                <input type="text" id="productSubtitle" name="subtitle"
                       value="<?php echo htmlspecialchars($product['subtitle'] ?? ''); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="e.g., Handheld Mass Spectrometer Basic Model">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Full Description</label>
                <textarea id="productDesc" name="description" rows="6"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                          placeholder="Detailed product description"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Display Order</label>
                <input type="number" id="productOrder" name="display_order" 
                       value="<?php echo $product['display_order'] ?? 0; ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Available in Countries *</label>
                <div class="grid grid-cols-3 gap-3">
                    <?php foreach ($countries_all as $c): ?>
                        <label class="flex items-center gap-2 px-4 py-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-red-50 transition-all">
                            <input type="checkbox" name="country_codes[]" value="<?php echo $c['code']; ?>"
                                   class="product-country-cb w-5 h-5 text-primary border-gray-300 rounded focus:ring-2 focus:ring-primary"
                                   <?php echo in_array($c['code'], $product_countries_list) ? 'checked' : ''; ?>>
                            <span class="text-lg"><?php echo $c['flag_emoji']; ?></span>
                            <span class="text-sm font-semibold text-gray-700"><?php echo $c['code']; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-500 mt-2">Centang negara mana saja yang menjual produk ini.</p>
            </div>

            <div class="mb-6">
                <label class="flex items-center gap-3">
                    <input type="checkbox" id="productActive" name="is_active"
                           <?php echo (!$product || $product['is_active']) ? 'checked' : ''; ?>
                           class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-2 focus:ring-primary">
                    <span class="text-sm font-semibold text-gray-700">Active</span>
                </label>
            </div>
        </div>

        <!-- Right Column -->
        <div>
            <h3 class="text-xl font-bold text-gray-800 mb-6">Media Files</h3>

            <!-- Brochure Upload -->
            <div class="mb-8">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Brochure (PDF)</label>
                
                <?php if ($product && !empty($product['brochure_file'])): ?>
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-file-pdf text-red-500 text-2xl"></i>
                            <div>
                                <p class="font-semibold text-gray-800">Current Brochure</p>
                                <a href="../<?php echo htmlspecialchars($product['brochure_file']); ?>" 
                                   target="_blank" 
                                   class="text-sm text-primary hover:text-primary-dark">
                                    View PDF
                                </a>
                            </div>
                        </div>
                        <button type="button" onclick="deleteBrochure()" 
                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm font-semibold transition-colors">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <input type="file" id="brochureFile" accept=".pdf" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Upload PDF brochure</p>
            </div>

            <!-- Video Section -->
            <div class="mb-8">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Product Video</label>

                <div class="flex gap-3 mb-4">
                    <button type="button" onclick="setVideoType('upload')" id="btnUpload"
                            class="px-4 py-2 rounded-lg font-semibold text-sm bg-primary text-white transition-colors">
                        <i class="fas fa-upload mr-1"></i> Upload Video
                    </button>
                    <button type="button" onclick="setVideoType('url')" id="btnUrl"
                            class="px-4 py-2 rounded-lg font-semibold text-sm bg-gray-200 text-gray-700 transition-colors">
                        <i class="fab fa-youtube mr-1"></i> YouTube / Vimeo
                    </button>
                </div>

                <div id="videoUploadSection">
                    <?php if ($product && !empty($product['video_file'])): ?>
                    <div class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-video text-blue-500 text-xl"></i>
                            <div>
                                <p class="font-semibold text-sm">Current Video</p>
                                <a href="../<?php echo htmlspecialchars($product['video_file']); ?>" 
                                   target="_blank" class="text-xs text-primary hover:underline">View Video</a>
                            </div>
                        </div>
                        <button type="button" onclick="deleteVideo()" 
                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs font-semibold">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                    <?php endif; ?>

                    <input type="file" id="videoFile" accept="video/mp4,video/webm"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Format MP4 / WebM — Maks 100MB</p>
                </div>

                <div id="videoUrlSection" class="hidden">
                    <?php if ($product && !empty($product['video_url'])): ?>
                    <div class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fab fa-youtube text-red-500 text-xl"></i>
                            <p class="font-semibold text-sm">Video URL sudah ada</p>
                        </div>
                        <button type="button" onclick="deleteVideo()" 
                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs font-semibold">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                    <?php endif; ?>

                    <input type="text" id="videoUrl" name="video_url"
                           value="<?php echo htmlspecialchars($product['video_url'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="https://www.youtube.com/watch?v=xxxxx">
                    <p class="text-xs text-gray-500 mt-1">Paste URL YouTube atau Vimeo</p>

                    <div id="videoPreview" class="mt-3 hidden">
                        <p class="text-xs font-semibold text-gray-600 mb-1">Preview:</p>
                        <iframe id="videoIframe" width="100%" height="200" 
                                frameborder="0" allowfullscreen class="rounded-lg border"></iframe>
                    </div>
                </div>
            </div>

            <!-- Images Upload -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Product Images</label>
                
                <?php if ($is_edit && count($product_images) > 0): ?>
                <div class="grid grid-cols-3 gap-4 mb-4" id="currentImagesGrid">
                    <?php foreach ($product_images as $img): ?>
                    <div class="relative group" id="img-<?php echo $img['id']; ?>">
                        <img src="../<?php echo htmlspecialchars($img['image_path']); ?>" 
                             alt="Product Image" 
                             class="w-full h-32 object-cover rounded-lg border-2 border-gray-200">
                        <button type="button" onclick="deleteImage(<?php echo $img['id']; ?>)" 
                                class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <input type="file" id="productImages" accept="image/*" multiple
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Upload multiple images (JPG, PNG, GIF, WEBP)</p>
            </div>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="mt-8 pt-6 border-t border-gray-200">
        <div class="flex gap-4">
            <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white py-3 px-6 rounded-lg font-semibold transition-colors">
                <i class="fas fa-save mr-2"></i> Save Product
            </button>
            <a href="products.php" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 px-6 rounded-lg font-semibold transition-colors text-center">
                Cancel
            </a>
        </div>
    </div>
</form>

<script>
// Auto-generate slug
document.getElementById('productName').addEventListener('input', function(e) {
    if (!<?php echo $is_edit ? 'true' : 'false'; ?>) {
        const slug = e.target.value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
        document.getElementById('productSlug').value = slug;
    }
});

<?php if ($product && !empty($product['video_type'])): ?>
setVideoType('<?php echo $product['video_type'] === 'upload' ? 'upload' : 'url'; ?>');
<?php endif; ?>

function setVideoType(type) {
    const uploadSection = document.getElementById('videoUploadSection');
    const urlSection    = document.getElementById('videoUrlSection');
    const btnUpload     = document.getElementById('btnUpload');
    const btnUrl        = document.getElementById('btnUrl');

    if (type === 'upload') {
        uploadSection.classList.remove('hidden');
        urlSection.classList.add('hidden');
        btnUpload.className = 'px-4 py-2 rounded-lg font-semibold text-sm bg-primary text-white transition-colors';
        btnUrl.className    = 'px-4 py-2 rounded-lg font-semibold text-sm bg-gray-200 text-gray-700 transition-colors';
    } else {
        uploadSection.classList.add('hidden');
        urlSection.classList.remove('hidden');
        btnUrl.className    = 'px-4 py-2 rounded-lg font-semibold text-sm bg-primary text-white transition-colors';
        btnUpload.className = 'px-4 py-2 rounded-lg font-semibold text-sm bg-gray-200 text-gray-700 transition-colors';
    }
}

document.getElementById('videoUrl').addEventListener('input', function () {
    const url     = this.value.trim();
    const preview = document.getElementById('videoPreview');
    const iframe  = document.getElementById('videoIframe');
    let embedUrl  = null;

    const yt = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/);
    if (yt) embedUrl = `https://www.youtube.com/embed/${yt[1]}`;

    const vm = url.match(/vimeo\.com\/(\d+)/);
    if (vm) embedUrl = `https://player.vimeo.com/video/${vm[1]}`;

    if (embedUrl) {
        iframe.src = embedUrl;
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
        iframe.src = '';
    }
});

async function deleteVideo() {
    const ok = await xModal.danger({ title: 'Hapus Video?', message: 'Video ini akan dihapus dari produk.', okText: 'Ya, Hapus' });
    if (!ok) return;
    const productId = document.getElementById('productId').value;
    try {
        const response = await fetch('ajax/delete-video.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        });
        const result = await response.json();
        if (result.success) {
            xModal.toast('Video berhasil dihapus', 'success', 1500);
            setTimeout(() => window.location.reload(), 500);
        } else {
            xModal.error(result.message);
        }
    } catch (e) {
        xModal.error('Terjadi kesalahan. Coba lagi.');
    }
}

document.getElementById('productForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const country_codes = Array.from(document.querySelectorAll('.product-country-cb:checked')).map(cb => cb.value);
    if (country_codes.length === 0) {
        xModal.error('Centang minimal 1 negara.');
        return;
    }

    const formData = new FormData();
    formData.append('id',            document.getElementById('productId').value);
    formData.append('brand_id',      document.getElementById('productBrand').value);
    formData.append('name',          document.getElementById('productName').value);
    formData.append('slug',          document.getElementById('productSlug').value);
    formData.append('subtitle',      document.getElementById('productSubtitle').value);
    formData.append('description',   document.getElementById('productDesc').value);
    formData.append('display_order', document.getElementById('productOrder').value);
    formData.append('is_active',     document.getElementById('productActive').checked ? 1 : 0);
    country_codes.forEach(c => formData.append('country_codes[]', c));

    const brochureFile = document.getElementById('brochureFile').files[0];
    if (brochureFile) formData.append('brochure', brochureFile);

    const videoFile = document.getElementById('videoFile').files[0];
    if (videoFile) formData.append('video', videoFile);

    formData.append('video_url', document.getElementById('videoUrl').value);

    const imageFiles = document.getElementById('productImages').files;
    for (let i = 0; i < imageFiles.length; i++) {
        formData.append('images[]', imageFiles[i]);
    }

    try {
        const response = await fetch('ajax/save-product.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            xModal.toast(result.message, 'success', 1500);
            setTimeout(() => window.location.href = 'products.php', 500);
        } else {
            xModal.error(result.message);
        }
    } catch (error) {
        xModal.error('An error occurred. Please try again.');
    }
});

async function deleteImage(imageId) {
    const ok = await xModal.danger({ title: 'Hapus Gambar?', message: 'Gambar ini akan dihapus dari produk.', okText: 'Ya, Hapus' });
    if (!ok) return;
    try {
        const response = await fetch('ajax/delete-image.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: imageId })
        });
        const result = await response.json();
        if (result.success) {
            document.getElementById('img-' + imageId).remove();
            xModal.toast('Gambar dihapus', 'success', 1500);
        } else {
            xModal.error(result.message);
        }
    } catch (error) {
        xModal.error('An error occurred. Please try again.');
    }
}

async function deleteBrochure() {
    const ok = await xModal.danger({ title: 'Hapus Brochure?', message: 'File brochure akan dihapus dari produk.', okText: 'Ya, Hapus' });
    if (!ok) return;
    const productId = document.getElementById('productId').value;
    try {
        const response = await fetch('ajax/delete-brochure.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        });
        const result = await response.json();
        if (result.success) {
            xModal.toast(result.message, 'success', 1500);
            setTimeout(() => window.location.reload(), 500);
        } else {
            xModal.error(result.message);
        }
    } catch (error) {
        xModal.error('An error occurred. Please try again.');
    }
}
</script>

<?php include 'includes/footer.php'; ?>