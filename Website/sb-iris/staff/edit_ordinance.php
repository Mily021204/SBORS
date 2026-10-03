<?php
// /sb-iris/staff/edit_ordinance.php
require_once '../config/db_connect.php';
require_once '../config/auth.php';

requireRole('SB Staff');

$message = '';
$ordinance_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$ordinance_id) {
    header("Location: dashboard.php");
    exit();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ord_no = trim($_POST['ordinance_number']);
    $title = trim($_POST['title']);
    $date_enacted = $_POST['date_enacted'];
    $category_id = $_POST['category_id'];
    $status = $_POST['status'];
    $description = trim($_POST['description']);
    $current_file = $_POST['current_file'];

    $newFileName = $current_file;

    // Handle optional PDF replacement
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = strtolower(pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION));
        if ($fileExtension === 'pdf') {
            $newFileName = md5(time() . $_FILES['document_file']['name']) . '.pdf';
            $destPath = '../uploads/ordinances/' . $newFileName;
            
            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $destPath)) {
                // Remove old file
                if (file_exists('../uploads/ordinances/' . $current_file)) {
                    unlink('../uploads/ordinances/' . $current_file);
                }
            }
        } else {
            $message = "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded'>Only PDF files are allowed.</div>";
        }
    }

    if (empty($message)) {
        $sql = "UPDATE ordinances SET ordinance_number = ?, title = ?, date_enacted = ?, category_id = ?, status = ?, description = ?, file_path = ? WHERE ordinance_id = ?";
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute([$ord_no, $title, $date_enacted, $category_id, $status, $description, $newFileName, $ordinance_id]);
            $message = "<div class='p-4 mb-4 text-green-700 bg-green-100 rounded'>Ordinance successfully updated.</div>";
        } catch (PDOException $e) {
            $message = "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded'>Error updating database. Number might already exist.</div>";
        }
    }
}

// Fetch current record data
$stmt = $pdo->prepare("SELECT * FROM ordinances WHERE ordinance_id = ?");
$stmt->execute([$ordinance_id]);
$ordinance = $stmt->fetch();

if (!$ordinance) {
    die("Ordinance not found.");
}

$categories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ordinance - SB-IRIS</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 font-sans min-h-screen">
    <div class="max-w-4xl mx-auto py-10 px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-blue-800">Edit Ordinance</h1>
            <a href="dashboard.php" class="text-gray-600 hover:text-gray-800 font-semibold">← Back to Dashboard</a>
        </div>

        <?= $message ?>

        <div class="bg-white p-8 rounded-lg shadow-md">
            <form action="edit_ordinance.php?id=<?= $ordinance_id ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="current_file" value="<?= htmlspecialchars($ordinance['file_path']) ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Ordinance Number</label>
                        <input type="text" name="ordinance_number" value="<?= htmlspecialchars($ordinance['ordinance_number']) ?>" required class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Date Enacted</label>
                        <input type="date" name="date_enacted" value="<?= htmlspecialchars($ordinance['date_enacted']) ?>" required class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Title</label>
                    <textarea name="title" required rows="2" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($ordinance['title']) ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Category</label>
                        <select name="category_id" required class="w-full px-4 py-2 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= $ordinance['category_id'] == $cat['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                        <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="Active" <?= $ordinance['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Amended" <?= $ordinance['status'] === 'Amended' ? 'selected' : '' ?>>Amended</option>
                            <option value="Repealed" <?= $ordinance['status'] === 'Repealed' ? 'selected' : '' ?>>Repealed</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Description / Subject</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($ordinance['description']) ?></textarea>
                </div>

                <div class="p-4 bg-gray-50 border rounded-md">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Replace Digital Document</label>
                    <p class="text-xs text-gray-500 mb-2">Leave blank to keep the current file.</p>
                    <input type="file" name="document_file" accept=".pdf" class="w-full px-4 py-2 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="text-right pt-4 border-t border-gray-200">
                    <button type="submit" class="px-6 py-2 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded shadow transition">
                        Update Ordinance
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>