<?php
// /sb-iris/staff/edit_resolution.php
require_once '../config/db_connect.php';
require_once '../config/auth.php';

// Enforce role-based access control
requireRole('SB Staff');

$message = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header("Location: dashboard.php");
    exit();
}

// Fetch categories for the dropdown
$stmtCat = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$categories = $stmtCat->fetchAll();

// Fetch existing resolution details
$stmtRes = $pdo->prepare("SELECT * FROM resolutions WHERE resolution_id = ?");
$stmtRes->execute([$id]);
$resolution = $stmtRes->fetch(PDO::FETCH_ASSOC);

if (!$resolution) {
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res_no = trim($_POST['resolution_number']);
    $title = trim($_POST['title']);
    $date_approved = $_POST['date_approved'];
    $category_id = $_POST['category_id'];
    $description = trim($_POST['description']);

    $fileNameToSave = $resolution['file_path']; // Default to current file
    $canUpdate = true;

    // Handle Optional PDF File Upload
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['document_file']['tmp_name'];
        $fileName = $_FILES['document_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if ($fileExtension === 'pdf') {
            $newFileName = md5(time() . $fileName) . '.pdf';
            $destPath = '../uploads/resolutions/' . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $fileNameToSave = $newFileName;
            } else {
                $canUpdate = false;
                $message = "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded'>Error moving uploaded file.</div>";
            }
        } else {
            $canUpdate = false;
            $message = "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded'>Only PDF files are allowed.</div>";
        }
    }

    if ($canUpdate) {
        $sql = "UPDATE resolutions 
                SET resolution_number = ?, title = ?, date_approved = ?, category_id = ?, description = ?, file_path = ? 
                WHERE resolution_id = ?";
        $stmtUpdate = $pdo->prepare($sql);
        
        try {
            $stmtUpdate->execute([$res_no, $title, $date_approved, $category_id, $description, $fileNameToSave, $id]);
            $message = "<div class='p-4 mb-4 text-green-700 bg-green-100 rounded'>Resolution updated successfully.</div>";

            // Refresh array data with updated records
            $stmtRes->execute([$id]);
            $resolution = $stmtRes->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $message = "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded'>Error updating database. Number might already exist.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SB Staff - Edit Resolution</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 font-sans min-h-screen">
    <div class="max-w-4xl mx-auto py-10 px-4">
        
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-blue-800">Edit Resolution</h1>
            <a href="dashboard.php" class="text-gray-600 hover:text-gray-800 font-semibold">← Back to Dashboard</a>
        </div>

        <?= $message ?>

        <div class="bg-white p-8 rounded-lg shadow-md">
            <form action="edit_resolution.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Resolution Number</label>
                        <input type="text" name="resolution_number" value="<?= htmlspecialchars($resolution['resolution_number']) ?>" required placeholder="e.g. RES-2026-001" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Date Approved</label>
                        <input type="date" name="date_approved" value="<?= htmlspecialchars($resolution['date_approved']) ?>" required class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Title</label>
                    <textarea name="title" required rows="2" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($resolution['title']) ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Category</label>
                    <select name="category_id" required class="w-full px-4 py-2 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>" <?= ($cat['category_id'] == $resolution['category_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Description / Subject</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($resolution['description']) ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Upload Digital Document (PDF only)</label>
                    <?php if (!empty($resolution['file_path'])): ?>
                        <div class="mb-2 text-sm text-gray-600">
                            Current file: 
                            <a href="../uploads/resolutions/<?= htmlspecialchars($resolution['file_path']) ?>" target="_blank" class="text-blue-600 hover:underline font-semibold">
                                View Current Document (PDF)
                            </a>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="document_file" accept=".pdf" class="w-full px-4 py-2 border border-gray-300 rounded bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Leave blank if you do not want to replace the current file.</p>
                </div>

                <div class="text-right pt-4 border-t border-gray-200 mt-6 space-x-2">
                    <a href="dashboard.php" class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold rounded shadow transition">Cancel</a>
                    <button type="submit" class="px-6 py-2 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded shadow transition">
                        Update Resolution
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>