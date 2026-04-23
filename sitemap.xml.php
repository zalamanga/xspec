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

$urls = [];

// ── Static pages ────────────────────────────────────────────────────────────
$urls[] = ['loc' => "$base/",          'priority' => '1.0', 'changefreq' => 'weekly',  'lastmod' => $today];
$urls[] = ['loc' => "$base/training",  'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $today];
$urls[] = ['loc' => "$base/downloads", 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => $today];
$urls[] = ['loc' => "$base/contact",   'priority' => '0.6', 'changefreq' => 'yearly',  'lastmod' => $today];

// ── Categories ──────────────────────────────────────────────────────────────
try {
    $stmt = $db->prepare("SELECT c.slug, c.name, c.updated_at FROM categories c
        JOIN category_countries cc ON cc.category_id = c.id
        WHERE c.is_active = 1 AND cc.country_code = :cc
        ORDER BY c.display_order ASC");
    $stmt->execute([':cc' => $active_cc]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $urls[] = [
            'loc'        => "$base/category/" . rawurlencode($row['slug']),
            'priority'   => '0.8',
            'changefreq' => 'weekly',
            'lastmod'    => !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : $today,
        ];
    }
} catch (Exception $e) { /* skip */ }

// ── Brands (+ image extension) ──────────────────────────────────────────────
try {
    $stmt = $db->prepare("SELECT b.slug, b.name, b.logo, b.updated_at FROM brands b
        JOIN brand_countries bc ON bc.brand_id = b.id
        WHERE b.is_active = 1 AND bc.country_code = :cc
        ORDER BY b.display_order ASC");
    $stmt->execute([':cc' => $active_cc]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $entry = [
            'loc'        => "$base/brand/" . rawurlencode($row['slug']),
            'priority'   => '0.7',
            'changefreq' => 'weekly',
            'lastmod'    => !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : $today,
        ];
        if (!empty($row['logo'])) {
            $entry['image'] = [
                'loc'     => $base . '/' . ltrim($row['logo'], '/'),
                'title'   => $row['name'] . ' Logo',
                'caption' => $row['name'],
            ];
        }
        $urls[] = $entry;
    }
} catch (Exception $e) { /* skip */ }

// ── Trainings (+ image extension) ───────────────────────────────────────────
try {
    $stmt = $db->prepare("SELECT id, title, poster_img, updated_at FROM trainings
        WHERE is_active = 1 AND country_code = :cc
        ORDER BY date_start DESC");
    $stmt->execute([':cc' => $active_cc]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $entry = [
            'loc'        => "$base/training/" . (int)$row['id'],
            'priority'   => '0.6',
            'changefreq' => 'monthly',
            'lastmod'    => !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : $today,
        ];
        if (!empty($row['poster_img'])) {
            $entry['image'] = [
                'loc'     => $base . '/' . ltrim($row['poster_img'], '/'),
                'title'   => $row['title'],
                'caption' => $row['title'],
            ];
        }
        $urls[] = $entry;
    }
} catch (Exception $e) { /* skip */ }

// ── Output XML ──────────────────────────────────────────────────────────────
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($u['loc']) . "</loc>\n";
    echo "    <lastmod>" . $u['lastmod'] . "</lastmod>\n";
    echo "    <changefreq>" . $u['changefreq'] . "</changefreq>\n";
    echo "    <priority>" . $u['priority'] . "</priority>\n";
    if (!empty($u['image'])) {
        echo "    <image:image>\n";
        echo "      <image:loc>" . htmlspecialchars($u['image']['loc']) . "</image:loc>\n";
        echo "      <image:title>" . htmlspecialchars($u['image']['title']) . "</image:title>\n";
        echo "      <image:caption>" . htmlspecialchars($u['image']['caption']) . "</image:caption>\n";
        echo "    </image:image>\n";
    }
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
