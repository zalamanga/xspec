<?php
// admin/dashboard.php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/country.php';

$page_title = 'Dashboard';
$active_cc  = get_active_country($db);

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'highest';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build date condition for query
$date_condition = "";
if ($date_filter === 'custom' && $start_date && $end_date) {
    $date_condition = "AND dl.downloaded_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
} elseif ($date_filter === 'today') {
    $date_condition = "AND DATE(dl.downloaded_at) = CURDATE()";
} elseif ($date_filter === 'yesterday') {
    $date_condition = "AND DATE(dl.downloaded_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} elseif ($date_filter === 'last7days') {
    $date_condition = "AND dl.downloaded_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($date_filter === 'last30days') {
    $date_condition = "AND dl.downloaded_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
} elseif ($date_filter === 'thismonth') {
    $date_condition = "AND MONTH(dl.downloaded_at) = MONTH(CURDATE()) AND YEAR(dl.downloaded_at) = YEAR(CURDATE())";
} elseif ($date_filter === 'lastmonth') {
    $date_condition = "AND MONTH(dl.downloaded_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(dl.downloaded_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
}

// Get product downloads statistics — filtered by active country
$order = $filter === 'highest' ? 'DESC' : 'ASC';
$query = "SELECT
            p.id,
            p.name as product_name,
            b.name as brand_name,
            c.name as category_name,
            COUNT(dl.id) as download_count,
            MAX(dl.downloaded_at) as last_download
          FROM products p
          JOIN product_countries pc ON pc.product_id = p.id AND pc.country_code = :cc1
          LEFT JOIN brands b ON p.brand_id = b.id
          LEFT JOIN categories c ON b.category_id = c.id
          LEFT JOIN download_logs dl ON p.id = dl.product_id
          WHERE p.is_active = 1 $date_condition
          GROUP BY p.id, p.name, b.name, c.name
          ORDER BY download_count $order
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([':cc1' => $active_cc]);
$download_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent products — filtered by active country
$query = "SELECT p.*, b.name as brand_name, c.name as category_name
          FROM products p
          JOIN product_countries pc ON pc.product_id = p.id AND pc.country_code = :cc
          JOIN brands b ON p.brand_id = b.id
          JOIN categories c ON b.category_id = c.id
          ORDER BY p.created_at DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute([':cc' => $active_cc]);
$recent_products = $stmt->fetchAll(PDO::FETCH_ASSOC);



include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Dashboard Content -->

<!-- Filter Section -->
<div class="bg-white rounded-xl shadow-md p-6 mb-6">
    <form method="GET" action="dashboard.php" class="flex flex-wrap gap-4 items-end">
        <!-- Sort Filter -->
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-sort mr-1"></i> Sort By
            </label>
            <select name="filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                <option value="highest" <?php echo $filter === 'highest' ? 'selected' : ''; ?>>Highest Downloads</option>
                <option value="lowest" <?php echo $filter === 'lowest' ? 'selected' : ''; ?>>Lowest Downloads</option>
            </select>
        </div>

        <!-- Date Filter -->
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-calendar mr-1"></i> Time Period
            </label>
            <select name="date_filter" id="dateFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                <option value="yesterday" <?php echo $date_filter === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                <option value="last7days" <?php echo $date_filter === 'last7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                <option value="last30days" <?php echo $date_filter === 'last30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                <option value="thismonth" <?php echo $date_filter === 'thismonth' ? 'selected' : ''; ?>>This Month</option>
                <option value="lastmonth" <?php echo $date_filter === 'lastmonth' ? 'selected' : ''; ?>>Last Month</option>
                <option value="custom" <?php echo $date_filter === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
            </select>
        </div>

        <!-- Custom Date Range (Hidden by default) -->
        <div id="customDateRange" class="flex-1 min-w-[200px] <?php echo $date_filter === 'custom' ? '' : 'hidden'; ?>">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-calendar-day mr-1"></i> Date Range
            </label>
            <div class="flex gap-2">
                <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                       placeholder="Start Date">
                <span class="flex items-center text-gray-500">to</span>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                       placeholder="End Date">
            </div>
        </div>

        <!-- Apply Button -->
        <div>
            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-semibold">
                <i class="fas fa-filter mr-2"></i> Apply Filter
            </button>
        </div>

        <!-- Reset Button -->
        <div>
            <a href="dashboard.php" class="inline-block px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-semibold">
                <i class="fas fa-redo mr-2"></i> Reset
            </a>
        </div>
    </form>
</div>

<!-- Active Filter Info -->
<?php if ($date_filter !== 'all' || $filter !== 'highest'): ?>
<div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-lg">
    <div class="flex items-center">
        <i class="fas fa-info-circle text-blue-500 mr-3"></i>
        <div>
            <p class="text-sm font-semibold text-blue-800">Active Filters:</p>
            <p class="text-sm text-blue-700">
                <?php 
                $filter_text = $filter === 'highest' ? 'Highest Downloads' : 'Lowest Downloads';
                $date_text = 'All Time';
                if ($date_filter === 'today') $date_text = 'Today';
                elseif ($date_filter === 'yesterday') $date_text = 'Yesterday';
                elseif ($date_filter === 'last7days') $date_text = 'Last 7 Days';
                elseif ($date_filter === 'last30days') $date_text = 'Last 30 Days';
                elseif ($date_filter === 'thismonth') $date_text = 'This Month';
                elseif ($date_filter === 'lastmonth') $date_text = 'Last Month';
                elseif ($date_filter === 'custom' && $start_date && $end_date) {
                    $date_text = date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date));
                }
                echo "$filter_text • $date_text";
                ?>
            </p>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Show/hide custom date range inputs
document.getElementById('dateFilter').addEventListener('change', function() {
    const customDateRange = document.getElementById('customDateRange');
    if (this.value === 'custom') {
        customDateRange.classList.remove('hidden');
    } else {
        customDateRange.classList.add('hidden');
    }
});
</script>

<!-- Most/Least Downloaded Products -->
<div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-chart-bar text-primary mr-2"></i>
                Product Download Statistics
            </h2>
            <?php if ($date_filter !== 'all'): ?>
            <p class="text-sm text-gray-500 mt-1">
                Showing results for: <span class="font-semibold text-primary">
                <?php 
                if ($date_filter === 'today') echo 'Today';
                elseif ($date_filter === 'yesterday') echo 'Yesterday';
                elseif ($date_filter === 'last7days') echo 'Last 7 Days';
                elseif ($date_filter === 'last30days') echo 'Last 30 Days';
                elseif ($date_filter === 'thismonth') echo 'This Month';
                elseif ($date_filter === 'lastmonth') echo 'Last Month';
                elseif ($date_filter === 'custom' && $start_date && $end_date) {
                    echo date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date));
                }
                ?>
                </span>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Rank</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Product Name</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Brand</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Category</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Downloads</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Last Download</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rank = 1;
                foreach ($download_stats as $stat): 
                ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 px-4">
                        <span class="w-8 h-8 rounded-full <?php echo $rank <= 3 ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700'; ?> inline-flex items-center justify-center font-bold text-sm">
                            <?php echo $rank++; ?>
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($stat['product_name']); ?></p>
                    </td>
                    <td class="py-3 px-4 text-gray-600"><?php echo htmlspecialchars($stat['brand_name']); ?></td>
                    <td class="py-3 px-4 text-gray-600"><?php echo htmlspecialchars($stat['category_name']); ?></td>
                    <td class="py-3 px-4 text-center">
                        <span class="px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-700">
                            <?php echo number_format($stat['download_count']); ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 text-gray-600">
                        <?php 
                        if ($stat['last_download']) {
                            echo date('M d, Y H:i', strtotime($stat['last_download']));
                        } else {
                            echo '<span class="text-gray-400 italic">Never</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($download_stats)): ?>
                <tr>
                    <td colspan="6" class="py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>No download data available yet</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Products -->
<div class="bg-white rounded-xl shadow-md p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-800">
            <i class="fas fa-clock text-primary mr-2"></i>
            Recent Products
        </h2>
        <a href="products.php" class="text-primary hover:text-primary-dark font-semibold text-sm">View All →</a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Product Name</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Brand</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Category</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Created</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_products as $product): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 px-4">
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($product['name']); ?></p>
                    </td>
                    <td class="py-3 px-4 text-gray-600"><?php echo htmlspecialchars($product['brand_name']); ?></td>
                    <td class="py-3 px-4 text-gray-600"><?php echo htmlspecialchars($product['category_name']); ?></td>
                    <td class="py-3 px-4 text-gray-600"><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                    <td class="py-3 px-4 text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $product['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                            <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>