<?php
// Get current month and year safely from query params or default to current date
$month = (!empty($_GET['m']) && is_numeric($_GET['m'])) ? intval($_GET['m']) : date('n');
$year  = (!empty($_GET['y']) && is_numeric($_GET['y'])) ? intval($_GET['y']) : date('Y');

// Month navigation calculations
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Calendar math
$first_day_timestamp = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day_timestamp);
$day_of_week = date('w', $first_day_timestamp); // 0 (Sun) to 6 (Sat)
$month_name = date('F Y', $first_day_timestamp);

// Fetch all bookings for this specific month & year
$start_date = sprintf('%04d-%02d-01', $year, $month);
$end_date = sprintf('%04d-%02d-%02d', $year, $month, $days_in_month);

$stmt = $pdo->prepare("SELECT * FROM bookings WHERE event_date BETWEEN ? AND ? ORDER BY start_time ASC");
$stmt->execute([$start_date, $end_date]);
$raw_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Metrics calculation
$total_month_bookings = count($raw_bookings);
$conf_room_count = count(array_filter($raw_bookings, fn($x) => $x['venue_id'] === 'conference-room'));
$annex_count = count(array_filter($raw_bookings, fn($x) => $x['venue_id'] === 'annex-building'));
$pending_count = count(array_filter($raw_bookings, fn($x) => $x['status'] === 'PENDING'));

// Group bookings by date (YYYY-MM-DD)
$bookings_by_date = [];
foreach ($raw_bookings as $b) {
    $bookings_by_date[$b['event_date']][] = $b;
}

// Fetch system settings for Maintenance Mode & GSO Contacts
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
?>

<div class="space-y-4 sm:space-y-6 w-full max-w-full overflow-hidden" x-data="{ venueFilter: 'all', selectedEvent: null }">

    <!-- Maintenance Mode Alert Banner -->
    <?php if ($is_maintenance): ?>
        <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-2xl shadow-sm flex items-start gap-3 border border-amber-200">
            <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg mt-0.5 shrink-0"></i>
            <div>
                <h3 class="text-sm font-bold text-amber-900">Online Booking Temporarily Unavailable</h3>
                <p class="text-xs text-amber-700 mt-0.5">
                    Public reservation requests are currently paused for schedule updates and system maintenance. 
                    For urgent booking inquiries, please reach out to <strong><?= htmlspecialchars($gso_dept) ?></strong> directly at <strong><?= htmlspecialchars($gso_hotline) ?></strong>.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Vibrant Colored KPI Stat Cards Banner -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 text-white p-3.5 sm:p-4.5 rounded-2xl shadow-md border border-emerald-500/30 flex items-center gap-3">
            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center shrink-0 font-bold text-white shadow-inner">
                <i class="fa-solid fa-calendar-check text-base sm:text-lg"></i>
            </div>
            <div>
                <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-emerald-100/90">Total Bookings</p>
                <p class="text-xl sm:text-2xl font-black text-white leading-tight"><?= $total_month_bookings ?></p>
            </div>
        </div>

        <div class="bg-gradient-to-br from-teal-600 to-cyan-700 text-white p-3.5 sm:p-4.5 rounded-2xl shadow-md border border-teal-500/30 flex items-center gap-3">
            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center shrink-0 font-bold text-white shadow-inner">
                <i class="fa-solid fa-people-roof text-base sm:text-lg"></i>
            </div>
            <div>
                <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-teal-100/90">Conference Room</p>
                <p class="text-xl sm:text-2xl font-black text-white leading-tight"><?= $conf_room_count ?></p>
            </div>
        </div>

        <div class="bg-gradient-to-br from-sky-600 to-indigo-700 text-white p-3.5 sm:p-4.5 rounded-2xl shadow-md border border-sky-500/30 flex items-center gap-3">
            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center shrink-0 font-bold text-white shadow-inner">
                <i class="fa-solid fa-building-user text-base sm:text-lg"></i>
            </div>
            <div>
                <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-sky-100/90">Annex Building</p>
                <p class="text-xl sm:text-2xl font-black text-white leading-tight"><?= $annex_count ?></p>
            </div>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-orange-600 text-white p-3.5 sm:p-4.5 rounded-2xl shadow-md border border-amber-400/30 flex items-center gap-3">
            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center shrink-0 font-bold text-white shadow-inner">
                <i class="fa-solid fa-hourglass-half text-base sm:text-lg"></i>
            </div>
            <div>
                <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-amber-100/90">Pending Review</p>
                <p class="text-xl sm:text-2xl font-black text-white leading-tight"><?= $pending_count ?></p>
            </div>
        </div>
    </div>

    <!-- Main Calendar Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-6">
        
        <!-- Header Controls -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4 border-b border-slate-200 pb-4 mb-4">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-base sm:text-xl font-extrabold text-slate-900 tracking-tight">Facility Schedule</h2>
                    <span class="text-[11px] sm:text-xs font-mono font-extrabold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200"><?= $month_name ?></span>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5">Interactive visual availability grid for LGU halls and facilities</p>
            </div>

            <div class="flex items-center gap-2 sm:gap-3 flex-wrap justify-between sm:justify-end">
                <!-- Venue Filter Switcher -->
                <div class="bg-slate-100 p-1 rounded-xl flex items-center text-[11px] sm:text-xs font-bold border border-slate-200">
                    <button @click="venueFilter = 'all'" :class="venueFilter === 'all' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-2.5 py-1.5 rounded-lg transition">All</button>
                    <button @click="venueFilter = 'conference-room'" :class="venueFilter === 'conference-room' ? 'bg-teal-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-2.5 py-1.5 rounded-lg transition">Conf. Room</button>
                    <button @click="venueFilter = 'annex-building'" :class="venueFilter === 'annex-building' ? 'bg-sky-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-2.5 py-1.5 rounded-lg transition">Annex</button>
                </div>

                <!-- Month Navigation Buttons -->
                <div class="flex items-center bg-slate-900 text-white p-1 rounded-xl shadow-xs">
                    <a href="index.php?tab=calendar&m=<?= $prev_month ?>&y=<?= $prev_year ?>" class="px-2.5 py-1.5 text-xs font-bold hover:bg-slate-800 rounded-lg transition text-emerald-400">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                    <span class="px-2.5 text-[11px] sm:text-xs font-mono font-extrabold tracking-wide text-white border-x border-slate-800"><?= strtoupper(date('M Y', $first_day_timestamp)) ?></span>
                    <a href="index.php?tab=calendar&m=<?= $next_month ?>&y=<?= $next_year ?>" class="px-2.5 py-1.5 text-xs font-bold hover:bg-slate-800 rounded-lg transition text-emerald-400">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Color Legend Bar -->
        <div class="flex items-center gap-2.5 sm:gap-3 text-[10px] sm:text-[11px] text-slate-600 mb-4 pb-3 border-b border-slate-100 font-semibold overflow-x-auto no-scrollbar">
            <span class="font-bold text-slate-800 uppercase tracking-wider shrink-0">Legend:</span>
            
            <div class="flex items-center gap-1.5 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200 shrink-0">
                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                <span class="text-emerald-900 font-bold">Approved</span>
            </div>

            <div class="flex items-center gap-1.5 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-200 shrink-0">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span class="text-amber-900 font-bold">Pending</span>
            </div>

            <div class="flex items-center gap-1.5 bg-rose-50 px-2.5 py-1 rounded-full border border-rose-200 shrink-0">
                <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                <span class="text-rose-900 font-bold">Rejected</span>
            </div>

            <div class="flex items-center gap-1.5 bg-teal-50 px-2.5 py-1 rounded-full border border-teal-200 shrink-0">
                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                <span class="text-teal-900 font-bold">Conf. Room</span>
            </div>

            <div class="flex items-center gap-1.5 bg-sky-50 px-2.5 py-1 rounded-full border border-sky-200 shrink-0">
                <span class="w-2 h-2 rounded-full bg-sky-600"></span>
                <span class="text-sky-900 font-bold">Annex Building</span>
            </div>
        </div>

        <!-- Days of Week Header -->
        <div class="grid grid-cols-7 gap-1.5 sm:gap-2 text-center font-black text-xs sm:text-sm uppercase tracking-wider text-white mb-2">
            <?php foreach ($days_of_week as $idx => $dow): ?>
                <div class="py-2 sm:py-2.5 rounded-lg sm:rounded-xl shadow-xs border-b-2 <?= ($idx === 0 || $idx === 6) ? 'bg-slate-800 border-indigo-400' : 'bg-slate-900 border-emerald-500' ?>">
                    <?= $dow ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Calendar Grid -->
        <div class="grid grid-cols-7 gap-1.5 sm:gap-2">
            <?php for ($i = 0; $i < $day_of_week; $i++): ?>
                <div class="min-h-[95px] sm:min-h-[135px] bg-slate-50/40 rounded-xl sm:rounded-2xl border border-dashed border-slate-200/80 p-1 opacity-25"></div>
            <?php endfor; ?>

            <?php 
            for ($day = 1; $day <= $days_in_month; $day++):
                $current_date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $is_today = ($current_date_str === date('Y-m-d'));
                $day_bookings = $bookings_by_date[$current_date_str] ?? [];
                
                $col_day_idx = ($day_of_week + $day - 1) % 7;
                $is_weekend = ($col_day_idx === 0 || $col_day_idx === 6);
            ?>
                <div class="group min-h-[105px] sm:min-h-[135px] rounded-xl sm:rounded-2xl border overflow-hidden transition-all flex flex-col justify-between <?= $is_today ? 'bg-gradient-to-b from-emerald-50 to-teal-50/50 border-emerald-500 ring-2 ring-emerald-500/30 shadow-sm' : ($is_weekend ? 'bg-slate-100/50 hover:bg-slate-100 border-slate-200' : 'bg-slate-50/30 hover:bg-white border-slate-200 hover:border-emerald-300 hover:shadow-xs') ?>">
                    
                    <div class="px-1.5 sm:px-2.5 py-1 flex items-center justify-between border-b <?= $is_today ? 'bg-emerald-100/80 border-emerald-200' : ($is_weekend ? 'bg-slate-200/60 border-slate-300/60' : 'bg-slate-100/80 border-slate-200/80') ?>">
                        <span class="text-[10px] sm:text-xs font-mono font-black <?= $is_today ? 'bg-emerald-600 text-white px-1.5 py-0.5 rounded-full shadow-xs' : 'text-slate-800 bg-white px-1.5 py-0.5 rounded-md border border-slate-200 shadow-2xs' ?>">
                            <?= $day ?>
                        </span>
                        <?php if ($is_today): ?>
                            <span class="text-[8px] sm:text-[9px] font-black tracking-wider bg-emerald-600 text-white px-1 rounded uppercase shadow-2xs">TODAY</span>
                        <?php elseif (!empty($day_bookings)): ?>
                            <span class="text-[9px] font-black text-emerald-800 bg-emerald-100 border border-emerald-300 px-1.5 py-0.2 rounded-full font-mono"><?= count($day_bookings) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="p-1 sm:p-2 space-y-1 flex-1 overflow-y-auto max-h-[90px] sm:max-h-[110px] flex flex-col justify-between">
                        <div class="space-y-1">
                            <?php foreach ($day_bookings as $b): 
                                $formatted_start = date("g:i A", strtotime($b['start_time']));
                                $event_payload = base64_encode(json_encode([
                                    'id' => $b['id'],
                                    'venueName' => $b['venueName'],
                                    'event_date' => $b['event_date'],
                                    'start_time' => date("g:i A", strtotime($b['start_time'])),
                                    'end_time' => date("g:i A", strtotime($b['end_time'])),
                                    'department' => $b['department'],
                                    'contact_name' => $b['contact_name'],
                                    'contact_info' => $b['contact_info'], // Included contact_info payload
                                    'event_title' => $b['event_title'],
                                    'status' => $b['status'],
                                    'rejection_reason' => $b['rejection_reason'] ?? $b['remarks'] ?? 'None specified'
                                ]));

                                // Dynamic CSS Classes based on status (Approved, Pending, Rejected)
                                if ($b['status'] === 'APPROVED') {
                                    $card_style = ($b['venue_id'] === 'conference-room') 
                                        ? 'bg-gradient-to-r from-teal-500 to-emerald-600 text-white border-teal-600 font-medium' 
                                        : 'bg-gradient-to-r from-sky-500 to-indigo-600 text-white border-sky-600 font-medium';
                                } elseif ($b['status'] === 'REJECTED') {
                                    $card_style = 'bg-gradient-to-r from-rose-500 to-red-600 text-white border-rose-600 font-bold opacity-80';
                                } else {
                                    // PENDING
                                    $card_style = 'bg-gradient-to-r from-amber-400 to-orange-500 text-slate-900 border-amber-500 font-bold';
                                }
                            ?>
                                <div x-show="venueFilter === 'all' || '<?= $b['venue_id'] ?>' === venueFilter"
                                     data-event="<?= $event_payload ?>"
                                     @click="selectedEvent = JSON.parse(atob($el.getAttribute('data-event')))"
                                     class="p-1 rounded-lg sm:rounded-xl text-[9px] sm:text-[10px] border cursor-pointer transition hover:scale-[1.02] shadow-2xs w-full <?= $card_style ?>">
                                    
                                    <div class="flex items-center justify-between font-bold">
                                        <span class="truncate max-w-[65%]" title="<?= htmlspecialchars($b['venueName']) ?>">
                                            <?= htmlspecialchars($b['venueName'] === 'Conference Room' ? 'Conf.' : 'Annex') ?>
                                        </span>
                                        <span class="font-mono text-[8px] sm:text-[9px] opacity-90"><?= $formatted_start ?></span>
                                    </div>

                                    <p class="font-semibold truncate text-white mt-0.5" title="<?= htmlspecialchars($b['event_title']) ?>">
                                        <?= htmlspecialchars($b['event_title']) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="pt-1 mt-1 border-t border-slate-200/60 text-right">
                            <?php if ($is_maintenance): ?>
                                <span class="text-[8px] font-bold text-slate-300 cursor-not-allowed">Paused</span>
                            <?php else: ?>
                                <a href="index.php?tab=request&date=<?= $current_date_str ?>" class="inline-flex items-center justify-center w-full sm:w-auto px-1.5 sm:px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[9px] sm:text-[10px] font-extrabold rounded-md sm:rounded-lg shadow-xs transition">
                                    <i class="fa-solid fa-plus text-[8px] mr-0.5"></i> Add
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            <?php endfor; ?>

            <?php 
            $total_cells_rendered = $day_of_week + $days_in_month;
            $remaining_cells = (7 - ($total_cells_rendered % 7)) % 7;
            for ($i = 0; $i < $remaining_cells; $i++):
            ?>
                <div class="min-h-[95px] sm:min-h-[135px] bg-slate-50/40 rounded-xl sm:rounded-2xl border border-dashed border-slate-200/80 p-1 opacity-25"></div>
            <?php endfor; ?>
        </div>

    </div>

    <!-- Public GSO Support Contact Footer Card -->
    <div class="bg-slate-900 text-white p-4 sm:p-5 rounded-2xl shadow-md flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4 border border-slate-800 text-center sm:text-left">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-950 border border-emerald-500/30 flex items-center justify-center text-emerald-400 shrink-0">
                <i class="fa-solid fa-headset text-lg"></i>
            </div>
            <div>
                <h4 class="text-xs font-bold uppercase text-emerald-400 tracking-wider">Need Booking Assistance?</h4>
                <p class="text-xs text-slate-300 font-medium"><?= htmlspecialchars($gso_dept) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2 bg-slate-800 px-4 py-2 rounded-xl border border-slate-700">
            <i class="fa-solid fa-phone text-emerald-400 text-xs"></i>
            <span class="text-xs font-mono font-bold text-white"><?= htmlspecialchars($gso_hotline) ?></span>
        </div>
    </div>

    <!-- Event Quick Detail Modal -->
    <div x-show="selectedEvent" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 border border-slate-100" @click.away="selectedEvent = null">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase px-2.5 py-1 rounded-full shadow-2xs" 
                          :class="{
                              'bg-emerald-100 text-emerald-800 border border-emerald-200': selectedEvent?.status === 'APPROVED',
                              'bg-amber-100 text-amber-800 border border-amber-200': selectedEvent?.status === 'PENDING',
                              'bg-rose-100 text-rose-800 border border-rose-200': selectedEvent?.status === 'REJECTED'
                          }" 
                          x-text="selectedEvent?.status"></span>
                    <h3 class="text-base font-bold text-slate-900 mt-2" x-text="selectedEvent?.event_title"></h3>
                </div>
                <button @click="selectedEvent = null" class="text-slate-400 hover:text-slate-600 p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <!-- Modal Details List with Contact Info Included -->
            <div class="space-y-2 text-xs text-slate-600 font-medium bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
                <div class="flex justify-between items-center border-b border-slate-200/60 pb-1.5"><span class="text-slate-400 font-bold uppercase text-[10px]">Venue:</span> <span class="font-bold text-slate-800" x-text="selectedEvent?.venueName"></span></div>
                <div class="flex justify-between items-center border-b border-slate-200/60 pb-1.5"><span class="text-slate-400 font-bold uppercase text-[10px]">Date:</span> <span class="font-mono font-bold text-slate-800" x-text="selectedEvent?.event_date"></span></div>
                <div class="flex justify-between items-center border-b border-slate-200/60 pb-1.5"><span class="text-slate-400 font-bold uppercase text-[10px]">Time Slot:</span> <span class="font-mono font-bold text-slate-800" x-text="(selectedEvent?.start_time || '') + ' - ' + (selectedEvent?.end_time || '')"></span></div>
                <div class="flex justify-between items-center border-b border-slate-200/60 pb-1.5"><span class="text-slate-400 font-bold uppercase text-[10px]">Department:</span> <span class="font-bold text-slate-800" x-text="selectedEvent?.department"></span></div>
                <div class="flex justify-between items-center border-b border-slate-200/60 pb-1.5"><span class="text-slate-400 font-bold uppercase text-[10px]">Contact Person:</span> <span class="font-bold text-slate-800" x-text="selectedEvent?.contact_name"></span></div>
                <div class="flex justify-between items-center"><span class="text-slate-400 font-bold uppercase text-[10px]">Contact Info:</span> <span class="font-mono font-extrabold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200" x-text="selectedEvent?.contact_info"></span></div>
                
                <template x-if="selectedEvent?.status === 'REJECTED'">
                    <div class="mt-2 pt-2 border-t border-slate-200">
                        <span class="text-rose-600 font-bold block mb-1">Reason for Rejection:</span>
                        <p class="text-slate-700 bg-rose-50 p-2 rounded-lg border border-rose-200 text-[11px]" x-text="selectedEvent?.rejection_reason"></p>
                    </div>
                </template>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="selectedEvent = null" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-xs font-bold text-slate-700">Close</button>
                <template x-if="selectedEvent?.status === 'APPROVED'">
                    <a :href="'index.php?tab=pass&id=' + selectedEvent?.id" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md">
                        <i class="fa-solid fa-id-card"></i> View Booking Pass
                    </a>
                </template>
            </div>
        </div>
    </div>

</div>