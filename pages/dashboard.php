<?php
// pages/dashboard.php
if (($_SESSION['user_role'] ?? '') === 'archivist') {
    include __DIR__ . '/archive.php';
    return;
}

if (($_SESSION['user_role'] ?? '') === 'intermediary') {
    $pendingLetters = getIntermediaryPendingLetters();
    ?>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Pending Letters</h1>
            <p class="text-slate-500 mt-1">Only letters awaiting the receiver's action are shown. Approved or rejected letters disappear automatically.</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Sender</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Receiver</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Sent date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Sent time</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pending for</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Remind</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php foreach ($pendingLetters as $letter): ?>
                        <tr class="hover:bg-slate-50 align-top">
                            <td class="px-4 py-4"><div class="font-semibold text-slate-800"><?= htmlspecialchars($letter['type_label']) ?></div><div class="text-xs text-slate-400 font-mono mt-1"><?= htmlspecialchars($letter['ref_number']) ?></div></td>
                            <td class="px-4 py-4 text-sm text-slate-700"><?= htmlspecialchars($letter['sender_name']) ?></td>
                            <td class="px-4 py-4 text-sm text-slate-700"><?= htmlspecialchars($letter['receiver_name']) ?></td>
                            <td class="px-4 py-4 text-sm text-slate-700"><?= htmlspecialchars($letter['sent_date']) ?></td>
                            <td class="px-4 py-4 text-sm text-slate-700"><?= htmlspecialchars(date('H:i:s', strtotime($letter['created_at']))) ?></td>
                            <td class="px-4 py-4 text-sm font-semibold text-amber-700"><?= (int)$letter['pending_days'] ?> day(s), <?= (int)$letter['pending_hours'] ?> hour(s)</td>
                            <td class="px-4 py-4 min-w-[240px]">
                                <div class="text-xs text-slate-500 mb-2"><i class="fas fa-bell mr-1"></i> Send a reminder to the receiver</div>
                                <form action="actions/add_intermediary_reminder.php" method="post" class="space-y-2">
                                    <input type="hidden" name="type" value="<?= htmlspecialchars($letter['document_type']) ?>">
                                    <input type="hidden" name="ref" value="<?= htmlspecialchars($letter['ref_number']) ?>">
                                    <textarea name="comment" rows="2" class="form-control text-xs" placeholder="Write a reminder comment..."></textarea>
                                    <button class="btn-outline text-xs" name="method" value="comment" type="submit"><i class="fas fa-paper-plane mr-1"></i> Send comment</button>
                                </form>
                                <form action="actions/add_intermediary_reminder.php" method="post" class="flex gap-2 mt-2">
                                    <input type="hidden" name="type" value="<?= htmlspecialchars($letter['document_type']) ?>">
                                    <input type="hidden" name="ref" value="<?= htmlspecialchars($letter['ref_number']) ?>">
                                    <input name="phone" type="tel" class="form-control text-xs" placeholder="Receiver phone number" required>
                                    <button class="btn-primary text-xs whitespace-nowrap" name="method" value="phone" type="submit"><i class="fas fa-phone mr-1"></i> Record call</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pendingLetters)): ?><tr><td colspan="7" class="text-center text-slate-400 py-12">No pending letters. Approved and rejected letters are not listed.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    return;
}
$stats = getDashboardStats();
$recent = getRecentRecords(5);

$dashboardRecords = [];
foreach ([
    'Incoming' => getIncomingLetters(),
    'Outgoing' => getOutgoingLetters(),
    'Internal' => getInternalMemos(),
] as $recordType => $records) {
    foreach ($records as $record) {
        $record['record_type'] = $recordType;
        $record['record_date'] = $record['received_date'] ?? $record['date'] ?? '';
        $dashboardRecords[] = $record;
    }
}
usort($dashboardRecords, static function ($a, $b) {
    return strtotime($b['created_at'] ?? $b['record_date']) <=> strtotime($a['created_at'] ?? $a['record_date']);
});

$dashboardCounts = [
    'incoming' => count(array_filter($dashboardRecords, static function ($record) { return $record['record_type'] === 'Incoming'; })),
    'outgoing' => count(array_filter($dashboardRecords, static function ($record) { return $record['record_type'] === 'Outgoing'; })),
    'registered' => count($dashboardRecords),
    'in_process' => count(array_filter($dashboardRecords, static function ($record) { return ($record['status'] ?? '') === 'in-process'; })),
    'pending' => count(array_filter($dashboardRecords, static function ($record) { return in_array($record['status'] ?? '', ['pending', 'pending_approval'], true); })),
    'completed' => count(array_filter($dashboardRecords, static function ($record) { return in_array($record['status'] ?? '', ['approved', 'completed', 'dispatched'], true); })),
];

$dashboardFilters = [
    'incoming' => 'Incoming Letters',
    'outgoing' => 'Outgoing Letters',
    'registered' => 'Registered Records',
    'in_process' => 'In Process Records',
    'pending' => 'Pending Records',
    'completed' => 'Completed Records',
];
$selectedDashboardFilter = $_GET['dashboard_filter'] ?? '';
if (!is_string($selectedDashboardFilter) || !isset($dashboardFilters[$selectedDashboardFilter])) {
    $selectedDashboardFilter = '';
}

$filteredDashboardRecords = array_values(array_filter($dashboardRecords, static function ($record) use ($selectedDashboardFilter) {
    switch ($selectedDashboardFilter) {
        case 'incoming':
            return $record['record_type'] === 'Incoming';
        case 'outgoing':
            return $record['record_type'] === 'Outgoing';
        case 'in_process':
            return ($record['status'] ?? '') === 'in-process';
        case 'pending':
            return in_array($record['status'] ?? '', ['pending', 'pending_approval'], true);
        case 'completed':
            return in_array($record['status'] ?? '', ['approved', 'completed', 'dispatched'], true);
        case 'registered':
            return true;
        default:
            return false;
    }
}));

$total_records = $dashboardCounts['registered'];
$total_pending = ($stats['pending_incoming'] ?? 0) + ($stats['pending_outgoing'] ?? 0) + ($stats['pending_internal'] ?? 0);
$total_approved = ($stats['approved_incoming'] ?? 0) + ($stats['approved_outgoing'] ?? 0) + ($stats['approved_internal'] ?? 0);
?>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div>
        <h1 style="font-size:1.5rem;font-weight:800;color:#0a1e3a;" id="dashGreeting">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>! 🎉</h1>
        <p style="font-size:0.85rem;color:#6b7280;" id="dashSubGreeting">Here's an overview of records operations today.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <span style="font-size:0.7rem;background:#e2e8f0;padding:4px 14px;border-radius:20px;color:#475569;">
            <i class="fas fa-building mr-1"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
        </span>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:20px;">
    <a href="?page=dashboard&amp;dashboard_filter=incoming" class="stat-card block" style="color:inherit;text-decoration:none;" aria-label="View all incoming letters">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="stat-icon blue"><i class="fas fa-inbox"></i></div>
            <div>
                <div style="font-size:0.7rem;color:#6b7280;font-weight:500;">Incoming</div>
                <div style="font-size:1.4rem;font-weight:700;color:#0a1e3a;"><?php echo $dashboardCounts['incoming']; ?></div>
            </div>
        </div>
    </a>
    <a href="?page=dashboard&amp;dashboard_filter=outgoing" class="stat-card block" style="color:inherit;text-decoration:none;" aria-label="View all outgoing letters">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="stat-icon green"><i class="fas fa-paper-plane"></i></div>
            <div>
                <div style="font-size:0.7rem;color:#6b7280;font-weight:500;">Outgoing</div>
                <div style="font-size:1.4rem;font-weight:700;color:#0a1e3a;"><?php echo $dashboardCounts['outgoing']; ?></div>
            </div>
        </div>
    </a>
    <a href="?page=dashboard&amp;dashboard_filter=registered" class="stat-card block" style="color:inherit;text-decoration:none;" aria-label="View all registered records">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="stat-icon purple"><i class="fas fa-pen-square"></i></div>
            <div>
                <div style="font-size:0.7rem;color:#6b7280;font-weight:500;">Registered</div>
                <div style="font-size:1.4rem;font-weight:700;color:#0a1e3a;"><?php echo $total_records; ?></div>
            </div>
        </div>
    </a>
    <a href="?page=dashboard&amp;dashboard_filter=in_process" class="stat-card block" style="color:inherit;text-decoration:none;" aria-label="View all records in process">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="stat-icon orange"><i class="fas fa-spinner"></i></div>
            <div>
                <div style="font-size:0.7rem;color:#6b7280;font-weight:500;">In Process</div>
                <div style="font-size:1.4rem;font-weight:700;color:#0a1e3a;"><?php echo $dashboardCounts['in_process']; ?></div>
            </div>
        </div>
    </a>
    <a href="?page=dashboard&amp;dashboard_filter=pending" class="stat-card block" style="color:inherit;text-decoration:none;" aria-label="View all pending records">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="stat-icon rose"><i class="fas fa-clock"></i></div>
            <div>
                <div style="font-size:0.7rem;color:#6b7280;font-weight:500;">Pending</div>
                <div style="font-size:1.4rem;font-weight:700;color:#0a1e3a;"><?php echo $dashboardCounts['pending']; ?></div>
            </div>
        </div>
    </a>
    <a href="?page=dashboard&amp;dashboard_filter=completed" class="stat-card block" style="color:inherit;text-decoration:none;" aria-label="View all completed records">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="stat-icon teal"><i class="fas fa-check-circle"></i></div>
            <div>
                <div style="font-size:0.7rem;color:#6b7280;font-weight:500;">Completed</div>
                <div style="font-size:1.4rem;font-weight:700;color:#0a1e3a;"><?php echo $dashboardCounts['completed']; ?></div>
            </div>
        </div>
    </a>
</div>

<?php if ($selectedDashboardFilter !== ''): ?>
<div class="table-wrap" style="margin-bottom:20px;">
    <div style="padding:12px 16px;border-bottom:1px solid #e9edf2;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;">
        <h4 style="font-size:0.85rem;font-weight:700;color:#0a1e3a;"><?php echo htmlspecialchars($dashboardFilters[$selectedDashboardFilter]); ?></h4>
        <a href="?page=dashboard" class="btn-outline" style="padding:4px 14px;font-size:0.7rem;text-decoration:none;">Back to Dashboard</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Type</th><th>Reference #</th><th>Title</th><th>Sender</th><th>Receiver</th><th>Status</th><th>Date</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filteredDashboardRecords as $record): ?>
            <tr>
                <td><?php echo htmlspecialchars($record['record_type']); ?></td>
                <td class="font-mono text-xs font-bold text-blue-600"><?php echo htmlspecialchars($record['ref_number'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($record['title'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($record['sender_name'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($record['receiver_name'] ?? ''); ?></td>
                <td><?php echo getStatusPill($record['status'] ?? 'pending'); ?></td>
                <td>
                    <?php echo htmlspecialchars($record['record_date']); ?>
                    <?php if ($record['record_type'] === 'Incoming' && !empty($record['received_time'])): ?>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars(substr($record['received_time'], 0, 5)); ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <button type="button" class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 px-2 py-1 rounded-lg transition"
                            onclick='viewLetter(<?php echo htmlspecialchars(json_encode($record["ref_number"]), ENT_QUOTES, "UTF-8"); ?>, <?php echo htmlspecialchars(json_encode(strtolower($record["record_type"])), ENT_QUOTES, "UTF-8"); ?>)'>
                        <i class="fas fa-eye"></i> View
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($filteredDashboardRecords)): ?>
            <tr><td colspan="8" class="text-center text-gray-400 py-8">No records found in this category.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="table-wrap" style="margin-bottom:20px;">
    <div style="padding:12px 16px;border-bottom:1px solid #e9edf2;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;">
        <h4 style="font-size:0.85rem;font-weight:700;color:#0a1e3a;">Recent Records</h4>
        <button class="btn-outline" style="padding:4px 14px;font-size:0.7rem;" onclick="showToast('Viewing all records','')">
            <i class="fas fa-external-link-alt"></i> View All
        </button>
    </div>
    <table>
        <thead>
            <tr>
                <th>Record No.</th>
                <th>Type</th>
                <th>From / To</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody id="recentRecordsBody">
            <?php foreach ($recent as $record): ?>
            <tr>
                <td class="font-mono text-xs font-bold text-blue-600"><?php echo htmlspecialchars($record['ref_number'] ?? ''); ?></td>
                <td><span class="status-pill <?php echo strtolower($record['type'] ?? ''); ?>"><?php echo htmlspecialchars($record['type'] ?? ''); ?></span></td>
                <td><?php echo htmlspecialchars($record['sender_name'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($record['title'] ?? ''); ?></td>
                <td><?php echo getStatusPill($record['status'] ?? 'pending'); ?></td>
                <td><?php echo htmlspecialchars($record['date'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recent)): ?>
            <tr><td colspan="6" class="text-center text-gray-400 py-4">No recent records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm" style="height:300px"><h3 class="font-semibold text-slate-800 mb-3">Records by type</h3><canvas id="recordsTypeChart"></canvas></div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm" style="height:300px"><h3 class="font-semibold text-slate-800 mb-3">Workflow status</h3><canvas id="recordsStatusChart"></canvas></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart !== 'undefined') {
        new Chart(document.getElementById('recordsTypeChart'), {type:'doughnut', data:{labels:['Incoming','Outgoing','Internal'], datasets:[{data:[<?php echo (int)$stats['incoming_count']; ?>,<?php echo (int)$stats['outgoing_count']; ?>,<?php echo (int)$stats['internal_count']; ?>], backgroundColor:['#2563eb','#059669','#7c3aed']}]}, options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}});
        new Chart(document.getElementById('recordsStatusChart'), {type:'bar', data:{labels:['Pending','Approved','Rejected'], datasets:[{label:'Documents', data:[<?php echo (int)$total_pending; ?>,<?php echo (int)$total_approved; ?>,<?php echo (int)(($stats['rejected_incoming'] ?? 0)+($stats['rejected_outgoing'] ?? 0)+($stats['rejected_internal'] ?? 0)); ?>], backgroundColor:['#f59e0b','#10b981','#ef4444']}]}, options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true, ticks:{precision:0}}},plugins:{legend:{display:false}}}});
    }
    // Chart.js initialization
    const canvas = document.createElement('canvas');
    canvas.id = 'recordsChart';
    const container = document.querySelector('.chart-container');
    if (container) {
        container.appendChild(canvas);
        initChart();
    }
});

function initChart() {
    const canvas = document.getElementById('recordsChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Incoming',
                data: [120, 145, 132, 168, 190, 210, 185, 170, 200, 220, 240, 260],
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.08)',
                fill: true,
                tension: 0.3,
                pointRadius: 2,
                pointBackgroundColor: '#2563eb',
                borderWidth: 2,
            }, {
                label: 'Outgoing',
                data: [80, 95, 102, 115, 130, 140, 125, 110, 135, 150, 165, 180],
                borderColor: '#059669',
                backgroundColor: 'rgba(5,150,105,0.08)',
                fill: true,
                tension: 0.3,
                pointRadius: 2,
                pointBackgroundColor: '#059669',
                borderWidth: 2,
            }, {
                label: 'Archived',
                data: [40, 45, 50, 55, 60, 70, 65, 58, 62, 68, 72, 85],
                borderColor: '#7c3aed',
                backgroundColor: 'rgba(124,58,237,0.08)',
                fill: true,
                tension: 0.3,
                pointRadius: 2,
                pointBackgroundColor: '#7c3aed',
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(10,30,58,0.9)',
                    titleColor: '#fff',
                    bodyColor: '#e2e8f0',
                    cornerRadius: 8,
                    padding: 8,
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(0,0,0,0.04)' }, 
                    ticks: { font: { size: 8 }, color: '#94a3b8', stepSize: 50 } 
                },
                x: { grid: { display: false }, ticks: { font: { size: 8 }, color: '#94a3b8' } }
            },
            interaction: { intersect: false, mode: 'index' }
        }
    });
}
</script>
