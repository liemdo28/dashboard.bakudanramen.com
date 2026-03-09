<?php
$pageTitle = 'Inbox';
$currentPage = 'inbox';

$typeIcons = [
    'task_assigned' => ['🎯', 'var(--blue)', 'var(--blue-bg)'],
    'task_commented' => ['💬', 'var(--purple)', 'var(--purple-bg)'],
    'task_due_soon' => ['⏰', 'var(--amber)', 'var(--amber-bg)'],
    'task_overdue' => ['🔥', 'var(--accent)', 'var(--accent-bg)'],
    'task_completed' => ['✅', 'var(--green)', 'var(--green-bg)'],
    'bill' => ['💳', 'var(--neon-cyan)', 'rgba(0,245,255,.10)'],
];

ob_start();
?>

<div class="flex-between mb-4">
    <div>
        <span class="text-muted text-sm"><?= $unreadCount ?> chưa đọc / <?= count($allNotifs) ?> tổng</span>
    </div>
    <?php if ($unreadCount > 0): ?>
    <button class="btn btn-sm btn-secondary" onclick="fetch('<?= APP_URL ?>/api/notifications/read-all',{method:'PUT'}).then(()=>location.reload())">Đánh dấu tất cả đã đọc</button>
    <?php endif; ?>
</div>

<div class="card">
    <?php if (empty($allNotifs)): ?>
        <div class="empty-state">
            <div class="icon">📨</div>
            <h3>Inbox trống</h3>
            <p>Bạn sẽ nhận thông báo khi có ai giao task, bình luận, hoặc task sắp đến hạn.</p>
        </div>
    <?php else: ?>
        <?php foreach ($allNotifs as $n):
            $ti = $typeIcons[$n['type']] ?? ['📌', 'var(--text-muted)', 'var(--bg-tertiary)'];
            $link = $n['task_id'] ? APP_URL . '/tasks/' . $n['task_id'] : '#';
        ?>
        <a href="<?= $link ?>" class="inbox-item <?= !$n['is_read'] ? 'unread' : '' ?>" style="text-decoration:none;color:inherit"
           onclick="fetch('<?= APP_URL ?>/api/notifications/<?= $n['id'] ?>/read',{method:'PUT'})">
            <div style="width:32px;height:32px;border-radius:10px;background:<?= $ti[2] ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:15px"><?= $ti[0] ?></div>
            <div class="inbox-body">
                <div class="inbox-title" style="<?= !$n['is_read'] ? 'color:var(--text)' : 'color:var(--text-secondary)' ?>"><?= e($n['title']) ?></div>
                <div class="inbox-msg"><?= e($n['message']) ?><?php if ($n['from_user_name']): ?> — <?= e($n['from_user_name']) ?><?php endif; ?></div>
            </div>
            <div class="inbox-time"><?= timeAgo($n['created_at']) ?></div>
        </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
