<?php
// /sb-iris/admin/manage_categories.php
require_once '../config/db_connect.php';
require_once '../config/auth.php';

// Restrict access to Administrators
requireRole('Administrator');

$message = '';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    if (!empty($category_name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
        try {
            $stmt->execute([$category_name]);
            $message = "<div class='p-4 mb-4 text-green-700 bg-green-100 rounded'>Category added successfully.</div>";
        } catch (PDOException $e) {
            $message = "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded'>Error: Category name already exists.</div>";
        }
    }
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $category_id = (int)$_POST['category_id'];
    $category_name = trim($_POST['category_name']);
    if (!empty($category_name)) {
        $stmt = $pdo->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?");
        try {
            $stmt->execute([$category_name, $category_id]);
            $message = "<div class='p-4 mb-4 text-green-700 bg-green-100 rounded'>Category updated successfully.</div>";
        } catch (PDOException $e) {
            $message = "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded'>Error updating category.</div>";
        }
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $category_id = (int)$_GET['delete'];
    
    // Check for foreign key constraints in both tables
    $stmtOrd = $pdo->prepare("SELECT COUNT(*) FROM ordinances WHERE category_id = ?");
    $stmtOrd->execute([$category_id]);
    $ordCount = $stmtOrd->fetchColumn();

    $stmtRes = $pdo->prepare("SELECT COUNT(*) FROM resolutions WHERE category_id = ?");
    $stmtRes->execute([$category_id]);
    $resCount = $stmtRes->fetchColumn();

    if ($ordCount == 0 && $resCount == 0) {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->execute([$category_id]);
        header("Location: manage_categories.php");
        exit();
    } else {
        $message = "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded'>Deletion blocked: This category is assigned to existing documents.</div>";
    }
}

// Fetch Categories
$categories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - SB-IRIS</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <script>
        function editCategory(id, name) {
            document.getElementById('form_title').innerText = 'Edit Category';
            document.getElementById('category_name').value = name;
            document.getElementById('category_id').value = id;
            document.getElementById('submit_btn').name = 'edit_category';
            document.getElementById('submit_btn').innerText = 'Update Category';
            document.getElementById('cancel_btn').classList.remove('hidden');
        }

        function cancelEdit() {
            document.getElementById('form_title').innerText = 'Add New Category';
            document.getElementById('category_name').value = '';
            document.getElementById('category_id').value = '';
            document.getElementById('submit_btn').name = 'add_category';
            document.getElementById('submit_btn').innerText = 'Save Category';
            document.getElementById('cancel_btn').classList.add('hidden');
        }
    </script>
</head>
<body class="bg-gray-100 text-gray-800 font-sans min-h-screen">

    <nav class="bg-blue-900 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="text-xl font-bold tracking-wider">SB-IRIS Admin</div>
            <a href="dashboard.php" class="text-blue-200 hover:text-white font-semibold text-sm transition">← Back to Dashboard</a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-8 px-4">
        <?= $message ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-lg shadow-md sticky top-6">
                    <h2 id="form_title" class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Add New Category</h2>
                    <form action="manage_categories.php" method="POST" class="space-y-4">
                        <input type="hidden" id="category_id" name="category_id" value="">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category Name</label>
                            <input type="text" id="category_name" name="category_name" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="flex space-x-2 border-t pt-4">
                            <button type="submit" id="submit_btn" name="add_category" class="w-full py-2 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded shadow transition">
                                Save Category
                            </button>
                            <button type="button" id="cancel_btn" onclick="cancelEdit()" class="hidden w-1/3 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold rounded shadow transition">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-4 bg-gray-50 border-b border-gray-200">
                        <h2 class="text-lg font-bold text-gray-800">Legislative Categories</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700 text-sm uppercase tracking-wider">
                                    <th class="px-4 py-3 font-semibold border-b">Category Name</th>
                                    <th class="px-4 py-3 font-semibold border-b text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm divide-y divide-gray-200">
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="2" class="px-4 py-4 text-center italic text-gray-500">No categories found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($cat['category_name']) ?></td>
                                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                                <button onclick="editCategory(<?= $cat['category_id'] ?>, '<?= htmlspecialchars(addslashes($cat['category_name'])) ?>')" class="text-blue-600 hover:text-blue-800 font-semibold mr-3">Edit</button>
                                                <a href="manage_categories.php?delete=<?= $cat['category_id'] ?>" class="text-red-600 hover:text-red-800 font-semibold" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
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
    </div>
</body>
</html>