<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Strict restriction guard
require_admin();

$stmt = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-7 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-4 sm:pb-5 mb-4 sm:mb-5">
        <div>
            <h2 class="text-base sm:text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-clipboard-check text-emerald-600"></i> Commission on Audit (COA) Compliance Logs
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Immutable chronological audit trail records of all facility reservation activities[cite: 15].</p>
        </div>
        <span class="text-xs bg-slate-100 text-slate-700 font-mono font-bold px-3 py-1.5 rounded-xl border border-slate-200 shrink-0 self-start sm:self-auto">
            <?= count($logs) ?> Total Entries
        </span>
    </div>

    <!-- Logs Container -->
    <div class="space-y-2.5 font-mono text-xs">
        <?php if (empty($logs)): ?>
            <div class="p-8 text-center text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                <i class="fa-solid fa-folder-open text-2xl mb-2 text-slate-300"></i>
                <p>No audit trail records logged yet[cite: 15].</p>
            </div>
        <?php else: ?>
            <?php foreach ($logs as $log): 
                $action = strtoupper($log['action_type'] ?? 'LOG');
                
                // Dynamic Badge Color according to action
                $badge_class = 'bg-slate-100 text-slate-700 border-slate-300';
                if (str_contains($action, 'APPROV')) {
                    $badge_class = 'bg-emerald-50 text-emerald-800 border-emerald-300';
                } elseif (str_contains($action, 'REJECT') || str_contains($action, 'CANCEL')) {
                    $badge_class = 'bg-rose-50 text-rose-800 border-rose-300';
                } elseif (str_contains($action, 'CREATE') || str_contains($action, 'SUBMIT')) {
                    $badge_class = 'bg-sky-50 text-sky-800 border-sky-300';
                } elseif (str_contains($action, 'BRANDING') || str_contains($action, 'UPDATE')) {
                    $badge_class = 'bg-amber-50 text-amber-800 border-amber-300';
                }
            ?>
                <div class="p-3.5 rounded-xl bg-slate-50 hover:bg-slate-100/80 border border-slate-200/90 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <!-- Action Badge -->
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold border uppercase tracking-wider shrink-0 <?= $badge_class ?>">
                            <?= htmlspecialchars($action) ?>
                        </span>
                        
                        <!-- Log Message Details -->
                        <div>
                            <p class="text-slate-800 font-sans text-xs font-semibold leading-relaxed">
                                <?= htmlspecialchars($log['details']) ?>
                            </p>
                            <span class="text-slate-400 text-[11px] block mt-0.5 font-mono">
                                Performed by: <strong class="text-slate-600 font-semibold"><?= htmlspecialchars($log['performed_by']) ?></strong>
                            </span>
                        </div>
                    </div>

                    <!-- Timestamp -->
                    <div class="text-slate-400 text-[11px] font-mono shrink-0 text-left sm:text-right sm:border-l sm:border-slate-200 sm:pl-4 pt-2 sm:pt-0 border-t border-slate-200/60 sm:border-t-0">
                        <i class="fa-regular fa-clock mr-1 text-slate-400"></i>
                        <?= htmlspecialchars(date('M d, Y • h:i A', strtotime($log['created_at']))) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>