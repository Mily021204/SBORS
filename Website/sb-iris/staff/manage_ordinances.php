<?php
// /sb-iris/staff/manage_ordinances.php
require_once '../config/db_connect.php';
require_once '../config/auth.php';

// Enforce role-based access control
requireRole('SB Staff');

$message = '';

// Fetch categories for the dropdown
$stmtCat = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$categories = $stmtCat->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ord_no = trim($_POST['ordinance_number']);
    $title = trim($_POST['title']);
    $date_enacted = $_POST['date_enacted'];
    $category_id = $_POST['category_id'];
    $status = $_POST['status'];
    $description = trim($_POST['description']);
    $created_by = $_SESSION['user_id'];

    // Handle PDF File Upload
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['document_file']['tmp_name'];
        $fileName = $_FILES['document_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if ($fileExtension === 'pdf') {
            $newFileName = md5(time() . $fileName) . '.pdf';
            $destPath = '../uploads/ordinances/' . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Insert into database
                $sql = "INSERT INTO ordinances (ordinance_number, title, date_enacted, category_id, status, description, file_path, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                try {
                    $stmt->execute([$ord_no, $title, $date_enacted, $category_id, $status, $description, $newFileName, $created_by]);
                    $message = "<div class='p-4 mb-4 text-green-700 bg-green-100 rounded'>Ordinance successfully added.</div>";
                } catch (PDOException $e) {
                    $message = "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded'>Error saving to database. Number might already exist.</div>";
                }
            } else {
                $message = "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded'>Error moving uploaded file.</div>";
            }
        } else {
            $message = "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded'>Only PDF files are allowed.</div>";
        }
    } else {
        $message = "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded'>Please select a file to upload.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SB Staff - Manage Ordinances</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 font-sans min-h-screen">
    <div class="max-w-4xl mx-auto py-10 px-4">
        
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-blue-800">Add New Ordinance</h1>
            <a href="dashboard.php" class="text-gray-600 hover:text-gray-800 font-semibold">← Back to Dashboard</a>
        </div>

        <?= $message ?>

        <div class="bg-white p-8 rounded-lg shadow-md">
            <form action="manage_ordinances.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Ordinance Number</label>
                        <input type="text" name="ordinance_number" required placeholder="e.g. ORD-2026-001" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Date Enacted</label>
                        <input type="date" name="date_enacted" required class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Title</label>
                    <textarea name="title" required rows="2" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Category</label>
                        <select name="category_id" required class="w-full px-4 py-2 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                        <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="Active">Active</option>
                            <option value="Amended">Amended</option>
                            <option value="Repealed">Repealed</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Description / Subject</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Upload Digital Document (PDF only)</label>
                    <input type="file" name="document_file" accept=".pdf" required class="w-full px-4 py-2 border border-gray-300 rounded bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="text-right pt-4 border-t border-gray-200">
                    <button type="submit" class="px-6 py-2 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded shadow transition">
                        Save Ordinance Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>