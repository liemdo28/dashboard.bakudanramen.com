<?php
$user = currentUser();
$notifModel = new Notification();
$unreadCount = $unreadCount ?? $notifModel->getUnreadCount($_SESSION['user_id']);

function tf_icon($name) {
    // Minimal Lucide-like SVGs (inline, no dependency)
    $icons = [
        'dashboard' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>',
        'inbox' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5 7h14v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7z"/><path d="M7 7l2-3h6l2 3"/></svg>',
        'pin' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2l8 8-4 4v7l-4-4H7l-4 4v-7l4-4z"/></svg>',
        'calendar' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>',
        'projects' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>',
        'bill' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 15h2"/><path d="M11 15h6"/></svg>',
        'admin' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1l9 4v6c0 5-3.8 9.4-9 12-5.2-2.6-9-7-9-12V5z"/><path d="M9 12l2 2 4-4"/></svg>',
        'logout' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>',
        'bell' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 10-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>',
        'vendor' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 7v1a3 3 0 006 0V7m0 1a3 3 0 006 0V7m0 1a3 3 0 006 0V7H3l2-4h14l2 4M5 21V10.9M19 21V10.9"/></svg>',
        'menu' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>',
    ];
    return $icons[$name] ?? '';
}
?>
<!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? t('page.dashboard')) ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <meta name="theme-color" content="#dc2626">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TaskFlow">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/icons/icon-192.png">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>✓</text></svg>">
</head>
<body>
<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1>Task<span>Flow</span></h1>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <a href="<?= APP_URL ?>/dashboard" class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <span class="icon"><?= tf_icon('dashboard') ?></span> <?= e(t('nav.dashboard')) ?>
                </a>

                <a href="<?= APP_URL ?>/inbox" class="nav-item <?= ($currentPage ?? '') === 'inbox' ? 'active' : '' ?>">
                    <span class="icon"><?= tf_icon('inbox') ?></span> <?= e(t('nav.inbox')) ?>
                    <?php if (($unreadCount ?? 0) > 0): ?>
                        <span class="badge danger"><?= (int)$unreadCount ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?= APP_URL ?>/my-tasks" class="nav-item <?= ($currentPage ?? '') === 'my-tasks' ? 'active' : '' ?>">
                    <span class="icon"><?= tf_icon('pin') ?></span> <?= e(t('nav.my_tasks')) ?>
                </a>

                <a href="<?= APP_URL ?>/calendar" class="nav-item <?= ($currentPage ?? '') === 'calendar' ? 'active' : '' ?>">
                    <span class="icon"><?= tf_icon('calendar') ?></span> <?= e(t('nav.calendar')) ?>
                </a>

                <a href="<?= APP_URL ?>/bills" class="nav-item <?= ($currentPage ?? '') === 'bills' ? 'active' : '' ?>">
                    <span class="icon"><?= tf_icon('bill') ?></span> <?= e(t('nav.bills')) ?>
                </a>

                <a href="<?= APP_URL ?>/projects" class="nav-item <?= ($currentPage ?? '') === 'projects' ? 'active' : '' ?>">
                    <span class="icon"><?= tf_icon('projects') ?></span> <?= e(t('nav.projects')) ?>
                </a>
            </div>

            <?php
            $projectModel = new Project();
            $sidebarProjects = $projectModel->getByUser($_SESSION['user_id']);
            if (!empty($sidebarProjects)):
            ?>
            <div class="nav-section">
                <div class="nav-section-title"><?= e(t('nav.projects_section')) ?></div>
                <?php foreach (array_slice($sidebarProjects, 0, 10) as $sp): ?>
                <a href="<?= APP_URL ?>/projects/<?= $sp['id'] ?>" class="nav-item <?= (isset($project) && $project['id'] == $sp['id']) ? 'active' : '' ?>">
                    <span class="project-dot" style="background:<?= e($sp['color']) ?>"></span>
                    <?= e($sp['name']) ?>
                    <span class="badge"><?= (int)$sp['task_count'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
            <div class="nav-section">
                <div class="nav-section-title"><?= e(t('nav.admin')) ?></div>
                <a href="<?= APP_URL ?>/admin/users" class="nav-item <?= ($currentPage ?? '') === 'admin-users' ? 'active' : '' ?>">
                    <span class="icon"><?= tf_icon('admin') ?></span> <?= e(t('nav.users')) ?>
                </a>
                <a href="<?= APP_URL ?>/admin/vendors" class="nav-item <?= ($currentPage ?? '') === 'admin-vendors' ? 'active' : '' ?>">
                    <span class="icon"><?= tf_icon('vendor') ?></span> <?= e(t('nav.vendors')) ?>
                </a>
            </div>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(mb_substr($user['name'], 0, 1)) ?></div>
                <div class="user-details">
                    <div class="name"><?= e($user['name']) ?></div>
                    <div class="role"><?= ucfirst($user['role']) ?></div>
                </div>
                <a href="<?= APP_URL ?>/logout" class="btn-ghost" title="<?= e(t('action.logout')) ?>">
                    <?= tf_icon('logout') ?>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main -->
    <main class="main-content">
        <header class="page-header">
            <div class="flex-center gap-2">
                <div class="mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
                    <?= tf_icon('menu') ?>
                </div>
                <h2><?= e($pageTitle ?? t('page.dashboard')) ?></h2>
            </div>

            <div class="header-actions">
                <div class="lang-switcher" aria-label="<?= e(t('header.language')) ?>">
                    <a href="<?= e(language_switch_url('vi')) ?>" class="lang-chip <?= current_locale() === 'vi' ? 'active' : '' ?>">VI</a>
                    <a href="<?= e(language_switch_url('en')) ?>" class="lang-chip <?= current_locale() === 'en' ? 'active' : '' ?>">EN</a>
                </div>
                <?= $headerActions ?? '' ?>

                <!-- Notification Bell -->
                <div style="position:relative" id="notifWrap">
                    <button class="notif-btn" onclick="toggleNotifDropdown()">
                        <?= tf_icon('bell') ?>
                        <?php if (($unreadCount ?? 0) > 0): ?>
                            <span class="notif-badge"><?= $unreadCount > 9 ? '9+' : (int)$unreadCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-dropdown-header">
                            <span><?= e(t('header.notifications')) ?></span>
                            <button class="btn btn-sm btn-secondary" onclick="markAllNotifRead()"><?= e(t('header.read_all')) ?></button>
                        </div>
                        <div id="notifList">
                            <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:12px"><?= e(t('header.loading')) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="content-area">
            <?php if ($msg = flash('success')): ?>
                <div class="alert alert-success">✅ <?= e($msg) ?></div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
                <div class="alert alert-error">❌ <?= e($msg) ?></div>
            <?php endif; ?>
            <?= $content ?? '' ?>
        </div>
    </main>
</div>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>

<?php if (!empty($extraJs ?? [])): ?>
    <?php foreach (($extraJs ?? []) as $js): ?>
        <script src="<?= APP_URL ?>/assets/js/<?= e($js) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<script>
// Register PWA
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= APP_URL ?>/sw.js').catch(()=>{});
}
</script>
</body>
</html>
