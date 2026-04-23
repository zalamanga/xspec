<?php
// 404.php - Custom 404 Error Page
require_once 'config/database.php';
require_once 'includes/country.php';

$database  = new Database();
$db        = $database->getConnection();
$active_cc = active_country();
$__ci      = active_country_info($db);
$__cname   = $__ci['name'] ?? 'Malaysia';

$page_title       = "404 - Page Not Found | XSpec " . $__cname;
$page_description = "The page you're looking for doesn't exist.";

// Popular categories di country aktif
$stmt = $db->prepare("SELECT c.id, c.name, c.slug
          FROM categories c
          JOIN category_countries cc ON cc.category_id = c.id
          WHERE c.is_active = 1 AND cc.country_code = :cc
          ORDER BY c.display_order ASC
          LIMIT 4");
$stmt->execute([':cc' => $active_cc]);
$popular_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#E31E24',
                        'primary-dark': '#B91419',
                    },
                    fontFamily: {
                        display: ['Poppins', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', system-ui, sans-serif;
        }
        
        /* Animated 404 Number */
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }
        
        /* Glitch effect for 404 */
        .glitch {
            position: relative;
            animation: glitch-skew 1s infinite;
        }
        
        @keyframes glitch-skew {
            0% {
                transform: skew(0deg);
            }
            10% {
                transform: skew(-2deg);
            }
            20% {
                transform: skew(2deg);
            }
            30% {
                transform: skew(0deg);
            }
            100% {
                transform: skew(0deg);
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>
    
    <!-- 404 Error Section -->
    <main class="min-h-screen flex items-center justify-center px-4 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Animated 404 Number -->
            <div class="mb-8">
                <h1 class="text-9xl md:text-[200px] font-display font-black text-primary glitch animate-float leading-none">
                    404
                </h1>
            </div>
            
            <!-- Error Message -->
            <div class="mb-8">
                <h2 class="text-3xl md:text-5xl font-display font-bold text-gray-900 mb-4">
                    Oops! Page Not Found
                </h2>
                <p class="text-lg md:text-xl text-gray-600 max-w-2xl mx-auto">
                    The page you're looking for seems to have gone missing. 
                    Don't worry, even the best explorers get lost sometimes!
                </p>
            </div>
            
            <!-- Search Suggestions Icon -->
            <div class="mb-12">
                <svg class="w-32 h-32 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-16">
                <a href="/"
                   class="bg-primary hover:bg-primary-dark text-white px-8 py-4 rounded-lg font-display font-semibold text-lg transition-all hover:-translate-y-1 hover:shadow-xl inline-flex items-center gap-3 w-full sm:w-auto justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Back to Home
                </a>
                
                <a href="javascript:history.back()" 
                   class="bg-white hover:bg-gray-50 text-gray-700 border-2 border-gray-300 px-8 py-4 rounded-lg font-display font-semibold text-lg transition-all hover:-translate-y-1 hover:shadow-xl inline-flex items-center gap-3 w-full sm:w-auto justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Go Back
                </a>
            </div>
            
          
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Mobile Menu Script -->
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
            menu.classList.toggle('flex');
        }

        function closeMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.add('hidden');
            menu.classList.remove('flex');
        }

        function toggleMobileDropdown(id) {
            const dropdown = document.getElementById(id);
            const icon = document.getElementById(id + '-icon');
            
            dropdown.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        }
    </script>
</body>
</html>