<footer class="bg-gray-800 text-white/70 pt-16 pb-8 lg:pt-20 lg:pb-10">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 lg:gap-14 mb-12 lg:mb-16">

            <!-- Kolom 1 -->
            <div>
                <h3 class="text-2xl lg:text-3xl font-display font-bold text-white mb-5">
                    XSpec <?php echo htmlspecialchars($__country_name ?? 'Malaysia'); ?>
                </h3>
                <p class="text-base lg:text-lg font-light leading-relaxed mb-8">
                    Leading provider of innovative technology solutions across Southeast Asia with excellent after-sales service.
                </p>
                <div class="flex flex-wrap items-center gap-6 lg:gap-8">
                    <img src="img/footer/1.png" alt="AI CERTs"     class="h-16 lg:h-20 w-auto object-contain brightness-0 invert">
                    <img src="img/footer/2.png" alt="EyeDetect"    class="h-16 lg:h-20 w-auto object-contain brightness-0 invert">
                    <img src="img/footer/3.png" alt="MSC Malaysia" class="h-16 lg:h-20 w-auto object-contain brightness-0 invert">
                    <img src="img/footer/4.png" alt="SIRIM"        class="h-16 lg:h-20 w-auto object-contain brightness-0 invert">
                </div>
            </div>

            <!-- Kolom 2 -->
            <div>
                <h3 class="text-2xl lg:text-3xl font-display font-bold text-white mb-6">Quick Links</h3>
                <ul class="space-y-3 lg:space-y-4">
                    <li><a href="category-brands.php?slug=oil-gas-marine"            class="text-base lg:text-lg font-light hover:text-primary hover:pl-2 transition-all duration-200 inline-block">Oil, Gas & Marine</a></li>
                    <li><a href="category-brands.php?slug=military-defence-security" class="text-base lg:text-lg font-light hover:text-primary hover:pl-2 transition-all duration-200 inline-block">Military, Defence & Security</a></li>
                    <li><a href="category-brands.php?slug=bio-tech-laboratory"       class="text-base lg:text-lg font-light hover:text-primary hover:pl-2 transition-all duration-200 inline-block">Bio-tech & Laboratory</a></li>
                    <li><a href="category-brands.php?slug=medical-healthcare"        class="text-base lg:text-lg font-light hover:text-primary hover:pl-2 transition-all duration-200 inline-block">Medical & Healthcare</a></li>
                    <li><a href="#contact"                                            class="text-base lg:text-lg font-light hover:text-primary hover:pl-2 transition-all duration-200 inline-block">Contact</a></li>
                </ul>
            </div>

            <!-- Kolom 3 -->
            <div class="flex items-center justify-center md:justify-end">
                <img src="img/logo.png" alt="XSpec logo"
                     class="w-full max-w-sm bg-gray-700/50 p-8 lg:p-10 rounded-2xl">
            </div>

        </div>

        <div class="border-t border-gray-700 pt-8 lg:pt-10 text-center">
            <p class="text-sm lg:text-base font-light">
                Copyright <a href="https://scorpio.mschosting.com/interface/root#/login" target="_blank" rel="noopener" class="hover:text-primary transition-colors">©</a> 2026 XSpec Technology. All rights reserved. Design <a href="dashboard.php" class="hover:text-primary transition-colors">by</a> Rafik Muzakir
            </p>
        </div>
    </div>
</footer>

<!-- Public Toast/Modal container (untuk notifikasi submit form dll.) -->
<div id="xNotifyModal"
     class="fixed inset-0 z-[10000] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeXNotify()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden transform scale-95 opacity-0 transition-all duration-200"
         id="xNotifyDialog">
        <div id="xNotifyAccent" class="h-1.5 w-full bg-gradient-to-r from-primary to-red-600"></div>
        <div class="p-8 text-center">
            <div id="xNotifyIconWrap"
                 class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-5 ring-8 ring-red-50/50">
                <svg id="xNotifyIcon" class="w-8 h-8 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                </svg>
            </div>
            <h3 id="xNotifyTitle" class="text-xl font-display font-bold text-gray-800 mb-2">Success</h3>
            <p id="xNotifyMessage" class="text-gray-600 mb-6 leading-relaxed">Your message has been sent.</p>
            <button onclick="closeXNotify()"
                    class="bg-primary hover:bg-primary-dark text-white px-8 py-3 rounded-xl font-semibold transition-all hover:shadow-lg hover:-translate-y-0.5">
                OK
            </button>
        </div>
    </div>
</div>

<script>
function showXNotify(opts) {
    const modal  = document.getElementById('xNotifyModal');
    const dialog = document.getElementById('xNotifyDialog');
    const accent = document.getElementById('xNotifyAccent');
    const wrap   = document.getElementById('xNotifyIconWrap');
    const icon   = document.getElementById('xNotifyIcon');
    const title  = document.getElementById('xNotifyTitle');
    const msg    = document.getElementById('xNotifyMessage');

    const themes = {
        success: {
            accent: 'h-1.5 w-full bg-gradient-to-r from-green-500 to-emerald-600',
            wrap:   'w-16 h-16 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-5 ring-8 ring-green-50/50',
            icon:   'w-8 h-8 text-green-600',
            svg:    '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>'
        },
        error: {
            accent: 'h-1.5 w-full bg-gradient-to-r from-primary to-red-700',
            wrap:   'w-16 h-16 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-5 ring-8 ring-red-50/50',
            icon:   'w-8 h-8 text-primary',
            svg:    '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/>'
        }
    };

    const t = themes[opts.type] || themes.success;
    accent.className = t.accent;
    wrap.className   = t.wrap;
    icon.className   = t.icon;
    icon.innerHTML   = t.svg;
    title.textContent = opts.title   || (opts.type === 'error' ? 'Oops' : 'Success');
    msg.textContent   = opts.message || '';

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    requestAnimationFrame(() => {
        dialog.classList.remove('scale-95', 'opacity-0');
        dialog.classList.add('scale-100', 'opacity-100');
    });
}
function closeXNotify() {
    const modal  = document.getElementById('xNotifyModal');
    const dialog = document.getElementById('xNotifyDialog');
    dialog.classList.add('scale-95', 'opacity-0');
    dialog.classList.remove('scale-100', 'opacity-100');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 200);
}
// Close on Esc
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('xNotifyModal');
        if (modal && !modal.classList.contains('hidden')) closeXNotify();
    }
});
</script>

<!-- WhatsApp Button -->
<div onclick="toggleWhatsApp()" class="fixed bottom-6 right-6 lg:bottom-8 lg:right-8 w-14 h-14 lg:w-16 lg:h-16 bg-green-500 rounded-full flex items-center justify-center cursor-pointer whatsapp-pulse hover:scale-110 transition-transform z-50">
    <svg class="w-8 h-8 lg:w-10 lg:h-10 fill-white" viewBox="0 0 32 32">
        <path d="M16 0C7.164 0 0 7.164 0 16c0 2.829.747 5.49 2.052 7.794L.057 30.838a.5.5 0 0 0 .643.643l7.044-1.995A15.933 15.933 0 0 0 16 32c8.836 0 16-7.164 16-16S24.836 0 16 0zm8.074 22.464c-.355.996-1.764 1.825-2.883 2.064-.757.163-1.747.291-5.077-1.091-4.267-1.768-7.021-6.102-7.236-6.385-.206-.282-1.685-2.24-1.685-4.273 0-2.033 1.067-3.035 1.445-3.448.379-.412.827-.515 1.103-.515.275 0 .55.003.792.014.254.012.594-.097.929.708.344.827 1.178 2.873 1.281 3.082.103.209.172.454.035.736-.138.282-.206.458-.413.706-.206.248-.434.554-.619.743-.206.206-.421.43-.181.845.24.412.1.067 2.029 3.035.982 1.518 1.807 1.991 2.254 2.218.344.175.743.147.994-.088.32-.301 1.375-1.604 1.742-2.157.367-.553.735-.461 1.238-.275.503.186 3.199 1.508 3.749 1.782.55.275.917.412 1.051.641.137.236.137 1.373-.218 2.369z" />
    </svg>
</div>

<!-- WhatsApp Popup -->
<div id="whatsappPopup" class="fixed bottom-24 right-6 lg:bottom-28 lg:right-8 w-[calc(100%-3rem)] max-w-xs bg-white rounded-xl shadow-2xl z-50 hidden slide-up">
    <div class="bg-green-500 p-5 rounded-t-xl text-white relative">
        <h3 class="font-display font-semibold text-lg mb-1">Chat with XSpec</h3>
        <p class="text-sm opacity-90">We typically reply within minutes</p>
        <button onclick="toggleWhatsApp()" class="absolute top-4 right-4 w-8 h-8 bg-white/20 rounded-full flex items-center justify-center hover:bg-white/30 transition-colors text-xl">×</button>
    </div>
    <div class="p-5">
        <div class="bg-gray-100 p-4 rounded-lg mb-4 text-sm text-gray-800 leading-relaxed">
            👋 Hello! How can we help you today? Send us a message and our team will get back to you shortly.
        </div>
        <input type="text" id="waName" placeholder="Your Name" class="w-full px-3 py-3 border border-gray-200 rounded-lg mb-3 text-sm focus:outline-none focus:border-green-500 transition-colors">
        <input type="text" id="waMessage" placeholder="Your Message" class="w-full px-3 py-3 border border-gray-200 rounded-lg mb-3 text-sm focus:outline-none focus:border-green-500 transition-colors">
        <button onclick="sendWhatsApp()" class="w-full bg-green-500 hover:bg-green-600 text-white py-3 rounded-lg font-display font-semibold text-sm transition-all hover:-translate-y-0.5 hover:shadow-lg">
            Send Message on WhatsApp
        </button>
    </div>
</div>
