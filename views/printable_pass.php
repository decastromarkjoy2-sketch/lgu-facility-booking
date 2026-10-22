<?php
$id = $_GET['id'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->execute([$id]);
$b = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$b) {
    echo "<div class='p-6 bg-white rounded-xl shadow-sm border border-slate-200 text-center'>Pass not found. <a href='index.php?tab=calendar' class='text-emerald-600 underline font-bold'>Back to Calendar</a></div>";
    return;
}

// Fetch dynamic branding and contact settings from database
$settings = [];
try {
    $stmt_set = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    while ($row = $stmt_set->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Fallback defaults if table is missing
}

$lgu_name      = $settings['lgu_name'] ?? 'MUNICIPALITY OF TAGO';
$province_name = $settings['province_name'] ?? 'PROVINCE OF SURIGAO DEL SUR';
$lgu_logo      = $settings['lgu_logo'] ?? 'https://placehold.co/100x100/166534/ffffff?text=TAGO+SEAL';
$gso_dept      = $settings['gso_department'] ?? 'General Services Office (GSO)';
$gso_hotline   = $settings['gso_hotline'] ?? '0912-345-6789 / (086) 213-4000';

// Generate Verification QR URL
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($b['id']);

$formatted_start = date("g:i A", strtotime($b['start_time']));
$formatted_end = date("g:i A", strtotime($b['end_time']));
$formatted_date = date("F j, Y", strtotime($b['event_date']));
?>

<style>
    @media print {
        body * { visibility: hidden; }
        #printable-pass, #printable-pass * { visibility: visible; }
        #printable-pass { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; margin: 0; padding: 20px; }
        .no-print { display: none !important; }
    }
</style>

<div class="bg-white rounded-2xl max-w-2xl w-full p-8 shadow-2xl border border-slate-200 mx-auto my-6" id="printable-pass">
    <!-- Official Header with QR Code -->
    <div class="flex items-center justify-between border-b-2 border-slate-800 pb-4 mb-6">
        <div class="flex items-center gap-4">
            <img src="<?= htmlspecialchars($lgu_logo) ?>" class="w-16 h-16 object-contain rounded-xl" alt="LGU Seal">
            <div>
                <p class="text-xs uppercase tracking-widest font-bold text-slate-500"><?= htmlspecialchars($province_name) ?></p>
                <h2 class="text-xl font-extrabold text-slate-900 uppercase tracking-tight"><?= htmlspecialchars($lgu_name) ?></h2>
                <p class="text-xs font-semibold text-slate-600"><?= htmlspecialchars($gso_dept) ?></p>
            </div>
        </div>
        <div class="text-right">
            <img src="<?= $qr_url ?>" class="w-16 h-16 rounded border p-0.5 inline-block" alt="Verification QR">
            <span class="block text-[9px] font-mono font-bold text-slate-500 mt-0.5">Scan to Verify</span>
        </div>
    </div>

    <!-- Badge Title -->
    <div class="text-center mb-6">
        <div class="inline-block bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
            Official Facility Booking Pass & Authorization
        </div>
    </div>

    <!-- Pass Details Grid -->
    <div class="grid grid-cols-2 gap-4 text-sm mb-6 bg-slate-50 p-4 rounded-xl border border-slate-200">
        <div>
            <span class="block text-xs uppercase font-bold text-slate-400">Control Number</span>
            <span class="font-mono font-bold text-slate-800 text-base"><?= htmlspecialchars($b['id']) ?></span>
        </div>
        <div>
            <span class="block text-xs uppercase font-bold text-slate-400">Status</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                ✓ APPROVED
            </span>
        </div>
        <div>
            <span class="block text-xs uppercase font-bold text-slate-400">Reserved Venue</span>
            <span class="font-bold text-slate-800"><?= htmlspecialchars($b['venueName']) ?></span>
        </div>
        <div>
            <span class="block text-xs uppercase font-bold text-slate-400">Date of Event</span>
            <span class="font-bold text-slate-800"><?= $formatted_date ?></span>
        </div>
        <div>
            <span class="block text-xs uppercase font-bold text-slate-400">Time Duration</span>
            <span class="font-bold text-slate-800"><?= $formatted_start ?> - <?= $formatted_end ?></span>
        </div>
        <div>
            <span class="block text-xs uppercase font-bold text-slate-400">Requesting Dept / Agency</span>
            <span class="font-bold text-slate-800"><?= htmlspecialchars($b['department']) ?></span>
        </div>
        <div class="col-span-2">
            <span class="block text-xs uppercase font-bold text-slate-400">Activity / Event Title</span>
            <span class="font-bold text-slate-900 text-base"><?= htmlspecialchars($b['event_title']) ?></span>
        </div>
        <div class="col-span-2">
            <span class="block text-xs uppercase font-bold text-slate-400">Purpose</span>
            <span class="text-slate-700"><?= htmlspecialchars($b['purpose']) ?></span>
        </div>
    </div>

    <!-- Terms and Rules Notice -->
    <div class="mb-6 p-3 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-900 space-y-1">
        <p class="font-bold uppercase tracking-wider">Facility Regulations Reminder:</p>
        <p>1. Present this permit pass to building security or the venue custodian prior to entry.</p>
        <p>2. Ensure cleanliness and proper shutdown of equipment immediately following the event duration.</p>
    </div>

    <!-- Official Signatures Block -->
    <div class="grid grid-cols-2 gap-8 pt-4 pb-6 border-t border-slate-300 text-center text-xs">
        <div>
            <div class="border-b border-slate-800 pb-1 font-bold text-slate-900 uppercase">
                <?= htmlspecialchars($b['contact_name']) ?>
            </div>
            <p class="text-[10px] text-slate-500 font-medium mt-0.5">Requesting Representative</p>
        </div>
        <div>
            <div class="border-b border-slate-800 pb-1 font-bold text-slate-900 uppercase">
                <?= htmlspecialchars($b['approval_office'] ?? 'LGU Approving Authority') ?>
            </div>
            <p class="text-[10px] text-slate-500 font-medium mt-0.5">Authorized Verifier / Office</p>
        </div>
    </div>

    <!-- GSO Contact Footer Note on Pass -->
    <div class="mb-6 p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600 flex items-center justify-between">
        <div>
            <p class="font-bold text-slate-800">For inquiries or schedule changes:</p>
            <p class="text-emerald-700 font-semibold"><i class="fa-solid fa-phone mr-1"></i> <?= htmlspecialchars($gso_hotline) ?></p>
        </div>
        <div class="text-right text-[10px] text-slate-400 font-mono uppercase">
            Official System Generated Pass
        </div>
    </div>

    <!-- Actions (Hidden on Print) -->
    <div class="flex items-center justify-between pt-4 border-t border-slate-200 no-print">
        <a href="index.php?tab=calendar" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">
            &larr; Back to Schedule
        </a>
        <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow flex items-center gap-1.5">
            <i class="fa-solid fa-print"></i> Print Official Pass
        </button>
    </div>
</div>