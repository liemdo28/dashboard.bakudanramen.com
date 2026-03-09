<?php
class Task {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        return $this->db->fetch(
            "SELECT t.*, u.name as assignee_name, c.name as creator_name, s.name as section_name, p.name as project_name
             FROM tasks t
             LEFT JOIN users u ON t.assignee_id = u.id
             LEFT JOIN users c ON t.created_by = c.id
             LEFT JOIN sections s ON t.section_id = s.id
             LEFT JOIN projects p ON t.project_id = p.id
             WHERE t.id = ?",
            [$id]
        );
    }

    public function getByProject($projectId) {
        return $this->db->fetchAll(
            "SELECT t.*, u.name as assignee_name FROM tasks t
             LEFT JOIN users u ON t.assignee_id = u.id
             WHERE t.project_id = ? ORDER BY t.position, t.created_at DESC",
            [$projectId]
        );
    }

    public function getBySection($sectionId) {
        return $this->db->fetchAll(
            "SELECT t.*, u.name as assignee_name FROM tasks t
             LEFT JOIN users u ON t.assignee_id = u.id
             WHERE t.section_id = ? ORDER BY t.position",
            [$sectionId]
        );
    }

    public function getByUser($userId, $limit = 20) {
        return $this->db->fetchAll(
            "SELECT t.*, p.name as project_name, s.name as section_name
             FROM tasks t
             LEFT JOIN projects p ON t.project_id = p.id
             LEFT JOIN sections s ON t.section_id = s.id
             WHERE t.assignee_id = ? AND t.is_completed = 0
             ORDER BY t.due_date ASC, t.priority DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    public function getUpcoming($userId, $days = 7) {
        return $this->db->fetchAll(
            "SELECT t.*, p.name as project_name FROM tasks t
             LEFT JOIN projects p ON t.project_id = p.id
             WHERE t.assignee_id = ? AND t.is_completed = 0
             AND t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY t.due_date ASC",
            [$userId, $days]
        );
    }

    public function getOverdue($userId) {
        return $this->db->fetchAll(
            "SELECT t.*, p.name as project_name FROM tasks t
             LEFT JOIN projects p ON t.project_id = p.id
             WHERE t.assignee_id = ? AND t.is_completed = 0
             AND t.due_date < CURDATE() ORDER BY t.due_date ASC",
            [$userId]
        );
    }

    public function create($data) {
        $maxPos = $this->db->fetch(
            "SELECT MAX(position) as mp FROM tasks WHERE section_id = ?",
            [$data['section_id'] ?? null]
        );
        return $this->db->insert(
            "INSERT INTO tasks (project_id, section_id, title, description, assignee_id, priority, status, due_date, start_date, position, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['project_id'],
                $data['section_id'] ?? null,
                $data['title'],
                $data['description'] ?? '',
                $data['assignee_id'] ?: null,
                $data['priority'] ?? 'medium',
                $data['status'] ?? 'todo',
                $data['due_date'] ?: null,
                $data['start_date'] ?: null,
                ($maxPos['mp'] ?? -1) + 1,
                $data['created_by']
            ]
        );
    }

    public function update($id, $data) {
        $fields = [];
        $params = [];
        $allowed = ['title', 'description', 'assignee_id', 'priority', 'status', 'due_date', 'start_date', 'section_id', 'position', 'is_completed'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $params[] = $data[$field] ?: null;
            }
        }
        if (isset($data['is_completed']) && $data['is_completed']) {
            $fields[] = "completed_at = NOW()";
        }
        $params[] = $id;
        return $this->db->update("UPDATE tasks SET " . implode(', ', $fields) . " WHERE id = ?", $params);
    }

    public function delete($id) {
        return $this->db->delete("DELETE FROM tasks WHERE id = ?", [$id]);
    }

    public function toggleComplete($id) {
        return $this->db->update(
            "UPDATE tasks SET is_completed = NOT is_completed,
             completed_at = IF(is_completed = 0, NOW(), NULL),
             status = IF(is_completed = 0, 'done', 'todo')
             WHERE id = ?",
            [$id]
        );
    }

    public function move($id, $sectionId, $position) {
        return $this->db->update(
            "UPDATE tasks SET section_id = ?, position = ? WHERE id = ?",
            [$sectionId, $position, $id]
        );
    }

    public function reorder($tasks) {
        foreach ($tasks as $task) {
            $this->db->update(
                "UPDATE tasks SET section_id = ?, position = ? WHERE id = ?",
                [$task['section_id'], $task['position'], $task['id']]
            );
        }
    }

    // Stats
    public function countByStatus($projectId = null) {
        $where = $projectId ? "WHERE project_id = ?" : "";
        $params = $projectId ? [$projectId] : [];
        return $this->db->fetchAll(
            "SELECT status, COUNT(*) as count FROM tasks $where GROUP BY status",
            $params
        );
    }

    public function countByUser($projectId = null) {
        $where = $projectId ? "AND t.project_id = ?" : "";
        $params = $projectId ? [$projectId] : [];
        return $this->db->fetchAll(
            "SELECT u.name, COUNT(*) as total,
             SUM(t.is_completed) as completed
             FROM tasks t JOIN users u ON t.assignee_id = u.id
             WHERE t.assignee_id IS NOT NULL $where
             GROUP BY t.assignee_id",
            $params
        );
    }

    public function totalCount() {
        $result = $this->db->fetch("SELECT COUNT(*) as total FROM tasks");
        return $result['total'];
    }

    public function completedCount() {
        $result = $this->db->fetch("SELECT COUNT(*) as total FROM tasks WHERE is_completed = 1");
        return $result['total'];
    }

    // Attachments
    public function getAttachments($taskId) {
        return $this->db->fetchAll(
            "SELECT a.*, u.name as user_name FROM attachments a
             JOIN users u ON a.user_id = u.id WHERE a.task_id = ? ORDER BY a.created_at DESC",
            [$taskId]
        );
    }

    public function addAttachment($data) {
        return $this->db->insert(
            "INSERT INTO attachments (task_id, user_id, filename, original_name, file_size, mime_type)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$data['task_id'], $data['user_id'], $data['filename'], $data['original_name'], $data['file_size'], $data['mime_type']]
        );
    }

    public function findAttachment($id) {
        return $this->db->fetch("SELECT * FROM attachments WHERE id = ?", [$id]);
    }

    public function deleteAttachment($id) {
        $att = $this->findAttachment($id);
        if ($att) {
            $filepath = UPLOAD_DIR . $att['filename'];
            if (file_exists($filepath)) unlink($filepath);
            return $this->db->delete("DELETE FROM attachments WHERE id = ?", [$id]);
        }
        return false;
    }
}
