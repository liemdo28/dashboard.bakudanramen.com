<?php
/**
 * CRON JOB - Run every hour
 * Hosting cPanel: Add cron job → php /home/username/dashboard.bakudanramen.com/cron.php
 * Or: wget -q -O /dev/null https://dashboard.bakudanramen.com/cron.php?key=YOUR_SECRET_KEY
 */

// Security: only allow CLI or with secret key
$isCliExecution = php_sapi_name() === 'cli';
$expectedKey = 'taskflow-cron-2024-secret'; // Change this!
$validWebRequest = isset($_GET['key']) && $_GET['key'] === $expectedKey;

if (!$isCliExecution && !$validWebRequest) {
    http_response_code(403);
    die('Forbidden');
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Notification.php';
require_once __DIR__ . '/models/Bill.php';

$notif = new Notification();

// 1. Check tasks due tomorrow → notify assignees
$notif->checkDueSoon();

// 2. Check overdue tasks → notify assignees
$notif->checkOverdue();

// 2b. Check bills due in 3 days → notify admins
$billModel = new Bill();
$billModel->updateOverdueStatuses();
$dueBills = $billModel->dueInDays(3);
if (!empty($dueBills)) {
    $db = Database::getInstance();
    $admins = $db->fetchAll("SELECT id FROM users WHERE role = 'admin' AND is_active = 1");
    foreach ($dueBills as $b) {
        foreach ($admins as $a) {
            $notif->create([
                'user_id' => $a['id'],
                'type' => 'bill',
                'title' => 'Bill sắp đến hạn (3 ngày)',
                'message' => $b['store_name'] . ' • ' . $b['title'] . ' • Due: ' . $b['due_date'] . ' • ' . rtrim(APP_URL,'/') . '/bills/store/' . $b['store_id'],
            ]);
        }
        $billModel->setReminded($b['id']);
    }
}


// 3. Process email queue → send pending emails
Notification::processEmailQueue(30);

echo "Cron completed: " . date('Y-m-d H:i:s') . "\n";
