<?php
class Notification {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($data) {
        $id = $this->db->insert(
            "INSERT INTO notifications (user_id, type, title, message, task_id, project_id, from_user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['user_id'],
                $data['type'],
                $data['title'],
                $data['message'] ?? '',
                $data['task_id'] ?? null,
                $data['project_id'] ?? null,
                $data['from_user_id'] ?? null,
            ]
        );

        // Also queue email if user has email_notifications enabled
        $this->queueEmailNotification($data);

        return $id;
    }

    private function queueEmailNotification($data) {
        $user = $this->db->fetch("SELECT name, email, email_notifications FROM users WHERE id = ?", [$data['user_id']]);
        if (!$user || !$user['email_notifications']) return;

        $subject = APP_NAME . ' - ' . $data['title'];
        $body = $this->buildEmailBody($data, $user);

        $this->db->insert(
            "INSERT INTO email_queue (to_email, to_name, subject, body) VALUES (?, ?, ?, ?)",
            [$user['email'], $user['name'], $subject, $body]
        );
    }

    private function buildEmailBody($data, $user) {
        $name = htmlspecialchars($user['name']);
        $title = htmlspecialchars($data['title']);
        $message = htmlspecialchars(isset($data['message']) ? $data['message'] : '');
        $taskUrl = '';
        if (!empty($data['task_id'])) {
            $taskUrl = APP_URL . '/tasks/' . $data['task_id'];
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif">
<div style="max-width:520px;margin:30px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)">
  <div style="background:linear-gradient(135deg,#dc2626,#991b1b);padding:28px 32px;text-align:center">
    <h1 style="color:#fff;margin:0;font-size:22px;font-weight:800;letter-spacing:-.3px">TaskFlow</h1>
  </div>
  <div style="padding:28px 32px">
    <p style="font-size:15px;color:#333;margin:0 0 8px">Xin chào <strong>{$name}</strong>,</p>
    <div style="background:#fef2f2;border-left:4px solid #dc2626;padding:14px 18px;border-radius:0 8px 8px 0;margin:16px 0">
      <p style="font-size:15px;font-weight:600;color:#991b1b;margin:0 0 4px">{$title}</p>
      <p style="font-size:13px;color:#666;margin:0">{$message}</p>
    </div>
    $taskButton = '';
if ($taskUrl) {
    $taskButton = '<p style="margin:20px 0 0"><a href="'.$taskUrl.'" style="display:inline-block;background:#dc2626;color:#fff;text-decoration:none;padding:10px 24px;border-radius:8px;font-weight:600;font-size:14px">Xem Task →</a></p>';
}
  </div>
  <div style="padding:16px 32px;background:#f9fafb;text-align:center">
    <p style="font-size:11px;color:#9ca3af;margin:0">Bạn nhận email này vì đã bật thông báo email trên TaskFlow.</p>
  </div>
</div>
</body></html>
HTML;
    }

    public function getByUser($userId, $limit = 30) {
        return $this->db->fetchAll(
            "SELECT n.*, u.name as from_user_name
             FROM notifications n
             LEFT JOIN users u ON n.from_user_id = u.id
             WHERE n.user_id = ? ORDER BY n.created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    public function getUnreadCount($userId) {
        $r = $this->db->fetch(
            "SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
        return $r['c'] ?? 0;
    }

    public function markRead($id, $userId) {
        $this->db->update(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    public function markAllRead($userId) {
        $this->db->update(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }

    // Called by cron: notify users about tasks due tomorrow
    public function checkDueSoon() {
        $tasks = $this->db->fetchAll(
            "SELECT t.id, t.title, t.assignee_id, t.project_id, p.name as project_name
             FROM tasks t
             LEFT JOIN projects p ON t.project_id = p.id
             WHERE t.due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
             AND t.is_completed = 0 AND t.assignee_id IS NOT NULL"
        );
        foreach ($tasks as $t) {
            // Check if we already notified today
            $existing = $this->db->fetch(
                "SELECT id FROM notifications WHERE task_id = ? AND user_id = ? AND type = 'task_due_soon' AND DATE(created_at) = CURDATE()",
                [$t['id'], $t['assignee_id']]
            );
            if (!$existing) {
                $this->create([
                    'user_id' => $t['assignee_id'],
                    'type' => 'task_due_soon',
                    'title' => 'Task sắp đến hạn ngày mai',
                    'message' => $t['title'] . ' - ' . ($t['project_name'] ?? ''),
                    'task_id' => $t['id'],
                    'project_id' => $t['project_id'],
                ]);
            }
        }
    }

    // Called by cron: notify users about overdue tasks
    public function checkOverdue() {
        $tasks = $this->db->fetchAll(
            "SELECT t.id, t.title, t.assignee_id, t.project_id, p.name as project_name
             FROM tasks t
             LEFT JOIN projects p ON t.project_id = p.id
             WHERE t.due_date < CURDATE()
             AND t.is_completed = 0 AND t.assignee_id IS NOT NULL"
        );
        foreach ($tasks as $t) {
            $existing = $this->db->fetch(
                "SELECT id FROM notifications WHERE task_id = ? AND user_id = ? AND type = 'task_overdue' AND DATE(created_at) = CURDATE()",
                [$t['id'], $t['assignee_id']]
            );
            if (!$existing) {
                $this->create([
                    'user_id' => $t['assignee_id'],
                    'type' => 'task_overdue',
                    'title' => 'Task đã quá hạn!',
                    'message' => $t['title'] . ' - ' . ($t['project_name'] ?? ''),
                    'task_id' => $t['id'],
                    'project_id' => $t['project_id'],
                ]);
            }
        }
    }

    // Process email queue (called by cron)
    public static function processEmailQueue($limit = 20) {
        $db = Database::getInstance();
        $emails = $db->fetchAll(
            "SELECT * FROM email_queue WHERE status = 'pending' AND attempts < 3 ORDER BY created_at ASC LIMIT ?",
            [$limit]
        );

        foreach ($emails as $email) {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . APP_NAME . " <noreply@" . parse_url(APP_URL, PHP_URL_HOST) . ">\r\n";

            $sent = @mail($email['to_email'], $email['subject'], $email['body'], $headers);

            if ($sent) {
                $db->update("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?", [$email['id']]);
            } else {
                $db->update(
                    "UPDATE email_queue SET attempts = attempts + 1, last_error = ?, status = IF(attempts >= 2, 'failed', 'pending') WHERE id = ?",
                    [error_get_last()['message'] ?? 'Unknown error', $email['id']]
                );
            }
        }
    }
}
