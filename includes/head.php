<?php
// Auto-resolve country for meta tags kalau belum di-load
if (!isset($__country_name)) {
    if (file_exists(__DIR__ . '/country.php')) {
        require_once __DIR__ . '/country.php';
        if (isset($db) && function_exists('active_country_info')) {
            $__country_row  = active_country_info($db);
            $__country_name = $__country_row['name']       ?? 'Malaysia';
            $__country_flag = $__country_row['flag_emoji'] ?? '🇲🇾';
        } else {
            $__country_name = 'Malaysia';
            $__country_flag = '🇲🇾';
        }
    } else {
        $__country_name = 'Malaysia';
        $__country_flag = '🇲🇾';
    }
}
$__brand_full = 'XSpec ' . $__country_name;

// SEO helpers
$__scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$__host     = $_SERVER['HTTP_HOST'] ?? 'xspectechnology.com';
$__base_url = $__scheme . '://' . $__host;
$__path     = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$__canonical = $__base_url . $__path;

$__page_title       = isset($title) ? $title : $__brand_full . ' - Excellent After Sales Service';
$__page_description = isset($meta_description) ? $meta_description : $__brand_full . ' provides excellent after sales service and innovative technology solutions across Southeast Asia.';
$__page_image       = isset($og_image) ? $og_image : $__base_url . '/img/logo.png';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="/">
    <title><?php echo htmlspecialchars($__page_title); ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/img/logo.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/img/logo.png">
    <link rel="apple-touch-icon" href="/img/logo.png">

    <!-- SEO -->
    <meta name="description" content="<?php echo htmlspecialchars($__page_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($__brand_full); ?>, NDT, inspection technology, after sales service, oil and gas, military defence, bio-tech laboratory, medical healthcare">
    <meta name="author" content="<?php echo htmlspecialchars($__brand_full); ?>">
    <meta name="robots" content="index, follow">
    <meta name="google-site-verification" content="x1IS14PVAtyYfyswpoxUUs0n2MeJIM8Gjz7T3xj3Wao">
    <link rel="canonical" href="<?php echo htmlspecialchars($__canonical); ?>">

    <!-- Open Graph / Facebook / WhatsApp / LinkedIn -->
    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="<?php echo htmlspecialchars($__brand_full); ?>">
    <meta property="og:title"       content="<?php echo htmlspecialchars($__page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($__page_description); ?>">
    <meta property="og:image"       content="<?php echo htmlspecialchars($__page_image); ?>">
    <meta property="og:url"         content="<?php echo htmlspecialchars($__canonical); ?>">
    <meta property="og:locale"      content="en_US">

    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?php echo htmlspecialchars($__page_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($__page_description); ?>">
    <meta name="twitter:image"       content="<?php echo htmlspecialchars($__page_image); ?>">

    <!-- Structured Data (Organization) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "<?php echo addslashes($__brand_full); ?>",
      "url": "<?php echo $__base_url; ?>",
      "logo": "<?php echo $__base_url; ?>/img/logo.png",
      "description": "<?php echo addslashes($__page_description); ?>",
      "areaServed": ["Malaysia", "Singapore", "Indonesia", "Southeast Asia"],
      "sameAs": []
    }
    </script>

    <?php if (!empty($breadcrumbs) && is_array($breadcrumbs)): ?>
    <!-- BreadcrumbList Schema — bikin breadcrumb muncul di Google results -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        <?php foreach ($breadcrumbs as $i => $bc): ?>
        {
          "@type": "ListItem",
          "position": <?php echo $i + 1; ?>,
          "name": "<?php echo addslashes($bc['name']); ?>",
          "item": "<?php echo $__base_url . $bc['url']; ?>"
        }<?php echo $i < count($breadcrumbs) - 1 ? ',' : ''; ?>
        <?php endforeach; ?>
      ]
    }
    </script>
    <?php endif; ?>

    <?php if (!empty($json_ld_extra)): ?>
    <!-- Extra structured data (Product / Event / etc.) -->
    <script type="application/ld+json">
    <?php echo $json_ld_extra; ?>
    </script>
    <?php endif; ?>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#e63946',
                        'primary-dark': '#d62839',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <style>
    /* ===== EXISTING ANIMATIONS (keep these) ===== */
    @keyframes pulse {
        0%, 100% {
            box-shadow: 0 4px 20px rgba(37, 211, 102, 0.4);
        }
        50% {
            box-shadow: 0 4px 30px rgba(37, 211, 102, 0.7);
        }
    }

    .whatsapp-pulse {
        animation: pulse 2s infinite;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .slide-up {
        animation: slideUp 0.3s ease;
    }

    .hero-pattern {
        background-image: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(230,57,70,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    }

    /* ===================================================
       🔥 NEW HEADER FIX - ADD THIS SECTION
       ================================================= */

    /* TOP SECTION - Base State */
    #topSection {
        position: relative;
        overflow: hidden;
        
        /* Transform-based animation */
        transform: translateY(0);
        opacity: 1;
        
        /* Smooth transition */
        transition: 
            transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
            opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
            padding 0.3s cubic-bezier(0.4, 0, 0.2, 1),
            margin 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        
        /* GPU acceleration untuk anti-flicker */
        will-change: transform, opacity;
        transform: translateZ(0);
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    /* 🔥 HIDDEN STATE - COMPLETE COLLAPSE (KEY FIX!) */
    #topSection.hidden-section {
        /* Slide UP dengan transform */
        transform: translateY(-100%) !important;
        opacity: 0 !important;
        
        /* 🎯 THE FIX - Force all spacing to ZERO */
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        margin: 0 !important;
        
        /* Force height jadi 0 */
        height: 0 !important;
        min-height: 0 !important;
        max-height: 0 !important;
        
        /* Hide dari interactions */
        pointer-events: none !important;
        visibility: hidden !important;
        
        /* Remove border */
        border: none !important;
        border-width: 0 !important;
    }

    /* 🔥 Force collapse DIRECT CHILDREN */
    #topSection.hidden-section > * {
        padding: 0 !important;
        margin: 0 !important;
        height: 0 !important;
        min-height: 0 !important;
        max-height: 0 !important;
        opacity: 0 !important;
        transform: scale(0) !important;
    }

    /* 🔥 Force collapse ALL DESCENDANTS */
    #topSection.hidden-section * {
        padding: 0 !important;
        margin: 0 !important;
    }

    /* MAIN HEADER - Smooth shadow transition */
    #mainHeader {
        position: sticky;
        top: 0;
        z-index: 50;
        
        /* Remove default spacing */
        padding: 0;
        margin: 0;
        
        /* Smooth shadow */
        transition: box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        
        /* Anti-flicker */
        transform: translateZ(0);
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
    }

    /* Enhanced shadow when scrolled */
    #mainHeader.shadow-xl {
        box-shadow: 
            0 20px 25px -5px rgba(0, 0, 0, 0.1), 
            0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    }

    /* 🔥 NAVIGATION - Ensure no top spacing */
    #mainHeader nav {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }

    /* Saat collapsed, nav tight ke top */
    #mainHeader.shadow-xl nav {
        border-top-width: 0 !important;
        margin-top: 0 !important;
        padding-top: 0 !important;
    }

    /* SAFARI & WEBKIT FIX */
    @supports (-webkit-appearance: none) {
        #topSection {
            -webkit-transform: translate3d(0, 0, 0);
        }
        
        #topSection.hidden-section {
            -webkit-transform: translate3d(0, -100%, 0);
        }
    }

    /* HARDWARE ACCELERATION - All elements */
    #mainHeader,
    #topSection,
    #topSection * {
        transform: translateZ(0);
        -webkit-transform: translateZ(0);
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
    }

    /* FONT RENDERING - Prevent blur during animation */
    #topSection,
    #topSection * {
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: optimizeLegibility;
    }

    /* MOBILE OPTIMIZATION - Extra aggressive */
    @media (max-width: 768px) {
        #topSection.hidden-section {
            display: none !important;
        }
    }
</style>
</head>

<body class="font-sans text-gray-700">