<?php
class CommentController {
    private $commentModel;
    public function __construct() { $this->commentModel = new Comment(); }

    public function store($taskId) {
        $content = trim($_POST['content'] ?? '');
        if (empty($content)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) json_response(['error' => 'Nội dung trống'], 400);
            redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
        }

        $id = $this->commentModel->create($taskId, $_SESSION['user_id'], $content);

        // Notify task assignee and creator about new comment
        $task = (new Task())->findById($taskId);
        if ($task) {
            $notifyUsers = array_unique(array_filter([$task['assignee_id'], $task['created_by']]));
            $user = currentUser();
            foreach ($notifyUsers as $uid) {
                if ($uid != $_SESSION['user_id']) {
                    notifyUser([
                        'user_id' => $uid,
                        'type' => 'task_commented',
                        'title' => ($user['name'] ?? 'Ai đó') . ' đã bình luận',
                        'message' => mb_substr($content, 0, 100) . ' — ' . $task['title'],
                        'task_id' => $taskId,
                        'project_id' => $task['project_id'],
                        'from_user_id' => $_SESSION['user_id'],
                    ]);
                }
            }
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $user = currentUser();
            json_response(['success'=>true, 'comment'=>['id'=>$id, 'content'=>$content, 'user_name'=>$user['name'], 'created_at'=>date('Y-m-d H:i:s')]]);
        }
        redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
    }

    public function delete($id) {
        $comment = $this->commentModel->findById($id);
        if ($comment && ($comment['user_id'] == $_SESSION['user_id'] || isAdmin())) {
            $this->commentModel->delete($id);
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) json_response(['success' => true]);
        redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
    }
}
