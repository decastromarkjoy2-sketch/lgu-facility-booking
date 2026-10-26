<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $venue_id     = $_POST['venueId'] ?? '';
    $venue_name   = ($venue_id === 'conference-room') ? 'Conference Room' : 'Annex Building';
    $event_date   = $_POST['date'] ?? '';
    $start_time   = $_POST['startTime'] ?? '';
    $end_time     = $_POST['endTime'] ?? '';
    
    // Handle Department: if "Others" is chosen, use the custom input value
    $department   = $_POST['department'] ?? '';
    if ($department === 'Others' && !empty($_POST['department_other'])) {
        $department = trim($_POST['department_other']);
    }

    $contact_name = $_POST['contactName'] ?? '';
    $contact_info = $_POST['contactInfo'] ?? '';
    $attendees    = $_POST['attendees'] ?? 0;
    $event_title  = $_POST['eventTitle'] ?? '';
    $purpose      = $_POST['purpose'] ?? '';

    // Enforce 30-minute buffer conflict check using PostgreSQL interval syntax
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE venue_id = :venue_id AND event_date = :event_date AND status IN ('APPROVED', 'PENDING') AND (:start_time < (end_time::time + INTERVAL '30 minutes')::text AND :end_time > (start_time::time - INTERVAL '30 minutes')::text)");
    $stmt->execute([':venue_id' => $venue_id, ':event_date' => $event_date, ':start_time' => $start_time, ':end_time' => $end_time]);
    
    if ($stmt->fetchColumn() > 0) {
        // Save form inputs to session so data is retained on error
        $_SESSION['old_input'] = $_POST;
        header("Location: ../index.php?tab=request&date=$event_date&error=conflict");
        exit;
    }

    $id = 'RES-' . date('Y') . '-' . rand(100, 999);

    // Fixed: Use lowercase camelCase "venueName" with double quotes to match the exact schema
    $insert = $pdo->prepare("INSERT INTO bookings (id, venue_id, \"venueName\", event_title, purpose, department, contact_name, contact_info, attendees, event_date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING')");
    $insert->execute([$id, $venue_id, $venue_name, $event_title, $purpose, $department, $contact_name, $contact_info, $attendees, $event_date, $start_time, $end_time]);

    $log = $pdo->prepare("INSERT INTO audit_logs (booking_id, action_type, details, performed_by) VALUES (?, 'BOOKING_SUBMITTED', ?, ?)");
    $log->execute([$id, "New reservation filed for $venue_name on $event_date", $contact_name]);

    // Clear old input session on successful submission
    unset($_SESSION['old_input']);

    // Automatically redirect back to the calendar view upon successful submission
    header("Location: ../index.php?tab=calendar&success=filed&id=$id");
    exit;
}
?>