<?php
// index.php - COMPLETE FIXED VERSION
require_once 'config/database.php';
require_once 'includes/country.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

$active_cc = active_country();
$country_info = active_country_info($db);
$country_name = $country_info['name'] ?? 'Malaysia';

$title = "XSpec " . $country_name . " - Excellent After Sales Service";
$currentPage = 'home';

// Categories tersedia di country aktif
$stmt = $db->prepare("SELECT c.* FROM categories c
    JOIN category_countries cc ON cc.category_id = c.id
    WHERE c.is_active = 1 AND cc.country_code = :cc
    ORDER BY c.display_order ASC");
$stmt->execute([':cc' => $active_cc]);
$categories_industry = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/head.php';
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="relative h-[500px] lg:h-[600px] overflow-hidden" id="home">
    <div class="absolute inset-0 bg-gradient-to-br from-gray-900/80 to-gray-800/80">
        <div class="hero-pattern absolute inset-0 opacity-20"></div>
    </div>

    <div class="relative h-full flex items-center justify-center">
        <div id="heroSlider" class="w-full h-full">
            <!-- Slide 1 -->
            <div class="hero-slide absolute inset-0 opacity-100 transition-opacity duration-1000">
                <img src="img/global2.png" alt="After Sales Service"
                    class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-br from-gray-900/70 to-gray-800/70"></div>
                <div class="relative h-full flex items-center justify-center">
                    <div class="text-center px-4 sm:px-6 lg:px-8">
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-light text-white mb-6 tracking-wide">
                            EMPOWERING SOUTHEAST ASIA <span class="text-primary font-medium"> CUTTING-EDGE SOUTHEAST
                                ASIA WITH</span> TECHNOLOGY
                        </h1>
                        <p class="text-base sm:text-lg text-white/90 max-w-3xl mx-auto font-light">
                            Your trusted partner for innovative equipment and uncompromised after sales support across
                            Oil & Gas sectors.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Slide 2 -->
            <div class="hero-slide absolute inset-0 opacity-0 transition-opacity duration-1000">
                <img src="img/innovative2.png" alt="Innovative Solutions"
                    class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-br from-gray-900/70 to-gray-800/70"></div>
                <div class="relative h-full flex items-center justify-center">
                    <div class="text-center px-4 sm:px-6 lg:px-8">
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-light text-white mb-6 tracking-wide">
                            <span class="text-primary font-medium">SECURING </span> THE FUTURE OF DEFENSE
                        </h1>
                        <p class="text-base sm:text-lg text-white/90 max-w-3xl mx-auto font-light">
                            Empowering elite responders with breakthrough chemical detection, forensic digital
                            intelligence, and world-class behavioral analysis training.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Slide 3 -->
            <div class="hero-slide absolute inset-0 opacity-0 transition-opacity duration-1000">
                <img src="img/exxelent2.png" alt="Trusted Partner" class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-br from-gray-900/70 to-gray-800/70"></div>
                <div class="relative h-full flex items-center justify-center">
                    <div class="text-center px-4 sm:px-6 lg:px-8">
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-light text-white mb-6 tracking-wide">
                            Precision in <span class="text-primary font-medium">Every Care Interaction</span>
                        </h1>
                        <p class="text-base sm:text-lg text-white/90 max-w-3xl mx-auto font-light">
                            Advanced nurse call systems, fall management, and laboratory essentials designed to
                            streamline workflows and enhance patient safety.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <button onclick="changeSlide(-1)"
            class="absolute left-4 lg:left-8 top-1/2 -translate-y-1/2 w-12 h-12 lg:w-14 lg:h-14 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white hover:bg-primary hover:border-primary transition-all flex items-center justify-center text-2xl z-10">‹</button>
        <button onclick="changeSlide(1)"
            class="absolute right-4 lg:right-8 top-1/2 -translate-y-1/2 w-12 h-12 lg:w-14 lg:h-14 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white hover:bg-primary hover:border-primary transition-all flex items-center justify-center text-2xl z-10">›</button>

        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-3 z-10">
            <span onclick="goToSlide(0)"
                class="slider-dot-0 w-4 h-4 rounded-full bg-primary border-2 border-white cursor-pointer transition-all"></span>
            <span onclick="goToSlide(1)"
                class="slider-dot-1 w-3 h-3 rounded-full bg-white/30 cursor-pointer transition-all hover:bg-white/60"></span>
            <span onclick="goToSlide(2)"
                class="slider-dot-2 w-3 h-3 rounded-full bg-white/30 cursor-pointer transition-all hover:bg-white/60"></span>
        </div>
    </div>
</section>


<!-- WHY CHOOSE US Section -->
<section class="py-16 lg:py-24 bg-gray-50 relative overflow-hidden">
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center mb-14">
            <p class="text-sm uppercase tracking-[0.4em] text-primary font-semibold mb-3">Our Advantage</p>
            <h2 class="text-4xl sm:text-5xl lg:text-6xl font-display font-normal text-gray-900 mb-4">
                Why Choose <span class="text-primary font-semibold">XSpec?</span>
            </h2>
            <div class="w-20 h-1 bg-primary mx-auto"></div>
        </div>

        <!-- Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">

            <!-- Card 1 -->
            <div class="group bg-primary rounded-xl p-10 shadow-lg hover:-translate-y-1 hover:shadow-2xl hover:shadow-red-400/40 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-16 translate-x-16"></div>
                <div class="w-16 h-16 rounded-lg bg-white/15 flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                </div>
                <h3 class="text-white text-2xl font-display font-semibold mb-4 leading-snug">Years of Expertise</h3>
                <p class="text-white/85 text-base leading-relaxed">
                    A trusted track record of delivering reliable solutions to government and industry across Southeast Asia.
                </p>
                <div class="mt-6 h-0.5 w-10 bg-white/40 group-hover:w-16 transition-all duration-300"></div>
            </div>

            <!-- Card 2 -->
            <div class="group bg-primary rounded-xl p-10 shadow-lg hover:-translate-y-1 hover:shadow-2xl hover:shadow-red-400/40 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-16 translate-x-16"></div>
                <div class="w-16 h-16 rounded-lg bg-white/15 flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-white text-2xl font-display font-semibold mb-4 leading-snug">World-Class Technology</h3>
                <p class="text-white/85 text-base leading-relaxed">
                    We deliver high-performance technologies to elevate critical operations — solutions designed around your exact needs.
                </p>
                <div class="mt-6 h-0.5 w-10 bg-white/40 group-hover:w-16 transition-all duration-300"></div>
            </div>

            <!-- Card 3 -->
            <div class="group bg-primary rounded-xl p-10 shadow-lg hover:-translate-y-1 hover:shadow-2xl hover:shadow-red-400/40 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-16 translate-x-16"></div>
                <div class="w-16 h-16 rounded-lg bg-white/15 flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h3 class="text-white text-2xl font-display font-semibold mb-4 leading-snug">24/7 After-Sales Support</h3>
                <p class="text-white/85 text-base leading-relaxed">
                    We go beyond delivery with responsive support, hands-on training, and ongoing maintenance to keep your operations running smoothly.
                </p>
                <div class="mt-6 h-0.5 w-10 bg-white/40 group-hover:w-16 transition-all duration-300"></div>
            </div>

        </div>
    </div>
</section>

<!-- Industries Section (CLICKABLE - Goes to category-brands.php) -->
<section class="py-16 lg:py-24 bg-white">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 lg:mb-16">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-display font-normal text-gray-900 mb-4">
                Our <span class="text-primary font-semibold">Industries</span>
            </h2>
            <div class="w-20 h-1 bg-primary mx-auto mb-4"></div>
            <p class="text-gray-600">Comprehensive solutions for every industry</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 lg:gap-4">
            <?php foreach ($categories_industry as $industry): ?>

        <?php
$href = $industry['slug'] === 'training-services'
    ? 'training-services.php'
    : 'category-brands.php?slug=' . urlencode($industry['slug']);
?>
            <a href="<?php echo $href; ?>"
                class="bg-white rounded-lg shadow-lg overflow-hidden hover:-translate-y-2 hover:shadow-2xl transition-all duration-300 group relative block">
                <div class="h-52 sm:h-60 lg:h-48 xl:h-56 bg-gray-100 overflow-hidden relative">
                    <?php
                    $category_images = [
                        'oil-gas-marine' => 'img/pro1.jpg',
                        'military-defence-security' => 'img/military2.png',
                        'biotech-laboratory' => 'img/labo2.png',
                        'medical-healthcare' => 'img/health2.png',
                        'training-services' => 'img/training.png'
                    ];
                    $img_src = isset($category_images[$industry['slug']]) 
                        ? $category_images[$industry['slug']] 
                        : 'https://via.placeholder.com/600x400?text=' . urlencode($industry['name']);
                    ?>
                    <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($industry['name']); ?>"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute top-3 left-0 bg-primary text-white px-4 py-1.5 text-xs sm:text-sm font-bold uppercase tracking-wide shadow-lg"
                        style="clip-path: polygon(0 0, 100% 0, 95% 100%, 0% 100%);">
                        <?php echo htmlspecialchars($industry['name']); ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="py-16 lg:py-24 bg-gray-50" id="about">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-display font-normal text-gray-900 mb-6">
                    About <span class="text-primary font-semibold">XSpec <?php echo htmlspecialchars($country_name); ?></span>
                </h2>
                <p class="text-gray-600 text-justify leading-relaxed mb-4">
                    XSpec <?php echo htmlspecialchars($country_name); ?> is a leading provider of innovative technology solutions, serving multiple industries
                    across Southeast Asia. We specialize in delivering cutting-edge
                    equipment and expertise.
                </p>
                <p class="text-gray-600 text-justify leading-relaxed mb-4">
                    Our extensive portfolio includes solutions for Oil & Gas, Military & Defense, Bio.Tech & Laboratory,
                    Medical & Healthcare, and Aesthetics industries. We pride ourselves on excellent after-sales service
                    and long-term partnerships with our clients.
                </p>
                <p class="text-gray-600 text-justify leading-relaxed mb-8">
                    Currently serving Malaysia, Indonesia, and Singapore, we are actively expanding our presence to the
                    Philippines and Thailand, bringing world-class solutions closer to your business.
                </p>
                <div class="flex flex-wrap gap-4">
                    <div class="bg-primary/10 p-4 rounded-lg">
                        <div class="text-3xl font-bold text-primary mb-1">
                            <?php echo count($categories_industry); ?>+
                        </div>
                        <div class="text-sm text-gray-600">Industries Served</div>
                    </div>
                    
                    <div class="bg-primary/10 p-4 rounded-lg">
                        <?php
                        $stmt = $db->prepare("SELECT COUNT(*) as total FROM brands b
                            JOIN brand_countries bc ON bc.brand_id = b.id
                            WHERE b.is_active = 1 AND bc.country_code = :cc");
                        $stmt->execute([':cc' => $active_cc]);
                        $brand_count = $stmt->fetch()['total'];
                        ?>
                        <div class="text-3xl font-bold text-primary mb-1">
                            <?php echo $brand_count; ?>+
                        </div>
                        <div class="text-sm text-gray-600">Product Brands</div>
                    </div>
                </div>
            </div>
            <div class="relative">
                <div
                    class="h-64 sm:h-80 lg:h-96 bg-white rounded-xl shadow-lg border-2 border-gray-100 overflow-hidden">
                    <img src="img/aboutxspec.png" alt="XSpec <?php echo htmlspecialchars($country_name); ?> Office" class="w-full h-full object-cover">
                </div>
                <div class="absolute -bottom-6 -right-6 bg-primary text-white p-6 rounded-xl shadow-xl">
                    <div class="text-2xl font-bold">Since 2015</div>
                    <!-- <div class="text-sm">Serving Southeast Asia</div> -->
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CLIENT SERVED Section -->
<section class="py-16 lg:py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center mb-12">
            <p class="text-xs uppercase tracking-[0.4em] text-primary font-semibold mb-3">Trusted By</p>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-display font-normal text-gray-900 mb-4">
                Clients We've <span class="text-primary font-semibold">Served</span>
            </h2>
            <div class="w-20 h-1 bg-primary mx-auto mb-4"></div>
            <p class="text-gray-500 text-sm">Trusted by government agencies, universities, and industry leaders across
                Malaysia</p>
        </div>

        <!-- Logo Grid — no outline, white bg, bigger padding -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6 lg:gap-8">

            <?php
            $clients = [
                1  => 'Polis DiRaja Malaysia',
                2  => 'Bomba Dan Penyelamat Malaysia',
                3  => 'MSAT',
                4  => 'SPRM Malaysia',
                5  => 'Kastam DiRaja Malaysia',
                6  => 'University of Malaya',
                7  => 'Penjara Malaysia',
                8  => 'Universiti Teknologi MARA',
                9  => 'Institut Penyelidikan Perubatan Malaysia',
                10 => 'LHDN Malaysia',
                11 => 'ADTEC JTM Kemaman',
                12 => 'Kimia Malaysia',
                13 => 'Institut Penyelidikan Perubatan',
                14 => 'Kementerian Sumber Manusia JKKP',
                15 => 'Jabatan Pertanian',
                16 => 'Universiti Putra Malaysia',
                17 => 'Client 17',
                18 => 'Client 18',
                19 => 'Client 19',
                20 => 'Client 20',
            ];
            foreach ($clients as $num => $name):
            ?>
            <div class="bg-white flex items-center justify-center p-6 lg:p-8 min-h-[140px] lg:min-h-[160px] hover:scale-105 transition-transform duration-300">
                <img src="img/clients/client<?php echo $num; ?>.png"
                     alt="<?php echo htmlspecialchars($name); ?>"
                     class="max-h-24 lg:max-h-28 w-auto object-contain">
            </div>
            <?php endforeach; ?>

        </div>

        <p class="text-center text-gray-400 text-xs mt-6">& many more government agencies and private sectors across
            Malaysia, Indonesia & Singapore</p>
    </div>
</section>

<!-- Contact Section -->
<section class="py-16 lg:py-24 bg-gradient-to-br from-gray-700 to-gray-600 text-white" id="contact">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20">
            <div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-display font-normal mb-8">
                    Get in <span class="text-primary font-semibold">Touch</span>
                </h2>
                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div
                            class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0">
                            📍</div>
                        <div>
                            <h4 class="font-semibold text-lg mb-1">Address</h4>
                            <p class="text-white/90 font-light">No. 8, Jalan Industry USJ 1/8, Taman Perindustrian USJ
                                1, 47600 Subang Jaya, SELANGOR, MALAYSIA.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div
                            class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0">
                            📧</div>
                        <div>
                            <h4 class="font-semibold text-lg mb-1">Email</h4>
                            <p class="text-white/90 font-light">inquiry@xspec.com.my</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div
                            class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0">
                            📞</div>
                        <div>
                            <h4 class="font-semibold text-lg mb-1">Phone</h4>
                            <p class="text-white/90 font-light">+60 3 8023 1161 / 2</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div
                            class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0">
                            🕐</div>
                        <div>
                            <h4 class="font-semibold text-lg mb-1">Operating Hours</h4>
                            <p class="text-white/90 font-light">Mon - Fri: 9:00 AM - 6:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-6 lg:p-10">
                <form id="contactForm" onsubmit="handleContactSubmit(event)">
                    <div class="mb-6">
                        <input type="text" name="name" placeholder="Name" required
                            class="w-full px-4 py-3 bg-white border border-white/20 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:border-primary transition-colors">
                    </div>
                    <div class="mb-6">
                        <input type="tel" name="phone" placeholder="Mobile / WhatsApp" required
                            class="w-full px-4 py-3 bg-white border border-white/20 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:border-primary transition-colors">
                    </div>
                    <div class="mb-6">
                        <input type="email" name="email" placeholder="Email" required
                            class="w-full px-4 py-3 bg-white border border-white/20 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:border-primary transition-colors">
                    </div>
                    <div class="mb-6">
                        <input type="text" name="company" placeholder="Company Name"
                            class="w-full px-4 py-3 bg-white border border-white/20 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:border-primary transition-colors">
                    </div>
                    <div class="mb-6">
                        <textarea name="address" placeholder="Company Address"
                            class="w-full px-4 py-3 bg-white border border-white/20 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:border-primary transition-colors resize-y min-h-[110px]"></textarea>
                    </div>
                    <div class="mb-6">
                        <textarea name="message" placeholder="Message" required
                            class="w-full px-4 py-3 bg-white border border-white/20 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:border-primary transition-colors resize-y min-h-[130px]"></textarea>
                    </div>
                    <button type="submit" id="contactSubmitBtn"
                        class="w-full bg-primary hover:bg-primary-dark text-white py-3 px-8 rounded-lg font-display font-semibold uppercase tracking-wide transition-all hover:-translate-y-0.5 hover:shadow-lg disabled:opacity-60 disabled:cursor-not-allowed">
                        <span id="contactBtnText">Send Message</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php
include 'includes/footer.php';
include 'includes/scripts.php';
?>