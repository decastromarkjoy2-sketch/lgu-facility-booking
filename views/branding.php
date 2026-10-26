<?php
// Ensure file is loaded securely within database context
if (isset($pdo) === false) {
    require_once __DIR__ . '/../config/database.php';
}

// Handle form submission and database update
$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lgu_name        = $_POST['lgu_name'] ?? '';
    $province_name   = $_POST['province_name'] ?? '';
    $portal_subtitle = $_POST['portal_subtitle'] ?? '';
    $gso_hotline     = $_POST['gso_hotline'] ?? '';
    $gso_dept        = $_POST['gso_department'] ?? '';
    $buffer_mins     = $_POST['buffer_minutes'] ?? '30';
    $facility_stat   = $_POST['facility_status'] ?? 'active';

    // Prepare settings array to update via SQL
    $settings_to_update = [
        'lgu_name'        => $lgu_name,
        'province_name'   => $province_name,
        'portal_subtitle' => $portal_subtitle,
        'gso_hotline'     => $gso_hotline,
        'gso_department'  => $gso_dept,
        'buffer_minutes'  => $buffer_mins,
        'facility_status' => $facility_stat
    ];

    // Fixed: Updated to PostgreSQL native ON CONFLICT syntax
    $update_stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value");
    foreach ($settings_to_update as $key => $val) {
        $update_stmt->execute([$key, $val]);
    }

    // Handle logo file upload if provided
    if (isset($_FILES['lgu_logo']) && $_FILES['lgu_logo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['lgu_logo']['tmp_name'];
        $file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9._-]/", "", $_FILES['lgu_logo']['name']);
        $upload_dir = __DIR__ . '/../uploads/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
            $logo_url = 'uploads/' . $file_name;
            $update_stmt->execute(['lgu_logo', $logo_url]);
        }
    }

    $success_msg = "Settings & Branding Updated Successfully!";
}

// Fetch current values from database
$current_settings = [];
try {
    $res = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
        $current_settings[$r['setting_key']] = $r['setting_value'];
    }
} catch (Exception $e) {}
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-7">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5 mb-5">
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">LGU Branding & System Settings</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Customize municipal header titles, official seal, and scheduling rules.</p>
            </div>
            <span class="text-xs bg-emerald-100 text-emerald-800 font-mono font-bold px-3 py-1 rounded-full border border-emerald-200 shrink-0">Admin Mode Active</span>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                <span><strong><?= htmlspecialchars($success_msg) ?></strong> Changes have been applied live to the portal header.</span>
            </div>
        <?php endif; ?>

        <form action="index.php?tab=branding" method="POST" enctype="multipart/form-data" class="space-y-5">
            
            <!-- Section 1: Official Logo -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b pb-1">1. Official Municipal Seal / Logo</h3>
                <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div class="bg-emerald-950 p-2 rounded-xl border border-emerald-500/30 shrink-0">
                        <img src="<?= htmlspecialchars($current_settings['lgu_logo'] ?? 'https://placehold.co/100x100/166534/ffffff?text=TAGO+SEAL') ?>" class="w-12 h-12 object-contain rounded-lg" alt="Current Logo">
                    </div>
                    <div class="flex-1 space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Upload New Seal (PNG, JPG)</label>
                        <input type="file" name="lgu_logo" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition cursor-pointer">
                    </div>
                </div>
            </div>

            <!-- Section 2: Header Identity -->
            <div class="space-y-4 pt-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b pb-1">2. Header Text Identity</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-bold uppercase text-slate-700 mb-1">LGU / Municipality Name *</label>
                        <input type="text" name="lgu_name" value="<?= htmlspecialchars($current_settings['lgu_name'] ?? 'MUNICIPALITY OF TAGO') ?>" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-bold uppercase text-slate-700 mb-1">Province Label *</label>
                        <input type="text" name="province_name" value="<?= htmlspecialchars($current_settings['province_name'] ?? 'PROVINCE OF SURIGAO DEL SUR') ?>" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-bold uppercase text-slate-700 mb-1">Portal Subtitle / Tagline *</label>
                        <input type="text" name="portal_subtitle" value="<?= htmlspecialchars($current_settings['portal_subtitle'] ?? 'General Services Office (GSO) • Facility Booking Portal') ?>" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none" placeholder="e.g. General Services Office (GSO) • Facility Booking Portal">
                    </div>
                </div>
            </div>

            <!-- Section 3: GSO Contacts -->
            <div class="space-y-4 pt-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b pb-1">3. GSO Support Contacts (Shown on Passes)</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-bold uppercase text-slate-700 mb-1">GSO Office Hotline / Mobile *</label>
                        <input type="text" name="gso_hotline" value="<?= htmlspecialchars($current_settings['gso_hotline'] ?? '0912-345-6789 / (086) 213-4000') ?>" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-bold uppercase text-slate-700 mb-1">In-Charge Department *</label>
                        <input type="text" name="gso_department" value="<?= htmlspecialchars($current_settings['gso_department'] ?? 'General Services Office - LGU Tago') ?>" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Section 4: Parameters -->
            <div class="space-y-4 pt-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b pb-1">4. Scheduling & Conflict Parameters</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-bold uppercase text-slate-700 mb-1">Automatic Prep Buffer Between Events</label>
                        <select name="buffer_minutes" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <option value="30" <?= (($current_settings['buffer_minutes'] ?? '30') === '30') ? 'selected' : '' ?>>30 Minutes (Recommended)</option>
                            <option value="45" <?= (($current_settings['buffer_minutes'] ?? '') === '45') ? 'selected' : '' ?>>45 Minutes</option>
                            <option value="60" <?= (($current_settings['buffer_minutes'] ?? '') === '60') ? 'selected' : '' ?>>60 Minutes (1 Hour)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold uppercase text-slate-700 mb-1">Facility Availability Status</label>
                        <select name="facility_status" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <option value="active" <?= (($current_settings['facility_status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active & Accepting Public Reservations</option>
                            <option value="maintenance" <?= (($current_settings['facility_status'] ?? '') === 'maintenance') ? 'selected' : '' ?>>Maintenance Mode (Temporarily Block New Bookings)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Save Action Button -->
            <div class="pt-4 border-t border-slate-200 flex justify-center">
                <button type="submit" class="w-full sm:w-auto py-3 px-8 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs uppercase tracking-wider rounded-xl shadow-md transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Save Branding Settings
                </button>
            </div>
        </form>
    </div>
</div>