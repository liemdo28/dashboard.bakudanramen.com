<?php
class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        return $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
    }

    public function findByEmail($email) {
        $normalizedEmail = strtolower(trim($email));
        return $this->db->fetch("SELECT * FROM users WHERE LOWER(email) = ?", [$normalizedEmail]);
    }

    public function getAll() {
        return $this->db->fetchAll("SELECT id, name, email, role, is_active, created_at FROM users ORDER BY name");
    }

    public function getActive() {
        return $this->db->fetchAll("SELECT id, name, email, avatar, role FROM users WHERE is_active = 1 ORDER BY name");
    }

    public function create($data) {
        $name = trim($data['name'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'member';

        return $this->db->insert(
            "INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)",
            [$name, $email, password_hash($password, PASSWORD_BCRYPT), $role]
        );
    }

    public function update($id, $data) {
        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $params[] = trim($data['name']);
        }

        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = strtolower(trim($data['email']));
        }

        foreach (['role', 'is_active', 'avatar'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (isset($data['password']) && $data['password'] !== '') {
            $fields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        return $this->db->update("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?", $params);
    }

    public function delete($id) {
        return $this->db->delete("DELETE FROM users WHERE id = ?", [$id]);
    }

    public function toggleActive($id) {
        return $this->db->update("UPDATE users SET is_active = NOT is_active WHERE id = ?", [$id]);
    }

    public function verify($email, $password) {
        $user = $this->findByEmail($email);

        if (!$user) return false;
        if (!isset($user['is_active']) || (int)$user['is_active'] !== 1) return false;

        $storedPassword = $user['password'] ?? '';
        if ($storedPassword === '') return false;

        // Password is already hashed (expected state).
        if (password_verify($password, $storedPassword)) {
            return $user;
        }

        // Legacy support: plain-text password in DB.
        // Allow once, then upgrade it to bcrypt hash.
        if ($password === $storedPassword) {
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            $this->db->update("UPDATE users SET password = ? WHERE id = ?", [$newHash, $user['id']]);
            $user['password'] = $newHash;
            return $user;
        }

        return false;
    }

    public function count() {
        $result = $this->db->fetch("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
        return $result['total'] ?? 0;
    }
}
