<?php
$auditLogs = getAuditLogs(250);
?>

<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Audit Trail</h1>
        <p class="mt-1 text-slate-500">A read-only record of sign-ins, user management, reports, and records activity.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <p class="text-sm text-slate-500">Showing the latest <?= count($auditLogs) ?> events</p>
            <input id="auditSearch" type="search" class="form-control md:w-80" placeholder="Search user, action, or detail" aria-label="Search audit trail" />
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date & time</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Details</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">IP address</th>
                    </tr>
                </thead>
                <tbody id="auditTable" class="divide-y divide-slate-100">
                    <?php if (!$auditLogs): ?>
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No audit events have been recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($auditLogs as $log): ?>
                            <tr class="audit-row hover:bg-slate-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-700"><?= htmlspecialchars(date('d M Y, H:i', strtotime($log['created_at']))) ?></td>
                                <td class="px-4 py-3 text-sm font-medium text-slate-800"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                                <td class="px-4 py-3 text-sm text-slate-600"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $log['user_role'] ?? 'unknown'))) ?></td>
                                <td class="px-4 py-3 text-sm font-medium text-sky-700"><?= htmlspecialchars($log['action'] ?? '') ?></td>
                                <td class="px-4 py-3 text-sm text-slate-600"><?= htmlspecialchars($log['details'] ?? '—') ?></td>
                                <td class="px-4 py-3 text-sm text-slate-500"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('auditSearch')?.addEventListener('input', function () {
    const query = this.value.toLowerCase();
    document.querySelectorAll('.audit-row').forEach(row => {
        row.hidden = !row.textContent.toLowerCase().includes(query);
    });
});
</script>
