<?php
$pageTitle = t('page.my_tasks');
$currentPage = 'my-tasks';
ob_start();
?>

<?php if (!empty($overdueTasks)): ?>
<div class="card mb-4" style="border-color:var(--accent-border)">
    <div class="card-header" style="background:var(--accent-bg)">
        <h3 style="color:var(--accent)">⚠️ <?= e(t('tasks.overdue')) ?> (<?= count($overdueTasks) ?>)</h3>
    </div>
    <div class="card-body" style="padding:0">
        <table class="data-table">
            <tbody>
            <?php foreach ($overdueTasks as $task): ?>
            <tr>
                <td style="width:30px">
                    <div class="task-check" onclick="location.href='<?= APP_URL ?>/tasks/<?= $task['id'] ?>/toggle'"></div>
                </td>
                <td><a href="<?= APP_URL ?>/tasks/<?= $task['id'] ?>" style="color:var(--text);font-weight:600"><?= e($task['title']) ?></a><div class="text-sm text-muted"><?= e($task['project_name'] ?? '') ?></div></td>
                <td><span class="tag priority-<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span></td>
                <td class="overdue text-sm" style="font-weight:600"><?= date('d/m/Y', strtotime($task['due_date'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>📌 <?= e(t('tasks.all_assigned')) ?></h3>
        <span class="text-muted text-sm"><?= count($myTasks) ?> tasks</span>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($myTasks)): ?>
            <div class="empty-state"><div class="icon">📝</div><h3><?= e(t('tasks.none')) ?></h3><p><?= e(t('tasks.none_assigned')) ?></p></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th style="width:30px"></th><th><?= e(t('tasks.task')) ?></th><th><?= e(t('tasks.project')) ?></th><th><?= e(t('tasks.priority')) ?></th><th><?= e(t('tasks.status')) ?></th><th><?= e(t('tasks.due')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($myTasks as $task): ?>
            <tr>
                <td>
                    <div class="task-check <?= $task['is_completed'] ? 'completed' : '' ?>" onclick="location.href='<?= APP_URL ?>/tasks/<?= $task['id'] ?>/toggle'">
                        <?= $task['is_completed'] ? '✓' : '' ?>
                    </div>
                </td>
                <td>
                    <a href="<?= APP_URL ?>/tasks/<?= $task['id'] ?>" style="color:var(--text);font-weight:600;<?= $task['is_completed'] ? 'text-decoration:line-through;opacity:.5' : '' ?>"><?= e($task['title']) ?></a>
                </td>
                <td class="text-muted text-sm"><?= e($task['project_name'] ?? '-') ?></td>
                <td><span class="tag priority-<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span></td>
                <td><span class="badge badge-<?= $task['status'] === 'done' ? 'active' : 'member' ?>"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span></td>
                <td class="text-sm <?= ($task['due_date'] && $task['due_date'] < date('Y-m-d') && !$task['is_completed']) ? 'overdue' : 'text-muted' ?>">
                    <?= $task['due_date'] ? date('d/m/Y', strtotime($task['due_date'])) : '-' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
