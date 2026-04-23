<?php
// category-brands.php - FIXED BREADCRUMB
require_once 'config/database.php';
require_once 'includes/country.php';

$database = new Database();
$db = $database->getConnection();

$active_cc = active_country();

// Get category slug from URL
$category_slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($category_slug)) {
    header('Location: /');
    exit;
}

// Get category info — HARUS tersedia di country aktif
$query = "SELECT c.* FROM categories c
          JOIN category_countries cc ON cc.category_id = c.id
          WHERE c.slug = :slug AND c.is_active = 1 AND cc.country_code = :cc
          LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute([':slug' => $category_slug, ':cc' => $active_cc]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header('Location: /');
    exit;
}

// Brands untuk category ini, filtered by country
$query = "SELECT b.id, b.name, b.slug, b.logo,
          COALESCE(b.short_description, '') as short_description,
          b.display_order,
          (SELECT COUNT(*) FROM products p
             JOIN product_countries pc ON pc.product_id = p.id
             WHERE p.brand_id = b.id AND p.is_active = 1 AND pc.country_code = :cc2) as product_count
          FROM brands b
          JOIN brand_countries bc ON bc.brand_id = b.id
          WHERE b.category_id = :category_id AND b.is_active = 1 AND bc.country_code = :cc
          ORDER BY b.display_order ASC";
$stmt = $db->prepare($query);
$stmt->execute([
    ':category_id' => $category['id'],
    ':cc'          => $active_cc,
    ':cc2'         => $active_cc,
]);
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

$__ci = active_country_info($db);
$title = htmlspecialchars($category['name']) . " - XSpec " . ($__ci['name'] ?? 'Malaysia');
$currentPage = 'brands';

include 'includes/head.php';
include 'includes/header.php';
?>

    

<!-- Category Header -->
<section class="py-12 bg-gradient-to-br from-gray-700 to-gray-600 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl font-display font-bold mb-4">
            <?php echo htmlspecialchars($category['name']); ?>
        </h1>
        <p class="text-xl text-white/90 max-w-3xl mx-auto">
            Explore our trusted brands and innovative solutions
        </p>
    </div>
</section>

<!-- Brands Grid -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-display font-bold text-gray-900 mb-4">
                Our <span class="text-primary">Brands</span>
            </h2>
            <p class="text-gray-600">Click "Read More" to explore products</p>
        </div>

        <?php if (count($brands) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <?php foreach ($brands as $brand): ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:-translate-y-2 hover:shadow-2xl transition-all duration-300 border border-gray-100">
                        
                        <!-- Brand Logo -->
                        <div class="bg-gradient-to-br from-gray-100 to-gray-200 p-8 flex items-center justify-center min-h-[180px]">
                            <?php if (!empty($brand['logo'])): ?>
                                <img src="<?php echo htmlspecialchars($brand['logo']); ?>" 
                                     alt="<?php echo htmlspecialchars($brand['name']); ?>" 
                                     class="max-h-24 max-w-full object-contain">
                            <?php else: ?>
                                <div class="text-center">
                                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-md">
                                        <i class="fas fa-building text-3xl text-primary"></i>
                                    </div>
                                    <p class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($brand['name']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Brand Info -->
                        <div class="p-6 bg-white">
                            <h3 class="text-xl font-display font-bold text-gray-900 mb-2 text-center">
                                <?php echo htmlspecialchars($brand['name']); ?>
                            </h3>
                            
                            <?php if (!empty($brand['short_description'])): ?>
                                <p class="text-gray-600 text-sm mb-4 text-center line-clamp-2">
                                    <?php echo htmlspecialchars($brand['short_description']); ?>
                                </p>
                            <?php else: ?>
                                <p class="text-gray-400 text-sm mb-4 text-center italic">No description available</p>
                            <?php endif; ?>

                            <!-- Product Count Badge -->
                            <div class="flex items-center justify-center space-x-2 mb-4">
                                <span class="bg-primary/10 text-primary px-3 py-1 rounded-full text-sm font-semibold">
                                    <?php 
                                    $count = isset($brand['product_count']) ? intval($brand['product_count']) : 0;
                                    echo $count; 
                                    ?> Product<?php echo $count != 1 ? 's' : ''; ?>
                                </span>
                            </div>

                            <!-- READ MORE BUTTON -->
                            <div class="text-center">
                                <a href="/brand/<?php echo rawurlencode($brand['slug']); ?>"
                                   class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-primary text-white font-semibold hover:bg-primary-dark transition-all hover:shadow-lg">
                                    Read More
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12 bg-gray-50 rounded-xl">
                <i class="fas fa-box-open text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-600 text-lg mb-6">No brands available in this category at the moment.</p>
                <a href="index.php" class="inline-block bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    ← Back to Industries
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
include 'includes/footer.php';
include 'includes/scripts.php';
?>