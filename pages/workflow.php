<?php $workflowRules = getWorkflowRules(); ?>
<div class="space-y-6">
    <div><h1 class="text-3xl font-bold text-slate-800">Workflow</h1><p class="text-slate-500 mt-1">Define what happens when a letter is submitted or reviewed.</p></div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <h2 class="font-semibold text-slate-800 mb-4">Add workflow rule</h2>
        <form action="actions/save_workflow_rule.php" method="post" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input class="form-control" name="name" placeholder="Rule name" required />
            <select class="form-control" name="trigger_event"><option value="letter_submitted">Letter submitted</option><option value="letter_approved">Letter approved</option><option value="letter_rejected">Letter rejected</option></select>
            <select class="form-control" name="action_type"><option value="notify_receiver">Notify receiver</option><option value="notify_sender">Notify sender</option><option value="email_receiver">Email receiver</option></select>
            <button class="btn-success" type="submit"><i class="fas fa-plus mr-1"></i> Add Rule</button>
            <textarea class="form-control md:col-span-4" name="description" rows="2" placeholder="Description (optional)"></textarea>
        </form>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="min-w-full"><thead class="bg-slate-50"><tr><th class="p-3 text-left">Rule</th><th class="p-3 text-left">Trigger</th><th class="p-3 text-left">Action</th><th class="p-3 text-left">Status</th><th class="p-3 text-left">Action</th></tr></thead><tbody class="divide-y divide-slate-100">
        <?php foreach ($workflowRules as $rule): ?><tr><td class="p-3"><div class="font-semibold"><?= htmlspecialchars($rule['name']) ?></div><div class="text-xs text-slate-500"><?= htmlspecialchars($rule['description'] ?? '') ?></div></td><td class="p-3 text-sm"><?= htmlspecialchars(str_replace('_', ' ', $rule['trigger_event'])) ?></td><td class="p-3 text-sm"><?= htmlspecialchars(str_replace('_', ' ', $rule['action_type'])) ?></td><td class="p-3"><span class="status-pill <?= $rule['is_active'] ? 'approved' : 'rejected' ?>"><?= $rule['is_active'] ? 'Active' : 'Inactive' ?></span></td><td class="p-3"><form action="actions/toggle_workflow_rule.php" method="post"><input type="hidden" name="id" value="<?= (int) $rule['id'] ?>" /><button class="btn-outline text-xs" type="submit"><?= $rule['is_active'] ? 'Disable' : 'Enable' ?></button></form></td></tr><?php endforeach; ?>
        <?php if (!$workflowRules): ?><tr><td colspan="5" class="p-8 text-center text-slate-500">No workflow rules configured.</td></tr><?php endif; ?></tbody></table>
    </div>
</div>
