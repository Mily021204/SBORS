<?php
// /sb-iris/public/search.php
require_once '../config/db_connect.php';

// 1. Fetch Categories for Dropdown
$stmtCat = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch Distinct Years for Dropdown (Combining both tables)
$sqlYears = "
    SELECT DISTINCT YEAR(date) as doc_year FROM (
        SELECT date_enacted as date FROM ordinances
        UNION
        SELECT date_approved as date FROM resolutions
    ) AS combined_dates 
    WHERE date IS NOT NULL 
    ORDER BY doc_year DESC
";
$stmtYears = $pdo->query($sqlYears);
$years = $stmtYears->fetchAll(PDO::FETCH_ASSOC);

// 3. Process Search Logic
$results = [];
$searchQuery = $_GET['query'] ?? '';
$docType = $_GET['type'] ?? 'all';
$catFilter = $_GET['category'] ?? 'all';
$yearFilter = $_GET['year'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$paramsOrd = [];
$paramsRes = [];
$whereOrd = " WHERE 1=1";
$whereRes = " WHERE 1=1";

if (!empty($searchQuery)) {
    $whereOrd .= " AND (title LIKE :q1 OR ordinance_number LIKE :q2 OR description LIKE :q3)";
    $paramsOrd['q1'] = '%' . $searchQuery . '%';
    $paramsOrd['q2'] = '%' . $searchQuery . '%';
    $paramsOrd['q3'] = '%' . $searchQuery . '%';

    $whereRes .= " AND (title LIKE :q1 OR resolution_number LIKE :q2 OR description LIKE :q3)";
    $paramsRes['q1'] = '%' . $searchQuery . '%';
    $paramsRes['q2'] = '%' . $searchQuery . '%';
    $paramsRes['q3'] = '%' . $searchQuery . '%';
}

if ($catFilter !== 'all') {
    $whereOrd .= " AND category_id = :cat";
    $paramsOrd['cat'] = $catFilter;
    
    $whereRes .= " AND category_id = :cat";
    $paramsRes['cat'] = $catFilter;
}

if ($yearFilter !== 'all') {
    $whereOrd .= " AND YEAR(date_enacted) = :year";
    $paramsOrd['year'] = $yearFilter;
    
    $whereRes .= " AND YEAR(date_approved) = :year";
    $paramsRes['year'] = $yearFilter;
}

if (!empty($dateFrom)) {
    $whereOrd .= " AND date_enacted >= :date_from";
    $paramsOrd['date_from'] = $dateFrom;

    $whereRes .= " AND date_approved >= :date_from";
    $paramsRes['date_from'] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereOrd .= " AND date_enacted <= :date_to";
    $paramsOrd['date_to'] = $dateTo;

    $whereRes .= " AND date_approved <= :date_to";
    $paramsRes['date_to'] = $dateTo;
}

$sqlOrd = "SELECT 'Ordinance' AS doc_type, ordinance_id AS id, ordinance_number AS doc_no, title, date_enacted AS doc_date, CONCAT('../uploads/ORDINANCE/', file_path) AS full_file_path FROM ordinances" . $whereOrd;
$sqlRes = "SELECT 'Resolution' AS doc_type, resolution_id AS id, resolution_number AS doc_no, title, date_approved AS doc_date, CONCAT('../uploads/RESOLUTION/', file_path) AS full_file_path FROM resolutions" . $whereRes;

// Execute queries based on selected document type
if ($docType === 'all' || $docType === 'ordinance') {
    $stmt = $pdo->prepare($sqlOrd);
    $stmt->execute($paramsOrd);
    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($docType === 'all' || $docType === 'resolution') {
    $stmt = $pdo->prepare($sqlRes);
    $stmt->execute($paramsRes);
    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

// Sort combined results by date descending
usort($results, function($a, $b) {
    return strtotime($b['doc_date']) - strtotime($a['doc_date']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SB-IRIS - Search</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 font-sans min-h-screen p-4 md:p-8">

    <div class="max-w-6xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
        
        <div class="bg-blue-800 text-white text-center py-6 px-4 relative">
            <a href="../index.php" class="absolute top-4 left-4 md:left-6 text-blue-200 hover:text-white text-sm font-semibold transition">← Back to Login</a>
            <h1 class="text-3xl font-bold tracking-wider">SB-IRIS</h1>
            <p class="text-blue-200 mt-2 text-sm md:text-base">Legislative Records Search</p>
        </div>

        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <form action="search.php" method="GET" class="space-y-4">
                <div>
                    <label for="search_query" class="sr-only">Search</label>
                    <input type="text" id="search_query" name="query" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search ordinances, resolutions, keywords..." class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <div>
                        <label for="doc_type" class="block text-sm font-semibold text-gray-700 mb-1">Document Type</label>
                        <select id="doc_type" name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all" <?= $docType === 'all' ? 'selected' : '' ?>>All</option>
                            <option value="ordinance" <?= $docType === 'ordinance' ? 'selected' : '' ?>>Ordinance</option>
                            <option value="resolution" <?= $docType === 'resolution' ? 'selected' : '' ?>>Resolution</option>
                        </select>
                    </div>
                    <div>
                        <label for="category" class="block text-sm font-semibold text-gray-700 mb-1">Category</label>
                        <select id="category" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all">All</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= $catFilter == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="year" class="block text-sm font-semibold text-gray-700 mb-1">Year</label>
                        <select id="year" name="year" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all">All</option>
                            <?php foreach ($years as $yr): ?>
                                <option value="<?= $yr['doc_year'] ?>" <?= $yearFilter == $yr['doc_year'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($yr['doc_year']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="date_from" class="block text-sm font-semibold text-gray-700 mb-1">Date From</label>
                        <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-semibold text-gray-700 mb-1">Date To</label>
                        <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="text-right pt-2">
                    <a href="search.php" class="inline-block px-6 py-3 mr-2 text-gray-600 hover:text-gray-800 font-semibold transition">Clear</a>
                    <button type="submit" class="w-full md:w-auto px-8 py-3 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded-md shadow transition duration-200">
                        SEARCH
                    </button>
                </div>
            </form>
        </div>

        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                Results (<?= count($results) ?>)
            </h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 text-sm uppercase tracking-wider border-b-2 border-gray-300">
                            <th class="px-4 py-3 font-semibold">Document No.</th>
                            <th class="px-4 py-3 font-semibold">Title</th>
                            <th class="px-4 py-3 font-semibold">Type</th>
                            <th class="px-4 py-3 font-semibold">Date</th>
                            <th class="px-4 py-3 font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm divide-y divide-gray-200">
                        <?php if (empty($results)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">No records found matching your search criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($results as $row): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900"><?= htmlspecialchars($row['doc_no']) ?></td>
                                    <td class="px-4 py-3 min-w-[250px]"><?= htmlspecialchars($row['title']) ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap"><?= htmlspecialchars($row['doc_type']) ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap"><?= date('M d, Y', strtotime($row['doc_date'])) ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="view_document.php?id=<?= $row['id'] ?>&type=<?= strtolower($row['doc_type']) ?>" class="inline-block px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>