<script>
// ===================================================
//  🔥 ENHANCED HEADER SCROLL CONTROLLER
//  With aggressive collapse and debug tools
// ===================================================

(function() {
    'use strict';
    
    const headerController = {
        topSection: document.getElementById('topSection'),
        mainHeader: document.getElementById('mainHeader'),
        lastScroll: 0,
        currentScroll: 0,
        ticking: false,
        isHidden: false,
        isTransitioning: false,
        SCROLL_THRESHOLD: 800,   // 🔥 UBAH SESUAI KEBUTUHAN
        DELTA: 10,
        TRANSITION_DURATION: 300,
        DEBUG_MODE: false  // Set true untuk debugging
    };

    // Validate elements
    if (!headerController.topSection || !headerController.mainHeader) {
        console.warn('Header elements not found');
        return;
    }

    // Debug logger
    function debugLog(message, data) {
        if (headerController.DEBUG_MODE) {
            console.log(`[HEADER] ${message}`, data || '');
        }
    }

    // 🔥 AGGRESSIVE COLLAPSE FUNCTION
    function forceCollapseTopSection() {
        const topSection = headerController.topSection;
        
        // Add hidden class
        topSection.classList.add('hidden-section');
        
        // 🔥 FORCE inline styles sebagai backup
        topSection.style.transform = 'translateY(-100%)';
        topSection.style.opacity = '0';
        topSection.style.padding = '0';
        topSection.style.margin = '0';
        topSection.style.height = '0';
        topSection.style.minHeight = '0';
        topSection.style.maxHeight = '0';
        topSection.style.overflow = 'hidden';
        topSection.style.visibility = 'hidden';
        
        // Force collapse children
        const children = topSection.querySelectorAll('*');
        children.forEach(child => {
            child.style.padding = '0';
            child.style.margin = '0';
        });
        
        debugLog('COLLAPSED', {
            height: topSection.offsetHeight,
            padding: window.getComputedStyle(topSection).padding
        });
    }

    // 🔥 RESTORE FUNCTION
    function restoreTopSection() {
        const topSection = headerController.topSection;
        
        // Remove hidden class
        topSection.classList.remove('hidden-section');
        
        // Clear inline styles (CSS ambil alih)
        topSection.style.transform = '';
        topSection.style.opacity = '';
        topSection.style.padding = '';
        topSection.style.margin = '';
        topSection.style.height = '';
        topSection.style.minHeight = '';
        topSection.style.maxHeight = '';
        topSection.style.overflow = '';
        topSection.style.visibility = '';
        
        // Clear children styles
        const children = topSection.querySelectorAll('*');
        children.forEach(child => {
            child.style.padding = '';
            child.style.margin = '';
        });
        
        debugLog('RESTORED', {
            height: topSection.offsetHeight
        });
    }

    // Main update function
    function updateHeader() {
        headerController.currentScroll = Math.max(0, window.pageYOffset || document.documentElement.scrollTop);
        
        debugLog('Scroll', {
            pos: headerController.currentScroll,
            hidden: headerController.isHidden
        });
        
        // 🔥 SKIP jika sedang transition
        if (headerController.isTransitioning) {
            headerController.ticking = false;
            return;
        }
        
        // Anti jitter
        const scrollDelta = Math.abs(headerController.currentScroll - headerController.lastScroll);
        if (scrollDelta < headerController.DELTA) {
            headerController.ticking = false;
            return;
        }
        
        const scrollingDown = headerController.currentScroll > headerController.lastScroll;
        const pastThreshold = headerController.currentScroll > headerController.SCROLL_THRESHOLD;
        const atTop = headerController.currentScroll <= 50;
        
        // Determine action
        let shouldHide = false;
        let shouldShow = false;
        
        if (atTop && headerController.isHidden) {
            shouldShow = true;
        } else if (scrollingDown && pastThreshold && !headerController.isHidden) {
            shouldHide = true;
        } else if (!scrollingDown && !atTop && headerController.isHidden) {
            shouldShow = true;
        }
        
        // Execute dengan transition lock
        if (shouldHide) {
            debugLog('HIDING header');
            
            headerController.isTransitioning = true;
            
            forceCollapseTopSection();
            headerController.mainHeader.classList.add('shadow-xl');
            headerController.isHidden = true;
            
            setTimeout(() => {
                headerController.isTransitioning = false;
            }, headerController.TRANSITION_DURATION);
            
        } else if (shouldShow) {
            debugLog('SHOWING header');
            
            headerController.isTransitioning = true;
            
            restoreTopSection();
            headerController.mainHeader.classList.remove('shadow-xl');
            headerController.isHidden = false;
            
            setTimeout(() => {
                headerController.isTransitioning = false;
            }, headerController.TRANSITION_DURATION);
        }
        
        headerController.lastScroll = headerController.currentScroll;
        headerController.ticking = false;
    }

    // Optimized scroll listener
    window.addEventListener('scroll', function() {
        if (!headerController.ticking) {
            window.requestAnimationFrame(updateHeader);
            headerController.ticking = true;
        }
    }, { passive: true });

    // Initialize
    window.addEventListener('load', function() {
        setTimeout(updateHeader, 100);
        debugLog('Header initialized');
    });

    // 🔍 DEBUG TOOLS (gunakan di console)
    window.headerDebug = {
        enable: function() {
            headerController.DEBUG_MODE = true;
            console.log('🔍 Debug mode ENABLED');
        },
        disable: function() {
            headerController.DEBUG_MODE = false;
            console.log('Debug mode disabled');
        },
        forceHide: function() {
            console.log('Forcing hide...');
            forceCollapseTopSection();
            headerController.mainHeader.classList.add('shadow-xl');
            headerController.isHidden = true;
        },
        forceShow: function() {
            console.log('Forcing show...');
            restoreTopSection();
            headerController.mainHeader.classList.remove('shadow-xl');
            headerController.isHidden = false;
        },
        getState: function() {
            const topSection = headerController.topSection;
            const computed = window.getComputedStyle(topSection);
            
            console.log('📊 CURRENT STATE:', {
                isHidden: headerController.isHidden,
                scrollPos: headerController.currentScroll,
                topHeight: topSection.offsetHeight,
                topPadding: computed.padding,
                topMargin: computed.margin,
                transform: computed.transform
            });
        },
        measureWhiteSpace: function() {
            const header = headerController.mainHeader;
            const nav = header.querySelector('nav');
            const headerTop = header.getBoundingClientRect().top;
            const navTop = nav.getBoundingClientRect().top;
            const whiteSpace = navTop - headerTop;
            
            console.log('📏 WHITE SPACE:', whiteSpace.toFixed(2) + 'px');
            console.log('Top section height:', headerController.topSection.offsetHeight + 'px');
            
            if (whiteSpace > 1) {
                console.warn('⚠️ WHITE SPACE DETECTED!');
                console.log('Check padding:', window.getComputedStyle(headerController.topSection).padding);
                console.log('Check margin:', window.getComputedStyle(headerController.topSection).margin);
            } else {
                console.log('✅ No white space detected');
            }
            
            return whiteSpace;
        }
    };
    
    console.log('🛠️ Header Debug Tools Available:');
    console.log('Type: headerDebug.enable() - Enable debug logging');
    console.log('Type: headerDebug.measureWhiteSpace() - Check for gaps');

})();


// ===== HERO SLIDER (KEEP EXISTING) =====
(function() {
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');

    function showSlide(n) {
        if (slides.length > 0) {
            slides.forEach((slide, index) => {
                slide.style.opacity = '0';
                slide.style.zIndex = '0';
                const dot = document.querySelector(`.slider-dot-${index}`);
                if (dot) {
                    dot.className = 'w-3 h-3 rounded-full bg-white/30 cursor-pointer transition-all hover:bg-white/60';
                }
            });

            if (n >= slides.length) currentSlide = 0;
            if (n < 0) currentSlide = slides.length - 1;

            slides[currentSlide].style.opacity = '1';
            slides[currentSlide].style.zIndex = '1';

            const activeDot = document.querySelector(`.slider-dot-${currentSlide}`);
            if (activeDot) {
                activeDot.className = 'w-4 h-4 rounded-full bg-primary border-2 border-white cursor-pointer transition-all';
            }
        }
    }

    window.changeSlide = function(n) {
        currentSlide += n;
        showSlide(currentSlide);
        resetAutoSlide();
    };

    window.goToSlide = function(n) {
        currentSlide = n;
        showSlide(currentSlide);
        resetAutoSlide();
    };

    let autoSlideInterval;
    if (slides.length > 0) {
        autoSlideInterval = setInterval(() => {
            currentSlide++;
            showSlide(currentSlide);
        }, 5000);
    }

    function resetAutoSlide() {
        if (slides.length > 0) {
            clearInterval(autoSlideInterval);
            autoSlideInterval = setInterval(() => {
                currentSlide++;
                showSlide(currentSlide);
            }, 5000);
        }
    }

    if (slides.length > 0) {
        showSlide(currentSlide);
    }
})();


// ===== MOBILE MENU FUNCTIONS (KEEP EXISTING) =====
window.toggleMobileMenu = function() {
    const menu = document.getElementById('mobileMenu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
};

window.closeMobileMenu = function() {
    const menu = document.getElementById('mobileMenu');
    if (menu) {
        menu.classList.add('hidden');
    }
    
    const dropdowns = document.querySelectorAll('[id^="mobile-cat-"]');
    dropdowns.forEach(dropdown => {
        if (!dropdown.id.endsWith('-icon')) {
            dropdown.classList.add('hidden');
            const icon = document.getElementById(dropdown.id + '-icon');
            if (icon) {
                icon.classList.remove('rotate-180');
            }
        }
    });
};

window.toggleMobileDropdown = function(id) {
    const dropdown = document.getElementById(id);
    const icon = document.getElementById(id + '-icon');
    
    if (dropdown && icon) {
        dropdown.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
    }
};


// ===== WHATSAPP FUNCTIONS (KEEP EXISTING) =====
window.toggleWhatsApp = function() {
    const popup = document.getElementById('whatsappPopup');
    if (popup) {
        popup.classList.toggle('hidden');
    }
};

window.sendWhatsApp = function() {
    const name = document.getElementById('waName')?.value;
    const message = document.getElementById('waMessage')?.value;

    if (!name || !message) {
        alert('Please fill in your name and message');
        return;
    }

    const phoneNumber = '60123456789';
    const whatsappMessage = `Hello, my name is ${name}. ${message}`;
    const whatsappURL = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(whatsappMessage)}`;

    window.open(whatsappURL, '_blank');

    document.getElementById('waName').value = '';
    document.getElementById('waMessage').value = '';
    toggleWhatsApp();
};


// ===== CONTACT FORM — submit to process-contact.php =====
window.handleContactSubmit = async function (e) {
    e.preventDefault();
    const form    = e.target;
    const btn     = document.getElementById('contactSubmitBtn');
    const btnText = document.getElementById('contactBtnText');

    // Loading state
    if (btn) btn.disabled = true;
    if (btnText) btnText.innerHTML = '<svg class="inline-block w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Sending...';

    try {
        const fd  = new FormData(form);
        const res = await fetch('process-contact.php', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();

        if (data.success) {
            form.reset();
            if (window.showXNotify) {
                showXNotify({ type: 'success', title: 'Message Sent!', message: data.message });
            } else {
                alert(data.message);
            }
        } else {
            if (window.showXNotify) {
                showXNotify({ type: 'error', title: 'Failed to Send', message: data.message || 'Please try again.' });
            } else {
                alert(data.message || 'Failed to send.');
            }
        }
    } catch (err) {
        if (window.showXNotify) {
            showXNotify({ type: 'error', title: 'Network Error', message: 'Check your connection and try again.' });
        } else {
            alert('Network error');
        }
    } finally {
        if (btn) btn.disabled = false;
        if (btnText) btnText.textContent = 'Send Message';
    }
};

// Kompatibilitas dengan form lama kalau masih ada onsubmit="handleSubmit(...)"
window.handleSubmit = window.handleContactSubmit;


// ===== SMOOTH SCROLL FOR ANCHOR LINKS (KEEP EXISTING) =====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }
        }
    });
});


// ===== CLOSE WHATSAPP POPUP WHEN CLICKING OUTSIDE (KEEP EXISTING) =====
document.addEventListener('click', function (e) {
    const popup = document.getElementById('whatsappPopup');
    const button = e.target.closest('.whatsapp-pulse');

    if (popup && !popup.contains(e.target) && !button) {
        popup.classList.add('hidden');
    }
});


// ===== KEYBOARD SUPPORT FOR ESC KEY (KEEP EXISTING) =====
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const popup = document.getElementById('whatsappPopup');
        const mobileMenu = document.getElementById('mobileMenu');
        
        if (popup && !popup.classList.contains('hidden')) {
            popup.classList.add('hidden');
        }
        if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
            closeMobileMenu();
        }
    }
});


// ===== WHATSAPP TEXTAREA ENTER TO SEND (KEEP EXISTING) =====
const waMessage = document.getElementById('waMessage');
if (waMessage) {
    waMessage.addEventListener('keypress', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendWhatsApp();
        }
    });
}
</script>
</body>
</html>