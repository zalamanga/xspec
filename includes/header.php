<?php
// includes/header.php - ULTRA SMOOTH Navigation header
// Uses existing $db connection from parent file

// Only create connection if it doesn't exist (for standalone pages)
if (!isset($db)) {
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
}

require_once __DIR__ . '/country.php';
$__active_cc      = active_country();
$__country_row    = active_country_info($db);
$__country_name   = $__country_row['name']       ?? 'Malaysia';
$__country_flag   = $__country_row['flag_emoji'] ?? '🇲🇾';
$__countries_sw   = get_countries_with_urls($db);

// Categories yang tersedia di country aktif
$query = "SELECT c.id, c.name, c.slug
          FROM categories c
          JOIN category_countries cc ON cc.category_id = c.id
          WHERE c.is_active = 1 AND cc.country_code = :cc
          ORDER BY c.display_order ASC";
$stmt = $db->prepare($query);
$stmt->execute([':cc' => $__active_cc]);
$nav_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Brands yang tersedia di country aktif
$query = "SELECT b.id, b.name, b.slug, b.category_id
          FROM brands b
          JOIN brand_countries bc ON bc.brand_id = b.id
          WHERE b.is_active = 1 AND bc.country_code = :cc
          ORDER BY b.display_order ASC";
$stmt = $db->prepare($query);
$stmt->execute([':cc' => $__active_cc]);
$nav_all_brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group brands by category ID
$nav_brands_by_category = [];
foreach ($nav_all_brands as $nav_brand) {
    $nav_brands_by_category[$nav_brand['category_id']][] = $nav_brand;
}
?>

<!-- Header with Ultra Smooth Shrinking Effect -->
<header id="mainHeader" class="bg-white shadow-lg sticky top-0 z-50">
    <!-- Logo & Download Section -->
    <div id="topSection" class="border-b border-gray-100 overflow-hidden">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-3 sm:py-4">
            <!-- ✅ SELALU flex-row (satu baris) di semua ukuran layar -->
            <div class="flex flex-row justify-between items-center gap-2">
                
                <!-- Logo + Country Label -->
                <div class="flex-shrink-0 flex items-center gap-3 sm:gap-4">
                    <a href="/" class="flex-shrink-0">
                        <img src="/img/logo.png" alt="XSpec <?php echo htmlspecialchars($__country_name); ?>" class="h-10 sm:h-16 lg:h-20 w-auto">
                    </a>
                    <div class="hidden sm:flex flex-col leading-tight border-l-2 border-primary pl-3 sm:pl-4">
                        <span class="text-[10px] sm:text-xs uppercase tracking-[0.2em] text-gray-400 font-semibold">XSpec</span>
                        <span class="text-lg sm:text-xl lg:text-2xl font-display font-bold text-gray-800 flex items-center gap-1.5">
                            <span class="text-base sm:text-lg"><?php echo $__country_flag; ?></span>
                            <?php echo htmlspecialchars($__country_name); ?>
                        </span>
                    </div>
                    <!-- Mobile: flag saja -->
                    <div class="sm:hidden text-2xl" title="<?php echo htmlspecialchars($__country_name); ?>">
                        <?php echo $__country_flag; ?>
                    </div>
                </div>

                <!-- Country Switcher + Sosmed + Download - selalu di kanan -->
                <div class="flex flex-row items-center gap-3 sm:gap-4 lg:gap-6">

                    <!-- Country Switcher -->
                    <div id="countrySwitcherWrap">
                        <button id="countrySwitcherBtn" type="button" onclick="toggleCountryDropdown(event)"
                                class="flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 sm:py-2.5 bg-white border-2 border-gray-200 hover:border-primary rounded-lg font-semibold text-xs sm:text-sm transition-all hover:shadow-md">
                            <span class="text-base sm:text-lg leading-none"><?php echo $__country_flag; ?></span>
                            <span class="hidden sm:inline text-gray-700 uppercase tracking-wide"><?php echo htmlspecialchars($__country_row['code'] ?? 'my'); ?></span>
                            <svg class="w-3 h-3 text-gray-500 transition-transform" id="countryCaret" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                    <!-- /Country Switcher -->

                    <!-- Social Media Icons -->
                    <div class="flex gap-3 sm:gap-4 lg:gap-5">
                        <a href="#" class="text-gray-600 hover:text-primary transition-colors">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-7 lg:h-7" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <!-- <a href="#" class="text-gray-600 hover:text-primary transition-colors">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-7 lg:h-7" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a> -->
                        <a href="#" class="text-gray-600 hover:text-primary transition-colors">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-7 lg:h-7" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                    </div>

                    <!-- Download Button -->
                    <a href="/downloads" class="bg-primary hover:bg-primary-dark text-white px-3 sm:px-6 lg:px-8 py-2 sm:py-2.5 lg:py-3 rounded-lg font-display font-semibold text-xs sm:text-sm lg:text-base transition-all hover:-translate-y-0.5 hover:shadow-lg inline-flex items-center gap-1.5 sm:gap-2 whitespace-nowrap">
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 lg:w-5 lg:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Download
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- DESKTOP NAVIGATION -->
    <nav class="hidden lg:block bg-white border-t border-gray-100">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <ul class="flex items-center justify-between gap-0">
                <li>
                    <a href="/" class="block px-2 py-4 text-base font-display font-medium uppercase tracking-normal hover:bg-gray-50 hover:text-primary transition-colors whitespace-nowrap">
                        Home
                    </a>
                </li>

                <?php foreach ($nav_categories as $nav_category): ?>
                    <?php
                    $category_has_brands = isset($nav_brands_by_category[$nav_category['id']]) && count($nav_brands_by_category[$nav_category['id']]) > 0;

                    if ($category_has_brands):
                        $category_brands = $nav_brands_by_category[$nav_category['id']];
                    ?>
                    <li class="relative group">
                        <a href="/category/<?php echo urlencode($nav_category['slug']); ?>"
                           class="px-2 py-4 text-base font-display font-medium uppercase tracking-normal hover:bg-gray-50 hover:text-primary transition-colors flex items-center gap-1 whitespace-nowrap">
                            <span><?php echo htmlspecialchars($nav_category['name']); ?></span>
                            <svg class="w-3 h-3 flex-shrink-0 transition-transform group-hover:rotate-180" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </a>
                        <ul class="hidden group-hover:block absolute top-full left-0 bg-white min-w-[240px] shadow-xl border-t-4 border-primary z-50">
                            <?php foreach ($category_brands as $index => $nav_brand): ?>
                                <li>
                                    <a href="/brand/<?php echo urlencode($nav_brand['slug']); ?>"
                                       class="block px-6 py-3 text-sm hover:bg-gray-50 hover:text-primary hover:pl-8 transition-all <?php echo ($index < count($category_brands) - 1) ? 'border-b border-gray-100' : ''; ?>">
                                        <?php echo htmlspecialchars($nav_brand['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                <?php endforeach; ?>

                <li>
                    <a href="/training" class="block px-2 py-4 text-base font-display font-medium uppercase tracking-normal hover:bg-gray-50 hover:text-primary transition-colors whitespace-nowrap">
                        Training & Services
                    </a>
                </li>

                <li>
                    <a href="#contact" class="block px-2 py-4 text-base font-display font-medium uppercase tracking-normal hover:bg-gray-50 hover:text-primary transition-colors whitespace-nowrap">
                        Contact Us
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- MOBILE NAVIGATION -->
    <nav class="lg:hidden bg-white border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between">
                <button class="p-4" onclick="toggleMobileMenu()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <ul id="mobileMenu" class="hidden flex-col w-full bg-white shadow-lg absolute left-0 top-full max-h-[70vh] overflow-y-auto z-50">
                <li>
                    <a href="/" onclick="closeMobileMenu()" class="block px-4 py-4 text-sm font-display font-medium uppercase tracking-wide hover:bg-gray-50 hover:text-primary transition-colors border-b border-gray-100">
                        Home
                    </a>
                </li>

                <?php foreach ($nav_categories as $nav_category): ?>
                    <?php
                    $category_has_brands = isset($nav_brands_by_category[$nav_category['id']]) && count($nav_brands_by_category[$nav_category['id']]) > 0;

                    if ($category_has_brands):
                        $category_brands = $nav_brands_by_category[$nav_category['id']];
                        $mobile_id = 'mobile-cat-' . $nav_category['id'];
                    ?>
                    <li class="border-b border-gray-100">
                        <div class="flex items-center">
                            <a href="/category/<?php echo urlencode($nav_category['slug']); ?>"
                               onclick="closeMobileMenu()"
                               class="flex-1 px-4 py-4 text-sm font-display font-medium uppercase tracking-wide hover:bg-gray-50 hover:text-primary transition-colors">
                                <?php echo htmlspecialchars($nav_category['name']); ?>
                            </a>
                            <button type="button"
                                    onclick="toggleMobileDropdown('<?php echo $mobile_id; ?>'); event.stopPropagation();"
                                    class="px-4 py-4 hover:bg-gray-50 transition-colors">
                                <svg id="<?php echo $mobile_id; ?>-icon" class="w-5 h-5 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                        <ul id="<?php echo $mobile_id; ?>" class="hidden bg-gray-50">
                            <?php foreach ($category_brands as $brand_index => $nav_brand): ?>
                                <li>
                                    <a href="/brand/<?php echo urlencode($nav_brand['slug']); ?>"
                                       onclick="closeMobileMenu()"
                                       class="block px-8 py-3 text-sm hover:bg-gray-100 hover:text-primary transition-all <?php echo ($brand_index < count($category_brands) - 1) ? 'border-b border-gray-200' : ''; ?>">
                                        <?php echo htmlspecialchars($nav_brand['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                <?php endforeach; ?>

                <li>
                    <a href="/training" onclick="closeMobileMenu()" class="block px-4 py-4 text-sm font-display font-medium uppercase tracking-wide hover:bg-gray-50 hover:text-primary transition-colors border-b border-gray-100">
                        Training & Services
                    </a>
                </li>

                <li>
                    <a href="#contact" onclick="closeMobileMenu()" class="block px-4 py-4 text-sm font-display font-medium uppercase tracking-wide hover:bg-gray-50 hover:text-primary transition-colors">
                        Contact Us
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</header>

<!-- Country Dropdown — di-render di luar <header> biar gak ketiban overflow-hidden -->
<div id="countryDropdown"
     class="hidden fixed w-60 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden"
     style="z-index: 9999;">
    <div class="bg-gradient-to-r from-primary to-red-600 px-4 py-3">
        <p class="text-white text-xs font-semibold uppercase tracking-widest">Select Region</p>
    </div>
    <div class="py-2">
        <?php foreach ($__countries_sw as $__cs): ?>
            <a href="<?php echo htmlspecialchars($__cs['url']); ?>"
               class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors group
                      <?php echo $__cs['code'] === $__active_cc ? 'bg-red-50' : ''; ?>">
                <span class="text-2xl leading-none"><?php echo $__cs['flag_emoji']; ?></span>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-800 text-sm group-hover:text-primary transition-colors">
                        XSpec <?php echo htmlspecialchars($__cs['name']); ?>
                    </p>
                    <p class="text-xs text-gray-400 uppercase tracking-wider">
                        <?php echo htmlspecialchars($__cs['code']); ?>
                    </p>
                </div>
                <?php if ($__cs['code'] === $__active_cc): ?>
                    <svg class="w-5 h-5 text-primary flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
(function () {
    const btn   = document.getElementById('countrySwitcherBtn');
    const dd    = document.getElementById('countryDropdown');
    const caret = document.getElementById('countryCaret');

    function positionDropdown() {
        if (!btn || !dd) return;
        const r = btn.getBoundingClientRect();
        const ddWidth = dd.offsetWidth || 240;
        const margin = 8;

        // Taruh di bawah tombol, align right
        let top  = r.bottom + margin;
        let left = r.right - ddWidth;

        // Jangan keluar dari viewport kiri
        if (left < 8) left = 8;
        // Jangan keluar dari viewport kanan
        const maxLeft = window.innerWidth - ddWidth - 8;
        if (left > maxLeft) left = maxLeft;

        dd.style.top  = top  + 'px';
        dd.style.left = left + 'px';
    }

    window.toggleCountryDropdown = function (e) {
        if (e) e.stopPropagation();
        const isHidden = dd.classList.contains('hidden');
        if (isHidden) {
            dd.classList.remove('hidden');
            positionDropdown();
            if (caret) caret.style.transform = 'rotate(180deg)';
        } else {
            dd.classList.add('hidden');
            if (caret) caret.style.transform = 'rotate(0deg)';
        }
    };

    // Tutup kalau klik di luar
    document.addEventListener('click', function (e) {
        if (dd.classList.contains('hidden')) return;
        if (btn.contains(e.target) || dd.contains(e.target)) return;
        dd.classList.add('hidden');
        if (caret) caret.style.transform = 'rotate(0deg)';
    });

    // Tutup dengan Esc
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !dd.classList.contains('hidden')) {
            dd.classList.add('hidden');
            if (caret) caret.style.transform = 'rotate(0deg)';
        }
    });

    // Re-position saat resize/scroll
    window.addEventListener('resize', function () {
        if (!dd.classList.contains('hidden')) positionDropdown();
    });
    window.addEventListener('scroll', function () {
        if (!dd.classList.contains('hidden')) positionDropdown();
    }, { passive: true });
})();
</script>