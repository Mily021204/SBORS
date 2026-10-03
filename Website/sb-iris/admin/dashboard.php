<?php
// /sb-iris/admin/dashboard.php
require_once '../config/db_connect.php';
require_once '../config/auth.php';

// Enforce role-based access control restrict access to Administrators only
requireRole('Administrator');

$message = '';

// Handle Add User Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (!empty($name) && !empty($email) && !empty($password) && !empty($role)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 'Active')";
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute([$name, $email, $hashed_password, $role]);
            $message = "<div class='p-4 mb-4 text-green-700 bg-green-100 rounded'>User account successfully created.</div>";
        } catch (PDOException $e) {
            $message = "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded'>Error: Email might already be registered.</div>";
        }
    } else {
        $message = "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded'>Please fill in all fields.</div>";
    }
}

// Handle Status Toggle (Activate/Deactivate)
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $toggle_id = (int)$_GET['id'];
    $new_status = $_GET['toggle'] === 'Inactive' ? 'Inactive' : 'Active';
    
    // Prevent admin from deactivating themselves
    if ($toggle_id !== $_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt->execute([$new_status, $toggle_id]);
        header("Location: dashboard.php");
        exit();
    } else {
        $message = "<div class='p-4 mb-4 text-yellow-700 bg-yellow-100 rounded'>You cannot deactivate your own account.</div>";
    }
}

// Handle Delete User
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Prevent admin from deleting themselves
    if ($delete_id !== $_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$delete_id]);
            header("Location: dashboard.php");
            exit();
        } catch (PDOException $e) {
            // Foreign key restraint violation protection
            $message = "<div class='p-4 mb-4 text-red-700 bg-red-100 rounded'>Cannot delete user: This account is linked to existing ordinances or resolutions. Please deactivate the user account instead.</div>";
        }
    } else {
        $message = "<div class='p-4 mb-4 text-yellow-700 bg-yellow-100 rounded'>You cannot delete your own account.</div>";
    }
}

// Fetch all users
$stmtUsers = $pdo->query("SELECT user_id, name, email, role, status FROM users ORDER BY role ASC, name ASC");
$users = $stmtUsers->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard - SB-IRIS</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 font-sans min-h-screen">

    <!-- Top Navigation -->
    <nav class="bg-blue-900 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="text-xl font-bold tracking-wider">SB-IRIS Admin</div>
            <div class="flex items-center space-x-6">
                <!-- Navigation Link for Categories -->
                <a href="manage_categories.php" class="text-blue-200 hover:text-white font-semibold text-sm transition">Manage Categories</a>
                <span class="text-sm border-l border-blue-700 pl-6">Welcome, <?= htmlspecialchars($_SESSION['name']) ?></span>
                <a href="edit_profile.php" class="text-sm text-blue-200 hover:text-white underline decoration-blue-400 transition">Edit Profile</a>
                <a href="../config/logout.php" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded text-sm font-semibold transition">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        <?= $message ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column: Add User Form -->
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Add New User</h2>
                    <form action="dashboard.php" method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" name="name" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" name="email" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" name="password" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <select name="role" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="SB Staff">SB Staff</option>
                                <option value="Administrator">Administrator</option>
                            </select>
                        </div>
                        <button type="submit" name="add_user" class="w-full py-2 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded shadow transition">
                            Create Account
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column: User Management Table -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-4 bg-gray-50 border-b border-gray-200">
                        <h2 class="text-lg font-bold text-gray-800">System Users</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700 text-sm uppercase tracking-wider">
                                    <th class="px-4 py-3 font-semibold border-b">Name</th>
                                    <th class="px-4 py-3 font-semibold border-b">Email</th>
                                    <th class="px-4 py-3 font-semibold border-b">Role</th>
                                    <th class="px-4 py-3 font-semibold border-b">Status</th>
                                    <th class="px-4 py-3 font-semibold border-b text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm divide-y divide-gray-200">
                                <?php foreach ($users as $u): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900"><?= htmlspecialchars($u['name']) ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap"><?= htmlspecialchars($u['email']) ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold"><?= htmlspecialchars($u['role']) ?></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?php if ($u['status'] === 'Active'): ?>
                                                <span class="text-green-600 font-bold">Active</span>
                                            <?php else: ?>
                                                <span class="text-red-600 font-bold">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap space-x-2">
                                            <?php if ($u['user_id'] !== $_SESSION['user_id']): ?>
                                                <?php if ($u['status'] === 'Active'): ?>
                                                    <a href="dashboard.php?toggle=Inactive&id=<?= $u['user_id'] ?>" class="text-red-600 hover:text-red-800 font-semibold" onclick="return confirm('Deactivate this user?');">Deactivate</a>
                                                <?php else: ?>
                                                    <a href="dashboard.php?toggle=Active&id=<?= $u['user_id'] ?>" class="text-green-600 hover:text-green-800 font-semibold">Activate</a>
                                                <?php endif; ?>
                                                <span class="text-gray-300">|</span>
                                                <a href="dashboard.php?delete_id=<?= $u['user_id'] ?>" class="text-red-600 hover:text-red-800 font-semibold" onclick="return confirm('Are you sure you want to delete this user permanently?');">Delete</a>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">Current User</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</body>
</html>