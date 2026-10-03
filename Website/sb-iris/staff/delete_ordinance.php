<?php
// /sb-iris/staff/delete_ordinance.php
require_once '../config/db_connect.php';
require_once '../config/auth.php';

// Enforce role-based access control
requireRole('SB Staff');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM ordinances WHERE ordinance_id = :id");
        $stmt->execute(['id' => $id]);
    } catch (PDOException $e) {
        // You may want to log this or pass it via session flash message
        error_log("Failed to delete ordinance: " . $e->getMessage());
    }
}

// Redirect back to dashboard (or manage_ordinances.php)
header("Location: dashboard.php");
exit;
?>