<?php
// downloads.php - DOWNLOAD CENTER (MODERN REDESIGN)
$title = "Download Center - XSpec";
$currentPage = 'downloads';

require_once 'config/database.php';
require_once 'includes/country.php';

$database = new Database();
$db = $database->getConnection();
$active_cc = active_country();

// Categories tersedia di country aktif
$stmt = $db->prepare("SELECT c.* FROM categories c
    JOIN category_countries cc ON cc.category_id = c.id
    WHERE c.is_active = 1 AND cc.country_code = :cc
    ORDER BY c.display_order ASC");
$stmt->execute([':cc' => $active_cc]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($categories)) {
    $active_category_slug = null;
    $active_category = null;
    $brands_with_products = [];
} else {
    $active_category_slug = $_GET['category'] ?? $categories[0]['slug'];

    $stmt = $db->prepare("SELECT c.* FROM categories c
        JOIN category_countries cc ON cc.category_id = c.id
        WHERE c.slug = :slug AND cc.country_code = :cc LIMIT 1");
    $stmt->execute([':slug' => $active_category_slug, ':cc' => $active_cc]);
    $active_category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$active_category) {
        $active_category = $categories[0];
        $active_category_slug = $active_category['slug'];
    }

    $stmt = $db->prepare("SELECT b.id, b.name, b.slug, b.logo
          FROM brands b
          JOIN brand_countries bc ON bc.brand_id = b.id
          WHERE b.category_id = :category_id AND b.is_active = 1 AND bc.country_code = :cc
          ORDER BY b.display_order ASC");
    $stmt->execute([':category_id' => $active_category['id'], ':cc' => $active_cc]);
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $brands_with_products = [];
    foreach ($brands as $brand) {
        $stmt = $db->prepare("SELECT p.id, p.name, p.slug, p.brochure_file
                  FROM products p
                  JOIN product_countries pc ON pc.product_id = p.id
                  WHERE p.brand_id = :brand_id AND p.is_active = 1
                        AND p.brochure_file IS NOT NULL AND p.brochure_file != ''
                        AND pc.country_code = :cc
                  ORDER BY p.display_order ASC");
        $stmt->execute([':brand_id' => $brand['id'], ':cc' => $active_cc]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($products) > 0) {
            $brand['products'] = $products;
            $brands_with_products[] = $brand;
        }
    }
}

include 'includes/head.php';
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="bg-gradient-to-br from-gray-800 to-gray-700 py-12 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-display font-bold mb-2">
            Download <span class="text-primary">Center</span>
        </h1>
        <p class="text-white/80">Product brochures & technical documentation</p>
    </div>
</section>

<!-- Category Tabs + Search -->
<section class="bg-white border-b-2 border-gray-200 sticky top-[100px] z-40 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="mb-4">
            <div class="relative max-w-xl">
                <input type="text" id="searchInput" placeholder="Search products or brands..."
                       class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>
        <div class="flex overflow-x-auto gap-2 pb-2">
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?php echo urlencode($cat['slug']); ?>"
                   class="flex-shrink-0 px-5 py-2 rounded-full font-semibold text-sm uppercase tracking-wide transition-all duration-300 whitespace-nowrap
                          <?php echo $cat['slug'] == $active_category_slug ? 'bg-primary text-white shadow-lg' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Download Content -->
<section class="py-8 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (count($brands_with_products) > 0): ?>
            <div class="space-y-6">
                <?php foreach ($brands_with_products as $brand): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300 brand-section">
                    <div class="bg-gradient-to-r from-gray-100 to-gray-50 px-6 py-6 flex items-center justify-center border-b-2 border-gray-200">
                        <?php if ($brand['logo']): ?>
                            <img src="<?php echo htmlspecialchars($brand['logo']); ?>"
                                 alt="<?php echo htmlspecialchars($brand['name']); ?>"
                                 class="h-24 w-auto object-contain brand-logo brand-logo-img"
                                 data-brand-name="<?php echo htmlspecialchars($brand['name']); ?>"
                                 data-product-count="<?php echo count($brand['products']); ?>">
                        <?php else: ?>
                            <div class="brand-logo" data-brand-name="<?php echo htmlspecialchars($brand['name']); ?>" data-product-count="<?php echo count($brand['products']); ?>">
                                <h2 class="text-2xl font-display font-bold text-gray-900"><?php echo htmlspecialchars($brand['name']); ?></h2>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <?php foreach ($brand['products'] as $product): ?>
                            <div class="product-item bg-gray-50 rounded-lg p-4 hover:bg-white hover:shadow-lg transition-all duration-300 border-2 border-transparent hover:border-primary/20">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/>
                                            <path fill="#fff" d="M14 2v6h6"/>
                                        </svg>
                                    </div>
                                    <h3 class="font-semibold text-gray-900 text-sm leading-tight flex-1 product-name">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </h3>
                                </div>
                                <button onclick="openBrochureModal(<?php echo $product['id']; ?>, '<?php echo addslashes(htmlspecialchars($product['name'])); ?>', '<?php echo addslashes($product['brochure_file']); ?>')"
                                        class="flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white px-4 py-2.5 rounded-lg font-semibold text-sm transition-all duration-300 hover:shadow-md w-full group">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View Brochure
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div id="noResults" class="hidden text-center py-16 bg-white rounded-xl shadow-md">
                <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <h3 class="text-xl font-bold text-gray-800 mb-2">No results found</h3>
                <p class="text-gray-600">Try different keywords or browse by category</p>
            </div>

        <?php else: ?>
            <div class="text-center py-16 bg-white rounded-xl shadow-md">
                <h3 class="text-xl font-bold text-gray-800 mb-2">No Downloads Available</h3>
                <p class="text-gray-600 mb-6">No downloadable brochures for this category yet</p>
                <a href="index.php" class="inline-block bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-all hover:shadow-md">Back to Home</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- MODAL FORM -->
<div id="downloadModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto transform transition-all scale-95 opacity-0" id="modalContent">
        <div class="bg-gradient-to-r from-primary to-red-600 text-white p-8 rounded-t-2xl relative">
            <button onclick="closeModal()" class="absolute top-6 right-6 text-white/80 hover:text-white transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <h3 class="text-2xl font-bold mb-1">View Brochure</h3>
            <p class="text-white/90 text-sm" id="modalProductName"></p>
        </div>
        <div class="p-8">
            <form id="brochureForm" onsubmit="submitBrochureForm(event)">
                <input type="hidden" id="modal_product_id" name="product_id">
                <input type="hidden" id="modal_brochure_file" name="brochure_file">

                <div class="mb-5">
                    <label class="block text-gray-700 font-bold mb-2">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none transition-colors"
                           placeholder="Enter your full name">
                </div>
                <div class="mb-5">
                    <label class="block text-gray-700 font-bold mb-2">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" required
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none transition-colors"
                           placeholder="your@email.com">
                </div>
                <div class="mb-5">
                    <label class="block text-gray-700 font-bold mb-2">Phone / WhatsApp <span class="text-red-500">*</span></label>
                    <input type="tel" name="phone" required
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none transition-colors"
                           placeholder="+60 12-345 6789">
                </div>
                <div class="mb-5">
                    <label class="block text-gray-700 font-bold mb-2">Company Name <span class="text-red-500">*</span></label>
                    <input type="text" name="company" required
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none transition-colors"
                           placeholder="Your company name">
                </div>

                <!-- ✅ FIELD REMARKS BARU -->
                <div class="mb-7">
                    <label class="block text-gray-700 font-bold mb-2">Remarks</label>
                    <textarea name="remarks" rows="3"
                              class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none transition-colors resize-none"
                              placeholder="Additional notes or inquiries (optional)"></textarea>
                </div>

                <button type="submit" id="submitBtn"
                        class="w-full bg-primary hover:bg-primary-dark text-white px-8 py-4 rounded-xl font-bold text-lg transition-all hover:shadow-2xl flex items-center justify-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <span id="submitBtnText">View Brochure</span>
                </button>
                <div id="formError" class="hidden mt-5 p-4 bg-red-50 border-2 border-red-200 rounded-xl text-red-700 text-sm font-semibold"></div>
            </form>
        </div>
    </div>
</div>

<!-- WhatsApp Float Button -->
<a href="https://wa.me/60123456789" target="_blank"
   class="fixed bottom-8 right-8 bg-green-500 hover:bg-green-600 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-xl transition-all hover:scale-110 z-50">
    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
    </svg>
</a>

<script>
function openBrochureModal(productId, productName, brochureFile) {
    document.getElementById('modal_product_id').value   = productId;
    document.getElementById('modal_brochure_file').value = brochureFile;
    document.getElementById('modalProductName').textContent = productName;
    document.getElementById('brochureForm').reset();
    document.getElementById('modal_product_id').value   = productId;
    document.getElementById('modal_brochure_file').value = brochureFile;
    document.getElementById('formError').classList.add('hidden');

    const modal = document.getElementById('downloadModal');
    const modalContent = document.getElementById('modalContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('downloadModal');
    const modalContent = document.getElementById('modalContent');
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }, 200);
}

async function submitBrochureForm(event) {
    event.preventDefault();

    const submitBtn     = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const formError     = document.getElementById('formError');

    submitBtn.disabled        = true;
    submitBtnText.textContent = 'Processing...';
    formError.classList.add('hidden');

    const formData = new FormData(event.target);
    formData.append('action', 'submit_download');

    try {
        const response = await fetch('process-download.php', {
            method: 'POST',
            body: formData
        });

        const rawText = await response.text();
        console.log('RAW RESPONSE:', rawText);

        let result;
        try {
            result = JSON.parse(rawText);
        } catch (e) {
            formError.textContent = 'Server error. Please try again or contact admin.';
            formError.classList.remove('hidden');
            return;
        }

        if (result.success) {
            closeModal();
            window.open(result.brochure_url, '_blank', 'noopener,noreferrer');
        } else {
            formError.textContent = result.message || 'An error occurred. Please try again.';
            formError.classList.remove('hidden');
        }

    } catch (error) {
        formError.textContent = 'Cannot reach server. Please check your internet connection.';
        formError.classList.remove('hidden');
        console.error('Fetch error:', error);
    } finally {
        submitBtn.disabled        = false;
        submitBtnText.textContent = 'View Brochure';
    }
}

document.getElementById('downloadModal').addEventListener('click', function(e) {
    if (e.target.id === 'downloadModal') closeModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});

document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase().trim();
    const brandSections = document.querySelectorAll('.brand-section');
    const noResults = document.getElementById('noResults');
    let hasVisibleResults = false;

    brandSections.forEach(section => {
        const brandLogo = section.querySelector('.brand-logo');
        const brandName = brandLogo.getAttribute('data-brand-name').toLowerCase();
        const products  = section.querySelectorAll('.product-item');
        let visibleProducts = 0;

        products.forEach(product => {
            const productName = product.querySelector('.product-name').textContent.toLowerCase();
            if (searchTerm === '' || productName.includes(searchTerm) || brandName.includes(searchTerm)) {
                product.style.display = 'block';
                visibleProducts++;
            } else {
                product.style.display = 'none';
            }
        });

        if (visibleProducts > 0) {
            section.style.display = 'block';
            hasVisibleResults = true;
        } else {
            section.style.display = 'none';
        }
    });

    if (searchTerm !== '' && !hasVisibleResults) {
        noResults.classList.remove('hidden');
    } else {
        noResults.classList.add('hidden');
    }
});
</script>

<style>
.brand-logo-img {
    filter: drop-shadow(0 2px 6px rgba(0,0,0,.20)) drop-shadow(0 1px 3px rgba(0,0,0,.12));
    transition: filter 0.3s ease, transform 0.3s ease;
}
.brand-logo-img:hover {
    filter: drop-shadow(0 4px 12px rgba(0,0,0,.28)) drop-shadow(0 2px 6px rgba(0,0,0,.16));
    transform: scale(1.05);
}
.overflow-x-auto::-webkit-scrollbar { height: 4px; }
.overflow-x-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #E63946; border-radius: 10px; }
.product-item { transition: all 0.3s ease; }
.brand-section { transition: all 0.3s ease; }
#modalContent { transition: all 0.2s ease-out; }
</style>

<?php
include 'includes/footer.php';
include 'includes/scripts.php';
?>