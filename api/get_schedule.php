<?php
require_once __DIR__ . '/../config/database.php';

// Prevent browser or proxy caching on HTTP headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Robust check: if m or y are empty strings or not set, default to current month and year
$month = (!empty($_GET['m']) && is_numeric($_GET['m'])) ? intval($_GET['m']) : date('n');
$year  = (!empty($_GET['y']) && is_numeric($_GET['y'])) ? intval($_GET['y']) : date('Y');
$tab   = $_GET['tab'] ?? 'calendar';

// Fetch all bookings for this specific month & year fresh from DB
$start_date = sprintf('%04d-%02d-01', $year, $month);
$days_in_month = date('t', mktime(0, 0, 0, $month, 1, $year));
$end_date = sprintf('%04d-%02d-%02d', $year, $month, $days_in_month);

$stmt = $pdo->prepare("SELECT * FROM bookings WHERE event_date BETWEEN ? AND ? ORDER BY start_time ASC");
$stmt->execute([$start_date, $end_date]);
$raw_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Metrics calculation for the calendar view
$total_month_bookings = count($raw_bookings);
$conf_room_count = count(array_filter($raw_bookings, fn($x) => $x['venue_id'] === 'conference-room'));
$annex_count = count(array_filter($raw_bookings, fn($x) => $x['venue_id'] === 'annex-building'));
$pending_count = count(array_filter($raw_bookings, fn($x) => $x['status'] === 'PENDING'));

$bookings_by_date = [];
foreach ($raw_bookings as $b) {
    $bookings_by_date[$b['event_date']][] = $b;
}

// Fetch system settings
$settings = [];
try {
    $stmt_set = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    while ($row = $stmt_set->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}

$facility_status = $settings['facility_status'] ?? 'active';
$is_maintenance  = ($facility_status === 'maintenance');
$gso_dept        = $settings['gso_department'] ?? 'General Services Office - LGU Tago';
$gso_hotline     = $settings['gso_hotline'] ?? '0912-345-6789 / (086) 213-4000';
$days_of_week = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

// Capture the view output buffer
ob_start();
if ($tab === 'calendar') {
    include __DIR__ . '/../views/calendar_view.php';
} elseif ($tab === 'manage') {
    include __DIR__ . '/../views/admin_manage.php';
}
$html = ob_get_clean();

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'html'   => $html,
    'hash'   => md5($html)
]);