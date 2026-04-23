<?php
// includes/country.php  (public site)
// Deteksi country dari subdomain. Harus di-include SEBELUM query apapun.
//
// Aturan:
//   sg.xspectechnology.com → sg
//   id.xspectechnology.com → id
//   xspectechnology.com    → my  (default / site utama Malaysia)
//   localhost / xampp      → my  (default saat development)

if (!function_exists('detect_country_from_host')) {
    function detect_country_from_host() {
        $host = strtolower($_SERVER['HTTP_HOST'] ?? '');

        // Ambil bagian subdomain paling depan
        // contoh: "sg.xspectechnology.com"    → "sg"
        //         "sgtes.xspectechnology.com" → "sgtes"
        //         "idtes.xspectechnology.com" → "idtes"
        $parts = explode('.', $host);
        $first = $parts[0] ?? '';

        // Match berdasarkan PREFIX supaya fleksibel:
        //   prefix "sg*" → sg   (sg, sgtes, sg-staging, dll)
        //   prefix "id*" → id   (id, idtes, id-staging, dll)
        //   prefix "my*" → my   (my, mytes, dll)
        // Selain itu → default 'my'.
        if (strpos($first, 'sg') === 0) return 'sg';
        if (strpos($first, 'id') === 0) return 'id';
        if (strpos($first, 'my') === 0) return 'my';

        return 'my';
    }
}

if (!function_exists('active_country')) {
    function active_country() {
        static $cached = null;
        if ($cached !== null) return $cached;

        // Override lewat ?country=xx (untuk testing di localhost)
        if (!empty($_GET['country']) && in_array($_GET['country'], ['my','sg','id'], true)) {
            $cached = $_GET['country'];
            return $cached;
        }

        $cached = detect_country_from_host();
        return $cached;
    }
}

if (!function_exists('active_country_info')) {
    function active_country_info($db) {
        $code = active_country();
        $stmt = $db->prepare("SELECT * FROM countries WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ---------------------------------------------------------------------
// Map kode negara → base URL per subdomain (buat tombol country switcher)
// ---------------------------------------------------------------------
if (!function_exists('country_base_urls')) {
    function country_base_urls() {
        return [
            'my' => 'https://xspectechnology.com',
            'sg' => 'https://sg.xspectechnology.com',
            'id' => 'https://id.xspectechnology.com',
        ];
    }
}

// Dapatkan base URL untuk country code tertentu.
// Kalau localhost, return dengan ?country=xx biar testing tetep bisa.
if (!function_exists('country_url')) {
    function country_url($code) {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $is_local = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);

        if ($is_local) {
            // Localhost: stay on same host, cuma ganti ?country=xx
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $path   = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
            return $scheme . '://' . $host . $path . '?country=' . $code;
        }

        $urls = country_base_urls();
        return $urls[$code] ?? $urls['my'];
    }
}

// Ambil list country lengkap (dari DB) + url-nya — buat render dropdown.
if (!function_exists('get_countries_with_urls')) {
    function get_countries_with_urls($db) {
        $stmt = $db->query("SELECT * FROM countries WHERE is_active = 1 ORDER BY display_order ASC");
        $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($countries as &$c) {
            $c['url'] = country_url($c['code']);
        }
        return $countries;
    }
}
