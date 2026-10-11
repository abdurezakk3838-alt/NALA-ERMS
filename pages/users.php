<?php
$users = getUsers();
$roleLabels = [
    'head_office' => 'Head Office',
    'branch' => 'Branch',
    'admin' => 'Administrator',
    'dept_manager' => 'Department Manager',
    'records_officer' => 'Records Officer',
    'director' => 'Director General',
    'intermediary' => 'Intermediary Officer',
    'archivist' => 'Archivist',
    'staff' => 'Staff Member'
];
$currentUsername = $_SESSION['username'] ?? '';
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">User Management</h1>
            <p class="text-slate-500 mt-1">Create new accounts, reset passwords, and manage active status.</p>
        </div>
        <button type="button" class="btn-success" onclick="openModal('addUserModal')">
            <i class="fas fa-user-plus mr-2"></i> Add New User
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Department</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($users as $user): ?>
                        <?php
                        $isActive = !empty($user['is_active']);
                        $statusClass = $isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700';
                        $statusText = $isActive ? 'Active' : 'Inactive';
                        $canManage = $user['role'] !== 'admin' && $user['username'] !== $currentUsername;
                        ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800"><?= htmlspecialchars($user['full_name'] ?? 'Unnamed User') ?></div>
                                <div class="text-sm text-slate-500"><?= htmlspecialchars($user['username'] ?? '') ?></div>
                                <div class="text-xs text-slate-400"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700"><?= htmlspecialchars($roleLabels[$user['role']] ?? ucfirst(str_replace('_', ' ', $user['role'] ?? 'staff'))) ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700"><?= htmlspecialchars($user['department'] ?? '—') ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass ?>">
                                    <?= $statusText ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($canManage): ?>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" class="btn-outline text-xs" onclick="resetUserPassword('<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES) ?>')">
                                            <i class="fas fa-key mr-1"></i> Reset Password
                                        </button>
                                        <button type="button" class="btn-outline text-xs" onclick="deleteUser('<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES) ?>')">
                                            <i class="fas fa-user-slash mr-1"></i> Deactivate
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">Protected account</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
