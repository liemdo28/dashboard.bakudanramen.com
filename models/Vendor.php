<?php
class Vendor {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    private function normalizedName($name) {
        return mb_strtolower(trim((string) $name));
    }

    private function hasVendorsTable() {
        return $this->db->tableExists('vendors');
    }

    private function hasAttachmentsTable() {
        return $this->db->tableExists('vendor_attachments');
    }

    public function getAll() {
        if (!$this->hasVendorsTable()) return [];
        return $this->db->fetchAll("SELECT * FROM vendors ORDER BY name");
    }

    public function getAllActive() {
        if (!$this->hasVendorsTable()) return [];
        return $this->db->fetchAll("SELECT * FROM vendors WHERE is_active = 1 ORDER BY name");
    }

    public function find($id) {
        if (!$this->hasVendorsTable()) return null;
        return $this->db->fetch("SELECT * FROM vendors WHERE id = ?", [$id]);
    }

    public function findByName($name) {
        if (!$this->hasVendorsTable()) return null;
        $normalized = $this->normalizedName($name);
        if ($normalized === '') return null;

        return $this->db->fetch(
            "SELECT * FROM vendors WHERE LOWER(TRIM(name)) = ? LIMIT 1",
            [$normalized]
        );
    }

    public function create($data) {
        if (!$this->hasVendorsTable()) return null;
        return $this->db->insert(
            "INSERT INTO vendors (name, payment_url, login_info, notes, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())",
            [$data['name'], $data['payment_url'] ?? null, $data['login_info'] ?? null, $data['notes'] ?? null]
        );
    }

    public function createOrGet($data) {
        $existing = $this->findByName($data['name'] ?? '');
        if ($existing) {
            $this->update($existing['id'], [
                'name' => $data['name'] ?? $existing['name'],
                'payment_url' => $data['payment_url'] ?? $existing['payment_url'],
                'login_info' => $data['login_info'] ?? $existing['login_info'],
                'notes' => $data['notes'] ?? $existing['notes'],
            ]);
            return (int) $existing['id'];
        }
        return $this->create($data);
    }

    public function update($id, $data) {
        if (!$this->hasVendorsTable()) return 0;
        return $this->db->execute(
            "UPDATE vendors SET name = ?, payment_url = ?, login_info = ?, notes = ?, updated_at = NOW() WHERE id = ?",
            [$data['name'], $data['payment_url'] ?? null, $data['login_info'] ?? null, $data['notes'] ?? null, $id]
        );
    }

    public function delete($id) {
        if (!$this->hasVendorsTable()) return 0;
        return $this->db->execute("DELETE FROM vendors WHERE id = ?", [$id]);
    }

    public function toggleActive($id) {
        if (!$this->hasVendorsTable()) return 0;
        return $this->db->execute("UPDATE vendors SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?", [$id]);
    }

    // Attachments
    public function getAttachments($vendorId) {
        if (!$this->hasAttachmentsTable()) return [];
        return $this->db->fetchAll("SELECT * FROM vendor_attachments WHERE vendor_id = ? ORDER BY created_at DESC", [$vendorId]);
    }

    public function addAttachment($data) {
        if (!$this->hasAttachmentsTable()) return null;
        return $this->db->insert(
            "INSERT INTO vendor_attachments (vendor_id, filename, original_name, file_size, mime_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
            [$data['vendor_id'], $data['filename'], $data['original_name'], $data['file_size'], $data['mime_type']]
        );
    }

    public function findAttachment($id) {
        if (!$this->hasAttachmentsTable()) return null;
        return $this->db->fetch("SELECT * FROM vendor_attachments WHERE id = ?", [$id]);
    }

    public function deleteAttachment($id) {
        if (!$this->hasAttachmentsTable()) return null;
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
        return $this->createOrGet([
            'name' => $name,
            'payment_url' => null,
            'login_info' => null,
            'notes' => null,
        ]);
    }

    public function syncBillsForVendor($vendorId, $vendorName, $matchName = null) {
        if (!$this->hasVendorsTable()) return 0;
        if (!$this->db->columnExists('bills', 'vendor_id')) return 0;

        $normalized = $this->normalizedName($matchName ?? $vendorName);
        if ($normalized === '') return 0;

        return $this->db->execute(
            "UPDATE bills
             SET vendor = ?, vendor_id = ?
             WHERE vendor_id = ?
             OR (vendor_id IS NULL AND LOWER(TRIM(vendor)) = ?)",
            [$vendorName, $vendorId, $vendorId, $normalized]
        );
    }
}
