<?php
class TaskController {
    private $taskModel;
    public function __construct() { $this->taskModel = new Task(); }

    public function show($id) {
        $task = $this->taskModel->findById($id);
        if (!$task) redirect('dashboard');
        $comments = (new Comment())->getByTask($id);
        $attachments = $this->taskModel->getAttachments($id);
        $users = (new User())->getActive();
        $sections = (new Project())->getSections($task['project_id']);
        require __DIR__ . '/../views/tasks/detail.php';
    }

    public function getJson($id) {
        $task = $this->taskModel->findById($id);
        if (!$task) json_response(['error' => 'Not found'], 404);
        json_response(['task' => $task, 'comments' => (new Comment())->getByTask($id), 'attachments' => $this->taskModel->getAttachments($id)]);
    }

    public function store() {
        $title = trim($_POST['title'] ?? '');
        if (empty($title)) { flash('error', 'Tiêu đề không được trống.'); redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard'); }

        $assigneeId = $_POST['assignee_id'] ?: null;
        $projectId = $_POST['project_id'];

        $id = $this->taskModel->create([
            'project_id' => $projectId,
            'section_id' => $_POST['section_id'] ?? null,
            'title' => $title,
            'description' => $_POST['description'] ?? '',
            'assignee_id' => $assigneeId,
            'priority' => $_POST['priority'] ?? 'medium',
            'status' => $_POST['status'] ?? 'todo',
            'due_date' => $_POST['due_date'] ?: null,
            'start_date' => $_POST['start_date'] ?: null,
            'created_by' => $_SESSION['user_id']
        ]);

        // Notify assignee
        if ($assigneeId && $assigneeId != $_SESSION['user_id']) {
            $proj = (new Project())->findById($projectId);
            notifyUser([
                'user_id' => $assigneeId,
                'type' => 'task_assigned',
                'title' => 'Task mới được giao cho bạn',
                'message' => $title . ' — ' . ($proj['name'] ?? ''),
                'task_id' => $id,
                'project_id' => $projectId,
                'from_user_id' => $_SESSION['user_id'],
            ]);
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) json_response(['success' => true, 'task_id' => $id]);
        flash('success', 'Tạo task thành công.');
        redirect($_SERVER['HTTP_REFERER'] ?? 'projects/' . $projectId);
    }

    public function update($id) {
        $task = $this->taskModel->findById($id);
        $data = [];
        foreach (['title','description','assignee_id','priority','status','due_date','start_date','section_id'] as $f) {
            if (isset($_POST[$f])) $data[$f] = $_POST[$f];
        }
        if (isset($_POST['is_completed'])) {
            $data['is_completed'] = $_POST['is_completed'] ? 1 : 0;
            $data['status'] = $_POST['is_completed'] ? 'done' : 'todo';
        }

        // Notify on reassignment
        if (isset($data['assignee_id']) && $data['assignee_id'] != ($task['assignee_id'] ?? '') && $data['assignee_id'] && $data['assignee_id'] != $_SESSION['user_id']) {
            notifyUser([
                'user_id' => $data['assignee_id'],
                'type' => 'task_assigned',
                'title' => 'Task được giao cho bạn',
                'message' => ($data['title'] ?? $task['title']),
                'task_id' => $id,
                'project_id' => $task['project_id'],
                'from_user_id' => $_SESSION['user_id'],
            ]);
        }

        $this->taskModel->update($id, $data);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) json_response(['success' => true]);
        flash('success', 'Cập nhật task thành công.');
        redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
    }

    public function delete($id) {
        $task = $this->taskModel->findById($id);
        $this->taskModel->delete($id);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) json_response(['success' => true]);
        flash('success', 'Xóa task thành công.');
        redirect('projects/' . ($task['project_id'] ?? ''));
    }

    public function toggleComplete($id) {
        $task = $this->taskModel->findById($id);
        $this->taskModel->toggleComplete($id);

        // Notify creator when assignee completes task
        if ($task && !$task['is_completed'] && $task['created_by'] != $_SESSION['user_id']) {
            notifyUser([
                'user_id' => $task['created_by'],
                'type' => 'task_completed',
                'title' => 'Task đã được hoàn thành',
                'message' => $task['title'],
                'task_id' => $id,
                'project_id' => $task['project_id'],
                'from_user_id' => $_SESSION['user_id'],
            ]);
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) json_response(['success' => true]);
        redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
    }

    public function move($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        $this->taskModel->move($id, $input['section_id'] ?? $_POST['section_id'] ?? null, $input['position'] ?? $_POST['position'] ?? 0);
        json_response(['success' => true]);
    }

    public function reorder() {
        $input = json_decode(file_get_contents('php://input'), true);
        $this->taskModel->reorder($input['tasks'] ?? []);
        json_response(['success' => true]);
    }

    public function upload($taskId) {
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) json_response(['error' => 'Upload failed'], 400);
        $file = $_FILES['file'];
        if ($file['size'] > MAX_UPLOAD_SIZE) json_response(['error' => 'File quá lớn'], 400);
        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
            $id = $this->taskModel->addAttachment(['task_id'=>$taskId, 'user_id'=>$_SESSION['user_id'], 'filename'=>$filename, 'original_name'=>$file['name'], 'file_size'=>$file['size'], 'mime_type'=>$file['type']]);
            json_response(['success' => true, 'id' => $id, 'filename' => $file['name']]);
        }
        json_response(['error' => 'Upload failed'], 500);
    }

    public function deleteAttachment($id) {
        $this->taskModel->deleteAttachment($id);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) json_response(['success' => true]);
        redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
    }

    public function downloadAttachment($id) {
        $att = $this->taskModel->findAttachment($id);
        if (!$att) redirect('dashboard');
        $fp = UPLOAD_DIR . $att['filename'];
        if (!file_exists($fp)) redirect('dashboard');
        header('Content-Type: ' . ($att['mime_type'] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $att['original_name'] . '"');
        header('Content-Length: ' . filesize($fp));
        readfile($fp);
        exit;
    }
}
