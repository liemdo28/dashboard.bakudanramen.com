<?php
class Store {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function allActive() {
        return $this->db->fetchAll("SELECT * FROM stores WHERE is_active = 1 ORDER BY name");
    }

    public function find($id) {
        return $this->db->fetch("SELECT * FROM stores WHERE id = ?", [$id]);
    }

    public function create($data) {
        return $this->db->insert(
            "INSERT INTO stores (name, address, color, is_active, created_at) VALUES (?, ?, ?, 1, NOW())",
            [$data['name'], $data['address'] ?? null, $data['color'] ?? null]
        );
    }

    public function update($id, $data) {
        return $this->db->execute(
            "UPDATE stores SET name = ?, address = ?, color = ? WHERE id = ?",
            [$data['name'], $data['address'] ?? null, $data['color'] ?? null, $id]
        );
    }

    public function delete($id) {
        // Soft delete - deactivate instead of hard delete (bills reference this store)
        return $this->db->execute("UPDATE stores SET is_active = 0 WHERE id = ?", [$id]);
    }
}
