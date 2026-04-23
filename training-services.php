<?php
// training.php  — Public Training & Services page
require_once 'config/database.php';
require_once 'includes/country.php';

$database = new Database();
$pdo = $database->getConnection();
$db  = $pdo;  // biar header.php bisa pakai $db

$active_cc = active_country();

$title            = "Training & Services - XSpec Malaysia";
$meta_description = "Professional training courses and technical services by XSpec Malaysia — NDT, inspection, and instrument calibration training delivered by certified experts across Southeast Asia.";
$breadcrumbs      = [
    ['name' => 'Home',                'url' => '/'],
    ['name' => 'Training & Services', 'url' => '/training'],
];
$currentPage = 'training';

$stmt = $pdo->prepare(
    "SELECT * FROM trainings WHERE is_active = 1 AND country_code = ? ORDER BY date_start ASC, sort_order ASC"
);
$stmt->execute([$active_cc]);
$trainings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by bulan
$byMonth = [];
foreach ($trainings as $t) {
    $key = date('F Y', strtotime($t['date_start']));
    $byMonth[$key][] = $t;
}

$totalPrograms = count($trainings);

include 'includes/head.php';
include 'includes/header.php';
?>

<style>
  .hero-pattern {
    background-image: repeating-linear-gradient(
      45deg,
      rgba(255,255,255,0.03) 0px,
      rgba(255,255,255,0.03) 1px,
      transparent 1px,
      transparent 40px
    );
  }
</style>

<!-- ===== HERO ===== -->
<section class="relative h-[420px] lg:h-[500px] overflow-hidden bg-gray-900">
  <img src="img/training/hero-training.jpg" alt="Training Hero"
       class="absolute inset-0 w-full h-full object-cover opacity-30">
  <div class="absolute inset-0 hero-pattern opacity-20"></div>
  <div class="absolute inset-0 bg-gradient-to-br from-gray-900/90 to-gray-800/80"></div>
  <div class="relative h-full flex items-center justify-center text-center px-4">
    <div>
      <p class="text-xs uppercase tracking-[0.4em] text-primary font-semibold mb-4">XSpec AI Certs · 2026</p>
      <h1 class="text-4xl sm:text-5xl lg:text-6xl font-display font-normal text-white mb-6 leading-tight">
        Training & <span class="text-primary font-semibold">Services</span>
      </h1>
      <div class="w-20 h-1 bg-primary mx-auto mb-6"></div>
      <p class="text-white/75 max-w-2xl mx-auto font-light leading-relaxed text-base sm:text-lg">
        Authorized Training Partner — Advancing the Standard of Integrity through live masterclasses,
        tactical programs, and self-paced digital certifications.
      </p>
      <div class="mt-10 flex flex-wrap justify-center gap-8">
        <div class="text-center">
          <div class="text-3xl font-display font-semibold text-primary"><?= $totalPrograms ?></div>
          <div class="text-xs uppercase tracking-widest text-white/50 mt-1">Live Masterclasses</div>
        </div>
        <div class="w-px bg-white/20 hidden sm:block"></div>
        <div class="text-center">
          <div class="text-3xl font-display font-semibold text-primary">2026</div>
          <div class="text-xs uppercase tracking-widest text-white/50 mt-1">Klang Valley, KL</div>
        </div>
        <div class="w-px bg-white/20 hidden sm:block"></div>
        <div class="text-center">
          <div class="text-3xl font-display font-semibold text-primary">5+</div>
          <div class="text-xs uppercase tracking-widest text-white/50 mt-1">Cert Tracks</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== TRAINING CALENDAR ===== -->
<section class="py-16 lg:py-20 bg-white">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center mb-10">
      <p class="text-xs uppercase tracking-[0.4em] text-primary font-semibold mb-3">2026 Schedule</p>
      <h2 class="text-3xl sm:text-4xl font-display font-normal text-gray-900 mb-4">
        Training <span class="text-primary font-semibold">Calendar</span>
      </h2>
      <div class="w-20 h-1 bg-primary mx-auto mb-4"></div>
      <p class="text-gray-500 text-sm">Tap on any program to view full details &amp; register.</p>
    </div>

    <div class="text-center mb-10">
      <a href="img/training/poster-2026.pdf" download
         class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white
                text-xs font-semibold uppercase tracking-widest px-6 py-3 rounded-lg
                transition-all hover:-translate-y-0.5 hover:shadow-lg">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>
        </svg>
        Download Public Program 2026
      </a>
    </div>

    <?php if (empty($byMonth)): ?>
      <p class="text-center text-gray-400 py-16">No upcoming trainings. Check back soon!</p>
    <?php else: ?>

      <?php foreach ($byMonth as $monthLabel => $items): ?>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 pb-2 mb-3
                  border-b border-gray-100 mt-8 first:mt-0">
          <?= htmlspecialchars($monthLabel) ?>
        </p>

        <?php foreach ($items as $t):
          $sDay   = date('j', strtotime($t['date_start']));
          $eDay   = date('j', strtotime($t['date_end']));
          $sMonth = date('M', strtotime($t['date_start']));
          $sYear  = date('Y', strtotime($t['date_start']));
          $dayRange = ($sDay === $eDay) ? $sDay : $sDay . '–' . $eDay;
          $diff = (new DateTime($t['date_start']))->diff(new DateTime($t['date_end']))->days + 1;
        ?>

        <a href="/training/<?= (int)$t['id'] ?>"
           class="group flex items-stretch rounded-xl
                  <?= $t['is_featured'] ? 'border-2 border-primary' : 'border border-gray-200' ?>
                  bg-white overflow-hidden mb-3 relative
                  hover:<?= $t['is_featured'] ? 'shadow-xl' : 'border-primary hover:shadow-lg' ?>
                  transition-all duration-300">

          <?php if ($t['is_featured']): ?>
            <div class="absolute top-3 right-16 sm:right-24 z-10">
              <span class="text-[10px] font-semibold bg-primary/10 text-primary px-2.5 py-1 rounded-full">
                ★ Featured
              </span>
            </div>
          <?php endif; ?>

          <!-- Date Tab -->
          <div class="bg-primary text-white flex flex-col items-center justify-center px-5 py-4
                      min-w-[80px] sm:min-w-[90px] flex-shrink-0">
            <span class="text-[10px] uppercase tracking-[0.18em] opacity-80 font-medium"><?= $sMonth ?></span>
            <span class="text-xl font-semibold leading-tight my-0.5"><?= $dayRange ?></span>
            <span class="text-[11px] opacity-70"><?= $sYear ?></span>
          </div>

          <!-- Info -->
          <div class="flex-1 px-5 py-4 min-w-0">
            <p class="text-gray-900 font-semibold text-sm sm:text-base leading-snug mb-2">
              <?= htmlspecialchars($t['category']) ?>
            </p>
            <div class="flex flex-wrap gap-x-4 gap-y-1">
              <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <rect x="3" y="4" width="18" height="18" rx="2" stroke-width="2"/>
                  <line x1="16" y1="2" x2="16" y2="6" stroke-width="2"/>
                  <line x1="8" y1="2" x2="8" y2="6" stroke-width="2"/>
                  <line x1="3" y1="10" x2="21" y2="10" stroke-width="2"/>
                </svg>
                <?= $diff ?> Day<?= $diff > 1 ? 's' : '' ?>
              </span>
              <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <?= htmlspecialchars($t['location']) ?>
              </span>
              <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <line x1="12" y1="1" x2="12" y2="23" stroke-width="2"/>
                  <path stroke-linecap="round" stroke-width="2" d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
                RM <?= number_format($t['price'], 0) ?> / pax
              </span>
            </div>
          </div>

          <!-- CTA -->
          <div class="flex items-center px-4 sm:px-5 flex-shrink-0">
            <span class="bg-primary group-hover:bg-primary-dark text-white
                         text-[10px] sm:text-xs font-semibold uppercase tracking-wider
                         px-3 sm:px-4 py-2 rounded-lg transition-colors whitespace-nowrap">
              View Details
            </span>
          </div>
        </a>

        <?php endforeach; ?>
      <?php endforeach; ?>

    <?php endif; ?>
  </div>
</section>

<!-- ===== CONTACT SECTION ===== -->
<section class="py-16 lg:py-24 bg-gradient-to-br from-gray-700 to-gray-600 text-white" id="contact">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center mb-14">
      <p class="text-xs uppercase tracking-[0.4em] text-primary font-semibold mb-3">Enquiries</p>
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-display font-normal text-white mb-4">
        Get in <span class="text-primary font-semibold">Touch</span>
      </h2>
      <div class="w-20 h-1 bg-primary mx-auto mb-4"></div>
      <p class="text-white/60 text-sm">Questions? Contact us to advance the standard of integrity together.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20">

      <div class="space-y-6">
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0 text-lg">📍</div>
          <div>
            <h4 class="font-semibold text-lg mb-1">Address</h4>
            <p class="text-white/75 font-light text-sm leading-relaxed">No. 8, Jalan Industry USJ 1/8, Taman Perindustrian USJ 1, 47600 Subang Jaya, Selangor, Malaysia.</p>
          </div>
        </div>
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0 text-lg">📞</div>
          <div>
            <h4 class="font-semibold text-lg mb-1">Dr. Nadia</h4>
            <p class="text-white/75 font-light">+6012 783 6893</p>
          </div>
        </div>
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0 text-lg">📧</div>
          <div>
            <h4 class="font-semibold text-lg mb-1">Email</h4>
            <p class="text-white/75 font-light">nadia@xspec.com.my</p>
          </div>
        </div>
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0 text-lg">🕐</div>
          <div>
            <h4 class="font-semibold text-lg mb-1">Operating Hours</h4>
            <p class="text-white/75 font-light">Mon – Fri: 9:00 AM – 6:00 PM</p>
          </div>
        </div>
        <div class="mt-6 rounded-xl overflow-hidden h-48 border border-white/10">
          <img src="img/training/office-map.jpg" alt="XSpec Office" loading="lazy"
               class="w-full h-full object-cover opacity-70"
               onerror="this.parentElement.style.display='none'">
        </div>
      </div>

      <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-6 lg:p-10">
        <form action="submit-training-inquiry.php" method="POST">
          <div class="mb-5">
            <input type="text" name="name" placeholder="Name" required
              class="w-full px-4 py-3 bg-white border border-white/20 rounded-lg text-gray-700
                     placeholder-gray-400 focus:outline-none focus:border-primary transition-colors">
          </div>
          <div class="mb-5">
            <input type="tel" name="phone" placeholder="Mobile / WhatsApp" required
              class="w-full px-4 py-3 bg-white border border-white/20 rounded-lg text-gray-700
                     placeholder-gray-400 focus:outline-none focus:border-primary transition-colors">
          </div>
          <div class="mb-5">
            <input type="email" name="email" placeholder="Email" required
              class="w-full px-4 py-3 bg-white border border-white/20 rounded-lg text-gray-700
                     placeholder-gray-400 focus:outline-none focus:border-primary transition-colors">
          </div>
          <div class="mb-5">
            <select name="training_interest"
              class="w-full px-4 py-3 bg-white border border-white/20 rounded-lg text-gray-500
                     focus:outline-none focus:border-primary transition-colors">
              <option value="">Select Training of Interest</option>
              <?php foreach ($trainings as $t): ?>
                <option value="<?= htmlspecialchars($t['title']) ?>">
                  <?= htmlspecialchars($t['category']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-5">
            <input type="text" name="company" placeholder="Company Name"
              class="w-full px-4 py-3 bg-white border border-white/20 rounded-lg text-gray-700
                     placeholder-gray-400 focus:outline-none focus:border-primary transition-colors">
          </div>
          <div class="mb-5">
            <textarea name="message" placeholder="Message" rows="4" required
              class="w-full px-4 py-3 bg-white border border-white/20 rounded-lg text-gray-700
                     placeholder-gray-400 focus:outline-none focus:border-primary transition-colors resize-y"></textarea>
          </div>
          <button type="submit"
            class="w-full bg-primary hover:bg-primary-dark text-white py-3 px-8 rounded-lg
                   font-display font-semibold uppercase tracking-wide
                   transition-all hover:-translate-y-0.5 hover:shadow-lg">
            Send Enquiry
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