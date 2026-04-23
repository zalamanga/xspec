<?php
// admin/categories.php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/country.php';

$page_title      = 'Manage Categories';
$active_cc       = get_active_country($db);
$countries_all   = get_countries($db);

// Categories yang ter-assign ke country aktif
$query = "SELECT c.*,
          (SELECT COUNT(*) FROM brands b
             JOIN brand_countries bc ON bc.brand_id = b.id
             WHERE b.category_id = c.id AND bc.country_code = :cc1) as brand_count,
          (SELECT COUNT(*) FROM products p
             JOIN brands b ON p.brand_id = b.id
             JOIN product_countries pc ON pc.product_id = p.id
             WHERE b.category_id = c.id AND pc.country_code = :cc2) as product_count,
          (SELECT GROUP_CONCAT(country_code) FROM category_countries WHERE category_id = c.id) as country_codes
          FROM categories c
          JOIN category_countries cc ON cc.category_id = c.id
          WHERE cc.country_code = :cc3
          ORDER BY c.display_order ASC";
$stmt = $db->prepare($query);
$stmt->execute([':cc1' => $active_cc, ':cc2' => $active_cc, ':cc3' => $active_cc]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats khusus country aktif
$stats = [];

$stmt = $db->prepare("SELECT COUNT(*) AS total FROM categories c
    JOIN category_countries cc ON cc.category_id = c.id
    WHERE c.is_active = 1 AND cc.country_code = :cc");
$stmt->execute([':cc' => $active_cc]);
$stats['total_categories'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->prepare("SELECT COUNT(*) AS total FROM brands b
    JOIN brand_countries bc ON bc.brand_id = b.id
    WHERE b.is_active = 1 AND bc.country_code = :cc");
$stmt->execute([':cc' => $active_cc]);
$stats['total_brands'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->prepare("SELECT COUNT(*) AS total FROM products p
    JOIN product_countries pc ON pc.product_id = p.id
    WHERE p.is_active = 1 AND pc.country_code = :cc");
$stmt->execute([':cc' => $active_cc]);
$stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="mb-6">
    <button onclick="openAddModal()" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-all hover:shadow-lg">
        <i class="fas fa-plus mr-2"></i> Add Category
    </button>
</div>

<!-- Categories Table -->
<div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">ID</th>
                    <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Name</th>
                    <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Slug</th>
                    <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Brands</th>
                    <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Products</th>
                    <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Order</th>
                    <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Status</th>
                    <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-4 px-6 text-gray-800"><?php echo $category['id']; ?></td>
                    <td class="py-4 px-6">
                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($category['name']); ?></p>
                    </td>
                    <td class="py-4 px-6 text-gray-600 text-sm font-mono"><?php echo htmlspecialchars($category['slug']); ?></td>
                    <td class="py-4 px-6 text-center">
                        <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-semibold">
                            <?php echo $category['brand_count']; ?>
                        </span>
                    </td>
                    <td class="py-4 px-6 text-center">
                        <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm font-semibold">
                            <?php echo $category['product_count']; ?>
                        </span>
                    </td>
                    <td class="py-4 px-6 text-center text-gray-600"><?php echo $category['display_order']; ?></td>
                    <td class="py-4 px-6 text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $category['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                            <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td class="py-4 px-6">
                        <div class="flex items-center justify-center gap-2">
                            <button onclick='openEditModal(<?php echo json_encode($category); ?>)' 
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')" 
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Summary Statistics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Total Categories Card -->
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-lg font-semibold mb-2">Total Categories</p>
                <p class="text-6xl font-bold"><?php echo $stats['total_categories']; ?></p>
            </div>
            <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                <i class="fas fa-th-large text-4xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Brands Card -->
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-lg font-semibold mb-2">Total Brands</p>
                <p class="text-6xl font-bold"><?php echo $stats['total_brands']; ?></p>
            </div>
            <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                <i class="fas fa-building text-4xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Products Card -->
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-lg font-semibold mb-2">Total Products</p>
                <p class="text-6xl font-bold"><?php echo $stats['total_products']; ?></p>
            </div>
            <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                <i class="fas fa-box text-4xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="categoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-primary to-red-600 p-6 text-white">
            <h3 id="modalTitle" class="text-2xl font-bold">Add Category</h3>
        </div>

        <form id="categoryForm" class="p-6">
            <input type="hidden" id="categoryId" name="id">

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Category Name *</label>
                <input type="text" id="categoryName" name="name" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="e.g., Oil, Gas & Marine">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Slug *</label>
                <input type="text" id="categorySlug" name="slug" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="e.g., oil-gas-marine">
                <p class="text-xs text-gray-500 mt-1">URL-friendly version (lowercase, use hyphens)</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Display Order</label>
                <input type="number" id="categoryOrder" name="display_order" value="0"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Available in Countries *</label>
                <div class="grid grid-cols-3 gap-3">
                    <?php foreach ($countries_all as $c): ?>
                        <label class="flex items-center gap-2 px-4 py-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-red-50 transition-all">
                            <input type="checkbox" name="country_codes[]" value="<?php echo $c['code']; ?>"
                                   class="category-country-cb w-5 h-5 text-primary border-gray-300 rounded focus:ring-2 focus:ring-primary"
                                   <?php echo $c['code'] === $active_cc ? 'checked' : ''; ?>>
                            <span class="text-lg"><?php echo $c['flag_emoji']; ?></span>
                            <span class="text-sm font-semibold text-gray-700"><?php echo $c['code']; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-500 mt-2">Centang negara mana saja yang menampilkan kategori ini.</p>
            </div>

            <div class="mb-6">
                <label class="flex items-center gap-3">
                    <input type="checkbox" id="categoryActive" name="is_active" checked
                           class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-2 focus:ring-primary">
                    <span class="text-sm font-semibold text-gray-700">Active</span>
                </label>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white py-3 rounded-lg font-semibold transition-colors">
                    Save Category
                </button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg font-semibold transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-generate slug from name
document.getElementById('categoryName').addEventListener('input', function(e) {
    const slug = e.target.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
    document.getElementById('categorySlug').value = slug;
});

const ACTIVE_CC = <?php echo json_encode($active_cc); ?>;

function setCategoryCountries(codes) {
    document.querySelectorAll('.category-country-cb').forEach(cb => {
        cb.checked = codes.includes(cb.value);
    });
}

// Open Add Modal
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Category';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryActive').checked = true;
    setCategoryCountries([ACTIVE_CC]); // default: country yang lagi aktif
    document.getElementById('categoryModal').classList.remove('hidden');
}

// Open Edit Modal
function openEditModal(category) {
    document.getElementById('modalTitle').textContent = 'Edit Category';
    document.getElementById('categoryId').value = category.id;
    document.getElementById('categoryName').value = category.name;
    document.getElementById('categorySlug').value = category.slug;
    document.getElementById('categoryOrder').value = category.display_order;
    document.getElementById('categoryActive').checked = category.is_active == 1;
    const codes = (category.country_codes || '').split(',').filter(Boolean);
    setCategoryCountries(codes);
    document.getElementById('categoryModal').classList.remove('hidden');
}

// Close Modal
function closeModal() {
    document.getElementById('categoryModal').classList.add('hidden');
}

// Submit Form
document.getElementById('categoryForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const id = document.getElementById('categoryId').value;
    const country_codes = Array.from(document.querySelectorAll('.category-country-cb:checked')).map(cb => cb.value);

    if (country_codes.length === 0) {
        xModal.error('Centang minimal 1 negara.');
        return;
    }

    const data = {
        id: id || null,
        name: document.getElementById('categoryName').value,
        slug: document.getElementById('categorySlug').value,
        display_order: document.getElementById('categoryOrder').value,
        is_active: document.getElementById('categoryActive').checked ? 1 : 0,
        country_codes
    };

    try {
        const response = await fetch('ajax/save-category.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
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
});

// Delete Category
async function deleteCategory(id, name) {
    const ok = await xModal.danger({
        title: 'Delete Category?',
        message: `Hapus "${name}"? Ini juga akan menghapus semua brand & product di bawahnya.`,
        okText: 'Ya, Hapus'
    });
    if (!ok) return;

    try {
        const response = await fetch('ajax/delete-category.php', {
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