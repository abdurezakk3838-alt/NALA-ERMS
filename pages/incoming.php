<?php
// pages/incoming.php
$incoming_letters = getIncomingLetters();
$user_role = $_SESSION['user_role'] ?? 'staff';
$user_name = $_SESSION['user_name'] ?? '';
?>

<div class="flex items-center justify-between mb-4 flex-wrap gap-3">
    <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-inbox text-blue-600 mr-2"></i>Incoming Records</h2>
    <?php if (hasPermission('incoming')): ?>
    <button onclick="openRegisterModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold shadow-sm transition flex items-center gap-2">
        <i class="fas fa-plus"></i> New Incoming Letter
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
                <th>Sender</th>
                <th>Receiver</th>
                <th>Classification</th>
                <th>Security</th>
                <th>Attached</th>
                <th>Status</th>
                <th>Date</th>
                <th>QR Code</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="incomingTableBody">
            <?php foreach ($incoming_letters as $letter): 
                $is_receiver = ($letter['receiver_name'] === $user_name);
                $is_pending = in_array($letter['status'], ['pending', 'pending_approval']);
                $can_approve = $is_receiver && $is_pending && (in_array($user_role, ['dept_manager', 'admin', 'director', 'head_office', 'records_officer']));
                $can_edit = in_array($user_role, ['admin', 'records_officer', 'head_office']) && !in_array($letter['status'], ['approved', 'rejected', 'archived', 'dispatched'], true);
            ?>
            <tr>
                <td class="font-mono text-xs font-bold text-blue-600"><?php echo htmlspecialchars($letter['ref_number']); ?></td>
                <td class="font-mono text-xs text-gray-600"><?php echo htmlspecialchars($letter['sender_unique_num'] ?? 'N/A'); ?></td>
                <td>
                    <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($letter['title']); ?></div>
                    <?php if (!empty($letter['keywords'])): ?>
                        <div class="text-[0.7rem] text-blue-600 mt-1"><i class="fas fa-tags mr-1"></i><?php echo htmlspecialchars($letter['keywords']); ?></div>
                    <?php endif; ?>
                    <?php if ($letter['receiver_remark']): ?>
                        <div class="text-[0.7rem] text-slate-500 italic"><i class="fas fa-comment-alt text-amber-500 mr-1"></i>"<?php echo htmlspecialchars($letter['receiver_remark']); ?>"</div>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($letter['sender_name']); ?></td>
                <td>
                    <div class="font-medium text-xs text-gray-800"><?php echo htmlspecialchars($letter['receiver_name']); ?></div>
                    <div class="text-[0.7rem] text-blue-600"><i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($letter['receiver_email'] ?? 'N/A'); ?></div>
                </td>
                <td><?php echo htmlspecialchars($letter['classification'] ?? '1.01 General Administration'); ?></td>
                <td><?php echo htmlspecialchars($letter['security_level'] ?? 'Official'); ?></td>
                <td>
                    <?php if ($letter['file_name']): ?>
                        <span class="file-attachment-badge"><i class="fas fa-paperclip"></i> <?php echo htmlspecialchars($letter['file_name']); ?></span>
                    <?php endif; ?>
                </td>
                <td><?php echo getStatusPill($letter['status']); ?></td>
                <td>
                    <div><?php echo htmlspecialchars(date('d M Y', strtotime($letter['received_date']))); ?></div>
                    <div class="text-xs text-gray-500">
                        <?php if (!empty($letter['received_time'])): ?>
                            <?php echo htmlspecialchars(date('H:i', strtotime($letter['received_time']))); ?>
                        <?php else: ?>
                            Time not recorded
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <div class="qrcode-preview cursor-pointer hover:opacity-80 p-1 border rounded-lg bg-white inline-block shadow-sm" 
                         onclick='openLetterQr(<?php echo htmlspecialchars(json_encode([
                             "ref" => $letter["ref_number"], "title" => $letter["title"],
                             "sender" => $letter["sender_name"], "receiver" => $letter["receiver_name"],
                             "status" => $letter["status"]
                         ]), ENT_QUOTES, "UTF-8"); ?>)' title="Click to enlarge QR code and view information">
                        <div id="qr-<?php echo $letter['ref_number']; ?>"></div>
                    </div>
                </td>
                <td>
                    <div class="flex items-center gap-1 flex-wrap">
                        <button onclick="viewLetter('<?php echo $letter['ref_number']; ?>', 'incoming')" 
                                class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 px-2 py-1 rounded-lg transition flex items-center gap-1">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <?php if ($can_approve): ?>
                            <button onclick="openReviewIncoming('<?php echo $letter['ref_number']; ?>')" 
                                    class="text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 px-2 py-1 rounded-lg transition flex items-center gap-1 font-medium">
                                <i class="fas fa-signature"></i> Review
                            </button>
                        <?php endif; ?>
                        <?php if ($can_edit): ?>
                            <button onclick="editIncoming('<?php echo $letter['ref_number']; ?>')" 
                                    class="text-xs bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 px-2 py-1 rounded-lg transition flex items-center gap-1">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($incoming_letters)): ?>
            <tr><td colspan="12" class="text-center text-gray-400 py-8">No incoming letters found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-4 flex items-center justify-between text-sm text-gray-400">
    <span id="incomingCount">Showing <?php echo count($incoming_letters); ?> entries</span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($incoming_letters as $letter): ?>
        try {
            new QRCode(document.getElementById('qr-<?php echo $letter['ref_number']; ?>'), {
                text: <?php echo json_encode("Reference: {$letter['ref_number']} | Title: {$letter['title']} | Sender: {$letter['sender_name']} | Receiver: {$letter['receiver_name']} | Status: {$letter['status']}"); ?>,
                width: 45,
                height: 45,
                colorDark: '#0a1e3a',
                colorLight: '#ffffff'
            });
        } catch(e) {}
    <?php endforeach; ?>
});

function openRegisterModal() {
    document.getElementById('registerModal').classList.add('active');
    // Reset form
    document.getElementById('refNumber').value = '';
    document.getElementById('senderUniqueNum').value = '';
    document.getElementById('letterTitle').value = '';
    document.getElementById('letterDesc').value = '';
    document.getElementById('senderName').value = '<?php echo htmlspecialchars($user_name); ?>';
    document.getElementById('letterClassification').value = '1.01 General Administration';
    document.getElementById('letterSecurityLevel').value = 'Official';
    document.getElementById('letterKeywords').value = '';
    const now = new Date();
    document.getElementById('letterDate').value = [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, '0'),
        String(now.getDate()).padStart(2, '0')
    ].join('-');
    document.getElementById('letterTime').value = [
        String(now.getHours()).padStart(2, '0'),
        String(now.getMinutes()).padStart(2, '0')
    ].join(':');
    document.getElementById('editRefNumber').value = '';
    document.getElementById('dropZoneContent').style.display = 'block';
    document.getElementById('filePreview').style.display = 'none';
    document.getElementById('uploadStatus').textContent = '';
}

function editIncoming(ref) {
    // Fetch letter details via AJAX and populate form
    fetch('api/get_incoming.php?ref=' + encodeURIComponent(ref))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('refNumber').value = data.letter.ref_number;
                document.getElementById('senderUniqueNum').value = data.letter.sender_unique_num || '';
                document.getElementById('letterTitle').value = data.letter.title;
                document.getElementById('letterDesc').value = data.letter.description || '';
                document.getElementById('senderName').value = data.letter.sender_name;
                document.getElementById('receiverSelect').value = data.letter.receiver_name;
                document.getElementById('letterClassification').value = data.letter.classification || '1.01 General Administration';
                document.getElementById('letterSecurityLevel').value = data.letter.security_level || 'Official';
                document.getElementById('letterKeywords').value = data.letter.keywords || '';
                document.getElementById('letterDate').value = data.letter.received_date;
                document.getElementById('letterTime').value = data.letter.received_time
                    ? data.letter.received_time.slice(0, 5)
                    : '';
                document.getElementById('editRefNumber').value = data.letter.ref_number;
                document.getElementById('registerModal').classList.add('active');
                if (data.letter.file_name) {
                    document.getElementById('dropZoneContent').style.display = 'none';
                    document.getElementById('filePreview').style.display = 'flex';
                    document.getElementById('filePreview').innerHTML = 
                        '<i class="fas fa-file-pdf text-red-500 text-2xl"></i><span class="file-name">' + data.letter.file_name + '</span><span class="text-xs text-gray-400">(attached)</span>';
                    document.getElementById('uploadStatus').textContent = 'File already attached. You can replace it.';
                }
            }
        })
        .catch(err => console.error(err));
}
</script>
