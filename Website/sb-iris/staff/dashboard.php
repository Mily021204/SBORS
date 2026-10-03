<?php
// /sb-iris/staff/dashboard.php
require_once '../config/db_connect.php';
require_once '../config/auth.php';

// Enforce role-based access control
requireRole('SB Staff');

// Fetch categories for search filter
$categories = [];
try {
    $stmtCat = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Categories table fallback if unavailable
}

// Fetch summary statistics
$ordCount = $pdo->query("SELECT COUNT(*) FROM ordinances")->fetchColumn();
$resCount = $pdo->query("SELECT COUNT(*) FROM resolutions")->fetchColumn();

// Fetch recent ordinances (Limit 5)
$stmtRecentOrd = $pdo->query("SELECT ordinance_id, ordinance_number, title, date_enacted, status FROM ordinances ORDER BY created_at DESC LIMIT 5");
$recentOrdinances = $stmtRecentOrd->fetchAll();

// Fetch recent resolutions (Limit 5)
$stmtRecentRes = $pdo->query("SELECT resolution_id, resolution_number, title, date_approved FROM resolutions ORDER BY created_at DESC LIMIT 5");
$recentResolutions = $stmtRecentRes->fetchAll();

// Handle Search Query logic
$searchKeyword = trim($_GET['q'] ?? '');
$searchType = $_GET['type'] ?? 'all';
$searchCategory = $_GET['category'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$searchResults = [];
$isSearching = isset($_GET['q']) || isset($_GET['type']) || isset($_GET['category']) || !empty($dateFrom) || !empty($dateTo);

if ($isSearching) {
    $ordConditions = [];
    $resConditions = [];
    $ordParams = [];
    $resParams = [];

    if (!empty($searchKeyword)) {
        $ordConditions[] = "(o.ordinance_number LIKE :q1 OR o.title LIKE :q2)";
        $ordParams['q1'] = "%$searchKeyword%";
        $ordParams['q2'] = "%$searchKeyword%";

        $resConditions[] = "(r.resolution_number LIKE :q1 OR r.title LIKE :q2)";
        $resParams['q1'] = "%$searchKeyword%";
        $resParams['q2'] = "%$searchKeyword%";
    }

    if ($searchCategory !== 'all') {
        $ordConditions[] = "o.category_id = :cat";
        $ordParams['cat'] = $searchCategory;
        $resConditions[] = "r.category_id = :cat";
        $resParams['cat'] = $searchCategory;
    }

    if (!empty($dateFrom)) {
        $ordConditions[] = "o.date_enacted >= :date_from";
        $ordParams['date_from'] = $dateFrom;

        $resConditions[] = "r.date_approved >= :date_from";
        $resParams['date_from'] = $dateFrom;
    }

    if (!empty($dateTo)) {
        $ordConditions[] = "o.date_enacted <= :date_to";
        $ordParams['date_to'] = $dateTo;

        $resConditions[] = "r.date_approved <= :date_to";
        $resParams['date_to'] = $dateTo;
    }

    $whereOrd = !empty($ordConditions) ? " WHERE " . implode(" AND ", $ordConditions) : "";
    $whereRes = !empty($resConditions) ? " WHERE " . implode(" AND ", $resConditions) : "";

    if ($searchType === 'all' || $searchType === 'ordinance') {
        $sqlOrd = "SELECT 'Ordinance' AS doc_type, o.ordinance_id AS doc_id, o.ordinance_number AS doc_no, o.title, o.date_enacted AS doc_date, o.status, c.category_name 
                   FROM ordinances o LEFT JOIN categories c ON o.category_id = c.category_id " . $whereOrd;
        $stmt = $pdo->prepare($sqlOrd);
        $stmt->execute($ordParams);
        $searchResults = array_merge($searchResults, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    if ($searchType === 'all' || $searchType === 'resolution') {
        $sqlRes = "SELECT 'Resolution' AS doc_type, r.resolution_id AS doc_id, r.resolution_number AS doc_no, r.title, r.date_approved AS doc_date, 'Approved' AS status, c.category_name 
                   FROM resolutions r LEFT JOIN categories c ON r.category_id = c.category_id " . $whereRes;
        $stmt = $pdo->prepare($sqlRes);
        $stmt->execute($resParams);
        $searchResults = array_merge($searchResults, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Sort search results chronologically descending
    usort($searchResults, function($a, $b) {
        return strtotime($b['doc_date']) - strtotime($a['doc_date']);
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SB Staff Dashboard - SB-IRIS</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 font-sans min-h-screen">

    <!-- Top Navigation -->
    <nav class="bg-blue-800 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="text-xl font-bold tracking-wider">SB-IRIS Staff Portal</div>
            <div class="flex items-center space-x-4">
                <span class="text-sm border-l border-blue-700 pl-4">Welcome, <?= htmlspecialchars($_SESSION['name']) ?></span>
                <a href="edit_profile.php" class="text-sm text-blue-200 hover:text-white underline decoration-blue-400 transition">Edit Profile</a>
                <a href="../config/logout.php" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded text-sm font-semibold transition ml-4">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        <!-- Action Dashboard (Expanded to 3 columns) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-blue-600 flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800"><?= number_format($ordCount) ?></h2>
                    <p class="text-gray-500 uppercase tracking-wide text-sm font-semibold mt-1">Total Ordinances</p>
                </div>
                <a href="manage_ordinances.php" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded shadow transition">
                    + Add Ordinance
                </a>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-green-600 flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800"><?= number_format($resCount) ?></h2>
                    <p class="text-gray-500 uppercase tracking-wide text-sm font-semibold mt-1">Total Resolutions</p>
                </div>
                <a href="manage_resolutions.php" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded shadow transition">
                    + Add Resolution
                </a>
            </div>

            <!-- Added New Reports Card -->
            <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-purple-600 flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">🖨️</h2>
                    <p class="text-gray-500 uppercase tracking-wide text-sm font-semibold mt-1">Analytics</p>
                </div>
                <a href="reports.php" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded shadow transition">
                    Generate Reports
                </a>
            </div>
        </div>

        <!-- Search Ordinances & Resolutions Section -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Search Ordinances & Resolutions</h2>
            <form action="dashboard.php" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    <div class="md:col-span-2 lg:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Keyword / Number / Title</label>
                        <input type="text" name="q" value="<?= htmlspecialchars($searchKeyword) ?>" placeholder="Enter search keyword or document number..." class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Document Type</label>
                        <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all" <?= $searchType === 'all' ? 'selected' : '' ?>>All Documents</option>
                            <option value="ordinance" <?= $searchType === 'ordinance' ? 'selected' : '' ?>>Ordinances</option>
                            <option value="resolution" <?= $searchType === 'resolution' ? 'selected' : '' ?>>Resolutions</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Category</label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= $searchCategory == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Date To</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="flex justify-end space-x-2 pt-2 border-t mt-4">
                    <?php if ($isSearching): ?>
                        <a href="dashboard.php" class="px-4 py-2 text-gray-600 hover:text-gray-800 font-semibold transition">Reset</a>
                    <?php endif; ?>
                    <button type="submit" class="px-6 py-2 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded shadow transition">
                        🔍 Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Search Results Table (Displays when search is performed) -->
        <?php if ($isSearching): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-800">Search Results (<?= count($searchResults) ?>)</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100 text-gray-700 text-sm uppercase tracking-wider">
                                <th class="px-4 py-3 font-semibold border-b">Type</th>
                                <th class="px-4 py-3 font-semibold border-b">Number</th>
                                <th class="px-4 py-3 font-semibold border-b">Title</th>
                                <th class="px-4 py-3 font-semibold border-b">Category</th>
                                <th class="px-4 py-3 font-semibold border-b">Date</th>
                                <th class="px-4 py-3 font-semibold border-b text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm divide-y divide-gray-200">
                            <?php if (empty($searchResults)): ?>
                                <tr><td colspan="6" class="px-4 py-6 text-center italic text-gray-500">No records found matching your query.</td></tr>
                            <?php else: ?>
                                <?php foreach ($searchResults as $row): ?>
                                    <?php 
                                        $viewUrl = "view_document.php?id=" . $row['doc_id'] . "&type=" . strtolower($row['doc_type']);
                                        $editUrl = ($row['doc_type'] === 'Ordinance') 
                                            ? "edit_ordinance.php?id=" . $row['doc_id'] 
                                            : "edit_resolution.php?id=" . $row['doc_id'];
                                        $deleteUrl = ($row['doc_type'] === 'Ordinance') 
                                            ? "delete_ordinance.php?id=" . $row['doc_id'] 
                                            : "delete_resolution.php?id=" . $row['doc_id'];
                                    ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-3 whitespace-nowrap font-semibold text-gray-900"><?= htmlspecialchars($row['doc_type']) ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-800"><?= htmlspecialchars($row['doc_no']) ?></td>
                                        <td class="px-4 py-3 truncate max-w-xs" title="<?= htmlspecialchars($row['title']) ?>"><?= htmlspecialchars($row['title']) ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap"><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap"><?= date('M d, Y', strtotime($row['doc_date'])) ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center space-x-2">
                                            <a href="<?= $viewUrl ?>" class="text-green-600 hover:text-green-800 font-semibold text-sm">View</a>
                                            <span class="text-gray-300">|</span>
                                            <a href="<?= $editUrl ?>" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">Edit</a>
                                            <span class="text-gray-300">|</span>
                                            <a href="<?= $deleteUrl ?>" onclick="return confirm('Are you sure you want to delete this <?= strtolower($row['doc_type']) ?>?');" class="text-red-600 hover:text-red-800 font-semibold text-sm">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Records Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Recent Ordinances -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-800">Recently Added Ordinances</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100 text-gray-700 text-sm uppercase tracking-wider">
                                <th class="px-4 py-3 font-semibold border-b">Number</th>
                                <th class="px-4 py-3 font-semibold border-b">Title</th>
                                <th class="px-4 py-3 font-semibold border-b">Status</th>
                                <th class="px-4 py-3 font-semibold border-b text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm divide-y divide-gray-200">
                            <?php if (empty($recentOrdinances)): ?>
                                <tr><td colspan="4" class="px-4 py-4 text-center italic text-gray-500">No ordinances found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentOrdinances as $ord): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900"><?= htmlspecialchars($ord['ordinance_number']) ?></td>
                                        <td class="px-4 py-3 truncate max-w-xs" title="<?= htmlspecialchars($ord['title']) ?>"><?= htmlspecialchars($ord['title']) ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap"><?= htmlspecialchars($ord['status']) ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center space-x-2">
                                            <a href="view_document.php?id=<?= $ord['ordinance_id'] ?>&type=ordinance" class="text-green-600 hover:text-green-800 font-semibold text-sm">View</a>
                                            <span class="text-gray-300">|</span>
                                            <a href="edit_ordinance.php?id=<?= $ord['ordinance_id'] ?>" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">Edit</a>
                                            <span class="text-gray-300">|</span>
                                            <a href="delete_ordinance.php?id=<?= $ord['ordinance_id'] ?>" onclick="return confirm('Are you sure you want to delete this ordinance?');" class="text-red-600 hover:text-red-800 font-semibold text-sm">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Resolutions -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-800">Recently Added Resolutions</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100 text-gray-700 text-sm uppercase tracking-wider">
                                <th class="px-4 py-3 font-semibold border-b">Number</th>
                                <th class="px-4 py-3 font-semibold border-b">Title</th>
                                <th class="px-4 py-3 font-semibold border-b">Date Approved</th>
                                <th class="px-4 py-3 font-semibold border-b text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm divide-y divide-gray-200">
                            <?php if (empty($recentResolutions)): ?>
                                <tr><td colspan="4" class="px-4 py-4 text-center italic text-gray-500">No resolutions found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentResolutions as $res): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900"><?= htmlspecialchars($res['resolution_number']) ?></td>
                                        <td class="px-4 py-3 truncate max-w-xs" title="<?= htmlspecialchars($res['title']) ?>"><?= htmlspecialchars($res['title']) ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap"><?= date('M d, Y', strtotime($res['date_approved'])) ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center space-x-2">
                                            <a href="view_document.php?id=<?= $res['resolution_id'] ?>&type=resolution" class="text-green-600 hover:text-green-800 font-semibold text-sm">View</a>
                                            <span class="text-gray-300">|</span>
                                            <a href="edit_resolution.php?id=<?= $res['resolution_id'] ?>" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">Edit</a>
                                            <span class="text-gray-300">|</span>
                                            <a href="delete_resolution.php?id=<?= $res['resolution_id'] ?>" onclick="return confirm('Are you sure you want to delete this resolution?');" class="text-red-600 hover:text-red-800 font-semibold text-sm">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</body>
</html>