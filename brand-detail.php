<?php
// brand-detail-grid.php - 2 COLUMN CARD LAYOUT VERSION + VIDEO SUPPORT
require_once 'config/database.php';
require_once 'includes/country.php';

$database = new Database();
$db = $database->getConnection();

$active_cc = active_country();

// Get brand slug from URL
$brand_slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($brand_slug)) {
    header('Location: /');
    exit;
}

// Brand info — harus tersedia di country aktif
$query = "SELECT
            b.id,
            b.name,
            b.slug,
            b.category_id,
            COALESCE(b.logo, '') as logo,
            COALESCE(b.short_description, '') as short_description,
            COALESCE(b.description, '') as description,
            COALESCE(c.name, 'Uncategorized') as category_name,
            COALESCE(c.slug, '') as category_slug
          FROM brands b
          LEFT JOIN categories c ON b.category_id = c.id
          JOIN brand_countries bc ON bc.brand_id = b.id
          WHERE b.slug = :slug AND b.is_active = 1 AND bc.country_code = :cc
          LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute([':slug' => $brand_slug, ':cc' => $active_cc]);
$brand = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$brand) {
    header('Location: /');
    exit;
}

// Ensure category_slug is not empty
if (empty($brand['category_slug'])) {
    if (!empty($brand['category_id'])) {
        $query = "SELECT slug FROM categories WHERE id = :id AND is_active = 1 LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $brand['category_id'], PDO::PARAM_INT);
        $stmt->execute();
        $cat_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cat_result && !empty($cat_result['slug'])) {
            $brand['category_slug'] = $cat_result['slug'];
        } else {
            $brand['category_slug'] = 'uncategorized';
        }
    } else {
        $brand['category_slug'] = 'uncategorized';
    }
}

// Products di brand ini — filtered by country aktif
$query = "SELECT
            p.id,
            p.name,
            p.slug,
            COALESCE(p.subtitle, '') as subtitle,
            COALESCE(p.description, '') as description,
            COALESCE(p.short_description, '') as short_description,
            COALESCE(p.brochure_file, '') as brochure_file,
            COALESCE(p.video_file, '') as video_file,
            COALESCE(p.video_url, '') as video_url,
            COALESCE(p.video_type, '') as video_type
          FROM products p
          JOIN product_countries pc ON pc.product_id = p.id
          WHERE p.brand_id = :brand_id AND p.is_active = 1 AND pc.country_code = :cc
          ORDER BY p.display_order ASC";
$stmt = $db->prepare($query);
$stmt->execute([':brand_id' => $brand['id'], ':cc' => $active_cc]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get images for each product
foreach ($products as $key => $product) {
    $query = "SELECT image_path FROM product_images 
              WHERE product_id = :product_id 
              ORDER BY display_order ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product['id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($images)) {
        $images = [['image_path' => 'img/placeholder.jpg']];
    }

    $products[$key]['images'] = $images;
}

if (empty($products)) {
    $redirect_slug = !empty($brand['category_slug']) ? $brand['category_slug'] : 'uncategorized';
    header('Location: /category/' . rawurlencode($redirect_slug));
    exit;
}

$__ci  = active_country_info($db);
$title = htmlspecialchars($brand['name']) . " - XSpec " . ($__ci['name'] ?? 'Malaysia');
$currentPage = 'brand-detail';

include 'includes/head.php';
include 'includes/header.php';
?>

<!-- Brand Header with Logo -->
<section class="py-8 bg-gradient-to-br from-gray-100 to-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <?php if (!empty($brand['logo'])): ?>
            <div class="flex justify-center mb-4">
                <img src="<?php echo htmlspecialchars($brand['logo']); ?>" 
                     alt="<?php echo htmlspecialchars($brand['name']); ?>" 
                     class="h-24 md:h-32 w-auto object-contain">
            </div>
        <?php else: ?>
            <h1 class="text-4xl md:text-5xl font-display font-bold text-gray-900">
                <?php echo htmlspecialchars($brand['name']); ?>
            </h1>
        <?php endif; ?>
        
        <?php if (!empty($brand['short_description'])): ?>
            <p class="text-gray-600 mt-3 max-w-2xl mx-auto text-lg">
                <?php echo htmlspecialchars($brand['short_description']); ?>
            </p>
        <?php endif; ?>
    </div>
</section>

<!-- Products Grid - 2 Columns -->
<section class="py-12 md:py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- 2 Column Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            
            <?php foreach ($products as $prod_index => $product): ?>
            
            <!-- Product Card -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-shadow flex flex-col h-full">
                
                <!-- Product Image Carousel -->
                <div class="relative bg-gradient-to-br from-gray-100 to-gray-200 p-4">
                    <!-- Main Image -->
                    <div class="aspect-[4/3] rounded-xl bg-white flex items-center justify-center overflow-hidden mb-3">
                        <img id="mainImage-<?php echo $prod_index; ?>" 
                             src="<?php echo htmlspecialchars($product['images'][0]['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="w-full h-full object-contain p-4">
                    </div>
                    
                    <!-- Navigation Arrows - Only if multiple images -->
                    <?php if (count($product['images']) > 1): ?>
                    <button onclick="previousImage(<?php echo $prod_index; ?>)" 
                            class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-2 shadow-lg transition-all hover:scale-110 z-10">
                        <svg class="w-5 h-5 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <button onclick="nextImage(<?php echo $prod_index; ?>)" 
                            class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-2 shadow-lg transition-all hover:scale-110 z-10">
                        <svg class="w-5 h-5 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    <?php endif; ?>
                    
                    <!-- Image Counter -->
                    <div class="absolute top-4 right-4 bg-black/80 text-white px-3 py-1 rounded-full text-sm font-bold z-10">
                        <span id="imageCounter-<?php echo $prod_index; ?>">1 / <?php echo count($product['images']); ?></span>
                    </div>
                    
                    <!-- Thumbnails -->
                    <div class="flex gap-2 overflow-x-auto pb-2 px-1">
                        <?php foreach ($product['images'] as $img_index => $image): ?>
                        <div onclick="changeImage(<?php echo $prod_index; ?>, <?php echo $img_index; ?>)"
                             class="thumbnail-<?php echo $prod_index; ?> flex-shrink-0 w-[60px] h-[60px] bg-gray-100 rounded-md overflow-hidden cursor-pointer border-2 transition-all hover:scale-105 <?php echo $img_index === 0 ? 'border-primary' : 'border-transparent'; ?>">
                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                 alt="Thumbnail <?php echo ($img_index + 1); ?>" 
                                 class="w-full h-full object-cover">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="p-5 flex flex-col flex-grow">
                    <!-- Title -->
                    <div class="border-b-2 border-gray-800 mb-3">
                        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 text-center line-clamp-2 min-h-[4rem]">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h2>
                    </div>
                    
                    <?php if (!empty($product['subtitle'])): ?>
                    <h3 class="text-sm text-gray-600 mb-2 text-center line-clamp-1">
                        <?php echo htmlspecialchars($product['subtitle']); ?>
                    </h3>
                    <?php endif; ?>
                    
                    <!-- Description -->
                    <?php if (!empty($product['description'])): ?>
                    <div class="text-gray-700 text-sm leading-relaxed mb-4 overflow-y-auto flex-grow" style="max-height: 150px; min-height: 100px;">
                        <p class="whitespace-pre-line">
                            <?php echo htmlspecialchars($product['description']); ?>
                        </p>
                    </div>
                    <?php else: ?>
                    <div class="flex-grow mb-4" style="min-height: 100px;"></div>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div class="mt-auto space-y-2">

                        <!-- Tombol Watch Video -->
                        <?php if (!empty($product['video_file']) || !empty($product['video_url'])): ?>
                        <button onclick="toggleVideo(<?php echo $prod_index; ?>)"
                                id="btnVideo-<?php echo $prod_index; ?>"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg font-bold text-base transition-all hover:shadow-xl flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            <span>Watch Video</span>
                        </button>

                        <!-- Video Player (hidden by default) -->
                        <div id="videoSection-<?php echo $prod_index; ?>" class="hidden rounded-lg overflow-hidden">
                            <?php if (!empty($product['video_file'])): ?>
                            <!-- Local Video -->
                            <video controls class="w-full rounded-lg" style="max-height: 280px;">
                                <source src="<?php echo htmlspecialchars($product['video_file']); ?>" type="video/mp4">
                                Browser kamu tidak mendukung video.
                            </video>

                            <?php elseif (!empty($product['video_url'])): ?>
                            <!-- YouTube / Vimeo Embed -->
                            <div class="relative w-full" style="padding-top: 56.25%;">
                                <iframe 
                                    src="<?php echo htmlspecialchars($product['video_url']); ?>"
                                    class="absolute inset-0 w-full h-full rounded-lg"
                                    frameborder="0" 
                                    allowfullscreen
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
                                </iframe>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Tombol Download Brochure -->
                        <?php if (!empty($product['brochure_file'])): ?>
                        <button onclick="openDownloadModal(<?php echo $prod_index; ?>)" 
                                class="w-full bg-primary hover:bg-primary-dark text-white px-4 py-3 rounded-lg font-bold text-base transition-all hover:shadow-xl flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Download Brochure
                        </button>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
            
            <?php endforeach; ?>
            
        </div>
        
        <!-- Back Button -->
        <div class="text-center">
            <a href="<?php echo !empty($brand['category_slug']) && $brand['category_slug'] !== 'uncategorized'
                        ? '/category/' . rawurlencode($brand['category_slug'])
                        : '/'; ?>"
               class="inline-flex items-center gap-3 bg-gray-200 hover:bg-gray-300 text-gray-800 px-8 py-4 rounded-xl font-bold text-lg transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to <?php echo !empty($brand['category_slug']) && $brand['category_slug'] !== 'uncategorized' 
                                  ? htmlspecialchars($brand['category_name']) 
                                  : 'Home'; ?>
            </a>
        </div>
    </div>
</section>

<!-- DOWNLOAD MODAL FORM -->
<div id="downloadModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto transform transition-all scale-95 opacity-0" id="modalContent">
        
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-primary to-red-600 text-white p-8 rounded-t-2xl relative">
            <button onclick="closeDownloadModal()" 
                    class="absolute top-6 right-6 text-white/80 hover:text-white transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <h3 class="text-3xl font-bold mb-2">Download Brochure</h3>
            <p class="text-white/90 text-lg">Please fill in your details</p>
        </div>

        <!-- Modal Body -->
        <div class="p-8">
            <form id="downloadForm" onsubmit="submitDownloadForm(event)">
                <input type="hidden" id="modal_product_id" name="product_id">
                <input type="hidden" id="modal_product_index" name="product_index">
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-3 text-lg">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="user_name" name="name" required 
                           class="w-full px-5 py-4 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none transition-colors text-lg"
                           placeholder="Enter your full name">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-3 text-lg">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="user_email" name="email" required 
                           class="w-full px-5 py-4 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none transition-colors text-lg"
                           placeholder="your@email.com">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-3 text-lg">
                        Phone / WhatsApp <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" id="user_phone" name="phone" required 
                           class="w-full px-5 py-4 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none transition-colors text-lg"
                           placeholder="+60 12-345 6789">
                </div>

                <div class="mb-8">
                    <label class="block text-gray-700 font-bold mb-3 text-lg">
                        Company Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="user_company" name="company" required 
                           class="w-full px-5 py-4 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none transition-colors text-lg"
                           placeholder="Your company name">
                </div>

                <button type="submit" id="submitDownloadBtn"
                        class="w-full bg-primary hover:bg-primary-dark text-white px-8 py-5 rounded-xl font-bold text-xl transition-all hover:shadow-2xl flex items-center justify-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span id="submitBtnText">Download Now</span>
                </button>

                <div id="formError" class="hidden mt-6 p-5 bg-red-50 border-2 border-red-200 rounded-xl text-red-700 text-base font-semibold"></div>
            </form>
        </div>
    </div>
</div>

<script>
// Product data from PHP
const productData = <?php echo json_encode($products); ?>;

// Current image index for EACH product
const currentImageIndex = {};
<?php foreach ($products as $index => $product): ?>
currentImageIndex[<?php echo $index; ?>] = 0;
<?php endforeach; ?>

// Change to specific image
function changeImage(productIdx, imageIdx) {
    currentImageIndex[productIdx] = imageIdx;
    updateImageDisplay(productIdx);
}

// Update image display
function updateImageDisplay(productIdx) {
    const product  = productData[productIdx];
    const imgIdx   = currentImageIndex[productIdx];
    const image    = product.images[imgIdx];
    
    const mainImage = document.getElementById(`mainImage-${productIdx}`);
    if (mainImage) {
        mainImage.src = image.image_path;
        mainImage.alt = product.name;
    }
    
    const counter = document.getElementById(`imageCounter-${productIdx}`);
    if (counter) {
        counter.textContent = `${imgIdx + 1} / ${product.images.length}`;
    }
    
    document.querySelectorAll(`.thumbnail-${productIdx}`).forEach((thumb, idx) => {
        if (idx === imgIdx) {
            thumb.classList.add('border-primary');
            thumb.classList.remove('border-transparent');
            thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        } else {
            thumb.classList.remove('border-primary');
            thumb.classList.add('border-transparent');
        }
    });
}

// Previous image
function previousImage(productIdx) {
    const product = productData[productIdx];
    currentImageIndex[productIdx]--;
    if (currentImageIndex[productIdx] < 0) {
        currentImageIndex[productIdx] = product.images.length - 1;
    }
    updateImageDisplay(productIdx);
}

// Next image
function nextImage(productIdx) {
    const product = productData[productIdx];
    currentImageIndex[productIdx]++;
    if (currentImageIndex[productIdx] >= product.images.length) {
        currentImageIndex[productIdx] = 0;
    }
    updateImageDisplay(productIdx);
}

// Toggle Video show/hide
function toggleVideo(productIdx) {
    const section  = document.getElementById(`videoSection-${productIdx}`);
    const btn      = document.getElementById(`btnVideo-${productIdx}`);
    const isHidden = section.classList.contains('hidden');

    if (isHidden) {
        section.classList.remove('hidden');
        btn.innerHTML = `
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
            </svg>
            <span>Hide Video</span>`;
    } else {
        section.classList.add('hidden');
        btn.innerHTML = `
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z"/>
            </svg>
            <span>Watch Video</span>`;
    }
}

// Open download modal
function openDownloadModal(productIdx) {
    const product = productData[productIdx];
    
    if (!product.brochure_file || product.brochure_file.trim() === '') {
        alert('Brochure not available for this product');
        return;
    }
    
    document.getElementById('modal_product_id').value    = product.id;
    document.getElementById('modal_product_index').value = productIdx;
    
    const modal        = document.getElementById('downloadModal');
    const modalContent = document.getElementById('modalContent');
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
    
    document.body.style.overflow = 'hidden';
}

// Close download modal
function closeDownloadModal() {
    const modal        = document.getElementById('downloadModal');
    const modalContent = document.getElementById('modalContent');
    
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        document.getElementById('downloadForm').reset();
        document.getElementById('formError').classList.add('hidden');
    }, 200);
}

// Submit download form
async function submitDownloadForm(event) {
    event.preventDefault();
    
    const submitBtn     = document.getElementById('submitDownloadBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const formError     = document.getElementById('formError');
    
    submitBtn.disabled         = true;
    submitBtnText.textContent  = 'Processing...';
    formError.classList.add('hidden');
    
    const formData = new FormData(event.target);
    formData.append('action', 'submit_download');
    
    try {
        const response = await fetch('process-download.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeDownloadModal();
            alert('Thank you! Your brochure download will start now.');
            window.open(result.brochure_url, '_blank');
        } else {
            formError.textContent = result.message || 'An error occurred. Please try again.';
            formError.classList.remove('hidden');
        }
        
    } catch (error) {
        formError.textContent = 'Network error. Please check your connection and try again.';
        formError.classList.remove('hidden');
    } finally {
        submitBtn.disabled        = false;
        submitBtnText.textContent = 'Download Now';
    }
}

// Close modal when clicking outside
document.getElementById('downloadModal')?.addEventListener('click', function(e) {
    if (e.target.id === 'downloadModal') {
        closeDownloadModal();
    }
});

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDownloadModal();
    }
});
</script>

<style>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.grid > div {
    display: flex;
    flex-direction: column;
}

.bg-white {
    transition: all 0.3s ease;
}

<?php foreach ($products as $index => $product): ?>
#mainImage-<?php echo $index; ?> {
    transition: opacity 0.3s ease-in-out;
}
<?php endforeach; ?>

#modalContent {
    transition: all 0.2s ease-out;
}

.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: #D1D5DB;
    border-radius: 2px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background-color: transparent;
}

.overflow-x-auto::-webkit-scrollbar {
    height: 4px;
}
.overflow-x-auto::-webkit-scrollbar-thumb {
    background-color: #D1D5DB;
    border-radius: 2px;
}

@supports (display: grid) {
    .grid > div {
        height: 100%;
    }
}

@media (max-width: 1024px) {
    .grid-cols-1.lg\\:grid-cols-2 {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
include 'includes/footer.php';
include 'includes/scripts.php';
?>