<?php
class Vendor {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll() {
        return $this->db->fetchAll("SELECT * FROM vendors ORDER BY name");
    }

    public function getAllActive() {
        return $this->db->fetchAll("SELECT * FROM vendors WHERE is_active = 1 ORDER BY name");
    }

    public function find($id) {
        return $this->db->fetch("SELECT * FROM vendors WHERE id = ?", [$id]);
    }

    public function create($data) {
        return $this->db->insert(
            "INSERT INTO vendors (name, payment_url, login_info, notes, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())",
            [$data['name'], $data['payment_url'] ?? null, $data['login_info'] ?? null, $data['notes'] ?? null]
        );
    }

    public function update($id, $data) {
        return $this->db->execute(
            "UPDATE vendors SET name = ?, payment_url = ?, login_info = ?, notes = ?, updated_at = NOW() WHERE id = ?",
            [$data['name'], $data['payment_url'] ?? null, $data['login_info'] ?? null, $data['notes'] ?? null, $id]
        );
    }

    public function delete($id) {
        return $this->db->execute("DELETE FROM vendors WHERE id = ?", [$id]);
    }

    public function toggleActive($id) {
        return $this->db->execute("UPDATE vendors SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?", [$id]);
    }

    // Attachments
    public function getAttachments($vendorId) {
        return $this->db->fetchAll("SELECT * FROM vendor_attachments WHERE vendor_id = ? ORDER BY created_at DESC", [$vendorId]);
    }

    public function addAttachment($data) {
        return $this->db->insert(
            "INSERT INTO vendor_attachments (vendor_id, filename, original_name, file_size, mime_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
            [$data['vendor_id'], $data['filename'], $data['original_name'], $data['file_size'], $data['mime_type']]
        );
    }

    public function findAttachment($id) {
        return $this->db->fetch("SELECT * FROM vendor_attachments WHERE id = ?", [$id]);
    }

    public function deleteAttachment($id) {
        $att = $this->findAttachment($id);
        if ($att) {
            $filepath = UPLOAD_DIR . $att['filename'];
            if (file_exists($filepath)) unlink($filepath);
            $this->db->execute("DELETE FROM vendor_attachments WHERE id = ?", [$id]);
        }
        return $att;
    }

    // Quick create from bill form - returns new vendor ID
    public function quickCreate($name) {
        return $this->db->insert(
            "INSERT INTO vendors (name, is_active, created_at) VALUES (?, 1, NOW())",
            [$name]
        );
    }
}
