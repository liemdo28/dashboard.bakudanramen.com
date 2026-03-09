<?php
class DashboardController {
    private $taskModel;
    private $projectModel;
    private $userModel;

    public function __construct() {
        $this->taskModel = new Task();
        $this->projectModel = new Project();
        $this->userModel = new User();
    }

    public function index() {
        $userId = $_SESSION['user_id'];
        $totalProjects = $this->projectModel->count();
        $totalTasks = $this->taskModel->totalCount();
        $completedTasks = $this->taskModel->completedCount();
        $totalMembers = $this->userModel->count();
        $myTasks = $this->taskModel->getByUser($userId, 10);
        $upcomingTasks = $this->taskModel->getUpcoming($userId, 7);
        $overdueTasks = $this->taskModel->getOverdue($userId);
        $tasksByStatus = $this->taskModel->countByStatus();
        $projects = $this->projectModel->getByUser($userId);
        $notifications = new Notification();
        $unreadCount = $notifications->getUnreadCount($userId);
        require __DIR__ . '/../views/dashboard/index.php';
    }

    public function calendar() {
        $userId = $_SESSION['user_id'];
        $projects = $this->projectModel->getByUser($userId);
        $notifications = new Notification();
        $unreadCount = $notifications->getUnreadCount($userId);
        require __DIR__ . '/../views/calendar/index.php';
    }

    public function inbox() {
        $userId = $_SESSION['user_id'];
        $notifModel = new Notification();
        $allNotifs = $notifModel->getByUser($userId, 50);
        $unreadCount = $notifModel->getUnreadCount($userId);
        $projects = $this->projectModel->getByUser($userId);
        require __DIR__ . '/../views/inbox/index.php';
    }

    public function myTasks() {
        $userId = $_SESSION['user_id'];
        $myTasks = $this->taskModel->getByUser($userId, 100);
        $overdueTasks = $this->taskModel->getOverdue($userId);
        $projects = $this->projectModel->getByUser($userId);
        $notifications = new Notification();
        $unreadCount = $notifications->getUnreadCount($userId);
        require __DIR__ . '/../views/dashboard/my_tasks.php';
    }
}
