<?php
$pageTitle = 'Calendar';
$currentPage = 'calendar';

// Calendar logic
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Normalize
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$startDow = (date('N', $firstDay)); // 1=Monday
$today = date('Y-m-d');

// Fetch tasks for this month
$db = Database::getInstance();
$calTasks = $db->fetchAll(
    "SELECT DISTINCT t.id, t.title, t.due_date, t.priority, t.status, t.is_completed, p.name as project_name, p.color as project_color
     FROM tasks t
     LEFT JOIN projects p ON t.project_id = p.id
     LEFT JOIN project_members pm ON p.id = pm.project_id
     WHERE t.due_date IS NOT NULL
     AND MONTH(t.due_date) = ? AND YEAR(t.due_date) = ?
     AND (pm.user_id = ? OR p.owner_id = ?)
     ORDER BY t.priority DESC",
    [$month, $year, $_SESSION['user_id'], $_SESSION['user_id']]
);

// Group tasks by date
$tasksByDate = [];
foreach ($calTasks as $t) {
    $tasksByDate[$t['due_date']][] = $t;
}

$monthNames = ['','Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'];
$dayNames = ['T2','T3','T4','T5','T6','T7','CN'];
$priColors = ['urgent'=>'#dc2626','high'=>'#f59e0b','medium'=>'#3b82f6','low'=>'#71717a'];

function normalizeCalendarHex($hex) {
    $hex = trim((string)$hex);
    if ($hex === '') return null;
    if ($hex[0] === '#') $hex = substr($hex, 1);
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    return preg_match('/^[0-9a-fA-F]{6}$/', $hex) ? strtolower($hex) : null;
}

function calendarTaskTextColor($hex) {
    $normalized = normalizeCalendarHex($hex);
    if (!$normalized) return '#f8fafc';

    $r = hexdec(substr($normalized, 0, 2));
    $g = hexdec(substr($normalized, 2, 2));
    $b = hexdec(substr($normalized, 4, 2));
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

    return $luminance > 0.68 ? '#050816' : '#f8fafc';
}

function calendarTaskBorderColor($textColor) {
    return $textColor === '#050816' ? 'rgba(5, 8, 22, .18)' : 'rgba(248, 250, 252, .18)';
}

ob_start();
?>

<div class="calendar-wrap">
    <div class="calendar-header">
        <div class="calendar-nav">
            <a href="<?= APP_URL ?>/calendar?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-secondary btn-sm">‹</a>
        </div>
        <h3><?= $monthNames[$month] ?> <?= $year ?></h3>
        <div class="calendar-nav" style="gap:6px">
            <a href="<?= APP_URL ?>/calendar?month=<?= (int)date('m') ?>&year=<?= (int)date('Y') ?>" class="btn btn-sm btn-outline">Hôm nay</a>
            <a href="<?= APP_URL ?>/calendar?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-secondary btn-sm">›</a>
        </div>
    </div>

    <div class="calendar-grid">
        <?php foreach ($dayNames as $dn): ?>
            <div class="calendar-day-header"><?= $dn ?></div>
        <?php endforeach; ?>

        <?php
        // Previous month padding
        $prevMonthDays = date('t', mktime(0,0,0,$month-1,1,$year));
        for ($i = $startDow - 1; $i > 0; $i--):
            $d = $prevMonthDays - $i + 1;
        ?>
            <div class="calendar-cell other"><div class="day-num"><?= $d ?></div></div>
        <?php endfor; ?>

        <?php
        // Current month days
        for ($d = 1; $d <= $daysInMonth; $d++):
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $isToday = ($dateStr === $today);
            $dayTasks = $tasksByDate[$dateStr] ?? [];
        ?>
            <div class="calendar-cell <?= $isToday ? 'today' : '' ?> <?= !empty($dayTasks) ? 'has-events' : '' ?>">
                <div class="calendar-day-meta">
                    <div class="day-num"><?= $d ?></div>
                    <?php if (!empty($dayTasks)): ?>
                        <span class="calendar-count"><?= count($dayTasks) ?></span>
                    <?php endif; ?>
                </div>
                <?php foreach (array_slice($dayTasks, 0, 3) as $t):
                    $color = $t['project_color'] ?? ($priColors[$t['priority']] ?? '#3b82f6');
                    if ($t['is_completed']) $color = '#22c55e';
                    $textColor = calendarTaskTextColor($color);
                    $borderColor = calendarTaskBorderColor($textColor);
                ?>
                    <a
                        class="calendar-task <?= $t['is_completed'] ? 'completed' : '' ?>"
                        href="<?= APP_URL ?>/tasks/<?= $t['id'] ?>"
                        style="background:<?= e($color) ?>;color:<?= e($textColor) ?>;border-color:<?= e($borderColor) ?>"
                        title="<?= e($t['title']) ?> • <?= e($t['project_name'] ?? 'Task') ?>"
                    >
                        <?= $t['is_completed'] ? '✓ ' : '' ?><?= e(mb_substr($t['title'], 0, 18)) ?>
                    </a>
                <?php endforeach; ?>
                <?php if (count($dayTasks) > 3): ?>
                    <div class="calendar-more">+<?= count($dayTasks) - 3 ?> thêm</div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>

        <?php
        // Next month padding
        $totalCells = $startDow - 1 + $daysInMonth;
        $remaining = (7 - ($totalCells % 7)) % 7;
        for ($i = 1; $i <= $remaining; $i++):
        ?>
            <div class="calendar-cell other"><div class="day-num"><?= $i ?></div></div>
        <?php endfor; ?>
    </div>
</div>

<div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;font-size:11px;color:var(--text-muted)">
    <span>📊 Tổng: <?= count($calTasks) ?> tasks trong tháng</span>
    <span>•</span>
    <span style="display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:2px;background:#dc2626;display:inline-block"></span> Urgent</span>
    <span style="display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:2px;background:#f59e0b;display:inline-block"></span> High</span>
    <span style="display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:2px;background:#3b82f6;display:inline-block"></span> Medium</span>
    <span style="display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:2px;background:#22c55e;display:inline-block"></span> Hoàn thành</span>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
