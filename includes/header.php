<?php
$db_file = __DIR__ . '/../config/database.php';
if (file_exists($db_file)) {
    require_once $db_file;
}

$auth_file = __DIR__ . '/auth.php';
if (file_exists($auth_file)) {
    require_once $auth_file;
}

$settings = [];
try {
    $stmt_set = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    while ($row = $stmt_set->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}

$lgu_name = $settings['lgu_name'] ?? 'MUNICIPALITY OF TAGO';
$province_name = $settings['province_name'] ?? 'PROVINCE OF SURIGAO DEL SUR';
$portal_subtitle = $settings['portal_subtitle'] ?? 'General Services Office (GSO) • Facility Booking Portal';
$lgu_logo = $settings['lgu_logo'] ?? 'https://placehold.co/100x100/166534/ffffff?text=TAGO+SEAL';

$currentRole = $_SESSION['role'] ?? 'public';
$isAdmin = in_array($currentRole, ['admin', 'gso_admin', 'mayor_admin']);
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 overflow-x-hidden w-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>LGU Conference & Facility Booking System</title>
    
    <!-- PWA App Support Meta Tags & Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#065f46">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="LGU Booking">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    <script>
        console.log("[Sync Engine] Active with venue role routing.");

        let lastContentHash = null;

        setInterval(async function() {
            const activeTag = document.activeElement ? document.activeElement.tagName : '';
            if (activeTag === 'INPUT' || activeTag === 'TEXTAREA' || activeTag === 'SELECT') return;

            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'calendar';
            const m = urlParams.get('m') || '';
            const y = urlParams.get('y') || '';

            const apiUrl = 'api/get_schedule.php?tab=' + tab + '&m=' + m + '&y=' + y + '&_t=' + Date.now();

            try {
                const res = await fetch(apiUrl, { cache: 'no-store' });
                if (!res.ok) return;

                const data = await res.json();
                if (data.status === 'success') {
                    if (lastContentHash === null) {
                        lastContentHash = data.hash;
                        return;
                    }

                    if (lastContentHash !== data.hash) {
                        const targetContainer = document.getElementById('view-content-wrapper');
                        if (targetContainer) {
                            const currentScrollY = window.scrollY;
                            targetContainer.innerHTML = data.html;
                            if (window.Alpine) window.Alpine.initTree(targetContainer);
                            window.scrollTo(0, currentScrollY);
                        }
                        lastContentHash = data.hash;
                    }
                }
            } catch (err) {}
        }, 1000);
    </script>
</head>
<body class="h-full font-sans text-slate-800 antialiased selection:bg-emerald-500 selection:text-white overflow-x-hidden w-full">
<div class="min-h-full flex flex-col w-full overflow-x-hidden">
    
    <!-- Dynamic Header Banner -->
    <header class="bg-slate-900 text-white shadow-lg border-b border-slate-800 no-print w-full overflow-hidden">
        <div class="px-4 sm:px-6 lg:px-8 py-4 w-full">
            <div class="flex items-center gap-4">
                <div class="bg-emerald-950/90 p-2 rounded-2xl border border-emerald-500/30 shrink-0 flex items-center justify-center shadow-md">
                    <img src="<?= htmlspecialchars($lgu_logo) ?>" class="w-11 h-11 object-contain rounded-xl" alt="LGU Logo">
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-extrabold text-emerald-400 tracking-widest uppercase truncate"><?= htmlspecialchars($province_name) ?></p>
                    <h1 class="text-lg sm:text-xl font-black tracking-tight text-white truncate"><?= htmlspecialchars($lgu_name) ?></h1>
                    <p class="text-xs text-slate-300 font-medium mt-0.5 truncate"><?= htmlspecialchars($portal_subtitle) ?></p>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm no-print w-full overflow-hidden" x-data="{ loginModal: false, selectedRole: 'gso' }">
        <div class="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8 w-full">
            <div class="flex justify-between items-center py-2 gap-2 w-full overflow-x-auto no-scrollbar">
                <div class="flex space-x-1 sm:space-x-2 py-1 min-w-max">
                    <?php $tab = $_GET['tab'] ?? 'calendar'; ?>
                    <a href="index.php?tab=calendar" class="px-3 py-2 text-xs sm:text-sm border-b-2 rounded-t-lg transition flex items-center gap-1.5 whitespace-nowrap shrink-0 <?= $tab === 'calendar' ? 'border-emerald-600 text-emerald-700 bg-emerald-50/50 font-bold' : 'border-transparent text-slate-600 hover:text-slate-900' ?>">
                        <i class="fa-solid fa-calendar-days text-emerald-600"></i> Schedules & Calendar
                    </a>

                    <?php if ($isAdmin): ?>
                        <a href="index.php?tab=manage" class="px-3 py-2 text-xs sm:text-sm border-b-2 rounded-t-lg transition flex items-center gap-1.5 whitespace-nowrap shrink-0 <?= $tab === 'manage' ? 'border-emerald-600 text-emerald-700 bg-emerald-50/50 font-bold' : 'border-transparent text-slate-600 hover:text-slate-900' ?>">
                            <i class="fa-solid fa-list-check text-emerald-600"></i> Manage Requests
                            <span class="text-[9px] font-extrabold uppercase px-2 py-0.5 rounded-full <?= $currentRole === 'mayor_admin' ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800' ?>">
                                <?= $currentRole === 'mayor_admin' ? "Mayor's Office" : "GSO" ?>
                            </span>
                        </a>
                        <a href="index.php?tab=audit" class="px-3 py-2 text-xs sm:text-sm border-b-2 rounded-t-lg transition flex items-center gap-1.5 whitespace-nowrap shrink-0 <?= $tab === 'audit' ? 'border-emerald-600 text-emerald-700 bg-emerald-50/50 font-bold' : 'border-transparent text-slate-600 hover:text-slate-900' ?>">
                            <i class="fa-solid fa-clipboard-list text-emerald-600"></i> COA Audit Trail
                        </a>
                        <a href="index.php?tab=branding" class="px-3 py-2 text-xs sm:text-sm border-b-2 rounded-t-lg transition flex items-center gap-2 whitespace-nowrap shrink-0 <?= $tab === 'branding' ? 'border-emerald-600 text-emerald-700 bg-emerald-50/50 font-bold' : 'border-transparent text-slate-600 hover:text-slate-900' ?>">
                            <i class="fa-solid fa-sliders text-emerald-600"></i> Settings
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Right Side Admin Switcher -->
                <div class="flex items-center gap-2 shrink-0 pl-2">
                    <?php if ($isAdmin): ?>
                        <a href="actions/logout_admin.php" class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap">
                            <i class="fa-solid fa-right-from-bracket"></i> Exit <?= $currentRole === 'mayor_admin' ? "Mayor's Office" : "GSO Admin" ?>
                        </a>
                    <?php else: ?>
                        <button @click="loginModal = true" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap shadow">
                            <i class="fa-solid fa-user-shield"></i> LGU Admin Login
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Dual Authority Admin Login Modal -->
        <div x-show="loginModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <form action="actions/login_admin.php" method="POST" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-amber-600"></i> LGU Approval Desk Access
                    </h3>
                    <button type="button" @click="loginModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <p class="text-xs text-slate-500">Select administrative desk to process facility approvals and manage schedules.</p>

                <!-- Desk Selection Tabs -->
                <div class="grid grid-cols-2 gap-2 bg-slate-100 p-1 rounded-xl">
                    <button type="button" @click="selectedRole = 'gso'" :class="selectedRole === 'gso' ? 'bg-amber-600 text-white font-bold' : 'text-slate-600 font-semibold'" class="py-2 text-xs rounded-lg transition text-center">
                        <i class="fa-solid fa-building-user mr-1"></i> GSO Admin (Annex)
                    </button>
                    <button type="button" @click="selectedRole = 'mayor'" :class="selectedRole === 'mayor' ? 'bg-emerald-700 text-white font-bold' : 'text-slate-600 font-semibold'" class="py-2 text-xs rounded-lg transition text-center">
                        <i class="fa-solid fa-landmark mr-1"></i> Mayor's Office (Conf.)
                    </button>
                </div>

                <input type="hidden" name="admin_type" :value="selectedRole">

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Passcode</label>
                    <input type="password" name="admin_pass" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500" placeholder="••••••••">
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="loginModal = false" class="px-4 py-2 bg-slate-100 rounded-lg text-xs font-bold text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold shadow">
                        Authenticate Desk
                    </button>
                </div>
            </form>
        </div>
    </nav>