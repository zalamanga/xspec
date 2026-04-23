<?php
// admin/trainings.php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/country.php';
$pdo = $pdo ?? $db ?? null;
$title       = 'Manage Trainings – XSpec Admin';
$currentPage = 'trainings';
$active_cc   = get_active_country($db);

// ── Flash message ──────────────────────────────────────────────────────────
$flash = '';
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// ── Toggle active / featured ───────────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $toggleId    = (int)$_GET['id'];
    $toggleField = $_GET['toggle'] === 'featured' ? 'is_featured' : 'is_active';
    $pdo->prepare("UPDATE trainings SET {$toggleField} = 1 - {$toggleField} WHERE id = ?")
        ->execute([$toggleId]);
    header('Location: trainings.php');
    exit;
}

// ── Delete ─────────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $row   = $pdo->prepare("SELECT poster_img, brochure FROM trainings WHERE id = ?");
    $row->execute([$delId]);
    $files = $row->fetch(PDO::FETCH_ASSOC);
    if ($files) {
        foreach (['poster_img', 'brochure'] as $f) {
            $path = '../' . ltrim($files[$f], '/');
            if ($files[$f] && file_exists($path)) @unlink($path);
        }
    }
    $pdo->prepare("DELETE FROM trainings WHERE id = ?")->execute([$delId]);
    $_SESSION['flash'] = 'Training deleted successfully.';
    header('Location: trainings.php');
    exit;
}

// ── Fetch trainings filtered by active country ─────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM trainings WHERE country_code = ? ORDER BY date_start ASC, sort_order ASC");
$stmt->execute([$active_cc]);
$trainings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="p-6 lg:p-10 max-w-7xl">

  <!-- Header -->
  <div class="flex items-center justify-between mb-8">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">Trainings</h1>
      <p class="text-sm text-gray-500 mt-1">Manage the 2026 training calendar</p>
    </div>
    <a href="training-form.php"
       class="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-white
              text-sm font-semibold px-5 py-2.5 rounded-lg transition-all hover:shadow-lg">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Add Training
    </a>
  </div>

  <!-- Flash -->
  <?php if ($flash): ?>
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3 flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
      </svg>
      <?= htmlspecialchars($flash) ?>
    </div>
  <?php endif; ?>

  <!-- Stats -->
  <?php
    $total    = count($trainings);
    $active   = count(array_filter($trainings, fn($r) => $r['is_active']));
    $featured = count(array_filter($trainings, fn($r) => $r['is_featured']));
    $hasForm  = count(array_filter($trainings, fn($r) => !empty($r['registration_link'])));
  ?>
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
    <?php foreach ([
      ['Total Programs', $total,    'bg-blue-50',   'text-blue-600'],
      ['Active',         $active,   'bg-green-50',  'text-green-600'],
      ['Featured',       $featured, 'bg-yellow-50', 'text-yellow-600'],
      ['Has Reg. Form',  $hasForm,  'bg-purple-50', 'text-purple-600'],
    ] as [$label, $val, $bg, $fg]): ?>
    <div class="<?= $bg ?> rounded-xl px-4 py-4">
      <p class="text-xs text-gray-500 uppercase tracking-widest mb-1"><?= $label ?></p>
      <p class="text-2xl font-semibold <?= $fg ?>"><?= $val ?></p>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Table -->
  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-200">
        <tr>
          <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-widest text-gray-500">Training</th>
          <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-widest text-gray-500 hidden sm:table-cell">Date</th>
          <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-widest text-gray-500 hidden md:table-cell">Price</th>
          <th class="text-center px-5 py-3 text-xs font-semibold uppercase tracking-widest text-gray-500">Form</th>
          <th class="text-center px-5 py-3 text-xs font-semibold uppercase tracking-widest text-gray-500">Active</th>
          <th class="text-right px-5 py-3 text-xs font-semibold uppercase tracking-widest text-gray-500">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <?php if (empty($trainings)): ?>
          <tr>
            <td colspan="6" class="text-center py-12 text-gray-400">
              No trainings yet. <a href="training-form.php" class="text-primary hover:underline">Add one</a>.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($trainings as $t): ?>
          <tr class="hover:bg-gray-50 transition-colors">

            <!-- Title + Category -->
            <td class="px-5 py-4">
              <div class="flex items-center gap-3">
                <?php if ($t['poster_img']): ?>
                  <img src="../<?= htmlspecialchars($t['poster_img']) ?>"
                       alt="" class="w-10 h-12 object-cover rounded border border-gray-200 flex-shrink-0"
                       onerror="this.style.display='none'">
                <?php else: ?>
                  <div class="w-10 h-12 bg-gray-100 rounded border border-gray-200 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                  </div>
                <?php endif; ?>
                <div class="min-w-0">
                  <p class="font-medium text-gray-900 leading-tight truncate max-w-[200px]">
                    <?= htmlspecialchars($t['title']) ?>
                  </p>
                  <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($t['category']) ?></p>
                  <?php if ($t['is_featured']): ?>
                    <span class="inline-block text-[10px] bg-yellow-100 text-yellow-700 rounded-full px-2 py-0.5 mt-1">★ Featured</span>
                  <?php endif; ?>
                </div>
              </div>
            </td>

            <!-- Date -->
            <td class="px-5 py-4 text-gray-600 hidden sm:table-cell whitespace-nowrap">
              <?= htmlspecialchars($t['date_label']) ?>
            </td>

            <!-- Price -->
            <td class="px-5 py-4 text-gray-600 hidden md:table-cell whitespace-nowrap">
              RM <?= number_format($t['price'], 0) ?>
            </td>

            <!-- Registration Form status -->
            <td class="px-5 py-4 text-center">
              <?php if (!empty($t['registration_link'])): ?>
                <a href="<?= htmlspecialchars($t['registration_link']) ?>" target="_blank"
                   title="Open registration form"
                   class="inline-flex items-center gap-1 text-xs text-green-600 hover:underline">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5 13l4 4L19 7"/>
                  </svg>
                  Set
                </a>
              <?php else: ?>
                <span class="text-xs text-gray-300">—</span>
              <?php endif; ?>
            </td>

            <!-- Active toggle -->
            <td class="px-5 py-4 text-center">
              <a href="?toggle=active&id=<?= $t['id'] ?>" title="Toggle active"
                 class="inline-block w-10 h-5 rounded-full transition-colors relative
                        <?= $t['is_active'] ? 'bg-primary' : 'bg-gray-300' ?>">
                <span class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-all
                             <?= $t['is_active'] ? 'right-0.5' : 'left-0.5' ?>"></span>
              </a>
            </td>

            <!-- Actions -->
            <td class="px-5 py-4 text-right">
              <div class="flex items-center justify-end gap-2">
                <a href="training-form.php?id=<?= $t['id'] ?>"
                   class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-primary
                          border border-gray-200 hover:border-primary px-3 py-1.5 rounded-lg transition-colors">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                  Edit
                </a>
                <a href="../training-detail.php?id=<?= $t['id'] ?>" target="_blank"
                   class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-blue-600
                          border border-gray-200 hover:border-blue-300 px-3 py-1.5 rounded-lg transition-colors">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                  View
                </a>
                <a href="?delete=<?= $t['id'] ?>"
                   data-training-title="<?= htmlspecialchars($t['title'], ENT_QUOTES) ?>"
                   onclick="return xDeleteTraining(this);"
                   class="training-delete inline-flex items-center gap-1 text-xs text-gray-500 hover:text-red-600
                          border border-gray-200 hover:border-red-300 px-3 py-1.5 rounded-lg transition-colors">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                  Delete
                </a>
              </div>
            </td>

          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<script>
function xDeleteTraining(el) {
    const title = el.getAttribute('data-training-title');
    const href  = el.getAttribute('href');
    xModal.danger({
        title: 'Delete Training?',
        message: `Hapus "${title}"? Poster & brochure juga akan dihapus.`,
        okText: 'Ya, Hapus'
    }).then(ok => {
        if (ok) window.location.href = href;
    });
    return false;
}
</script>

