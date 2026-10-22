<?php
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'APPROVED', approved_by = 'Admin (GSO Chief)' WHERE id = ?");
    $stmt->execute([$id]);

    $log = $pdo->prepare("INSERT INTO audit_logs (booking_id, action_type, details, performed_by) VALUES (?, 'BOOKING_APPROVED', ?, 'Admin')");
    $log->execute([$id, "Reservation $id approved by GSO Admin"]);

    header("Location: ../index.php?tab=manage");
    exit;
}
?>
