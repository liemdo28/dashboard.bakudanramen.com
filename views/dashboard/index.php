<?php
function ui_status($status) {
  $map = [
    'todo' => [t('dashboard.status.todo'), 'pill pill-neutral'],
    'in_progress' => [t('dashboard.status.in_progress'), 'pill pill-info'],
    'review' => [t('dashboard.status.review'), 'pill pill-warn'],
    'done' => [t('dashboard.status.done'), 'pill pill-ok'],
  ];
  $key = $status ?: 'todo';
  return $map[$key] ?? [ucfirst(str_replace('_',' ',$key)), 'pill pill-neutral'];
}

function ui_priority($p) {
  $p = strtolower((string)$p);
  if (in_array($p, ['high','urgent','p1'])) return ['High', 'pill pill-danger'];
  if (in_array($p, ['medium','normal','p2'])) return ['Medium', 'pill pill-warn'];
  if (in_array($p, ['low','p3'])) return ['Low', 'pill pill-neutral'];
  return [ucfirst($p ?: '—'), 'pill pill-neutral'];
}

function ui_due($dueDate) {
  if (empty($dueDate)) return [t('common.no_due'), 'chip chip-muted'];
  $ts = strtotime($dueDate);
  if (!$ts) return [$dueDate, 'chip chip-muted'];

  $today = strtotime(date('Y-m-d'));
  $diffDays = (int) floor(($ts - $today) / 86400);

  if ($diffDays < 0) return [t('common.overdue_days', ['days' => abs($diffDays)]), 'chip chip-danger'];
  if ($diffDays === 0) return [t('common.due_today'), 'chip chip-warn'];
  if ($diffDays <= 3) return [t('common.due_in_days', ['days' => $diffDays]), 'chip chip-info'];
  return [date('M d', $ts), 'chip chip-muted'];
}

function ui_task_url($taskId) {
  return 'index.php?route=tasks/view&id=' . urlencode($taskId);
}

$pageTitle = t('page.dashboard');
$currentPage = 'dashboard';
ob_start();
?>

<!-- Stats -->
<div class="grid grid-4 mb-4">
    <div class="stat-card">
        <div class="stat-icon red">📁</div>
        <div>
            <div class="stat-value"><?= $totalProjects ?></div>
            <div class="stat-label"><?= e(t('dashboard.projects')) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon dark">📋</div>
        <div>
            <div class="stat-value"><?= $totalTasks ?></div>
            <div class="stat-label"><?= e(t('dashboard.total_tasks')) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div>
            <div class="stat-value"><?= $completedTasks ?></div>
            <div class="stat-label"><?= e(t('dashboard.completed')) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">👥</div>
        <div>
            <div class="stat-value"><?= $totalMembers ?></div>
            <div class="stat-label"><?= e(t('dashboard.members')) ?></div>
        </div>
    </div>
</div>

<div class="grid grid-2 mb-4">
    <!-- Task Distribution -->
    <div class="card">
        <div class="card-header"><h3>📊 <?= e(t('dashboard.task_distribution')) ?></h3></div>
        <div class="card-body">
            <?php
            $statusMap = ['todo'=>0,'in_progress'=>0,'review'=>0,'done'=>0];
            foreach ($tasksByStatus as $s) $statusMap[$s['status']] = $s['count'];
            $maxVal = max(1, max($statusMap));
            $sLabels = ['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'];
            $sColors = ['todo'=>'var(--text-muted)','in_progress'=>'var(--blue)','review'=>'var(--amber)','done'=>'var(--green)'];
            ?>
            <div class="chart-bar-container">
                <?php foreach ($statusMap as $key => $val): ?>
                <div class="chart-bar-wrap">
                    <div class="chart-bar-value"><?= $val ?></div>
                    <div class="chart-bar" style="height:<?= ($val / $maxVal * 100) ?>%;background:<?= $sColors[$key] ?>;min-height:4px"></div>
                    <div class="chart-bar-label"><?= $sLabels[$key] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Overdue -->
    <div class="card">
        <div class="card-header">
            <h3>⚠️ <?= e(t('dashboard.overdue_tasks')) ?></h3>
            <span class="badge badge-admin"><?= count($overdueTasks) ?></span>
        </div>
        <div class="card-body">
            <?php if (empty($overdueTasks)): ?>
                <p class="text-muted text-center" style="padding:16px"><?= e(t('dashboard.no_overdue')) ?></p>
            <?php else: ?>
                <?php foreach (array_slice($overdueTasks, 0, 5) as $task): ?>
                <div class="flex-between" style="padding:8px 0;border-bottom:1px solid var(--border)">
                    <div>
                        <a href="<?= APP_URL ?>/tasks/<?= $task['id'] ?>" style="font-size:13px;font-weight:600;color:var(--text)"><?= e($task['title']) ?></a>
                        <div class="text-sm text-muted"><?= e($task['project_name']) ?></div>
                    </div>
                    <span class="due-date overdue"><?= date('d/m', strtotime($task['due_date'])) ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- My Tasks -->
<div class="card mb-4">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3>📌 <?= e(t('dashboard.my_tasks')) ?></h3>
        <span class="text-muted text-sm"><?= count($myTasks) ?> tasks</span>
    </div>

    <div class="card-body">
        <?php if (empty($myTasks)): ?>
            <div class="empty-state">
                <div class="icon">📝</div>
                <h3><?= e(t('dashboard.no_tasks')) ?></h3>
                <p><?= e(t('dashboard.no_assigned_tasks')) ?></p>
            </div>
        <?php else: ?>

        <div class="task-card-list">
            <?php foreach ($myTasks as $task): ?>
                <?php
                $status = $task['status'];
                $priority = $task['priority'];
                $due = $task['due_date'];

                $statusLabel = ucfirst(str_replace('_',' ',$status));
                $priorityLabel = ucfirst($priority);

                $isOverdue = ($due && $due < date('Y-m-d'));
                ?>

                <a class="task-card" href="<?= APP_URL ?>/tasks/<?= $task['id'] ?>">
                    <div class="task-main">
                        <div class="task-title"><?= e($task['title']) ?></div>

                        <div class="task-meta">
                            <span class="task-project"><?= e($task['project_name'] ?? '-') ?></span>

                            <span class="task-priority priority-<?= e($priority) ?>">
                                <?= e($priorityLabel) ?>
                            </span>

                            <span class="task-status status-<?= e($status) ?>">
                                <?= e($statusLabel) ?>
                            </span>

                            <?php if ($due): ?>
                                <span class="task-due <?= $isOverdue ? 'overdue' : '' ?>">
                                    <?= date('d/m/Y', strtotime($due)) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="task-arrow">→</div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>
</div>

<!-- Projects -->
<div class="flex-between mb-3">
    <h3 style="font-size:15px;font-weight:700"><?= e(t('dashboard.recent_projects')) ?></h3>
    <a href="<?= APP_URL ?>/projects/create" class="btn btn-primary btn-sm"><?= e(t('dashboard.create_project')) ?></a>
</div>

<div class="grid grid-3">
    <?php foreach (array_slice($projects, 0, 6) as $proj):
        $pct = $proj['task_count'] > 0 ? round($proj['completed_count'] / $proj['task_count'] * 100) : 0;
    ?>
    <a href="<?= APP_URL ?>/projects/<?= $proj['id'] ?>" class="project-card">
        <div class="card-accent" style="background:<?= e($proj['color']) ?>"></div>
        <div class="card-content">
            <h3><?= e($proj['name']) ?></h3>
            <p><?= e(mb_substr($proj['description'] ?? t('common.untitled'), 0, 60)) ?></p>
            <div class="progress-bar">
                <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= e($proj['color']) ?>"></div>
            </div>
            <div class="card-footer">
                <span><?= $pct . e(t('dashboard.complete_percent')) ?></span>
                <span><?= $proj['task_count'] ?> tasks</span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- (Optional) JS: currently unused because dashboard doesn't have [data-seg="mywork"] -->
<script>
(function(){
  const seg = document.querySelector('[data-seg="mywork"]');
  const list = document.getElementById('myworkList');
  if(!seg || !list) return;

  function isToday(d){ if(!d) return false; const x=new Date(d); const t=new Date(); return x.toDateString()===t.toDateString(); }
  function daysDiff(d){
    if(!d) return null;
    const x=new Date(d); const t=new Date();
    const a=new Date(t.getFullYear(),t.getMonth(),t.getDate());
    const b=new Date(x.getFullYear(),x.getMonth(),x.getDate());
    return Math.round((b-a)/86400000);
  }

  seg.addEventListener('click', (e)=>{
    const btn = e.target.closest('.seg'); if(!btn) return;
    seg.querySelectorAll('.seg').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');

    const f = btn.dataset.filter;
    [...list.querySelectorAll('.task-card')].forEach(card=>{
      const st = (card.dataset.status || '').toLowerCase();
      const due = card.dataset.due || '';
      const dd = daysDiff(due);

      let ok = true;
      if (f === 'today') ok = isToday(due);
      if (f === 'week') ok = (dd !== null && dd >= 0 && dd <= 7);
      if (f === 'in_progress') ok = (st === 'in_progress');
      if (f === 'all') ok = true;

      card.style.display = ok ? '' : 'none';
    });
  });
})();
</script>

<?php
$content = ob_get_clean();

/**
 * Header actions:
 * - Create Project
 * - Connect Google Calendar (OAuth)
 *
 * Nếu controller của bạn có biến $googleConnected (bool), nút sẽ đổi trạng thái.
 * Không có biến đó cũng không sao (nút vẫn hiện).
 */
$googleConnected = $googleConnected ?? false;

$headerActions = ''
  . '<a href="' . APP_URL . '/projects/create" class="btn btn-primary btn-sm">' . e(t('dashboard.create_project')) . '</a>'
  . ' '
  . (
      $googleConnected
      ? '<span class="btn btn-sm btn-secondary" style="cursor:default;opacity:.9">' . e(t('dashboard.google_connected')) . '</span>'
      : '<a href="' . APP_URL . '/google/connect" class="btn btn-sm btn-secondary">' . e(t('dashboard.connect_google')) . '</a>'
    );

require __DIR__ . '/../layouts/main.php';
