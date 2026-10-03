<?php
// /sb-iris/staff/delete_resolution.php
require_once '../config/db_connect.php';
require_once '../config/auth.php';

// Enforce role-based access control
requireRole('SB Staff');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM resolutions WHERE resolution_id = :id");
        $stmt->execute(['id' => $id]);
    } catch (PDOException $e) {
        // You may want to log this or pass it via session flash message
        error_log("Failed to delete resolution: " . $e->getMessage());
    }
}

// Redirect back to dashboard (or manage_resolutions.php)
header("Location: dashboard.php");
exit;
?>