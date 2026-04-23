<?php
// training-detail.php  — Public Training Detail
require_once 'config/database.php';
require_once 'includes/country.php';

$database = new Database();
$pdo = $database->getConnection();
$db  = $pdo;
$active_cc = active_country();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: /training');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM trainings WHERE id = ? AND is_active = 1 AND country_code = ?");
$stmt->execute([$id, $active_cc]);
$training = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$training) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

function fmtPrice($val) {
    return 'RM ' . number_format((float)$val, 0);
}

// Tombol Register: pakai registration_link kalau ada, fallback ke contact
$registerHref   = !empty($training['registration_link'])
                    ? htmlspecialchars($training['registration_link'])
                    : 'training.php#contact';
$registerTarget = !empty($training['registration_link']) ? '_blank' : '_self';
$registerRel    = !empty($training['registration_link']) ? 'noopener noreferrer' : '';

$waText = urlencode('Hi Dr. Nadia, I\'m interested in ' . $training['title']);
$waLink = 'https://wa.me/60127836893?text=' . $waText;

$__ci        = active_country_info($db);
$title       = $training['title'] . ' – XSpec ' . ($__ci['name'] ?? 'Malaysia');
$currentPage = 'training';

include 'includes/head.php';
include 'includes/header.php';
?>

<!-- ===== TRAINING DETAIL ===== -->
<section class="bg-gray-50 min-h-screen py-10 lg:py-16">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">



    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-start">

      <!-- POSTER -->
      <div class="lg:col-span-3">
        <div class="rounded-xl overflow-hidden border border-gray-200 shadow-md bg-white">
          <?php if ($training['poster_img']): ?>
            <img src="<?= htmlspecialchars($training['poster_img']) ?>"
                 alt="<?= htmlspecialchars($training['title']) ?>"
                 class="w-full h-auto object-contain"
                 onerror="this.src='https://placehold.co/600x800/f1f5f9/94a3b8?text=Poster'">
          <?php else: ?>
            <div class="flex items-center justify-center min-h-[400px] bg-gray-100">
              <p class="text-gray-400 text-sm">No poster available</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- SIDEBAR -->
      <div class="lg:col-span-2 space-y-5 lg:sticky lg:top-[88px]">

        <!-- Badge + Title -->
        <div>
          <span class="inline-block text-xs font-semibold uppercase tracking-widest
                       text-primary border border-primary/30 rounded-full px-3 py-1 mb-3">
            <?= htmlspecialchars($training['category']) ?>
          </span>
          <h1 class="text-xl sm:text-2xl font-display font-semibold text-gray-900 leading-snug">
            <?= htmlspecialchars($training['title']) ?>
          </h1>
          <?php if ($training['is_featured']): ?>
            <span class="inline-block mt-2 text-xs bg-yellow-100 text-yellow-700 rounded-full px-3 py-1">
              ★ Featured Program
            </span>
          <?php endif; ?>
        </div>

        <!-- Info rows -->
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
          <div class="divide-y divide-gray-100">

            <!-- Date -->
            <div class="flex items-center gap-3 px-5 py-3.5">
              <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-widest">Date</p>
                <p class="text-sm font-medium text-gray-800">
                  <?= htmlspecialchars(
                    $training['date_label'] ?:
                    date('j M', strtotime($training['date_start'])) . ' – ' .
                    date('j M Y', strtotime($training['date_end']))
                  ) ?>
                </p>
              </div>
            </div>

            <!-- Time -->
            <div class="flex items-center gap-3 px-5 py-3.5">
              <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-widest">Time</p>
                <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($training['time_label']) ?></p>
              </div>
            </div>

            <!-- Venue -->
            <div class="flex items-center gap-3 px-5 py-3.5">
              <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-widest">Venue</p>
                <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($training['location']) ?></p>
              </div>
            </div>

            <!-- Price -->
            <div class="flex items-center gap-3 px-5 py-3.5">
              <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <line x1="12" y1="1" x2="12" y2="23" stroke-width="2" stroke-linecap="round"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
              </svg>
              <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-widest">Investment</p>
                <p class="text-sm font-medium text-gray-800">
                  <?= fmtPrice($training['price']) ?> / pax
                  <?php if ($training['price_group'] > 0): ?>
                    <span class="text-gray-400 text-xs ml-1">
                      · Group: <?= fmtPrice($training['price_group']) ?> (<?= htmlspecialchars($training['price_group_min']) ?>)
                    </span>
                  <?php endif; ?>
                </p>
              </div>
            </div>

          </div>
        </div>

        <!-- CTA Buttons -->
        <div class="space-y-3">

          <!-- Register Now -->
          <a href="<?= $registerHref ?>"
             <?= $registerTarget !== '_self' ? "target=\"{$registerTarget}\"" : '' ?>
             <?= $registerRel ? "rel=\"{$registerRel}\"" : '' ?>
             class="flex items-center justify-center gap-2 w-full bg-primary hover:bg-primary/90
                    text-white text-sm font-semibold uppercase tracking-wide py-3.5 rounded-xl
                    transition-all hover:-translate-y-0.5 hover:shadow-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Register Now
            <?php if (!empty($training['registration_link'])): ?>
              <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
              </svg>
            <?php endif; ?>
          </a>

          <?php if ($training['brochure']): ?>
          <a href="<?= htmlspecialchars($training['brochure']) ?>" download
             class="flex items-center justify-center gap-2 w-full border border-gray-200 hover:border-primary
                    text-gray-600 hover:text-primary text-sm font-medium py-3.5 rounded-xl
                    transition-all hover:-translate-y-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>
            </svg>
            Download Brochure
          </a>
          <?php endif; ?>

          <!-- WhatsApp -->
          <a href="<?= $waLink ?>" target="_blank"
             class="flex items-center justify-center gap-2 w-full bg-[#25D366] hover:bg-[#1ebe5d]
                    text-white text-sm font-medium py-3 rounded-xl transition-all hover:-translate-y-0.5">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            WhatsApp Dr. Nadia
          </a>
        </div>

        <!-- Contact -->
        <p class="text-center text-xs text-gray-400">
          Dr. Nadia · +6012 783 6893 &nbsp;|&nbsp; Cik Syahirah · +6012 386 7967
        </p>

      </div>
    </div>
  </div>
</section>

<?php
include 'includes/footer.php';
include 'includes/scripts.php';
?>