<?php
$archivedRecords = getArchivedRecords();
$typeLabels = [
    'incoming_letters' => 'Incoming Letter',
    'outgoing_letters' => 'Outgoing Letter',
    'internal_memos' => 'Internal Memo'
];
$typeKeys = [
    'incoming_letters' => 'incoming',
    'outgoing_letters' => 'outgoing',
    'internal_memos' => 'internal'
];
?>

<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Archive</h1>
        <p class="mt-1 text-slate-500">Approved and rejected letters are automatically preserved here with their complete record and attachment.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <p class="text-sm text-slate-500"><?= count($archivedRecords) ?> archived document<?= count($archivedRecords) === 1 ? '' : 's' ?></p>
            <input id="archiveSearch" type="search" class="form-control md:w-80" placeholder="Search reference, title, sender, receiver, type, or status" aria-label="Search archive" />
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50"><tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Reference</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Document</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Sender</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Receiver</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Outcome</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Archived by</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Archived on</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$archivedRecords): ?>
                        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">No approved or rejected documents have been archived yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($archivedRecords as $record): ?>
                            <?php
                            $type = $typeKeys[$record['original_table']] ?? 'incoming';
                            $archivedData = json_decode($record['archived_data'] ?? '', true) ?: [];
                            ?>
                            <tr class="archive-row hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono text-sm font-semibold text-sky-700"><?= htmlspecialchars($record['ref_number'] ?? '') ?></td>
                                <td class="px-4 py-3"><div class="font-medium text-slate-800"><?= htmlspecialchars($record['title'] ?? '') ?></div><div class="text-xs text-slate-500"><?= htmlspecialchars($typeLabels[$record['original_table']] ?? 'Document') ?></div></td>
                                <td class="px-4 py-3 text-sm text-slate-600"><?= htmlspecialchars($archivedData['sender_name'] ?? '—') ?></td>
                                <td class="px-4 py-3 text-sm text-slate-600"><?= htmlspecialchars($archivedData['receiver_name'] ?? '—') ?></td>
                                <td class="px-4 py-3"><?= getStatusPill($record['document_status'] ?: 'archived') ?></td>
                                <td class="px-4 py-3 text-sm text-slate-600"><?= htmlspecialchars($record['archived_by'] ?? 'System') ?></td>
                                <td class="px-4 py-3 text-sm text-slate-600"><?= htmlspecialchars(date('d M Y, H:i', strtotime($record['archived_date']))) ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="viewArchivedLetter(<?= (int) $record['id'] ?>)" class="btn-outline text-xs"><i class="fas fa-eye mr-1"></i> Open</button>
                                        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                                            <button type="button" onclick="deleteArchivedLetter(<?= (int) $record['id'] ?>, '<?= str_replace("'", "\\'", htmlspecialchars($record['ref_number'] ?? '', ENT_QUOTES, 'UTF-8')) ?>')" class="btn-outline text-xs text-red-600 border-red-200 hover:bg-red-50"><i class="fas fa-trash mr-1"></i> Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('archiveSearch')?.addEventListener('input', function () {
    const query = this.value.toLowerCase();
    document.querySelectorAll('.archive-row').forEach(row => row.hidden = !row.textContent.toLowerCase().includes(query));
});
</script>
