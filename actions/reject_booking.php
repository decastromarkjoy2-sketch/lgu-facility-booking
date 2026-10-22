<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['booking_id'] ?? '';
    $reason = $_POST['reject_reason'] ?? 'No reason specified';

    $stmt = $pdo->prepare("UPDATE bookings SET status = 'REJECTED', rejection_reason = ? WHERE id = ?");
    $stmt->execute([$reason, $id]);

    $log = $pdo->prepare("INSERT INTO audit_logs (booking_id, action_type, details, performed_by) VALUES (?, 'BOOKING_REJECTED', ?, 'Admin')");
    $log->execute([$id, "Reservation $id rejected. Reason: $reason"]);

    header("Location: ../index.php?tab=manage");
    exit;
}
?>
