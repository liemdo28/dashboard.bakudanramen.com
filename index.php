<?php
/**
 * TaskFlow v2 - Entry Point & Router
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Project.php';
require_once __DIR__ . '/models/Task.php';
require_once __DIR__ . '/models/Comment.php';
require_once __DIR__ . '/models/Notification.php';
require_once __DIR__ . '/models/Store.php';
require_once __DIR__ . '/models/Bill.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/ProjectController.php';
require_once __DIR__ . '/controllers/TaskController.php';
require_once __DIR__ . '/controllers/CommentController.php';
require_once __DIR__ . '/controllers/BillController.php';
require_once __DIR__ . '/models/Vendor.php';
require_once __DIR__ . '/controllers/VendorController.php';

function isLoggedIn() { return isset($_SESSION['user_id']); }
function currentUser() {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) { $user = (new User())->findById($_SESSION['user_id']); }
    return $user;
}
function isAdmin() { $u = currentUser(); return $u && $u['role'] === 'admin'; }
function redirect($path) { header("Location: " . rtrim(APP_URL, '/') . "/" . ltrim($path, '/')); exit; }
function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function csrf_token() { if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function verify_csrf($t) { return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $t); }
function flash($key, $msg = null) { if ($msg !== null) { $_SESSION['flash'][$key] = $msg; } else { $m = $_SESSION['flash'][$key] ?? null; unset($_SESSION['flash'][$key]); return $m; } }
function json_response($data, $code = 200) { http_response_code($code); header('Content-Type: application/json'); echo json_encode($data); exit; }
function timeAgo($dt) { $d = (new DateTime())->diff(new DateTime($dt)); if ($d->y) return $d->y.'n trước'; if ($d->m) return $d->m.'th trước'; if ($d->d) return $d->d.'d trước'; if ($d->h) return $d->h.'h trước'; if ($d->i) return $d->i.'p trước'; return 'Vừa xong'; }
function notifyUser($data) { try { (new Notification())->create($data); } catch (Exception $e) {} }
function dueColor($dueDate, $status = 'pending') {
    if ($status === 'paid') return '#333333';
    $days = (int)((strtotime($dueDate) - strtotime('today')) / 86400);
    if ($days < 0) return '#ff1744';
    if ($days === 0) return '#ff2bd6';
    if ($days <= 3) return '#ffea00';
    return '#2979ff';
}

$route = isset($_GET['route']) ? trim($_GET['route'], '/') : '';
$method = $_SERVER['REQUEST_METHOD'];
$publicRoutes = ['login', 'register', 'manifest.json', 'sw.js'];

// PWA Manifest
if ($route === 'manifest.json') {
    header('Content-Type: application/json');
    echo json_encode(['name'=>'TaskFlow','short_name'=>'TaskFlow','description'=>'Quản lý công việc','start_url'=>APP_URL.'/dashboard','display'=>'standalone','background_color'=>'#111','theme_color'=>'#dc2626','orientation'=>'any','icons'=>[['src'=>APP_URL.'/assets/icons/icon-192.png','sizes'=>'192x192','type'=>'image/png'],['src'=>APP_URL.'/assets/icons/icon-512.png','sizes'=>'512x512','type'=>'image/png']]],JSON_UNESCAPED_SLASHES);
    exit;
}
// Service Worker
if ($route === 'sw.js') {
    header('Content-Type: application/javascript');
    echo "const C='taskflow-v2';self.addEventListener('install',e=>e.waitUntil(caches.open(C).then(c=>c.addAll(['/dashboard','/assets/css/style.css','/assets/js/app.js']))));self.addEventListener('fetch',e=>{if(e.request.method!=='GET')return;e.respondWith(fetch(e.request).then(r=>{if(r.ok){const rc=r.clone();caches.open(C).then(c=>c.put(e.request,rc))}return r}).catch(()=>caches.match(e.request)))});";
    exit;
}

if (!isLoggedIn() && !in_array($route, $publicRoutes)) redirect('login');

switch (true) {
    case $route === 'login': $c = new AuthController(); $method === 'POST' ? $c->login() : $c->showLogin(); break;
    case $route === 'register': $c = new AuthController(); $method === 'POST' ? $c->register() : $c->showRegister(); break;
    case $route === 'logout': (new AuthController())->logout(); break;
    case $route === '' || $route === 'dashboard': (new DashboardController())->index(); break;
    case $route === 'calendar': (new DashboardController())->calendar(); break;
    case $route === 'inbox': (new DashboardController())->inbox(); break;
    case $route === 'my-tasks': (new DashboardController())->myTasks(); break;

    // Notifications API
    case $route === 'api/notifications':
        if (!isLoggedIn()) json_response(['error'=>'Unauthorized'],401);
        $n = new Notification();
        json_response(['notifications'=>$n->getByUser($_SESSION['user_id']),'unread'=>$n->getUnreadCount($_SESSION['user_id'])]);
        break;
    case preg_match('/^api\/notifications\/(\d+)\/read$/', $route, $m):
        (new Notification())->markRead($m[1], $_SESSION['user_id']); json_response(['ok'=>1]); break;
    case $route === 'api/notifications/read-all':
        (new Notification())->markAllRead($_SESSION['user_id']); json_response(['ok'=>1]); break;

    // Calendar API
    case $route === 'api/calendar':
        $mo = $_GET['month'] ?? date('m'); $yr = $_GET['year'] ?? date('Y');
        $tasks = Database::getInstance()->fetchAll(
            "SELECT DISTINCT t.id, t.title, t.due_date, t.priority, t.status, t.is_completed, p.name as project_name, p.color as project_color
             FROM tasks t LEFT JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id
             WHERE t.due_date IS NOT NULL AND MONTH(t.due_date) = ? AND YEAR(t.due_date) = ? AND (pm.user_id = ? OR p.owner_id = ?)
             ORDER BY t.due_date, t.priority DESC", [$mo, $yr, $_SESSION['user_id'], $_SESSION['user_id']]);
        json_response(['tasks'=>$tasks]); break;


    // Bills (Tracking)
    case $route === 'bills': (new BillController())->index(); break;
    case preg_match('/^bills\/store\/(\d+)$/', $route, $m): (new BillController())->storeView($m[1]); break;
    case $route === 'bills/store/create' && $method === 'POST': (new BillController())->createStore(); break;
    case preg_match('/^bills\/store\/(\d+)\/update$/', $route, $m) && $method === 'POST': (new BillController())->updateStore($m[1]); break;
    case preg_match('/^bills\/store\/(\d+)\/delete$/', $route, $m): (new BillController())->deleteStore($m[1]); break;
    case $route === 'bills/create' && $method === 'POST': (new BillController())->createBill(); break;
    case preg_match('/^bills\/(\d+)\/paid$/', $route, $m): (new BillController())->markPaid($m[1]); break;
    case preg_match('/^bills\/(\d+)\/update$/', $route, $m) && $method === 'POST': (new BillController())->updateBill($m[1]); break;
    case preg_match('/^bills\/(\d+)\/upload$/', $route, $m) && $method === 'POST': (new BillController())->uploadBillFile($m[1]); break;
    case preg_match('/^bills\/(\d+)\/delete$/', $route, $m): (new BillController())->deleteBill($m[1]); break;
    case preg_match('/^bills\/(\d+)\/duplicate$/', $route, $m): (new BillController())->duplicateBill($m[1]); break;
    case preg_match('/^bill-attachments\/(\d+)\/delete$/', $route, $m): (new BillController())->deleteBillAttachment($m[1]); break;
    case preg_match('/^bill-attachments\/(\d+)\/download$/', $route, $m): (new BillController())->downloadBillAttachment($m[1]); break;

    // Projects
    case $route === 'projects': $c = new ProjectController(); $method === 'POST' ? $c->store() : $c->index(); break;
    case preg_match('/^projects\/create$/', $route): (new ProjectController())->create(); break;
    case preg_match('/^projects\/(\d+)$/', $route, $m): (new ProjectController())->show($m[1]); break;
    case preg_match('/^projects\/(\d+)\/edit$/', $route, $m): $c = new ProjectController(); $method === 'POST' ? $c->update($m[1]) : $c->edit($m[1]); break;
    case preg_match('/^projects\/(\d+)\/delete$/', $route, $m): (new ProjectController())->delete($m[1]); break;
    case preg_match('/^projects\/(\d+)\/members$/', $route, $m): if ($method==='POST') (new ProjectController())->addMember($m[1]); break;
    case preg_match('/^projects\/(\d+)\/members\/(\d+)\/remove$/', $route, $m): (new ProjectController())->removeMember($m[1],$m[2]); break;
    case preg_match('/^projects\/(\d+)\/sections$/', $route, $m): if ($method==='POST') (new ProjectController())->addSection($m[1]); break;
    case preg_match('/^sections\/(\d+)\/delete$/', $route, $m): (new ProjectController())->deleteSection($m[1]); break;

    // Tasks
    case $route === 'tasks' && $method === 'POST': (new TaskController())->store(); break;
    case preg_match('/^tasks\/(\d+)$/', $route, $m): $c = new TaskController(); $method === 'POST' ? $c->update($m[1]) : $c->show($m[1]); break;
    case preg_match('/^tasks\/(\d+)\/delete$/', $route, $m): (new TaskController())->delete($m[1]); break;
    case preg_match('/^tasks\/(\d+)\/toggle$/', $route, $m): (new TaskController())->toggleComplete($m[1]); break;
    case $route === 'tasks/reorder' && $method === 'POST': (new TaskController())->reorder(); break;
    case preg_match('/^tasks\/(\d+)\/move$/', $route, $m) && $method === 'POST': (new TaskController())->move($m[1]); break;

    // Comments
    case preg_match('/^tasks\/(\d+)\/comments$/', $route, $m) && $method === 'POST': (new CommentController())->store($m[1]); break;
    case preg_match('/^comments\/(\d+)\/delete$/', $route, $m): (new CommentController())->delete($m[1]); break;

    // Attachments
    case preg_match('/^tasks\/(\d+)\/upload$/', $route, $m) && $method === 'POST': (new TaskController())->upload($m[1]); break;
    case preg_match('/^attachments\/(\d+)\/delete$/', $route, $m): (new TaskController())->deleteAttachment($m[1]); break;
    case preg_match('/^attachments\/(\d+)\/download$/', $route, $m): (new TaskController())->downloadAttachment($m[1]); break;

    // Admin - Users
    case $route === 'admin/users': if (!isAdmin()) redirect('dashboard'); $c = new AuthController(); $method === 'POST' ? $c->createUser() : $c->listUsers(); break;
    case preg_match('/^admin\/users\/(\d+)\/toggle$/', $route, $m): if (!isAdmin()) redirect('dashboard'); (new AuthController())->toggleUser($m[1]); break;
    case preg_match('/^admin\/users\/(\d+)\/delete$/', $route, $m): if (!isAdmin()) redirect('dashboard'); (new AuthController())->deleteUser($m[1]); break;

    // Admin - Vendors
    case $route === 'admin/vendors': if (!isAdmin()) redirect('dashboard'); $c = new VendorController(); $method === 'POST' ? $c->create() : $c->index(); break;
    case preg_match('/^admin\/vendors\/(\d+)\/update$/', $route, $m) && $method === 'POST': if (!isAdmin()) redirect('dashboard'); (new VendorController())->update($m[1]); break;
    case preg_match('/^admin\/vendors\/(\d+)\/delete$/', $route, $m): if (!isAdmin()) redirect('dashboard'); (new VendorController())->delete($m[1]); break;
    case preg_match('/^admin\/vendors\/(\d+)\/toggle$/', $route, $m): if (!isAdmin()) redirect('dashboard'); $v = new Vendor(); $v->toggleActive($m[1]); flash('success','Vendor updated'); redirect('admin/vendors'); break;
    case preg_match('/^admin\/vendors\/(\d+)\/upload$/', $route, $m) && $method === 'POST': if (!isAdmin()) redirect('dashboard'); (new VendorController())->upload($m[1]); break;
    case preg_match('/^vendor-attachments\/(\d+)\/delete$/', $route, $m): if (!isAdmin()) redirect('dashboard'); (new VendorController())->deleteAttachment($m[1]); break;
    case preg_match('/^vendor-attachments\/(\d+)\/download$/', $route, $m): if (!isAdmin()) redirect('dashboard'); (new VendorController())->downloadAttachment($m[1]); break;

    // JSON API
    case $route === 'api/tasks/reorder' && $method === 'POST': (new TaskController())->reorder(); break;
    case preg_match('/^api\/tasks\/(\d+)\/move$/', $route, $m) && $method === 'POST': (new TaskController())->move($m[1]); break;
    case preg_match('/^api\/tasks\/(\d+)$/', $route, $m): (new TaskController())->getJson($m[1]); break;

    default: http_response_code(404); echo '<h1>404</h1>'; break;
}
