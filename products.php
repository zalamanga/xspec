<?php
// admin/products.php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/country.php';

$page_title = 'Manage Products';
$active_cc  = get_active_country($db);

// Filters
$filter_category = $_GET['category'] ?? '';
$filter_brand    = $_GET['brand'] ?? '';

// Categories di country aktif (untuk dropdown filter)
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

// Products di country aktif
$where  = ['p.is_active = 1', 'pc.country_code = :cc'];
$params = [':cc' => $active_cc];

if ($filter_category) {
    $where[] = 'c.id = :category_id';
    $params[':category_id'] = $filter_category;
}
if ($filter_brand) {
    $where[] = 'b.id = :brand_id';
    $params[':brand_id'] = $filter_brand;
}

$where_sql = implode(' AND ', $where);

$query = "SELECT p.*, b.name as brand_name, c.name as category_name,
          (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY display_order ASC LIMIT 1) as first_image,
          (SELECT COUNT(*) FROM product_images WHERE product_id = p.id) as image_count,
          (SELECT GROUP_CONCAT(country_code) FROM product_countries WHERE product_id = p.id) as country_codes
          FROM products p
          JOIN brands b ON p.brand_id = b.id
          JOIN categories c ON b.category_id = c.id
          JOIN product_countries pc ON pc.product_id = p.id
          WHERE $where_sql
          ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="mb-6 flex flex-wrap gap-4">
    <a href="product-form.php" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-all hover:shadow-lg">
        <i class="fas fa-plus mr-2"></i> Add Product
    </a>

    <!-- Filters -->
    <select onchange="updateFilter('category', this.value)" 
            class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select onchange="updateFilter('brand', this.value)" 
            class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        <option value="">All Brands</option>
        <?php foreach ($all_brands as $brand): ?>
            <option value="<?php echo $brand['id']; ?>" <?php echo $filter_brand == $brand['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($brand['name']); ?> (<?php echo htmlspecialchars($brand['category_name']); ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <?php if ($filter_category || $filter_brand): ?>
    <button onclick="window.location.href='products.php'" 
            class="px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-semibold transition-colors">
        <i class="fas fa-times mr-2"></i> Clear Filters
    </button>
    <?php endif; ?>
</div>

<!-- Products Grid -->
<?php if (count($products) > 0): ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php foreach ($products as $product): ?>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:-translate-y-2 hover:shadow-2xl transition-all duration-300 border border-gray-100">
        <!-- Product Image -->
        <div class="bg-gradient-to-br from-gray-100 to-gray-200 p-4 h-48 flex items-center justify-center relative">
            <?php if ($product['first_image']): ?>
                <img src="../<?php echo htmlspecialchars($product['first_image']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     class="max-w-full max-h-full object-contain">
            <?php else: ?>
                <div class="text-gray-400 text-center">
                    <i class="fas fa-image text-5xl mb-2"></i>
                    <p class="text-sm">No Image</p>
                </div>
            <?php endif; ?>

            <!-- Image Count Badge -->
            <?php if ($product['image_count'] > 0): ?>
            <div class="absolute top-2 right-2 bg-black bg-opacity-70 text-white px-2 py-1 rounded-full text-xs font-semibold">
                <i class="fas fa-images mr-1"></i> <?php echo $product['image_count']; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div class="p-4">
            <!-- Category & Brand Tags -->
            <div class="flex gap-2 mb-3">
                <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-semibold">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </span>
                <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-semibold">
                    <?php echo htmlspecialchars($product['brand_name']); ?>
                </span>
            </div>

            <h3 class="font-bold text-gray-800 mb-2 line-clamp-2">
                <?php echo htmlspecialchars($product['name']); ?>
            </h3>

            <?php if ($product['subtitle']): ?>
            <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                <?php echo htmlspecialchars($product['subtitle']); ?>
            </p>
            <?php endif; ?>

            <!-- Status + Countries Badge -->
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $product['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
                <?php
                $codes = array_filter(explode(',', $product['country_codes'] ?? ''));
                foreach ($codes as $code):
                ?>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 uppercase">
                        <?php echo htmlspecialchars($code); ?>
                    </span>
                <?php endforeach; ?>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
                <a href="product-form.php?id=<?php echo $product['id']; ?>" 
                   class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors text-center">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <button onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')" 
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="bg-white rounded-xl shadow-md p-12 text-center">
    <i class="fas fa-box-open text-gray-300 text-6xl mb-4"></i>
    <p class="text-gray-600 text-lg mb-4">No products found</p>
    <a href="product-form.php" class="inline-block bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors">
        <i class="fas fa-plus mr-2"></i> Add Your First Product
    </a>
</div>
<?php endif; ?>

<script>
function updateFilter(type, value) {
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set(type, value);
    } else {
        url.searchParams.delete(type);
    }
    window.location.href = url.toString();
}

async function deleteProduct(id, name) {
    const ok = await xModal.danger({
        title: 'Delete Product?',
        message: `Hapus "${name}"? Semua gambar & data product ini juga akan dihapus.`,
        okText: 'Ya, Hapus'
    });
    if (!ok) return;

    try {
        const response = await fetch('ajax/delete-product.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
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