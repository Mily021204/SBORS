<?php
// /sb-iris/staff/edit_profile.php
require_once '../config/db_connect.php';
require_once '../config/auth.php';

// Enforce role-based access control
requireRole('SB Staff');

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    
    if (empty($name)) {
        $message = "Name cannot be empty.";
        $messageType = "red";
    } else {
        try {
            if (!empty($newPassword)) {
                // Update name and password using correct column name: user_id
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = :name, password = :password WHERE user_id = :id");
                $stmt->execute(['name' => $name, 'password' => $hashedPassword, 'id' => $userId]);
            } else {
                // Update name only using correct column name: user_id
                $stmt = $pdo->prepare("UPDATE users SET name = :name WHERE user_id = :id");
                $stmt->execute(['name' => $name, 'id' => $userId]);
            }
            
            // Update session variable so the nav bar reflects the new name immediately
            $_SESSION['name'] = $name;
            
            $message = "Profile updated successfully!";
            $messageType = "green";
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $messageType = "red";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - SB-IRIS</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 font-sans min-h-screen">
    
    <nav class="bg-blue-800 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="text-xl font-bold tracking-wider">SB-IRIS Staff Portal</div>
            <a href="dashboard.php" class="text-sm text-blue-200 hover:text-white transition">← Back to Dashboard</a>
        </div>
    </nav>

    <div class="max-w-lg mx-auto mt-10 bg-white p-8 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Profile</h2>

        <?php if ($message): ?>
            <div class="mb-4 p-4 rounded bg-<?= $messageType ?>-100 text-<?= $messageType ?>-800 font-semibold">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="edit_profile.php" method="POST" class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Full Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($_SESSION['name']) ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="new_password" class="block text-sm font-semibold text-gray-700 mb-1">New Password</label>
                <input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Only fill this out if you wish to change your password.</p>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-3 px-4 rounded transition duration-200">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

</body>
</html>