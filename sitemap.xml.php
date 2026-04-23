<?php
header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/country.php';

$database = new Database();
$db = $database->getConnection();

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'xspectechnology.com';
$base   = $scheme . '://' . $host;
$today  = date('Y-m-d');

$active_cc = active_country();

// Static pages
$urls = [
    ['loc' => "$base/",            'priority' => '1.0', 'changefreq' => 'weekly'],
    ['loc' => "$base/training",    'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => "$base/downloads",   'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => "$base/contact",     'priority' => '0.6', 'changefreq' => 'yearly'],
];

// Categories (per country aktif)
try {
    $stmt = $db->prepare("SELECT c.slug FROM categories c
        JOIN category_countries cc ON cc.category_id = c.id
        WHERE c.is_active = 1 AND cc.country_code = :cc
        ORDER BY c.display_order ASC");
    $stmt->execute([':cc' => $active_cc]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $urls[] = [
            'loc'        => "$base/category/" . rawurlencode($row['slug']),
            'priority'   => '0.8',
            'changefreq' => 'weekly',
        ];
    }
} catch (Exception $e) { /* skip kalau error */ }

// Brands (per country aktif)
try {
    $stmt = $db->prepare("SELECT b.slug FROM brands b
        JOIN brand_countries bc ON bc.brand_id = b.id
        WHERE b.is_active = 1 AND bc.country_code = :cc
        ORDER BY b.display_order ASC");
    $stmt->execute([':cc' => $active_cc]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $urls[] = [
            'loc'        => "$base/brand/" . rawurlencode($row['slug']),
            'priority'   => '0.7',
            'changefreq' => 'weekly',
        ];
    }
} catch (Exception $e) { /* skip kalau error */ }

// Trainings (by ID)
try {
    $stmt = $db->prepare("SELECT id FROM trainings WHERE is_active = 1 AND country_code = :cc");
    $stmt->execute([':cc' => $active_cc]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $urls[] = [
            'loc'        => "$base/training/" . (int)$row['id'],
            'priority'   => '0.6',
            'changefreq' => 'monthly',
        ];
    }
} catch (Exception $e) { /* skip kalau error */ }

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($u['loc']) . "</loc>\n";
    echo "    <lastmod>$today</lastmod>\n";
    echo "    <changefreq>{$u['changefreq']}</changefreq>\n";
    echo "    <priority>{$u['priority']}</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
