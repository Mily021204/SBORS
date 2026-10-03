<?php
// config/auth.php
session_start();

// Check if a user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Restrict access based on user role
function requireRole($requiredRole) {
    if (!isLoggedIn()) {
        header("Location: /sb-iris/index.php");
        exit();
    }
    
    if ($_SESSION['role'] !== $requiredRole) {
        // Route users to their respective dashboards if they wander off-limits
        if ($_SESSION['role'] === 'Administrator') {
            header("Location: /sb-iris/admin/dashboard.php");
        } else if ($_SESSION['role'] === 'SB Staff') {
            header("Location: /sb-iris/staff/dashboard.php");
        } else {
            header("Location: /sb-iris/public/search.php");
        }
        exit();
    }
}
?>