<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Ensure user is authorized
if (!isset($_SESSION['role']) || $_SESSION['role'] === 'public') {
    header("Location: ../index.php?tab=calendar&error=unauthorized");
    exit;
}

if (isset($_GET['id'])) {
    $booking_id = $_GET['id'];

    // Try logging to audit trail safely if audit_logs table exists
    try {
        $stmt_log = $pdo->prepare("INSERT INTO audit_logs (booking_id, action_type, performed_by, details, created_at) VALUES (?, 'DELETE', ?, ?, NOW())");
        $stmt_log->execute([
            $booking_id,
            $_SESSION['office_name'] ?? 'Admin',
            "Deleted booking request ID: {$booking_id}"
        ]);
    } catch (Exception $e) {
        // Silently skip audit insert if table structure differs
    }

    // Delete the booking record
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
}

header("Location: ../index.php?tab=manage");
exit;