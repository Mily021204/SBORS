<?php
// /sb-iris/public/view_document.php
require_once '../config/db_connect.php';

$doc_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$doc_type = isset($_GET['type']) ? $_GET['type'] : '';

if (!$doc_id || !in_array($doc_type, ['ordinance', 'resolution'])) {
    die("Invalid document request.");
}

$record = null;
$folder = '';

if ($doc_type === 'ordinance') {
    $stmt = $pdo->prepare("SELECT o.*, c.category_name FROM ordinances o LEFT JOIN categories c ON o.category_id = c.category_id WHERE o.ordinance_id = ?");
    $stmt->execute([$doc_id]);
    $record = $stmt->fetch();
    $folder = 'ordinances';
} else {
    $stmt = $pdo->prepare("SELECT r.*, c.category_name FROM resolutions r LEFT JOIN categories c ON r.category_id = c.category_id WHERE r.resolution_id = ?");
    $stmt->execute([$doc_id]);
    $record = $stmt->fetch();
    $folder = 'resolutions';
}

if (!$record) {
    die("Document not found.");
}

$file_url = "../uploads/{$folder}/" . $record['file_path'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Document - SB-IRIS</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 font-sans min-h-screen p-4 md:p-8">

    <div class="max-w-6xl mx-auto">
        <div class="mb-4">
            <a href="dashboard.php" class="text-blue-700 hover:text-blue-900 font-semibold transition">← Back to Dashboard</a>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden flex flex-col lg:flex-row">
            
            <!-- Document Metadata -->
            <div class="w-full lg:w-1/3 bg-gray-50 p-6 border-r border-gray-200">
                <div class="mb-6 border-b pb-4">
                    <span class="inline-block px-3 py-1 bg-blue-800 text-white text-xs font-bold rounded uppercase tracking-wider mb-2">
                        <?= ucfirst($doc_type) ?>
                    </span>
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">
                        <?= htmlspecialchars($record[$doc_type . '_number']) ?>
                    </h1>
                </div>

                <div class="space-y-4 text-sm">
                    <div>
                        <span class="block font-semibold text-gray-500 uppercase tracking-wide text-xs">Title</span>
                        <p class="text-gray-900 font-medium"><?= htmlspecialchars($record['title']) ?></p>
                    </div>
                    <div>
                        <span class="block font-semibold text-gray-500 uppercase tracking-wide text-xs">Date</span>
                        <p class="text-gray-900"><?= date('F d, Y', strtotime($record[$doc_type === 'ordinance' ? 'date_enacted' : 'date_approved'])) ?></p>
                    </div>
                    <div>
                        <span class="block font-semibold text-gray-500 uppercase tracking-wide text-xs">Category</span>
                        <p class="text-gray-900"><?= htmlspecialchars($record['category_name']) ?></p>
                    </div>
                    
                    <?php if ($doc_type === 'ordinance'): ?>
                    <div>
                        <span class="block font-semibold text-gray-500 uppercase tracking-wide text-xs">Status</span>
                        <span class="px-2 py-1 inline-block mt-1 <?= $record['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?> rounded font-semibold">
                            <?= htmlspecialchars($record['status']) ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <div>
                        <span class="block font-semibold text-gray-500 uppercase tracking-wide text-xs">Description / Subject</span>
                        <p class="text-gray-900 text-justify"><?= nl2br(htmlspecialchars($record['description'])) ?></p>
                    </div>
                </div>
            </div>

            <!-- PDF Viewer -->
            <div class="w-full lg:w-2/3 bg-gray-200 min-h-[600px] lg:min-h-screen relative">
                <?php if (!empty($record['file_path']) && file_exists(__DIR__ . '/' . $file_url)): ?>
                    <iframe src="<?= htmlspecialchars($file_url) ?>" class="w-full h-full absolute top-0 left-0 border-none" title="Document Viewer"></iframe>
                <?php else: ?>
                    <div class="flex items-center justify-center h-full w-full absolute">
                        <div class="text-center text-gray-500">
                            <p class="text-xl font-bold mb-2">No Digital Copy Available</p>
                            <p class="text-sm">The PDF file for this record has not been uploaded or was removed.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</body>
</html>