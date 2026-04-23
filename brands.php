<?php
// admin/brands.php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/country.php';

$page_title    = 'Manage Brands';
$active_cc     = get_active_country($db);
$countries_all = get_countries($db);

// Filter
$filter_category = $_GET['category'] ?? '';

// Categories yang ter-assign ke country aktif (untuk dropdown filter & modal)
$stmt = $db->prepare("SELECT c.* FROM categories c
    JOIN category_countries cc ON cc.category_id = c.id
    WHERE c.is_active = 1 AND cc.country_code = :cc
    ORDER BY c.display_order ASC");
$stmt->execute([':cc' => $active_cc]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Brands yang ter-assign ke country aktif
$where  = ['bc.country_code = :cc'];
$params = [':cc' => $active_cc];

if ($filter_category) {
    $where[] = 'b.category_id = :category_id';
    $params[':category_id'] = $filter_category;
}

$where_sql = implode(' AND ', $where);

$query = "SELECT b.*, c.name as category_name,
          (SELECT COUNT(*) FROM products p
             JOIN product_countries pc ON pc.product_id = p.id
             WHERE p.brand_id = b.id AND pc.country_code = :cc2) as product_count,
          (SELECT GROUP_CONCAT(country_code) FROM brand_countries WHERE brand_id = b.id) as country_codes
          FROM brands b
          JOIN categories c ON b.category_id = c.id
          JOIN brand_countries bc ON bc.brand_id = b.id
          WHERE {$where_sql}
          ORDER BY c.display_order, b.display_order ASC";
$params[':cc2'] = $active_cc;

$stmt = $db->prepare($query);
$stmt->execute($params);
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="mb-6 flex gap-4">
    <button onclick="openAddModal()" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-all hover:shadow-lg">
        <i class="fas fa-plus mr-2"></i> Add Brand
    </button>

    <!-- Filter -->
    <select onchange="window.location.href='brands.php?category=' + this.value" 
            class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<!-- Brands Table -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">ID</th>
                    <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Logo</th>
                    <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Brand Name</th>
                    <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Category</th>
                    <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Slug</th>
                    <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Products</th>
                    <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Order</th>
                    <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Status</th>
                    <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($brands as $brand): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-4 px-6 text-gray-800"><?php echo $brand['id']; ?></td>
                    <td class="py-4 px-6">
                        <?php if ($brand['logo']): ?>
                            <img src="../<?php echo htmlspecialchars($brand['logo']); ?>" alt="Logo" class="h-12 object-contain">
                        <?php else: ?>
                            <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                <i class="fas fa-building text-gray-400"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="py-4 px-6">
                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($brand['name']); ?></p>
                        <?php if ($brand['short_description']): ?>
                            <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars(substr($brand['short_description'], 0, 50)) . (strlen($brand['short_description']) > 50 ? '...' : ''); ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="py-4 px-6 text-gray-600"><?php echo htmlspecialchars($brand['category_name']); ?></td>
                    <td class="py-4 px-6 text-gray-600 text-sm font-mono"><?php echo htmlspecialchars($brand['slug']); ?></td>
                    <td class="py-4 px-6 text-center">
                        <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm font-semibold">
                            <?php echo $brand['product_count']; ?>
                        </span>
                    </td>
                    <td class="py-4 px-6 text-center">
                        <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm font-semibold">
                            <?php echo $brand['display_order']; ?>
                        </span>
                    </td>
                    <td class="py-4 px-6 text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $brand['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                            <?php echo $brand['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td class="py-4 px-6">
                        <div class="flex items-center justify-center gap-2">
                            <button onclick='openEditModal(<?php echo json_encode($brand); ?>)' 
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="deleteBrand(<?php echo $brand['id']; ?>, '<?php echo htmlspecialchars($brand['name']); ?>')" 
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

<!-- Add/Edit Modal -->
<div id="brandModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-primary to-red-600 p-6 text-white">
            <h3 id="modalTitle" class="text-2xl font-bold">Add Brand</h3>
        </div>

        <form id="brandForm" class="p-6">
            <input type="hidden" id="brandId" name="id">

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Category *</label>
                <select id="brandCategory" name="category_id" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Brand Name *</label>
                <input type="text" id="brandName" name="name" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="e.g., INTRON">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Slug *</label>
                <input type="text" id="brandSlug" name="slug" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="e.g., intron">
                <p class="text-xs text-gray-500 mt-1">URL-friendly version (lowercase, use hyphens)</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Brand Logo</label>
                
                <!-- Current Logo Preview -->
                <div id="currentLogoPreview" class="hidden mb-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <img id="currentLogoImg" src="" alt="Current Logo" class="h-16 object-contain">
                            <p class="text-sm text-gray-600">Current Logo</p>
                        </div>
                        <button type="button" onclick="removeCurrentLogo()" 
                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm font-semibold transition-colors">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>

                <!-- Upload New Logo -->
                <input type="file" id="brandLogoFile" name="logo_file" accept="image/*"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Upload brand logo (JPG, PNG, GIF, WEBP, SVG)</p>
                
                <!-- Hidden field for existing logo path -->
                <input type="hidden" id="existingLogo" name="existing_logo" value="">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Short Description</label>
                <textarea id="brandShortDesc" name="short_description" rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                          placeholder="Brief description for brand card"></textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Display Order</label>
                <input type="number" id="brandOrder" name="display_order" min="1"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="Pilih category dulu...">
                <p class="text-xs text-gray-500 mt-1">Auto-fill dari urutan terakhir per category. Bisa diubah manual.</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Available in Countries *</label>
                <div class="grid grid-cols-3 gap-3">
                    <?php foreach ($countries_all as $c): ?>
                        <label class="flex items-center gap-2 px-4 py-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-red-50 transition-all">
                            <input type="checkbox" name="country_codes[]" value="<?php echo $c['code']; ?>"
                                   class="brand-country-cb w-5 h-5 text-primary border-gray-300 rounded focus:ring-2 focus:ring-primary"
                                   <?php echo $c['code'] === $active_cc ? 'checked' : ''; ?>>
                            <span class="text-lg"><?php echo $c['flag_emoji']; ?></span>
                            <span class="text-sm font-semibold text-gray-700"><?php echo $c['code']; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-500 mt-2">Centang negara mana saja yang menjual brand ini.</p>
            </div>

            <div class="mb-6">
                <label class="flex items-center gap-3">
                    <input type="checkbox" id="brandActive" name="is_active" checked
                           class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-2 focus:ring-primary">
                    <span class="text-sm font-semibold text-gray-700">Active</span>
                </label>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white py-3 rounded-lg font-semibold transition-colors">
                    Save Brand
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
document.getElementById('brandName').addEventListener('input', function(e) {
    const slug = e.target.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
    document.getElementById('brandSlug').value = slug;
});

// Auto-fetch next display_order per category (hanya saat Add mode)
document.getElementById('brandCategory').addEventListener('change', async function() {
    const categoryId = this.value;
    const brandId = document.getElementById('brandId').value;

    // Hanya auto-fill kalau mode Add (brandId kosong)
    if (!brandId && categoryId) {
        try {
            const response = await fetch(`ajax/get-next-order.php?category_id=${categoryId}`);
            const result = await response.json();
            if (result.success) {
                document.getElementById('brandOrder').value = result.next_order;
            }
        } catch (error) {
            console.error('Failed to fetch next order:', error);
        }
    }
});

const ACTIVE_CC = <?php echo json_encode($active_cc); ?>;

function setBrandCountries(codes) {
    document.querySelectorAll('.brand-country-cb').forEach(cb => {
        cb.checked = codes.includes(cb.value);
    });
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Brand';
    document.getElementById('brandForm').reset();
    document.getElementById('brandId').value = '';
    document.getElementById('brandActive').checked = true;
    document.getElementById('brandOrder').value = '';
    document.getElementById('currentLogoPreview').classList.add('hidden');
    document.getElementById('existingLogo').value = '';
    setBrandCountries([ACTIVE_CC]);
    document.getElementById('brandModal').classList.remove('hidden');
}

function openEditModal(brand) {
    document.getElementById('modalTitle').textContent = 'Edit Brand';
    document.getElementById('brandId').value = brand.id;
    document.getElementById('brandCategory').value = brand.category_id;
    document.getElementById('brandName').value = brand.name;
    document.getElementById('brandSlug').value = brand.slug;
    document.getElementById('brandShortDesc').value = brand.short_description || '';
    document.getElementById('brandOrder').value = brand.display_order;
    document.getElementById('brandActive').checked = brand.is_active == 1;

    if (brand.logo) {
        document.getElementById('existingLogo').value = brand.logo;
        document.getElementById('currentLogoImg').src = '../' + brand.logo;
        document.getElementById('currentLogoPreview').classList.remove('hidden');
    } else {
        document.getElementById('currentLogoPreview').classList.add('hidden');
    }

    const codes = (brand.country_codes || '').split(',').filter(Boolean);
    setBrandCountries(codes);

    document.getElementById('brandModal').classList.remove('hidden');
}

function removeCurrentLogo() {
    document.getElementById('existingLogo').value = 'REMOVE';
    document.getElementById('currentLogoPreview').classList.add('hidden');
}

function closeModal() {
    document.getElementById('brandModal').classList.add('hidden');
}

// Submit form dengan file upload
document.getElementById('brandForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const country_codes = Array.from(document.querySelectorAll('.brand-country-cb:checked')).map(cb => cb.value);
    if (country_codes.length === 0) {
        xModal.error('Centang minimal 1 negara.');
        return;
    }

    const formData = new FormData();
    formData.append('id', document.getElementById('brandId').value || '');
    formData.append('category_id', document.getElementById('brandCategory').value);
    formData.append('name', document.getElementById('brandName').value);
    formData.append('slug', document.getElementById('brandSlug').value);
    formData.append('short_description', document.getElementById('brandShortDesc').value);
    formData.append('display_order', document.getElementById('brandOrder').value);
    formData.append('is_active', document.getElementById('brandActive').checked ? 1 : 0);
    formData.append('existing_logo', document.getElementById('existingLogo').value);
    country_codes.forEach(c => formData.append('country_codes[]', c));

    const logoFile = document.getElementById('brandLogoFile').files[0];
    if (logoFile) {
        formData.append('logo_file', logoFile);
    }

    try {
        const response = await fetch('ajax/save-brand.php', {
            method: 'POST',
            body: formData
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

async function deleteBrand(id, name) {
    const ok = await xModal.danger({
        title: 'Delete Brand?',
        message: `Hapus "${name}"? Ini juga akan menghapus semua product di bawah brand ini.`,
        okText: 'Ya, Hapus'
    });
    if (!ok) return;

    try {
        const response = await fetch('ajax/delete-brand.php', {
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