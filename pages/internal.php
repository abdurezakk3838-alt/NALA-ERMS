<?php
// pages/internal.php
$internal_memos = getInternalMemos();
$user_role = $_SESSION['user_role'] ?? 'staff';
$user_name = $_SESSION['user_name'] ?? '';
?>

<div class="flex items-center justify-between mb-4 flex-wrap gap-3">
    <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-envelope text-violet-600 mr-2"></i>Internal Memos</h2>
    <?php if (hasPermission('internal')): ?>
    <button onclick="openRegisterInternalModal()" class="bg-violet-600 hover:bg-violet-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold shadow-sm transition flex items-center gap-2">
        <i class="fas fa-plus"></i> New Internal Memo
    </button>
    <?php endif; ?>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Ref #</th>
                <th>Title</th>
                <th>Receiver</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($internal_memos as $memo): 
                $can_approve = ($memo['status'] === 'pending' && $memo['receiver_name'] === $user_name && in_array($user_role, ['dept_manager', 'admin', 'director', 'head_office', 'records_officer']));
                $can_edit = in_array($user_role, ['admin', 'records_officer', 'head_office']) && !in_array($memo['status'], ['approved', 'rejected', 'archived', 'dispatched'], true);
            ?>
            <tr>
                <td class="font-mono text-xs font-bold text-violet-700"><?php echo htmlspecialchars($memo['ref_number']); ?></td>
                <td>
                    <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($memo['title']); ?></div>
                    <div class="text-[0.7rem] text-slate-500 mt-1"><?php echo htmlspecialchars($memo['description'] ?? ''); ?></div>
                </td>
                <td>
                    <div class="font-medium text-xs text-gray-800"><?php echo htmlspecialchars($memo['receiver_name']); ?></div>
                    <div class="text-[0.7rem] text-violet-600"><?php echo htmlspecialchars($memo['receiver_email'] ?? 'N/A'); ?></div>
                </td>
                <td><?php echo getStatusPill($memo['status']); ?></td>
                <td><?php echo htmlspecialchars($memo['date']); ?></td>
                <td>
                    <div class="flex items-center gap-1 flex-wrap">
                        <button onclick="viewLetter('<?php echo $memo['ref_number']; ?>', 'internal')" class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 px-2 py-1 rounded-lg transition flex items-center gap-1">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <?php if ($can_approve): ?>
                        <button onclick="openReviewInternal('<?php echo $memo['ref_number']; ?>')" class="text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 px-2 py-1 rounded-lg transition flex items-center gap-1 font-medium">
                            <i class="fas fa-signature"></i> Review
                        </button>
                        <?php endif; ?>
                        <?php if ($can_edit): ?>
                        <button onclick="editInternal('<?php echo $memo['ref_number']; ?>')" class="text-xs bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 px-2 py-1 rounded-lg transition flex items-center gap-1">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($internal_memos)): ?>
            <tr><td colspan="6" class="text-center text-gray-400 py-8">No internal memos found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function openRegisterInternalModal() {
    document.getElementById('registerInternalModal').classList.add('active');
    document.getElementById('internalRefNumber').value = '';
    document.getElementById('internalTitle').value = '';
    document.getElementById('internalDesc').value = '';
    document.getElementById('internalSenderName').value = '<?php echo htmlspecialchars($user_name); ?>';
    document.getElementById('internalReceiverSelect').value = '';
    document.getElementById('internalDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('internalEditRef').value = '';
    document.getElementById('internalDropZoneContent').style.display = 'block';
    document.getElementById('internalFilePreview').style.display = 'none';
    document.getElementById('internalUploadStatus').textContent = '';
    updateReceiverBadge(document.getElementById('internalReceiverSelect'), 'internalReceiverEmailBadge');
}

function editInternal(ref) {
    fetch('api/get_letter.php?ref=' + encodeURIComponent(ref) + '&type=internal')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('internalRefNumber').value = data.letter.ref_number || '';
                document.getElementById('internalTitle').value = data.letter.title || '';
                document.getElementById('internalDesc').value = data.letter.description || '';
                document.getElementById('internalSenderName').value = data.letter.sender_name || '';
                document.getElementById('internalReceiverSelect').value = data.letter.receiver_name || '';
                document.getElementById('internalDate').value = data.letter.date || '';
                document.getElementById('internalEditRef').value = data.letter.ref_number || '';
                updateReceiverBadge(document.getElementById('internalReceiverSelect'), 'internalReceiverEmailBadge');
                document.getElementById('registerInternalModal').classList.add('active');
                if (data.letter.file_name) {
                    document.getElementById('internalDropZoneContent').style.display = 'none';
                    document.getElementById('internalFilePreview').style.display = 'flex';
                    document.getElementById('internalFilePreview').innerHTML = '<i class="fas fa-file-pdf text-red-500 text-2xl"></i><span class="file-name">' + data.letter.file_name + '</span><span class="text-xs text-gray-400">(attached)</span>';
                    document.getElementById('internalUploadStatus').textContent = 'File already attached. You can replace it.';
                }
            }
        })
        .catch(err => console.error(err));
}
</script>
