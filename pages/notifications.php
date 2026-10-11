<?php
$notifications = getUserNotifications($_SESSION['user_id'] ?? 0);
markNotificationsRead($_SESSION['user_id'] ?? 0);
?>
<div class="space-y-6">
    <div><h1 class="text-3xl font-bold text-slate-800">Notifications</h1><p class="text-slate-500 mt-1">Letters and workflow updates sent to your account.</p></div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm divide-y divide-slate-100">
        <?php if (!$notifications): ?><div class="p-8 text-center text-slate-500">No notifications yet.</div><?php endif; ?>
        <?php foreach ($notifications as $notification): ?>
            <a href="<?= htmlspecialchars($notification['link'] ?: '#') ?>" class="block p-4 hover:bg-slate-50"><div class="font-semibold text-slate-800"><?= htmlspecialchars($notification['title']) ?></div><div class="text-sm text-slate-600 mt-1"><?= htmlspecialchars($notification['message']) ?></div><div class="text-xs text-slate-400 mt-2"><?= htmlspecialchars(date('d M Y, H:i', strtotime($notification['created_at']))) ?></div></a>
        <?php endforeach; ?>
    </div>
</div>
