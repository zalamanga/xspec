<?php
// admin/training-form.php  — Add & Edit Training
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/country.php';
$pdo = $pdo ?? $db ?? null;

$active_cc     = get_active_country($db);
$countries_all = get_countries($db);
$valid_cc      = array_column($countries_all, 'code');

$isEdit  = isset($_GET['id']) || isset($_POST['id']);
$id      = $isEdit ? (int)($_GET['id'] ?? $_POST['id']) : null;
$errors  = [];

$row = [
    'id'                => '',
    'category'          => '',
    'country_code'      => $active_cc,
    'title'             => '',
    'date_label'        => '',
    'date_start'        => '',
    'date_end'          => '',
    'time_label'        => '8AM – 5.30PM',
    'location'          => '',
    'price'             => '',
    'price_group'       => '',
    'price_group_min'   => 'min 4 pax',
    'registration_link' => '',
    'poster_img'        => '',
    'brochure'          => '',
    'is_featured'       => 0,
    'is_active'         => 1,
    'sort_order'        => 0,
];

if ($isEdit && $id) {
    $stmt = $pdo->prepare("SELECT * FROM trainings WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) $row = array_merge($row, $existing);
}

// ── Handle POST ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cc_post = $_POST['country_code'] ?? $active_cc;
    if (!in_array($cc_post, $valid_cc)) $cc_post = $active_cc;

    $data = [
        'category'          => trim($_POST['category']          ?? ''),
        'country_code'      => $cc_post,
        'title'             => trim($_POST['title']             ?? ''),
        'date_label'        => trim($_POST['date_label']        ?? ''),
        'date_start'        => trim($_POST['date_start']        ?? ''),
        'date_end'          => trim($_POST['date_end']          ?? ''),
        'time_label'        => trim($_POST['time_label']        ?? '8AM – 5.30PM'),
        'location'          => trim($_POST['location']          ?? ''),
        'price'             => floatval($_POST['price']         ?? 0),
        'price_group'       => floatval($_POST['price_group']   ?? 0),
        'price_group_min'   => trim($_POST['price_group_min']   ?? 'min 4 pax'),
        'registration_link' => trim($_POST['registration_link'] ?? ''),
        'is_featured'       => isset($_POST['is_featured']) ? 1 : 0,
        'is_active'         => isset($_POST['is_active'])   ? 1 : 0,
        'sort_order'        => (int)($_POST['sort_order']       ?? 0),
        'poster_img'        => $row['poster_img'],
        'brochure'          => $row['brochure'],
    ];

    // Validate
    if (!$data['category'])   $errors[] = 'Category is required.';
    if (!$data['title'])      $errors[] = 'Title is required.';
    if (!$data['date_start']) $errors[] = 'Start date is required.';
    if (!$data['date_end'])   $errors[] = 'End date is required.';

    // Validate registration link format (kalau diisi)
    if ($data['registration_link'] && !filter_var($data['registration_link'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Registration link must be a valid URL (include https://).';
    }

    // ── Upload poster ──────────────────────────────────────────────────────
    if (!empty($_FILES['poster_img']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['poster_img']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Poster must be JPG, PNG, WebP, or GIF.';
        } else {
            $uploadDir = '../img/training/posters/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = 'poster_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['poster_img']['tmp_name'], $uploadDir . $filename)) {
                if ($data['poster_img'] && file_exists('../' . ltrim($data['poster_img'], '/')))
                    @unlink('../' . ltrim($data['poster_img'], '/'));
                $data['poster_img'] = 'img/training/posters/' . $filename;
            } else {
                $errors[] = 'Failed to upload poster. Check folder permissions.';
            }
        }
    }

    // ── Upload brochure ────────────────────────────────────────────────────
    if (!empty($_FILES['brochure']['name'])) {
        $ext = strtolower(pathinfo($_FILES['brochure']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $errors[] = 'Brochure must be a PDF file.';
        } else {
            $uploadDir = '../img/training/brochures/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = 'brochure_' . time() . '_' . uniqid() . '.pdf';
            if (move_uploaded_file($_FILES['brochure']['tmp_name'], $uploadDir . $filename)) {
                if ($data['brochure'] && file_exists('../' . ltrim($data['brochure'], '/')))
                    @unlink('../' . ltrim($data['brochure'], '/'));
                $data['brochure'] = 'img/training/brochures/' . $filename;
            } else {
                $errors[] = 'Failed to upload brochure. Check folder permissions.';
            }
        }
    }

    // ── Save ───────────────────────────────────────────────────────────────
    if (empty($errors)) {
        if ($isEdit && $id) {
            $sql = "UPDATE trainings SET
                        category          = :category,
                        country_code      = :country_code,
                        title             = :title,
                        date_label        = :date_label,
                        date_start        = :date_start,
                        date_end          = :date_end,
                        time_label        = :time_label,
                        location          = :location,
                        price             = :price,
                        price_group       = :price_group,
                        price_group_min   = :price_group_min,
                        registration_link = :registration_link,
                        poster_img        = :poster_img,
                        brochure          = :brochure,
                        is_featured       = :is_featured,
                        is_active         = :is_active,
                        sort_order        = :sort_order
                    WHERE id = :id";
            $data['id'] = $id;
        } else {
            $sql = "INSERT INTO trainings
                        (category, country_code, title, date_label, date_start, date_end, time_label,
                         location, price, price_group, price_group_min, registration_link,
                         poster_img, brochure, is_featured, is_active, sort_order)
                    VALUES
                        (:category, :country_code, :title, :date_label, :date_start, :date_end, :time_label,
                         :location, :price, :price_group, :price_group_min, :registration_link,
                         :poster_img, :brochure, :is_featured, :is_active, :sort_order)";
        }
        $pdo->prepare($sql)->execute($data);
        $_SESSION['flash'] = $isEdit ? 'Training updated.' : 'Training added successfully.';
        header('Location: trainings.php');
        exit;
    }

    $row = array_merge($row, $data);
}

$pageTitle = $isEdit ? 'Edit Training' : 'Add New Training';
$title     = $pageTitle . ' – XSpec Admin';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="p-6 lg:p-10 max-w-4xl">

  <!-- Header -->
  <div class="flex items-center gap-4 mb-8">
    <a href="trainings.php" class="text-gray-400 hover:text-gray-600 transition-colors">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
      </svg>
    </a>
    <div>
      <h1 class="text-2xl font-semibold text-gray-900"><?= $pageTitle ?></h1>
      <p class="text-sm text-gray-500 mt-0.5">
        <?= $isEdit ? 'Update training details below.' : 'Fill in the details for the new training program.' ?>
      </p>
    </div>
  </div>

  <!-- Errors -->
  <?php if (!empty($errors)): ?>
    <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
      <p class="text-sm font-semibold text-red-700 mb-2">Please fix the following:</p>
      <ul class="list-disc list-inside space-y-1">
        <?php foreach ($errors as $e): ?>
          <li class="text-sm text-red-600"><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="space-y-8">
    <?php if ($isEdit): ?>
      <input type="hidden" name="id" value="<?= $id ?>">
    <?php endif; ?>

    <!-- ── BASIC INFO ──────────────────────────────────────────────────── -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
      <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-widest">Basic Information</h2>
      </div>
      <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">

        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-1.5">Country *</label>
          <select name="country_code" required
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm
                         focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
              <?php foreach ($countries_all as $c): ?>
                  <option value="<?= $c['code'] ?>" <?= $row['country_code'] === $c['code'] ? 'selected' : '' ?>>
                      <?= $c['flag_emoji'] . ' ' . htmlspecialchars($c['name']) ?>
                  </option>
              <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-1.5">Category *</label>
          <input type="text" name="category" required
                 value="<?= htmlspecialchars($row['category']) ?>"
                 placeholder="e.g. Body Language & Deception Detection"
                 class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm
                        focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-1.5">Sort Order</label>
          <input type="number" name="sort_order" min="0"
                 value="<?= (int)$row['sort_order'] ?>"
                 class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm
                        focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-1.5">Full Title *</label>
          <input type="text" name="title" required
                 value="<?= htmlspecialchars($row['title']) ?>"
                 placeholder="e.g. Body Language Skills & Deception Detection Methodology Course"
                 class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm
                        focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
        </div>

      </div>
    </div>

    <!-- ── SCHEDULE ────────────────────────────────────────────────────── -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
      <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-widest">Schedule</h2>
      </div>
      <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">

        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-1.5">Start Date *</label>
          <input type="date" name="date_start" required
                 value="<?= htmlspecialchars($row['date_start']) ?>"
                 class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm
                        focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-1.5">End Date *</label>
          <input type="date" name="date_end" required
                 value="<?= htmlspecialchars($row['date_end']) ?>"
                 class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm
                        focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-1.5">
            Date Label
            <span class="normal-case text-gray-400 ml-1">(tampil di website)</span>
          </label>
          <input type="text" name="date_label" id="date_label"
                 value="<?= htmlspecialchars($row['date_label']) ?>"
                 placeholder="Auto-generate dari tanggal di atas"
                 class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm
                        focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
          <p class="text-xs text-gray-400 mt-1">Kosongkan untuk auto-generate.</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-1.5">Time</label>
          <input type="text" name="time_label"
                 value="<?= htmlspecialchars($row['time_label']) ?>"
                 placeholder="8AM – 5.30PM"
                 class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm
                        focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-1.5">Venue *</label>
          <input type="text" name="location" required
                 value="<?= htmlspecialchars($row['location']) ?>"
                 placeholder="e.g. Hotel Wyndham Grand Bangsar, KL"
                 class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm
                        focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
        </div>

      </div>
    </div>

    <!-- ── PRICING ─────────────────────────────────────────────────────── -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
      <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-widest">Pricing</h2>
      </div>
      <div class="p-6 grid grid-cols-1 sm:grid-cols-3 gap-5">

        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-1.5">Price / Pax (RM)</label>
          <input type="number" name="price" min="0" step="0.01"
                 value="<?= htmlspecialchars($row['price']) ?>"
                 placeholder="5500"
                 class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm
                        focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-1.5">Group Price (RM)</label>
          <input type="number" name="price_group" min="0" step="0.01"
                 value="<?= htmlspecialchars($row['price_group']) ?>"
                 placeholder="4950"
                 class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm
                        focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-1.5">Group Min</label>
          <input type="text" name="price_group_min"
                 value="<?= htmlspecialchars($row['price_group_min']) ?>"
                 placeholder="min 4 pax"
                 class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm
                        focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
        </div>

      </div>
    </div>

    <!-- ── REGISTRATION LINK ────────────────────────────────────────────── -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
      <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-widest">Registration</h2>
      </div>
      <div class="p-6">

        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-1.5">
          Registration Form Link
          <span class="normal-case text-gray-400 ml-1">(Google Form / Typeform / dll)</span>
        </label>
        <div class="flex items-center gap-3">
          <input type="url" name="registration_link"
                 value="<?= htmlspecialchars($row['registration_link']) ?>"
                 placeholder="https://forms.gle/xxxxxxxxxxxxx"
                 class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm
                        focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
          <?php if (!empty($row['registration_link'])): ?>
            <a href="<?= htmlspecialchars($row['registration_link']) ?>" target="_blank"
               class="inline-flex items-center gap-1.5 text-xs text-primary border border-primary/30
                      hover:bg-primary/5 px-3 py-2.5 rounded-lg transition-colors whitespace-nowrap">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
              </svg>
              Test Link
            </a>
          <?php endif; ?>
        </div>
        <p class="text-xs text-gray-400 mt-2">
          Paste link Google Form di sini. Tombol "Register Now" di halaman publik akan langsung redirect ke link ini.
          Kalau kosong, tombol akan redirect ke halaman contact.
        </p>

      </div>
    </div>

    <!-- ── MEDIA ───────────────────────────────────────────────────────── -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
      <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-widest">Media</h2>
      </div>
      <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">

        <!-- Poster -->
        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-1.5">
            Training Poster
            <span class="normal-case text-gray-400 ml-1">(JPG/PNG/WebP)</span>
          </label>
          <?php if ($row['poster_img']): ?>
            <div class="mb-3 flex items-center gap-3">
              <img src="../<?= htmlspecialchars($row['poster_img']) ?>"
                   alt="Current poster"
                   class="w-16 h-20 object-cover rounded border border-gray-200"
                   onerror="this.style.display='none'">
              <div class="text-xs text-gray-500">
                <p class="font-medium">Poster sekarang</p>
                <p class="text-gray-400">Upload baru untuk replace</p>
              </div>
            </div>
          <?php endif; ?>
          <input type="file" name="poster_img" accept="image/*"
                 class="w-full text-sm text-gray-600
                        file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                        file:text-xs file:font-semibold file:bg-primary/10 file:text-primary
                        hover:file:bg-primary/20 transition cursor-pointer">
        </div>

        <!-- Brochure -->
        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-1.5">
            Brochure
            <span class="normal-case text-gray-400 ml-1">(PDF only)</span>
          </label>
          <?php if ($row['brochure']): ?>
            <div class="mb-3 flex items-center gap-2">
              <svg class="w-8 h-8 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <div class="text-xs text-gray-500">
                <p class="font-medium">Brochure sekarang</p>
                <a href="../<?= htmlspecialchars($row['brochure']) ?>" target="_blank"
                   class="text-primary hover:underline">Preview PDF ↗</a>
              </div>
            </div>
          <?php endif; ?>
          <input type="file" name="brochure" accept=".pdf"
                 class="w-full text-sm text-gray-600
                        file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                        file:text-xs file:font-semibold file:bg-primary/10 file:text-primary
                        hover:file:bg-primary/20 transition cursor-pointer">
        </div>

      </div>
    </div>

    <!-- ── SETTINGS ────────────────────────────────────────────────────── -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
      <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-widest">Settings</h2>
      </div>
      <div class="p-6 flex flex-wrap gap-8">

        <label class="flex items-center gap-3 cursor-pointer select-none">
          <input type="checkbox" name="is_active" value="1"
                 <?= $row['is_active'] ? 'checked' : '' ?>
                 class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
          <div>
            <p class="text-sm font-medium text-gray-800">Active</p>
            <p class="text-xs text-gray-400">Tampil di halaman publik</p>
          </div>
        </label>

        <label class="flex items-center gap-3 cursor-pointer select-none">
          <input type="checkbox" name="is_featured" value="1"
                 <?= $row['is_featured'] ? 'checked' : '' ?>
                 class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
          <div>
            <p class="text-sm font-medium text-gray-800">Featured</p>
            <p class="text-xs text-gray-400">Highlight dengan gold badge & border</p>
          </div>
        </label>

      </div>
    </div>

    <!-- ── SUBMIT ──────────────────────────────────────────────────────── -->
    <div class="flex items-center gap-4 pb-10">
      <button type="submit"
              class="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-white
                     font-semibold text-sm px-8 py-3 rounded-xl transition-all hover:shadow-lg">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <?= $isEdit ? 'Update Training' : 'Save Training' ?>
      </button>
      <a href="trainings.php" class="text-sm text-gray-500 hover:text-gray-700 hover:underline transition-colors">
        Cancel
      </a>
    </div>

  </form>
</div>

<script>
// Auto-generate date_label dari date_start + date_end
const start = document.querySelector('[name="date_start"]');
const end   = document.querySelector('[name="date_end"]');
const label = document.getElementById('date_label');
const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

function updateLabel() {
  if (label.value) return;
  if (!start.value || !end.value) return;
  const s = new Date(start.value), e = new Date(end.value);
  if (s.getMonth() === e.getMonth() && s.getFullYear() === e.getFullYear()) {
    label.placeholder = `${s.getDate()} – ${e.getDate()} ${months[s.getMonth()]} ${s.getFullYear()}`;
  } else {
    label.placeholder = `${s.getDate()} ${months[s.getMonth()]} – ${e.getDate()} ${months[e.getMonth()]} ${e.getFullYear()}`;
  }
}
start.addEventListener('change', updateLabel);
end.addEventListener('change',   updateLabel);
</script>
