<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch today's approved bookings for the occupancy card
$today_str = date('Y-m-d');
$stmt_today = $pdo->prepare("SELECT * FROM bookings WHERE event_date = ? AND status = 'APPROVED' ORDER BY start_time ASC");
$stmt_today->execute([$today_str]);
$today_bookings = $stmt_today->fetchAll(PDO::FETCH_ASSOC);

// Capture pre-filled date or retained old inputs from session
$prefilled_date = $_GET['date'] ?? '';
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']); // Clear after retrieving

$selected_venue   = $old['venueId'] ?? 'conference-room';
$selected_date    = $old['date'] ?? $prefilled_date;
$start_time       = $old['startTime'] ?? '';
$end_time         = $old['endTime'] ?? '';
$raw_department   = $old['department'] ?? '';
$contact_name     = $old['contactName'] ?? '';
$contact_info     = $old['contactInfo'] ?? '';
$attendees        = $old['attendees'] ?? '';
$event_title      = $old['eventTitle'] ?? '';
$purpose          = $old['purpose'] ?? '';

// Handle pre-filled or old department selection logic for "Others"
$predefined_depts = [
    'Office of the Mayor',
    'Office of the Sangguniang Bayan',
    'MDRRMO',
    'Municipal Budget Office',
    'General Services Office (GSO)',
    'External / Barangay / Public'
];

if (!empty($raw_department) && !in_array($raw_department, $predefined_depts)) {
    $department = 'Others';
    $department_other = $raw_department;
} else {
    $department = $raw_department;
    $department_other = $old['department_other'] ?? '';
}
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6 items-start">
    
    <!-- LEFT COLUMN: Main Booking Form -->
    <div class="lg:col-span-8 bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-7">
        <div class="border-b border-slate-200 pb-4 mb-5 sm:mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="text-base sm:text-xl font-extrabold text-slate-900 tracking-tight">MUNICIPALITY OF TAGO - Booking Application</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Submit official facility reservation request for LGU approval</p>
            </div>
            <span class="text-[11px] sm:text-xs bg-slate-100 text-slate-600 px-3 py-1 rounded-full font-mono font-semibold shrink-0 self-start sm:self-auto">GSO Form 102</span>
        </div>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'conflict'): ?>
            <div class="mb-5 p-3.5 sm:p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs sm:text-sm flex items-center gap-2.5">
                <i class="fa-solid fa-triangle-exclamation text-red-600 text-base shrink-0"></i>
                <span><strong>Schedule Conflict Detected!</strong> The requested venue is already booked for that timeframe. Please adjust your time below.</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="mb-5 p-3.5 sm:p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                <span><strong>Reservation Filed Successfully!</strong> Control Ref No: <span class="font-mono font-bold"><?= htmlspecialchars($_GET['id'] ?? '') ?></span></span>
            </div>
        <?php endif; ?>

        <form action="actions/submit_booking.php" method="POST" class="space-y-4 sm:space-y-5" x-data="{ venueId: '<?= htmlspecialchars($selected_venue) ?>', dept: '<?= htmlspecialchars($department) ?>' }">
            
            <!-- Venue Cards -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Select Requested Venue *</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <label :class="venueId === 'conference-room' ? 'ring-2 ring-emerald-500 bg-emerald-50/50 border-emerald-300' : 'bg-slate-50 border-slate-200 hover:border-slate-300'" class="relative border rounded-xl p-3.5 sm:p-4 cursor-pointer transition flex flex-col justify-between">
                        <input type="radio" name="venueId" value="conference-room" x-model="venueId" class="sr-only" required>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-bold text-slate-900 text-xs sm:text-sm">Conference Room</span>
                                <span class="text-[10px] sm:text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold">Cap: 150</span>
                            </div>
                            <p class="text-[11px] sm:text-xs text-slate-500">Ideal for SB Sessions, Public Hearings, & General Assemblies</p>
                        </div>
                    </label>

                    <label :class="venueId === 'annex-building' ? 'ring-2 ring-emerald-500 bg-emerald-50/50 border-emerald-300' : 'bg-slate-50 border-slate-200 hover:border-slate-300'" class="relative border rounded-xl p-3.5 sm:p-4 cursor-pointer transition flex flex-col justify-between">
                        <input type="radio" name="venueId" value="annex-building" x-model="venueId" class="sr-only" required>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-bold text-slate-900 text-xs sm:text-sm">Annex Building</span>
                                <span class="text-[10px] sm:text-xs px-2 py-0.5 rounded bg-sky-100 text-sky-800 font-bold">Cap: 40</span>
                            </div>
                            <p class="text-[11px] sm:text-xs text-slate-500">Best for Departmental Meetings & Small Workshops</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Date and Time Fields -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Date of Event *</label>
                    <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Start Time *</label>
                    <input type="time" name="startTime" value="<?= htmlspecialchars($start_time) ?>" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">End Time *</label>
                    <input type="time" name="endTime" value="<?= htmlspecialchars($end_time) ?>" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
            </div>

            <!-- Department and Contact -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Department / Unit *</label>
                    <select name="department" x-model="dept" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="">Select Office / Department...</option>
                        <option value="Office of the Mayor">Office of the Mayor</option>
                        <option value="Office of the Sangguniang Bayan">Office of the Sangguniang Bayan</option>
                        <option value="MDRRMO">MDRRMO</option>
                        <option value="Municipal Budget Office">Municipal Budget Office</option>
                        <option value="General Services Office (GSO)">General Services Office (GSO)</option>
                        <option value="External / Barangay / Public">External / Barangay / Public</option>
                        <option value="Others">Others (Specify below)</option>
                    </select>

                    <!-- Conditional Text Input for 'Others' -->
                    <div x-show="dept === 'Others'" x-cloak class="mt-2.5">
                        <input type="text" name="department_other" value="<?= htmlspecialchars($department_other) ?>" placeholder="Please specify exact department or organization..." class="w-full px-3 py-2.5 border border-emerald-400 bg-emerald-50/50 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none font-semibold text-slate-800">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Contact Person *</label>
                    <input type="text" name="contactName" value="<?= htmlspecialchars($contact_name) ?>" placeholder="e.g., Juan Dela Cruz" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Contact Email / Phone *</label>
                    <input type="text" name="contactInfo" value="<?= htmlspecialchars($contact_info) ?>" placeholder="0917-XXX-XXXX" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Est. Attendees *</label>
                    <input type="number" name="attendees" value="<?= htmlspecialchars($attendees) ?>" min="1" max="200" placeholder="e.g. 45" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Activity / Meeting Title *</label>
                <input type="text" name="eventTitle" value="<?= htmlspecialchars($event_title) ?>" placeholder="e.g., MDRRMO Disaster Orientation" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Purpose / Agenda Summary *</label>
                <textarea name="purpose" rows="3" placeholder="Briefly describe purpose of event..." required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"><?= htmlspecialchars($purpose) ?></textarea>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3.5 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs sm:text-sm rounded-xl shadow-md transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Submit Booking Request for Verification
                </button>
            </div>
        </form>
    </div>

    <!-- RIGHT COLUMN: Compact Sidebar Cards -->
    <div class="lg:col-span-4 space-y-4">
        
        <!-- Compact Schedule Panel -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-2.5 flex items-center justify-between">
                <span><i class="fa-solid fa-clock text-emerald-600 mr-1"></i> Today's Schedule</span>
                <span class="font-mono text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded"><?= $today_str ?></span>
            </h3>

            <div class="space-y-2">
                <?php foreach ($today_bookings as $tb): ?>
                    <div class="p-2.5 rounded-lg border border-slate-100 bg-slate-50 text-[11px] space-y-0.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800"><?= htmlspecialchars($tb['venueName']) ?></span>
                            <span class="font-mono text-[10px] font-semibold text-emerald-800 bg-emerald-100 px-1.5 py-0.5 rounded"><?= htmlspecialchars($tb['start_time']) ?> - <?= htmlspecialchars($tb['end_time']) ?></span>
                        </div>
                        <p class="font-medium text-slate-700 truncate"><?= htmlspecialchars($tb['event_title']) ?></p>
                        <p class="text-[10px] text-slate-500"><?= htmlspecialchars($tb['department']) ?></p>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($today_bookings)): ?>
                    <div class="text-center py-4 text-slate-400">
                        <i class="fa-solid fa-calendar-check text-xl mb-1 text-slate-300"></i>
                        <p class="text-[11px] italic">No approved reservations today.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Compact Regulations Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-2.5 flex items-center gap-1.5">
                <i class="fa-solid fa-shield-halved text-emerald-600"></i> Booking Regulations
            </h3>
            <ul class="text-[11px] space-y-2 text-slate-600 leading-snug">
                <li class="flex items-start gap-1.5">
                    <i class="fa-solid fa-check text-emerald-600 text-[10px] mt-0.5 shrink-0"></i>
                    <span><strong>30-Min Buffer:</strong> Auto prep buffer between events.</span>
                </li>
                <li class="flex items-start gap-1.5">
                    <i class="fa-solid fa-check text-emerald-600 text-[10px] mt-0.5 shrink-0"></i>
                    <span><strong>Priority Protocol:</strong> SB & Emergency MDRRMO hold priority.</span>
                </li>
                <li class="flex items-start gap-1.5">
                    <i class="fa-solid fa-check text-emerald-600 text-[10px] mt-0.5 shrink-0"></i>
                    <span><strong>Lead Time:</strong> File at least 48 hours in advance.</span>
                </li>
            </ul>
        </div>

    </div>
</div>