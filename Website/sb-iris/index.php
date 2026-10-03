<?php
// index.php
require_once 'config/db_connect.php';
session_start();

// TEMPORARY ADMIN CREATION TOOL - DELETE AFTER SUCCESSFUL LOGIN
if (isset($_GET['create_admin'])) {
    $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('System Admin', 'admin@example.com', ?, 'Administrator', 'Active') ON DUPLICATE KEY UPDATE password = ?");
        $stmt->execute([$hashedPassword, $hashedPassword]);
        echo "Admin user created or updated successfully with password 'admin123'!";
    } catch (PDOException $e) {
        echo "Error creating user: " . $e->getMessage();
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT user_id, name, password, role, status FROM users WHERE email = ? AND status = 'Active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verify password hash
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // Route based on role
            if ($user['role'] === 'Administrator') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: staff/dashboard.php");
            }
            exit();
        } else {
            $error = 'Invalid email or password, or account is inactive.';
        }
    } else {
        $error = 'Please enter both email and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SB-IRIS - Login</title>
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen px-4 sm:px-6">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg w-full max-w-lg">
        <div class="text-center mb-8">
            <h1 class="text-4xl sm:text-5xl font-extrabold text-blue-900 tracking-tight mb-3">SB-IRIS</h1>
            <p class="text-xs sm:text-sm font-semibold text-gray-600 leading-relaxed uppercase tracking-wide px-2">
                A Web-Based Sangguniang Bayan Ordinances and Resolutions Information Retrieval and Indexing System
            </p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" name="email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit" class="w-full py-2.5 px-4 bg-blue-700 hover:bg-blue-800 text-white font-semibold rounded-md transition duration-200 shadow">
                Log In
            </button>
        </form>

        <div class="mt-6 text-center border-t pt-4">
            <p class="text-sm text-gray-600 mb-2">Are you a public user?</p>
            <a href="public/search.php" class="inline-block w-full py-2.5 px-4 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-md transition duration-200">
                Proceed to Public Search
            </a>
        </div>
    </div>
</body>
</html>