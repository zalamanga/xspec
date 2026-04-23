<?php
// admin/includes/sidebar.php
require_once __DIR__ . '/country.php';

// $db disediakan oleh halaman yang include sidebar ini (via includes/db.php).
// Kalau belum ada, load.
if (!isset($db)) {
    require_once __DIR__ . '/db.php';
}

$current_page    = basename($_SERVER['PHP_SELF']);
$countries_list  = get_countries($db);
$active_country  = get_active_country_info($db);
?>
<!-- Sidebar -->
<aside class="w-64 bg-gradient-to-b from-gray-800 to-gray-900 text-white flex-shrink-0">
    <div class="p-6 border-b border-gray-700">
        <img src="../img/logo.png" alt="XSpec" class="h-12 brightness-0 invert">
        <p class="text-xs text-gray-400 mt-2">Admin Panel</p>
    </div>

    <nav class="p-4">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg mb-2 transition-colors <?php echo $current_page == 'dashboard.php' ? 'bg-primary text-white' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-home w-5"></i>
            <span>Dashboard</span>
        </a>

        <a href="categories.php" class="flex items-center gap-3 px-4 py-3 rounded-lg mb-2 transition-colors <?php echo $current_page == 'categories.php' ? 'bg-primary text-white' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-th-large w-5"></i>
            <span>Categories</span>
        </a>

        <a href="brands.php" class="flex items-center gap-3 px-4 py-3 rounded-lg mb-2 transition-colors <?php echo $current_page == 'brands.php' ? 'bg-primary text-white' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-building w-5"></i>
            <span>Brands</span>
        </a>

        <a href="products.php" class="flex items-center gap-3 px-4 py-3 rounded-lg mb-2 transition-colors <?php echo $current_page == 'products.php' || $current_page == 'product-form.php' ? 'bg-primary text-white' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-box w-5"></i>
            <span>Products</span>
        </a>
      <a href="trainings.php" class="flex items-center gap-3 px-4 py-3 rounded-lg mb-2 transition-colors <?php echo $current_page == 'trainings.php' ? 'bg-primary text-white' : 'hover:bg-gray-700'; ?>">
    <i class="fas fa-chalkboard-teacher w-5"></i>
    <span>Training</span>
</a>

        <a href="clients.php" class="flex items-center gap-3 px-4 py-3 rounded-lg mb-2 transition-colors <?php echo $current_page == 'clients.php' ? 'bg-primary text-white' : 'hover:bg-gray-700'; ?>">
    <i class="fas fa-handshake w-5"></i>
    <span>Clients</span>
</a>

        <div class="border-t border-gray-700 my-4"></div>

        <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-red-600 transition-colors">
            <i class="fas fa-sign-out-alt w-5"></i>
            <span>Logout</span>
        </a>
    </nav>

    <div class="absolute bottom-0 w-64 p-4 border-t border-gray-700 bg-gray-900">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <p class="text-sm font-semibold"><?php echo $_SESSION['admin_name']; ?></p>
                <p class="text-xs text-gray-400"><?php echo ucfirst($_SESSION['admin_role']); ?></p>
            </div>
        </div>
    </div>
</aside>

<!-- Main Content -->
<div class="flex-1 flex flex-col overflow-hidden">
    <!-- Top Bar -->
    <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <h1 class="text-2xl font-bold text-gray-800">
                <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?>
            </h1>

            <div class="flex items-center gap-4">
                <!-- Country Switcher -->
                <div class="relative">
                    <div class="flex items-center gap-1 bg-gray-100 rounded-xl p-1 shadow-inner">
                        <?php foreach ($countries_list as $c): ?>
                            <button type="button"
                                    onclick="xSwitchCountry('<?php echo $c['code']; ?>')"
                                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all
                                           <?php echo $active_country && $active_country['code'] === $c['code']
                                                ? 'bg-primary text-white shadow-md shadow-red-300/40'
                                                : 'text-gray-600 hover:bg-white hover:text-primary'; ?>">
                                <span class="text-base leading-none"><?php echo $c['flag_emoji']; ?></span>
                                <span class="hidden sm:inline"><?php echo htmlspecialchars($c['name']); ?></span>
                                <span class="sm:hidden uppercase"><?php echo $c['code']; ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <span class="text-sm text-gray-600 hidden md:inline">
                    <?php echo date('l, F d, Y'); ?>
                </span>
            </div>
        </div>
    </header>

    <script>
    async function xSwitchCountry(code) {
        try {
            const res = await fetch('ajax/switch-country.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code })
            });
            const data = await res.json();
            if (data.success) {
                if (window.xModal && xModal.toast) {
                    xModal.toast('Switched to ' + code.toUpperCase(), 'success', 1200);
                    setTimeout(() => window.location.reload(), 400);
                } else {
                    window.location.reload();
                }
            } else {
                if (window.xModal) xModal.error(data.message || 'Failed to switch country');
                else alert(data.message || 'Failed');
            }
        } catch (e) {
            if (window.xModal) xModal.error('Network error. Try again.');
            else alert('Network error');
        }
    }
    </script>

    <!-- Page Content -->
    <main class="flex-1 overflow-y-auto p-6">