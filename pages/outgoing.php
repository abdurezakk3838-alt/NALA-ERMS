<?php
// pages/outgoing.php
$outgoing_letters = getOutgoingLetters();
$user_role = $_SESSION['user_role'] ?? 'staff';
$user_name = $_SESSION['user_name'] ?? '';
?>

<div class="flex items-center justify-between mb-4 flex-wrap gap-3">
    <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-paper-plane text-emerald-600 mr-2"></i>Outgoing Letters</h2>
    <?php if (hasPermission('outgoing')): ?>
    <button onclick="openRegisterOutgoingModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold shadow-sm transition flex items-center gap-2">
        <i class="fas fa-plus"></i> New Outgoing Letter
    </button>
    <?php endif; ?>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Ref #</th>
                <th>Sender Unique #</th>
                <th>Title</th>
                <th>Receiver</th>
                <th>Classification</th>
                <th>Security</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($outgoing_letters as $letter): 
                $can_approve = canApproveOutgoingLetter($letter);
                $can_register = canRegisterOutgoingLetter($letter);
                $can_dispatch = canDispatchOutgoingLetter($letter);
                $can_edit = in_array($user_role, ['admin', 'records_officer', 'head_office']) && !in_array($letter['status'], ['approved', 'rejected', 'archived', 'dispatched'], true);
            ?>
            <tr>
                <td class="font-mono text-xs font-bold text-emerald-700"><?php echo htmlspecialchars($letter['ref_number']); ?></td>
                <td class="font-mono text-xs text-slate-600"><?php echo htmlspecialchars($letter['sender_unique_num'] ?? 'N/A'); ?></td>
                <td>
                    <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($letter['title']); ?></div>
                    <div class="text-[0.7rem] text-slate-500 mt-1"><?php echo htmlspecialchars($letter['description'] ?? ''); ?></div>
                </td>
                <td>
                    <div class="font-medium text-xs text-gray-800"><?php echo htmlspecialchars($letter['receiver_name']); ?></div>
                    <div class="text-[0.7rem] text-emerald-600"><?php echo htmlspecialchars($letter['receiver_email'] ?? 'N/A'); ?></div>
                </td>
                <td><?php echo htmlspecialchars($letter['classification'] ?? '1.01 General Administration'); ?></td>
                <td><?php echo htmlspecialchars($letter['security_level'] ?? 'Official'); ?></td>
                <td><?php echo getStatusPill($letter['status']); ?></td>
                <td><?php echo htmlspecialchars($letter['date']); ?></td>
                <td>
                    <div class="flex items-center gap-1 flex-wrap">
                        <button onclick="viewLetter('<?php echo $letter['ref_number']; ?>', 'outgoing')" class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 px-2 py-1 rounded-lg transition flex items-center gap-1">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <?php if ($can_approve): ?>
                        <button onclick="openReviewOutgoing('<?php echo $letter['ref_number']; ?>')" class="text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 px-2 py-1 rounded-lg transition flex items-center gap-1 font-medium">
                            <i class="fas fa-signature"></i> Review
                        </button>
                        <?php endif; ?>
                        <?php if ($can_edit): ?>
                        <button onclick="editOutgoing('<?php echo $letter['ref_number']; ?>')" class="text-xs bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 px-2 py-1 rounded-lg transition flex items-center gap-1">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <?php endif; ?>
                        <?php if ($can_register): ?>
                        <button onclick="registerOutgoing('<?php echo $letter['ref_number']; ?>')" class="text-xs bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 px-2 py-1 rounded-lg transition flex items-center gap-1">
                            <i class="fas fa-stamp"></i> Register
                        </button>
                        <?php endif; ?>
                        <?php if ($can_dispatch): ?>
                        <button onclick="dispatchOutgoing('<?php echo $letter['ref_number']; ?>')" class="text-xs bg-violet-50 hover:bg-violet-100 text-violet-700 border border-violet-200 px-2 py-1 rounded-lg transition flex items-center gap-1">
                            <i class="fas fa-truck"></i> Dispatch
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($outgoing_letters)): ?>
            <tr><td colspan="8" class="text-center text-gray-400 py-8">No outgoing letters found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('outgoingReceiverSelect')?.addEventListener('change', function() {
        updateReceiverBadge(this, 'outgoingReceiverEmailBadge');
    });
});

function openRegisterOutgoingModal() {
    document.getElementById('registerOutgoingModal').classList.add('active');
    document.getElementById('outgoingRefNumber').value = '';
    document.getElementById('outgoingTitle').value = '';
    document.getElementById('outgoingDesc').value = '';
    document.getElementById('outgoingSenderName').value = '<?php echo htmlspecialchars($user_name); ?>';
    document.getElementById('outgoingSenderUniqueNum').value = '';
    document.getElementById('outgoingReceiverSelect').value = '';
    document.getElementById('outgoingClassification').value = '1.01 General Administration';
    document.getElementById('outgoingSecurityLevel').value = 'Official';
    document.getElementById('outgoingDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('outgoingEditRef').value = '';
    document.getElementById('outDropZoneContent').style.display = 'block';
    document.getElementById('outFilePreview').style.display = 'none';
    document.getElementById('outUploadStatus').textContent = '';
    updateReceiverBadge(document.getElementById('outgoingReceiverSelect'), 'outgoingReceiverEmailBadge');
}

function editOutgoing(ref) {
    fetch('api/get_letter.php?ref=' + encodeURIComponent(ref) + '&type=outgoing')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('outgoingRefNumber').value = data.letter.ref_number || '';
                document.getElementById('outgoingTitle').value = data.letter.title || '';
                document.getElementById('outgoingDesc').value = data.letter.description || '';
                document.getElementById('outgoingSenderName').value = data.letter.sender_name || '';
                document.getElementById('outgoingSenderUniqueNum').value = data.letter.sender_unique_num || '';
                document.getElementById('outgoingReceiverSelect').value = data.letter.receiver_name || '';
                document.getElementById('outgoingClassification').value = data.letter.classification || '1.01 General Administration';
                document.getElementById('outgoingSecurityLevel').value = data.letter.security_level || 'Official';
                document.getElementById('outgoingDate').value = data.letter.date || '';
                document.getElementById('outgoingEditRef').value = data.letter.ref_number || '';
                updateReceiverBadge(document.getElementById('outgoingReceiverSelect'), 'outgoingReceiverEmailBadge');
                document.getElementById('registerOutgoingModal').classList.add('active');
                if (data.letter.file_name) {
                    document.getElementById('outDropZoneContent').style.display = 'none';
                    document.getElementById('outFilePreview').style.display = 'flex';
                    document.getElementById('outFilePreview').innerHTML = '<i class="fas fa-file-pdf text-red-500 text-2xl"></i><span class="file-name">' + data.letter.file_name + '</span><span class="text-xs text-gray-400">(attached)</span>';
                    document.getElementById('outUploadStatus').textContent = 'File already attached. You can replace it.';
                }
            }
        })
        .catch(err => console.error(err));
}
</script>
