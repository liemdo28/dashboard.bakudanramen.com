<?php
class Bill {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    private function supportsVendors() {
        return $this->db->tableExists('vendors') && $this->db->columnExists('bills', 'vendor_id');
    }

    private function hasBillAttachments() {
        return $this->db->tableExists('bill_attachments');
    }

    private function hasPaidAtColumn() {
        return $this->db->columnExists('bills', 'paid_at');
    }

    public function getByStore($storeId, $month=null, $year=null) {
        $params = [$storeId];
        $where = "WHERE b.store_id = ?";
        if ($month && $year) {
            $where .= " AND MONTH(b.due_date) = ? AND YEAR(b.due_date) = ?";
            $params[] = $month; $params[] = $year;
        }

        $vendorJoin = '';
        $vendorFields = "NULL AS vendor_name, NULL AS vendor_payment_url";
        if ($this->supportsVendors()) {
            $vendorJoin = "LEFT JOIN vendors v ON v.id = b.vendor_id";
            $vendorFields = "v.name AS vendor_name, v.payment_url AS vendor_payment_url";
        }

        return $this->db->fetchAll(
            "SELECT b.*, s.name AS store_name, s.color AS store_color,
                    $vendorFields
             FROM bills b
             JOIN stores s ON s.id = b.store_id
             $vendorJoin
             $where
             ORDER BY b.due_date ASC, FIELD(b.status,'overdue','pending','paid') ASC, b.title ASC",
            $params
        );
    }

    public function find($id) {
        return $this->db->fetch(
            "SELECT b.*, s.name AS store_name, s.color AS store_color
             FROM bills b JOIN stores s ON s.id=b.store_id WHERE b.id = ?",
            [$id]
        );
    }

    public function create($data) {
        if ($this->db->columnExists('bills', 'vendor_id')) {
            return $this->db->insert(
                "INSERT INTO bills (store_id, title, vendor, vendor_id, amount, due_date, status, note, color, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, NOW())",
                [
                    $data['store_id'],
                    $data['title'],
                    $data['vendor'] ?? null,
                    $data['vendor_id'] ?? null,
                    $data['amount'] ?? null,
                    $data['due_date'],
                    $data['note'] ?? null,
                    $data['color'] ?? null,
                    $_SESSION['user_id'] ?? null
                ]
            );
        }

        return $this->db->insert(
            "INSERT INTO bills (store_id, title, vendor, amount, due_date, status, note, color, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, NOW())",
            [
                $data['store_id'],
                $data['title'],
                $data['vendor'] ?? null,
                $data['amount'] ?? null,
                $data['due_date'],
                $data['note'] ?? null,
                $data['color'] ?? null,
                $_SESSION['user_id'] ?? null
            ]
        );
    }

    public function update($id, $data) {
        if ($this->db->columnExists('bills', 'vendor_id')) {
            return $this->db->execute(
                "UPDATE bills SET title = ?, vendor = ?, vendor_id = ?, amount = ?, due_date = ?, status = ?, note = ?, color = ?, updated_at = NOW() WHERE id = ?",
                [
                    $data['title'],
                    $data['vendor'] ?? null,
                    $data['vendor_id'] ?? null,
                    $data['amount'] ?? null,
                    $data['due_date'],
                    $data['status'] ?? 'pending',
                    $data['note'] ?? null,
                    $data['color'] ?? null,
                    $id
                ]
            );
        }

        return $this->db->execute(
            "UPDATE bills SET title = ?, vendor = ?, amount = ?, due_date = ?, status = ?, note = ?, color = ?, updated_at = NOW() WHERE id = ?",
            [
                $data['title'],
                $data['vendor'] ?? null,
                $data['amount'] ?? null,
                $data['due_date'],
                $data['status'] ?? 'pending',
                $data['note'] ?? null,
                $data['color'] ?? null,
                $id
            ]
        );
    }

    public function delete($id) {
        $attachments = $this->getAttachments($id);
        foreach ($attachments as $att) {
            $filepath = UPLOAD_DIR . $att['filename'];
            if (file_exists($filepath)) unlink($filepath);
        }
        return $this->db->execute("DELETE FROM bills WHERE id = ?", [$id]);
    }

    public function markPaid($id) {
        if (!$this->hasPaidAtColumn()) {
            return $this->db->execute("UPDATE bills SET status='paid' WHERE id = ?", [$id]);
        }
        return $this->db->execute("UPDATE bills SET status='paid', paid_at = NOW() WHERE id = ?", [$id]);
    }

    public function markPending($id) {
        if (!$this->hasPaidAtColumn()) {
            return $this->db->execute("UPDATE bills SET status='pending' WHERE id = ?", [$id]);
        }
        return $this->db->execute("UPDATE bills SET status='pending', paid_at = NULL WHERE id = ?", [$id]);
    }

    /**
     * Duplicate a bill to a target month (for recurring bills)
     */
    public function duplicate($id, $targetDate) {
        $bill = $this->find($id);
        if (!$bill) return null;
        return $this->create([
            'store_id' => $bill['store_id'],
            'title' => $bill['title'],
            'vendor' => $bill['vendor'],
            'vendor_id' => $bill['vendor_id'],
            'amount' => null, // Amount will differ each month
            'due_date' => $targetDate,
            'note' => $bill['note'],
            'color' => $bill['color'],
        ]);
    }

    /**
     * Monthly summary: total counts and amounts by status for a store+month
     */
    public function monthlySummary($storeId, $month, $year) {
        return $this->db->fetch(
            "SELECT
                COUNT(*) AS total_bills,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) AS overdue_count,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
                COALESCE(SUM(amount), 0) AS total_amount,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) AS paid_amount,
                COALESCE(SUM(CASE WHEN status IN ('pending','overdue') THEN amount ELSE 0 END), 0) AS unpaid_amount
             FROM bills WHERE store_id = ? AND MONTH(due_date) = ? AND YEAR(due_date) = ?",
            [$storeId, $month, $year]
        );
    }

    /**
     * Count bills per store (for store list)
     */
    public function countByStore() {
        return $this->db->fetchAll(
            "SELECT store_id,
                    COUNT(*) AS total,
                    SUM(CASE WHEN status IN ('pending','overdue') THEN 1 ELSE 0 END) AS unpaid
             FROM bills GROUP BY store_id"
        );
    }

    // Attachments
    public function getAttachments($billId) {
        if (!$this->hasBillAttachments()) return [];
        return $this->db->fetchAll("SELECT * FROM bill_attachments WHERE bill_id = ? ORDER BY created_at DESC", [$billId]);
    }

    public function addAttachment($data) {
        if (!$this->hasBillAttachments()) return null;
        return $this->db->insert(
            "INSERT INTO bill_attachments (bill_id, filename, original_name, file_size, mime_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
            [$data['bill_id'], $data['filename'], $data['original_name'], $data['file_size'], $data['mime_type']]
        );
    }

    public function findAttachment($id) {
        if (!$this->hasBillAttachments()) return null;
        return $this->db->fetch("SELECT * FROM bill_attachments WHERE id = ?", [$id]);
    }

    public function deleteAttachment($id) {
        if (!$this->hasBillAttachments()) return null;
        $att = $this->findAttachment($id);
        if ($att) {
            $filepath = UPLOAD_DIR . $att['filename'];
            if (file_exists($filepath)) unlink($filepath);
            $this->db->execute("DELETE FROM bill_attachments WHERE id = ?", [$id]);
        }
        return $att;
    }

    public function updateOverdueStatuses() {
        return $this->db->execute("UPDATE bills SET status='overdue' WHERE status='pending' AND due_date < CURDATE()");
    }

    public function dueInDays($days) {
        return $this->db->fetchAll(
            "SELECT b.*, s.name AS store_name FROM bills b JOIN stores s ON s.id=b.store_id
             WHERE b.status='pending' AND b.due_date = DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND (b.reminded_at IS NULL OR DATE(b.reminded_at) <> CURDATE())",
            [$days]
        );
    }

    public function setReminded($billId) {
        return $this->db->execute("UPDATE bills SET reminded_at = NOW() WHERE id = ?", [$billId]);
    }
}
