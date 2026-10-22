<?php
require_once __DIR__ . '/../config/database.php';

// Determine logged-in desk role
$currentRole = $_SESSION['role'] ?? 'admin';
$officeName  = $_SESSION['office_name'] ?? 'LGU Approval Desk';

// Venue-specific filtering query
if ($currentRole === 'mayor_admin') {
    // Mayor's Office Desk -> Conference Room Requests
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE venue_id = 'conference-room' ORDER BY created_at DESC");
    $stmt->execute();
    $desk_title = "Mayor's Office Approval Desk";
    $desk_subtitle = "Review and process Conference Room reservation requests for executive approval.";
    $desk_icon = "fa-landmark";
    $badge_color = "bg-indigo-100 text-indigo-800 border-indigo-200";
} elseif ($currentRole === 'gso_admin') {
    // General Services Office Desk -> Annex Building Requests
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE venue_id = 'annex-building' ORDER BY created_at DESC");
    $stmt->execute();
    $desk_title = "GSO Approval Desk";
    $desk_subtitle = "Review Annex Building room requests, evaluate schedule status, and manage passes.";
    $desk_icon = "fa-building-user";
    $badge_color = "bg-amber-100 text-amber-800 border-amber-200";
} else {
    // Master Administrator -> All Requests
    $stmt = $pdo->query("SELECT * FROM bookings ORDER BY created_at DESC");
    $desk_title = "LGU Central Approval Desk";
    $desk_subtitle = "Master administration for all LGU halls, conference rooms, and facility requests.";
    $desk_icon = "fa-shield-halved";
    $badge_color = "bg-emerald-100 text-emerald-800 border-emerald-200";
}

$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
$pending_count = count(array_filter($bookings, fn($x) => $x['status'] === 'PENDING'));
?>
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-7 max-w-6xl mx-auto overflow-hidden" x-data="{ rejectModal: false, rejectId: '', eventTitle: '' }">
    <!-- Dynamic Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 border-b border-slate-200 pb-4 sm:pb-5 mb-4 sm:mb-5">
        <div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <h2 class="text-base sm:text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="fa-solid <?= $desk_icon ?> text-emerald-600"></i> <?= htmlspecialchars($desk_title) ?>
                </h2>
                <span class="text-[10px] sm:text-xs font-mono font-extrabold px-2.5 py-0.5 rounded-full border <?= $badge_color ?>">
                    <?= htmlspecialchars($officeName) ?>
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5"><?= htmlspecialchars($desk_subtitle) ?></p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <span class="text-xs font-mono font-bold px-3 py-1.5 rounded-xl border <?= $pending_count > 0 ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-slate-100 text-slate-600 border-slate-200' ?>">
                <i class="fa-solid fa-clock mr-1"></i> <?= $pending_count ?> Pending Request<?= $pending_count !== 1 ? 's' : '' ?>
            </span>
        </div>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="p-8 text-center text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
            <i class="fa-solid fa-inbox text-2xl mb-2 text-slate-300"></i>
            <p class="font-medium text-xs">No booking requests assigned to this desk yet.</p>
        </div>
    <?php else: ?>

        <!-- MOBILE VIEW: Stacked Cards (Visible on Phones) -->
        <div class="block md:hidden space-y-3 w-full">
            <?php foreach ($bookings as $b): 
                $formatted_start = date("g:i A", strtotime($b['start_time']));
                $formatted_end = date("g:i A", strtotime($b['end_time']));
            ?>
                <div class="bg-slate-50 rounded-2xl border border-slate-200 p-3.5 space-y-3 shadow-2xs w-full overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                        <span class="font-mono font-bold text-xs bg-white px-2 py-0.5 rounded border border-slate-200 text-slate-700 truncate max-w-[140px]">
                            <?= htmlspecialchars($b['id']) ?>
                        </span>
                        <?php if ($b['status'] === 'APPROVED'): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200 shrink-0">
                                <i class="fa-solid fa-circle-check text-emerald-600"></i> Approved
                            </span>
                        <?php elseif ($b['status'] === 'PENDING'): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-amber-100 text-amber-800 border border-amber-200 shrink-0">
                                <i class="fa-solid fa-hourglass-half text-amber-600"></i> Pending
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-rose-100 text-rose-800 border border-rose-200 shrink-0">
                                <i class="fa-solid fa-circle-xmark text-rose-600"></i> <?= htmlspecialchars($b['status']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="min-w-0">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Event & Venue</span>
                        <h3 class="font-extrabold text-slate-900 text-sm truncate" title="<?= htmlspecialchars($b['event_title']) ?>"><?= htmlspecialchars($b['event_title']) ?></h3>
                        <p class="text-xs font-semibold text-emerald-700 truncate"><?= htmlspecialchars($b['venueName']) ?></p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs bg-white p-2.5 rounded-xl border border-slate-200/80 font-mono overflow-hidden">
                        <div class="min-w-0">
                            <span class="text-[10px] text-slate-400 block font-sans uppercase font-bold">Date & Time</span>
                            <span class="text-slate-800 font-bold block truncate"><?= htmlspecialchars($b['event_date']) ?></span>
                            <span class="text-slate-500 block text-[11px] truncate"><?= $formatted_start ?> - <?= $formatted_end ?></span>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[10px] text-slate-400 block font-sans uppercase font-bold">Contact</span>
                            <span class="text-slate-800 font-bold block truncate"><?= htmlspecialchars($b['contact_name']) ?></span>
                            <span class="text-slate-500 block text-[11px] truncate"><?= htmlspecialchars($b['department']) ?></span>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-200/60">
                        <?php if ($b['status'] === 'PENDING'): ?>
                            <a href="actions/approve_booking.php?id=<?= $b['id'] ?>" class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-extrabold text-xs text-center transition shadow-xs flex items-center justify-center gap-1">
                                <i class="fa-solid fa-check"></i> Approve
                            </a>
                            <button @click="rejectModal = true; rejectId = '<?= $b['id'] ?>'; eventTitle = '<?= htmlspecialchars(addslashes($b['event_title'])) ?>'" class="flex-1 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-extrabold text-xs text-center transition shadow-xs flex items-center justify-center gap-1">
                                <i class="fa-solid fa-xmark"></i> Reject
                            </button>
                            <a href="actions/delete_booking.php?id=<?= $b['id'] ?>" onclick="return confirm('Permanently delete this pending request?')" class="py-2 px-3 bg-slate-200 hover:bg-rose-600 hover:text-white text-slate-700 rounded-xl font-bold text-xs transition flex items-center justify-center" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        <?php elseif ($b['status'] === 'REJECTED'): ?>
                            <a href="index.php?tab=pass&id=<?= $b['id'] ?>" class="flex-1 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl font-bold text-xs text-center transition flex items-center justify-center gap-1.5 shadow-2xs">
                                <i class="fa-solid fa-print text-slate-500"></i> Pass
                            </a>
                            <a href="actions/delete_booking.php?id=<?= $b['id'] ?>" onclick="return confirm('Permanently delete this rejected request?')" class="py-2 px-3 bg-slate-200 hover:bg-rose-600 hover:text-white text-slate-700 rounded-xl font-bold text-xs transition flex items-center justify-center" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        <?php else: ?>
                            <a href="index.php?tab=pass&id=<?= $b['id'] ?>" class="w-full py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl font-bold text-xs text-center transition flex items-center justify-center gap-1.5 shadow-2xs">
                                <i class="fa-solid fa-print text-slate-500"></i> View Official Pass
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- DESKTOP / TABLET VIEW: Scrollable Table (Hidden on Phones) -->
        <div class="hidden md:block overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-xs border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-slate-900 text-slate-200 uppercase font-bold text-[11px] tracking-wider">
                        <th class="p-3.5">Control ID</th>
                        <th class="p-3.5">Venue & Date</th>
                        <th class="p-3.5">Event & Agency</th>
                        <th class="p-3.5">Contact Person</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    <?php foreach ($bookings as $b): 
                        $formatted_start = date("g:i A", strtotime($b['start_time']));
                        $formatted_end = date("g:i A", strtotime($b['end_time']));
                    ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3.5 font-mono font-bold text-slate-900 shrink-0">
                                <span class="bg-slate-100 px-2 py-1 rounded border border-slate-200 text-slate-700"><?= htmlspecialchars($b['id']) ?></span>
                            </td>
                            <td class="p-3.5">
                                <p class="font-bold text-slate-800"><?= htmlspecialchars($b['venueName']) ?></p>
                                <span class="font-mono text-slate-500 text-[11px] block mt-0.5">
                                    <i class="fa-regular fa-calendar text-slate-400 mr-1"></i><?= htmlspecialchars($b['event_date']) ?> 
                                    <br>
                                    <i class="fa-regular fa-clock text-slate-400 mr-1"></i><?= $formatted_start ?> - <?= $formatted_end ?>
                                </span>
                            </td>
                            <td class="p-3.5 max-w-[220px]">
                                <p class="font-bold text-slate-900 truncate" title="<?= htmlspecialchars($b['event_title']) ?>"><?= htmlspecialchars($b['event_title']) ?></p>
                                <span class="text-slate-500 font-medium block truncate" title="<?= htmlspecialchars($b['department']) ?>"><?= htmlspecialchars($b['department']) ?></span>
                            </td>
                            <td class="p-3.5">
                                <p class="font-bold text-slate-800"><?= htmlspecialchars($b['contact_name']) ?></p>
                                <span class="font-mono text-slate-500 text-[11px]"><?= htmlspecialchars($b['contact_info']) ?></span>
                            </td>
                            <td class="p-3.5">
                                <?php if ($b['status'] === 'APPROVED'): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <i class="fa-solid fa-circle-check text-emerald-600"></i> Approved
                                    </span>
                                <?php elseif ($b['status'] === 'PENDING'): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-amber-100 text-amber-800 border border-amber-200">
                                        <i class="fa-solid fa-hourglass-half text-amber-600"></i> Pending
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-rose-100 text-rose-800 border border-rose-200" title="<?= htmlspecialchars($b['rejection_reason'] ?? '') ?>">
                                        <i class="fa-solid fa-circle-xmark text-rose-600"></i> <?= htmlspecialchars($b['status']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3.5 text-right whitespace-nowrap">
                                <?php if ($b['status'] === 'PENDING'): ?>
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="actions/approve_booking.php?id=<?= $b['id'] ?>" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-extrabold transition shadow-2xs flex items-center gap-1 text-[11px]">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </a>
                                        <button @click="rejectModal = true; rejectId = '<?= $b['id'] ?>'; eventTitle = '<?= htmlspecialchars(addslashes($b['event_title'])) ?>'" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-extrabold transition shadow-2xs flex items-center gap-1 text-[11px]">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </button>
                                        <a href="actions/delete_booking.php?id=<?= $b['id'] ?>" onclick="return confirm('Permanently delete this pending request?')" class="px-2.5 py-1.5 bg-slate-200 hover:bg-rose-600 hover:text-white text-slate-700 rounded-lg font-bold transition text-[11px]" title="Delete Request">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                <?php elseif ($b['status'] === 'REJECTED'): ?>
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="index.php?tab=pass&id=<?= $b['id'] ?>" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-lg font-bold text-[11px] transition inline-flex items-center gap-1">
                                            <i class="fa-solid fa-print text-slate-500"></i> Pass
                                        </a>
                                        <a href="actions/delete_booking.php?id=<?= $b['id'] ?>" onclick="return confirm('Permanently delete this rejected request?')" class="px-2.5 py-1.5 bg-slate-200 hover:bg-rose-600 hover:text-white text-slate-700 rounded-lg font-bold transition text-[11px]" title="Delete Request">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <a href="index.php?tab=pass&id=<?= $b['id'] ?>" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-lg font-bold text-[11px] transition inline-flex items-center gap-1">
                                        <i class="fa-solid fa-print text-slate-500"></i> Pass
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Rejection Modal -->
    <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <form action="actions/reject_booking.php" method="POST" class="bg-white rounded-2xl max-w-md w-full p-5 sm:p-6 shadow-2xl border border-slate-100 space-y-4" @click.away="rejectModal = false">
            <input type="hidden" name="booking_id" x-model="rejectId">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Reject Booking Request
                </h3>
                <button type="button" @click="rejectModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <div class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-200">
                <span class="text-slate-400 block uppercase font-bold text-[10px]">Event Title:</span>
                <p class="font-bold text-slate-800 truncate" x-text="eventTitle"></p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Reason for Rejection *</label>
                <textarea name="reject_reason" rows="3" required class="w-full p-3 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none" placeholder="Explain schedule conflict or missing document reasons..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" @click="rejectModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-xs font-bold text-slate-700">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-extrabold shadow-md flex items-center gap-1.5">
                    <i class="fa-solid fa-ban"></i> Confirm Rejection
                </button>
            </div>
        </form>
    </div>
</div>