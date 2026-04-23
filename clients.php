<?php
// admin/clients.php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/country.php';

$page_title    = 'Manage Clients';
$active_cc     = get_active_country($db);
$countries_all = get_countries($db);
$upload_dir    = '../img/clients/';
$success = '';
$error = '';

// ─── HANDLE POST ACTIONS ───────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Validate country_code helper
    $valid_cc = array_column($countries_all, 'code');

    // ── ADD CLIENT ──
    if ($action === 'add') {
        $name    = trim($_POST['name'] ?? '');
        $order   = (int)($_POST['display_order'] ?? 0);
        $cc_post = $_POST['country_code'] ?? $active_cc;
        $cc_post = in_array($cc_post, $valid_cc) ? $cc_post : $active_cc;

        if (!$name) {
            $error = 'Client name is required.';
        } elseif (empty($_FILES['logo']['name'])) {
            $error = 'Logo image is required.';
        } else {
            $file     = $_FILES['logo'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','gif','webp','svg'];

            if (!in_array($ext, $allowed)) {
                $error = 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp, svg.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $error = 'File too large. Max 2MB.';
            } else {
                $filename = 'client_' . time() . '_' . uniqid() . '.' . $ext;
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                    $stmt = $db->prepare("INSERT INTO clients (name, logo, country_code, display_order) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $filename, $cc_post, $order]);
                    $success = "Client \"$name\" added successfully.";
                } else {
                    $error = 'Failed to upload file. Check folder permissions.';
                }
            }
        }
    }

    // ── EDIT CLIENT ──
    if ($action === 'edit') {
        $id      = (int)($_POST['id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $order   = (int)($_POST['display_order'] ?? 0);
        $cc_post = $_POST['country_code'] ?? $active_cc;
        $cc_post = in_array($cc_post, $valid_cc) ? $cc_post : $active_cc;

        if (!$id || !$name) {
            $error = 'Invalid data.';
        } else {
            // fetch existing logo
            $stmt = $db->prepare("SELECT logo FROM clients WHERE id = ?");
            $stmt->execute([$id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            $logo = $existing['logo'];

            // replace logo if new file uploaded
            if (!empty($_FILES['logo']['name'])) {
                $file    = $_FILES['logo'];
                $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp','svg'];

                if (!in_array($ext, $allowed)) {
                    $error = 'Invalid file type.';
                } elseif ($file['size'] > 2 * 1024 * 1024) {
                    $error = 'File too large. Max 2MB.';
                } else {
                    $filename = 'client_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                        // delete old if not legacy clientX.png
                        if ($logo && !preg_match('/^client\d+\.png$/', $logo) && file_exists($upload_dir . $logo)) {
                            @unlink($upload_dir . $logo);
                        }
                        $logo = $filename;
                    } else {
                        $error = 'Failed to upload new logo.';
                    }
                }
            }

            if (!$error) {
                $stmt = $db->prepare("UPDATE clients SET name=?, logo=?, country_code=?, display_order=? WHERE id=?");
                $stmt->execute([$name, $logo, $cc_post, $order, $id]);
                $success = "Client updated successfully.";
            }
        }
    }

    // ── TOGGLE ACTIVE ──
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("UPDATE clients SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
        header('Location: clients.php');
        exit;
    }

    // ── DELETE CLIENT ──
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("SELECT logo FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // only delete file if it's not a legacy clientX.png
            if (!preg_match('/^client\d+\.png$/', $row['logo']) && file_exists($upload_dir . $row['logo'])) {
                @unlink($upload_dir . $row['logo']);
            }
            $db->prepare("DELETE FROM clients WHERE id = ?")->execute([$id]);
            $success = 'Client deleted.';
        }
    }
}

// ─── FETCH CLIENTS (filtered by active country) ───────────────────────────
$stmt = $db->prepare("SELECT * FROM clients WHERE country_code = ? ORDER BY display_order ASC, id ASC");
$stmt->execute([$active_cc]);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="p-6">

    <?php if ($success): ?>
    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center gap-2">
        <i class="fas fa-check-circle text-green-500"></i> <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-center gap-2">
        <i class="fas fa-exclamation-circle text-red-500"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-handshake text-primary mr-2"></i> Manage Clients
            </h1>
            <p class="text-sm text-gray-500 mt-1">Logos displayed in "Clients We've Served" section on homepage</p>
        </div>
        <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                class="px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition font-semibold text-sm">
            <i class="fas fa-plus mr-2"></i> Add Client
        </button>
    </div>

    <!-- Stats bar -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 text-center">
            <div class="text-2xl font-bold text-primary"><?php echo count($clients); ?></div>
            <div class="text-xs text-gray-500 mt-1">Total Clients</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 text-center">
            <div class="text-2xl font-bold text-green-600"><?php echo count(array_filter($clients, fn($c) => $c['is_active'])); ?></div>
            <div class="text-xs text-gray-500 mt-1">Active</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 text-center">
            <div class="text-2xl font-bold text-gray-400"><?php echo count(array_filter($clients, fn($c) => !$c['is_active'])); ?></div>
            <div class="text-xs text-gray-500 mt-1">Hidden</div>
        </div>
    </div>

    <!-- Client Grid Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Order</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Logo</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Client Name</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">File</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Status</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clients)): ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400">
                            <i class="fas fa-users text-4xl mb-3 block"></i>
                            No clients yet. Add your first client!
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($clients as $c): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-4 text-gray-500 font-mono text-sm"><?php echo $c['display_order']; ?></td>
                        <td class="py-3 px-4">
                            <div class="w-20 h-14 flex items-center justify-center bg-gray-50 rounded border border-gray-200 p-1">
                                <img src="../img/clients/<?php echo htmlspecialchars($c['logo']); ?>"
                                     alt="<?php echo htmlspecialchars($c['name']); ?>"
                                     class="max-h-full max-w-full object-contain"
                                     onerror="this.src='https://via.placeholder.com/80x50?text=No+Logo'">
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($c['name']); ?></p>
                        </td>
                        <td class="py-3 px-4 text-gray-400 text-xs font-mono"><?php echo htmlspecialchars($c['logo']); ?></td>
                        <td class="py-3 px-4 text-center">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                <button type="submit"
                                        class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $c['is_active'] ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'; ?> transition-colors">
                                    <?php echo $c['is_active'] ? 'Active' : 'Hidden'; ?>
                                </button>
                            </form>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex justify-center gap-2">
                                <!-- Edit button -->
                                <button onclick="openEdit(<?php echo htmlspecialchars(json_encode($c)); ?>)"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <!-- Delete button -->
                                <form method="POST" class="inline client-delete-form">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                    <input type="hidden" name="client_name" value="<?php echo htmlspecialchars($c['name']); ?>">
                                    <button type="submit" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ─── ADD MODAL ──────────────────────────────────────────────────────────── -->
<div id="addModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b">
            <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-plus text-primary mr-2"></i>Add Client</h3>
            <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Client Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required placeholder="e.g. Polis DiRaja Malaysia"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Country <span class="text-red-500">*</span></label>
                <select name="country_code" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary text-sm">
                    <?php foreach ($countries_all as $c): ?>
                        <option value="<?php echo $c['code']; ?>" <?php echo $c['code'] === $active_cc ? 'selected' : ''; ?>>
                            <?php echo $c['flag_emoji'] . ' ' . htmlspecialchars($c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Logo Image <span class="text-red-500">*</span></label>
                <input type="file" name="logo" accept="image/*" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:bg-primary file:text-white file:text-xs">
                <p class="text-xs text-gray-400 mt-1">Max 2MB. JPG, PNG, GIF, WEBP, SVG.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Display Order</label>
                <input type="number" name="display_order" value="<?php echo count($clients) + 1; ?>" min="0"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary text-sm">
                <p class="text-xs text-gray-400 mt-1">Lower number = shown first.</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark font-semibold text-sm transition">
                    <i class="fas fa-save mr-2"></i> Save Client
                </button>
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="flex-1 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold text-sm transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ─── EDIT MODAL ─────────────────────────────────────────────────────────── -->
<div id="editModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b">
            <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-edit text-blue-500 mr-2"></i>Edit Client</h3>
            <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Client Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="editName" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Country <span class="text-red-500">*</span></label>
                <select name="country_code" id="editCountry" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary text-sm">
                    <?php foreach ($countries_all as $c): ?>
                        <option value="<?php echo $c['code']; ?>">
                            <?php echo $c['flag_emoji'] . ' ' . htmlspecialchars($c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Current Logo</label>
                <div class="w-28 h-16 bg-gray-50 border border-gray-200 rounded p-1 flex items-center justify-center mb-2">
                    <img id="editLogoPreview" src="" alt="" class="max-h-full max-w-full object-contain">
                </div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Replace Logo (optional)</label>
                <input type="file" name="logo" accept="image/*"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:bg-blue-500 file:text-white file:text-xs">
                <p class="text-xs text-gray-400 mt-1">Leave empty to keep current logo.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Display Order</label>
                <input type="number" name="display_order" id="editOrder" min="0"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary text-sm">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold text-sm transition">
                    <i class="fas fa-save mr-2"></i> Update
                </button>
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                        class="flex-1 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold text-sm transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(data) {
    document.getElementById('editId').value    = data.id;
    document.getElementById('editName').value  = data.name;
    document.getElementById('editOrder').value = data.display_order;
    document.getElementById('editCountry').value = data.country_code || 'my';
    document.getElementById('editLogoPreview').src = '../img/clients/' + data.logo;
    document.getElementById('editModal').classList.remove('hidden');
}
// Close modal on backdrop click
['addModal','editModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
});
// Client delete with xModal
document.querySelectorAll('.client-delete-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const name = this.querySelector('input[name="client_name"]').value;
        const ok = await xModal.danger({
            title: 'Delete Client?',
            message: `Hapus client "${name}"?`,
            okText: 'Ya, Hapus'
        });
        if (ok) this.submit();
    });
});
</script>

<?php include 'includes/footer.php'; ?>