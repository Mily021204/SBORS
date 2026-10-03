<?php
// /sb-iris/staff/reports.php
require_once '../config/db_connect.php';
require_once '../config/auth.php';

requireRole('SB Staff');

// Fetch Categories for Filter
$stmtCat = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

// Default Filter Values (Widened to the entire current year)
$docType = $_GET['type'] ?? 'all';
$startDate = $_GET['start_date'] ?? date('Y-01-01'); // 1st of current year
$endDate = $_GET['end_date'] ?? date('Y-12-31'); // Last day of current year
$catFilter = $_GET['category'] ?? 'all';

$results = [];
$params = [];

// Wrapped in DATE() to prevent datetime timestamp mismatches
$whereOrd = " WHERE DATE(date_enacted) BETWEEN :start_date AND :end_date";
$whereRes = " WHERE DATE(date_approved) BETWEEN :start_date AND :end_date";
$params['start_date'] = $startDate;
$params['end_date'] = $endDate;

if ($catFilter !== 'all') {
    $whereOrd .= " AND o.category_id = :cat";
    $whereRes .= " AND r.category_id = :cat";
    $params['cat'] = $catFilter;
}

// Queries with JOINs to get category names
$sqlOrd = "SELECT 'Ordinance' AS doc_type, ordinance_number AS doc_no, title, date_enacted AS doc_date, status, c.category_name 
           FROM ordinances o LEFT JOIN categories c ON o.category_id = c.category_id " . $whereOrd;

$sqlRes = "SELECT 'Resolution' AS doc_type, resolution_number AS doc_no, title, date_approved AS doc_date, 'Approved' AS status, c.category_name 
           FROM resolutions r LEFT JOIN categories c ON r.category_id = c.category_id " . $whereRes;

if ($docType === 'all' || $docType === 'ordinance') {
    $stmt = $pdo->prepare($sqlOrd);
    $stmt->execute($params);
    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($docType === 'all' || $docType === 'resolution') {
    $stmt = $pdo->prepare($sqlRes);
    $stmt->execute($params);
    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

// Sort combined results by date ascending for chronological reporting
usort($results, function($a, $b) {
    return strtotime($a['doc_date']) - strtotime($b['doc_date']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reports - SB-IRIS</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <style>
        /* Ensures backgrounds print correctly if needed */
        @media print {
            body { background-color: white !important; }
            @page { margin: 1cm; }
            /* Hide URL printing in some browsers */
            a[href]:after { content: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 font-sans min-h-screen">

    <!-- Top Navigation (Hidden on Print) -->
    <nav class="bg-blue-800 text-white shadow-md print:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="text-xl font-bold tracking-wider">SB-IRIS Staff Portal</div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-blue-200 hover:text-white text-sm font-semibold transition">← Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        <!-- Filter Form (Hidden on Print) -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8 print:hidden">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Report Parameters</h2>
            <form action="reports.php" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Document Type</label>
                        <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all" <?= $docType === 'all' ? 'selected' : '' ?>>All Documents</option>
                            <option value="ordinance" <?= $docType === 'ordinance' ? 'selected' : '' ?>>Ordinances Only</option>
                            <option value="resolution" <?= $docType === 'resolution' ? 'selected' : '' ?>>Resolutions Only</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Category</label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= $catFilter == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="text-right pt-2 border-t mt-4">
                    <a href="reports.php" class="inline-block px-6 py-2 mr-2 text-gray-600 hover:text-gray-800 font-semibold transition">Reset</a>
                    <button type="submit" class="px-6 py-2 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded shadow transition">
                        Generate Report
                    </button>
                </div>
            </form>
        </div>

        <!-- Report Output Area -->
        <div class="bg-white p-8 rounded-lg shadow-md print:shadow-none print:p-0">
            
            <!-- Print Header -->
            <div class="relative flex items-center justify-center mb-8 pb-4 border-b print:border-none">
                <!-- Agoo Logo positioned absolutely to the left -->
                <div class="absolute left-0 top-0">
                    <img src="../assets/images/agoo logo.jpg" alt="Municipality of Agoo Logo" class="h-20 w-20 object-contain">
                </div>
                <!-- Centered Text -->
                <div class="text-center pt-2">
                    <h1 class="text-2xl font-bold text-gray-900 uppercase tracking-wide">Legislative Records Report</h1>
                    <p class="text-gray-600 mt-1 font-medium">Period: <?= date('F d, Y', strtotime($startDate)) ?> to <?= date('F d, Y', strtotime($endDate)) ?></p>
                </div>
            </div>

            <!-- Print Button (Hidden on Print) -->
            <div class="flex justify-end mb-4 print:hidden">
                <button onclick="window.print()" class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white font-bold rounded shadow flex items-center transition">
                    🖨️ Print / Save to PDF
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100 text-gray-800 text-sm uppercase tracking-wider">
                            <th class="px-4 py-3 border border-gray-300 font-semibold">Date</th>
                            <th class="px-4 py-3 border border-gray-300 font-semibold">Type</th>
                            <th class="px-4 py-3 border border-gray-300 font-semibold">Number</th>
                            <th class="px-4 py-3 border border-gray-300 font-semibold">Title</th>
                            <th class="px-4 py-3 border border-gray-300 font-semibold">Category</th>
                            <th class="px-4 py-3 border border-gray-300 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        <?php if (empty($results)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center italic text-gray-500 border border-gray-300">No records found for the selected period.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td class="px-4 py-2 border border-gray-300 whitespace-nowrap"><?= date('m/d/Y', strtotime($row['doc_date'])) ?></td>
                                    <td class="px-4 py-2 border border-gray-300 whitespace-nowrap font-semibold"><?= htmlspecialchars($row['doc_type']) ?></td>
                                    <td class="px-4 py-2 border border-gray-300 whitespace-nowrap"><?= htmlspecialchars($row['doc_no']) ?></td>
                                    <td class="px-4 py-2 border border-gray-300"><?= htmlspecialchars($row['title']) ?></td>
                                    <td class="px-4 py-2 border border-gray-300 whitespace-nowrap"><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></td>
                                    <td class="px-4 py-2 border border-gray-300 whitespace-nowrap"><?= htmlspecialchars($row['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Prepared By Section -->
            <div class="mt-12 flex justify-end">
                <div class="text-left">
                    <p class="text-sm font-semibold text-gray-700">Prepared by:</p>
                    <p class="text-base font-bold text-gray-900 mt-6 border-b border-gray-800 pb-1 px-2 inline-block">
                        <?= htmlspecialchars($_SESSION['name'] ?? '') ?>
                    </p>
                </div>
            </div>
            
            <!-- Print Footer (Only visible on Print) -->
            <div class="hidden print:block mt-8 text-sm text-gray-500 text-center">
                <p>Generated by SB-IRIS on <?= date('F d, Y h:i A') ?></p>
            </div>
        </div>
    </div>
</body>
</html>