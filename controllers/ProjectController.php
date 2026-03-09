<?php
class ProjectController {
    private $projectModel;
    private $taskModel;
    private $userModel;

    public function __construct() {
        $this->projectModel = new Project();
        $this->taskModel = new Task();
        $this->userModel = new User();
    }

    public function index() {
        $projects = $this->projectModel->getByUser($_SESSION['user_id']);
        require __DIR__ . '/../views/projects/index.php';
    }

    public function create() {
        $users = $this->userModel->getActive();
        require __DIR__ . '/../views/projects/create.php';
    }

    public function store() {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            flash('error', 'Tên project không được để trống.');
            redirect('projects/create');
        }

        $id = $this->projectModel->create([
            'name' => $name,
            'description' => $_POST['description'] ?? '',
            'color' => $_POST['color'] ?? '#DC2626',
            'owner_id' => $_SESSION['user_id']
        ]);

        // Add selected members
        if (!empty($_POST['members'])) {
            foreach ($_POST['members'] as $memberId) {
                if ($memberId != $_SESSION['user_id']) {
                    $this->projectModel->addMember($id, $memberId);
                }
            }
        }

        flash('success', 'Tạo project thành công.');
        redirect('projects/' . $id);
    }

    public function show($id) {
        $project = $this->projectModel->findById($id);
        if (!$project) redirect('projects');

        // Check access
        if (!isAdmin() && !$this->projectModel->isMember($id, $_SESSION['user_id'])) {
            redirect('projects');
        }

        $view = $_GET['view'] ?? 'board';
        $sections = $this->projectModel->getSections($id);
        $tasks = $this->taskModel->getByProject($id);
        $members = $this->projectModel->getMembers($id);
        $allUsers = $this->userModel->getActive();

        // Group tasks by section for board view
        $tasksBySection = [];
        foreach ($sections as $section) {
            $tasksBySection[$section['id']] = array_filter($tasks, function($t) use ($section) {
                return $t['section_id'] == $section['id'];
            });
            usort($tasksBySection[$section['id']], function($a, $b) {
                return $a['position'] - $b['position'];
            });
        }

        require __DIR__ . '/../views/projects/show.php';
    }

    public function edit($id) {
        $project = $this->projectModel->findById($id);
        if (!$project) redirect('projects');
        $members = $this->projectModel->getMembers($id);
        $users = $this->userModel->getActive();
        require __DIR__ . '/../views/projects/edit.php';
    }

    public function update($id) {
        $this->projectModel->update($id, [
            'name' => trim($_POST['name'] ?? ''),
            'description' => $_POST['description'] ?? '',
            'color' => $_POST['color'] ?? '#DC2626',
            'status' => $_POST['status'] ?? 'active'
        ]);
        flash('success', 'Cập nhật project thành công.');
        redirect('projects/' . $id);
    }

    public function delete($id) {
        $project = $this->projectModel->findById($id);
        if ($project && ($project['owner_id'] == $_SESSION['user_id'] || isAdmin())) {
            $this->projectModel->delete($id);
            flash('success', 'Xóa project thành công.');
        }
        redirect('projects');
    }

    public function addMember($id) {
        $userId = $_POST['user_id'] ?? 0;
        if ($userId) {
            $this->projectModel->addMember($id, $userId);
            flash('success', 'Thêm thành viên thành công.');
        }
        redirect('projects/' . $id);
    }

    public function removeMember($projectId, $userId) {
        $this->projectModel->removeMember($projectId, $userId);
        flash('success', 'Xóa thành viên thành công.');
        redirect('projects/' . $projectId);
    }

    public function addSection($projectId) {
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            $this->projectModel->addSection($projectId, $name);
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            json_response(['success' => true]);
        }
        redirect('projects/' . $projectId);
    }

    public function deleteSection($sectionId) {
        $this->projectModel->deleteSection($sectionId);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            json_response(['success' => true]);
        }
        redirect($_SERVER['HTTP_REFERER'] ?? 'projects');
    }
}
