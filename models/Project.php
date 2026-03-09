<?php
class Project {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        return $this->db->fetch(
            "SELECT p.*, u.name as owner_name FROM projects p JOIN users u ON p.owner_id = u.id WHERE p.id = ?",
            [$id]
        );
    }

    public function getByUser($userId) {
        return $this->db->fetchAll(
            "SELECT DISTINCT p.*, u.name as owner_name,
             (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_count,
             (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND is_completed = 1) as completed_count
             FROM projects p
             JOIN users u ON p.owner_id = u.id
             LEFT JOIN project_members pm ON p.id = pm.project_id
             WHERE p.owner_id = ? OR pm.user_id = ?
             ORDER BY p.updated_at DESC",
            [$userId, $userId]
        );
    }

    public function getAll() {
        return $this->db->fetchAll(
            "SELECT p.*, u.name as owner_name,
             (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_count,
             (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND is_completed = 1) as completed_count
             FROM projects p JOIN users u ON p.owner_id = u.id ORDER BY p.updated_at DESC"
        );
    }

    public function create($data) {
        $id = $this->db->insert(
            "INSERT INTO projects (name, description, color, owner_id, status) VALUES (?, ?, ?, ?, ?)",
            [$data['name'], $data['description'] ?? '', $data['color'] ?? '#DC2626', $data['owner_id'], $data['status'] ?? 'active']
        );
        // Add owner as project member
        $this->db->insert(
            "INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'owner')",
            [$id, $data['owner_id']]
        );
        // Create default sections
        $sections = ['To Do', 'In Progress', 'Review', 'Done'];
        foreach ($sections as $i => $name) {
            $this->db->insert(
                "INSERT INTO sections (project_id, name, position) VALUES (?, ?, ?)",
                [$id, $name, $i]
            );
        }
        return $id;
    }

    public function update($id, $data) {
        $fields = [];
        $params = [];
        foreach (['name', 'description', 'color', 'status'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        $params[] = $id;
        return $this->db->update("UPDATE projects SET " . implode(', ', $fields) . " WHERE id = ?", $params);
    }

    public function delete($id) {
        return $this->db->delete("DELETE FROM projects WHERE id = ?", [$id]);
    }

    // Members
    public function getMembers($projectId) {
        return $this->db->fetchAll(
            "SELECT u.id, u.name, u.email, u.avatar, pm.role FROM project_members pm
             JOIN users u ON pm.user_id = u.id WHERE pm.project_id = ? ORDER BY u.name",
            [$projectId]
        );
    }

    public function addMember($projectId, $userId, $role = 'editor') {
        return $this->db->insert(
            "INSERT IGNORE INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)",
            [$projectId, $userId, $role]
        );
    }

    public function removeMember($projectId, $userId) {
        return $this->db->delete(
            "DELETE FROM project_members WHERE project_id = ? AND user_id = ?",
            [$projectId, $userId]
        );
    }

    public function isMember($projectId, $userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as c FROM project_members WHERE project_id = ? AND user_id = ?",
            [$projectId, $userId]
        );
        return $result['c'] > 0;
    }

    // Sections
    public function getSections($projectId) {
        return $this->db->fetchAll(
            "SELECT * FROM sections WHERE project_id = ? ORDER BY position",
            [$projectId]
        );
    }

    public function addSection($projectId, $name) {
        $maxPos = $this->db->fetch("SELECT MAX(position) as mp FROM sections WHERE project_id = ?", [$projectId]);
        return $this->db->insert(
            "INSERT INTO sections (project_id, name, position) VALUES (?, ?, ?)",
            [$projectId, $name, ($maxPos['mp'] ?? -1) + 1]
        );
    }

    public function deleteSection($sectionId) {
        return $this->db->delete("DELETE FROM sections WHERE id = ?", [$sectionId]);
    }

    public function count() {
        $result = $this->db->fetch("SELECT COUNT(*) as total FROM projects WHERE status = 'active'");
        return $result['total'];
    }
}
