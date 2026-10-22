<?php
// Fetch dynamic settings for footer if $settings is not already set
if (isset($settings) === false) {
    $settings = [];
    try {
        $stmt_set = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        while ($row = $stmt_set->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {}
}

$lgu_name = $settings['lgu_name'] ?? 'MUNICIPALITY OF TAGO';
$gso_dept = $settings['gso_department'] ?? 'General Services Office';
$dev_attribution = $settings['developer_attribution'] ?? 'Designed & Developed by Engr. Mark Joy B. De Castro ECE, ECT.';
?>

<footer class="bg-white border-t border-slate-200 py-6 mt-auto no-print text-center">
    <div class="max-w-6xl mx-auto px-4 space-y-1">
        <p class="text-xs font-semibold text-slate-600">
            <?= htmlspecialchars($lgu_name) ?> • <?= htmlspecialchars($gso_dept) ?> v2.0
        </p>
        <p class="text-[11px] font-mono text-slate-400">
            Facility Booking System © <?= date('Y') ?> | <strong class="text-slate-600 font-semibold"><?= htmlspecialchars($dev_attribution) ?></strong>
        </p>
    </div>
</footer>