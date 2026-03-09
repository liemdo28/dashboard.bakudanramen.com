<?php
class Bill {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->ensureSchema();
    }

    private function ensureSchema() {
        if (!$this->db->tableExists('bills')) {
            return;
        }

        if (!$this->db->columnExists('bills', 'repeat_type')) {
            try {
                $this->db->execute("ALTER TABLE bills ADD COLUMN repeat_type VARCHAR(20) NOT NULL DEFAULT 'none' AFTER due_date");
                $this->db->invalidateSchemaCache('bills');
            } catch (Exception $e) {
            }
        }

        if (!$this->db->columnExists('bills', 'repeat_interval')) {
            try {
                $this->db->execute("ALTER TABLE bills ADD COLUMN repeat_interval INT NULL DEFAULT 1 AFTER repeat_type");
                $this->db->invalidateSchemaCache('bills');
            } catch (Exception $e) {
            }
        }

        if (!$this->db->columnExists('bills', 'repeat_day')) {
            try {
                $this->db->execute("ALTER TABLE bills ADD COLUMN repeat_day TINYINT UNSIGNED NULL AFTER repeat_interval");
                $this->db->invalidateSchemaCache('bills');
            } catch (Exception $e) {
            }
        }

        if (!$this->db->columnExists('bills', 'repeat_parent_id')) {
            try {
                $this->db->execute("ALTER TABLE bills ADD COLUMN repeat_parent_id INT NULL AFTER repeat_day");
                $this->db->invalidateSchemaCache('bills');
            } catch (Exception $e) {
            }
        }
    }

    private function supportsVendors() {
        return $this->db->tableExists('vendors') && $this->db->columnExists('bills', 'vendor_id');
    }

    private function hasBillAttachments() {
        return $this->db->tableExists('bill_attachments');
    }

    private function hasPaidAtColumn() {
        return $this->db->columnExists('bills', 'paid_at');
    }

    private function supportsRepeat() {
        return $this->db->columnExists('bills', 'repeat_type')
            && $this->db->columnExists('bills', 'repeat_interval')
            && $this->db->columnExists('bills', 'repeat_day')
            && $this->db->columnExists('bills', 'repeat_parent_id');
    }

    private function normalizeRepeatData($data, $fallbackDueDate = null) {
        $type = strtolower(trim((string) ($data['repeat_type'] ?? 'none')));
        if ($type !== 'monthly') {
            return [
                'repeat_type' => 'none',
                'repeat_interval' => 1,
                'repeat_day' => null,
            ];
        }

        $interval = max(1, (int) ($data['repeat_interval'] ?? 1));
        $dueDate = $data['due_date'] ?? $fallbackDueDate;
        $defaultDay = $dueDate ? (int) date('j', strtotime($dueDate)) : 1;
        $day = (int) ($data['repeat_day'] ?? $defaultDay);
        $day = max(1, min(31, $day));

        return [
            'repeat_type' => 'monthly',
            'repeat_interval' => $interval,
            'repeat_day' => $day,
        ];
    }

    private function buildRecurringDateForMonth($sourceBill, $month, $year) {
        $sourceTs = strtotime($sourceBill['due_date']);
        if (!$sourceTs) {
            return null;
        }

        $sourceMonth = (int) date('n', $sourceTs);
        $sourceYear = (int) date('Y', $sourceTs);
        $monthsDiff = (($year - $sourceYear) * 12) + ($month - $sourceMonth);
        if ($monthsDiff < 0) {
            return null;
        }

        $interval = max(1, (int) ($sourceBill['repeat_interval'] ?? 1));
        if ($monthsDiff % $interval !== 0) {
            return null;
        }

        $day = (int) ($sourceBill['repeat_day'] ?? date('j', $sourceTs));
        $day = max(1, min(31, $day));
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        return sprintf('%04d-%02d-%02d', $year, $month, min($day, $lastDay));
    }

    private function deleteSingle($id) {
        $attachments = $this->getAttachments($id);
        foreach ($attachments as $att) {
            $filepath = UPLOAD_DIR . $att['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        return $this->db->execute("DELETE FROM bills WHERE id = ?", [$id]);
    }

    public function getByStore($storeId, $month = null, $year = null) {
        $params = [$storeId];
        $where = "WHERE b.store_id = ?";
        if ($month && $year) {
            $where .= " AND MONTH(b.due_date) = ? AND YEAR(b.due_date) = ?";
            $params[] = $month;
            $params[] = $year;
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
             FROM bills b
             JOIN stores s ON s.id = b.store_id
             WHERE b.id = ?",
            [$id]
        );
    }

    public function create($data) {
        $repeat = $this->normalizeRepeatData($data, $data['due_date'] ?? null);

        $fields = [
            'store_id',
            'title',
            'vendor',
            'amount',
            'due_date',
            'status',
            'note',
            'color',
            'created_by',
            'created_at',
        ];
        $placeholders = ['?', '?', '?', '?', '?', '?', '?', '?', '?', 'NOW()'];
        $params = [
            $data['store_id'],
            $data['title'],
            $data['vendor'] ?? null,
            $data['amount'] ?? null,
            $data['due_date'],
            $data['status'] ?? 'pending',
            $data['note'] ?? null,
            $data['color'] ?? null,
            $_SESSION['user_id'] ?? null,
        ];

        if ($this->db->columnExists('bills', 'vendor_id')) {
            array_splice($fields, 3, 0, ['vendor_id']);
            array_splice($placeholders, 3, 0, ['?']);
            array_splice($params, 3, 0, [$data['vendor_id'] ?? null]);
        }

        if ($this->supportsRepeat()) {
            $fields[] = 'repeat_type';
            $fields[] = 'repeat_interval';
            $fields[] = 'repeat_day';
            $fields[] = 'repeat_parent_id';
            $placeholders[] = '?';
            $placeholders[] = '?';
            $placeholders[] = '?';
            $placeholders[] = '?';
            $params[] = $repeat['repeat_type'];
            $params[] = $repeat['repeat_interval'];
            $params[] = $repeat['repeat_day'];
            $params[] = $data['repeat_parent_id'] ?? null;
        }

        return $this->db->insert(
            "INSERT INTO bills (" . implode(', ', $fields) . ")
             VALUES (" . implode(', ', $placeholders) . ")",
            $params
        );
    }

    public function update($id, $data) {
        $fields = [
            'title = ?',
            'vendor = ?',
            'amount = ?',
            'due_date = ?',
            'status = ?',
            'note = ?',
            'color = ?',
            'updated_at = NOW()',
        ];
        $params = [
            $data['title'],
            $data['vendor'] ?? null,
            $data['amount'] ?? null,
            $data['due_date'],
            $data['status'] ?? 'pending',
            $data['note'] ?? null,
            $data['color'] ?? null,
        ];

        if ($this->db->columnExists('bills', 'vendor_id')) {
            array_splice($fields, 2, 0, ['vendor_id = ?']);
            array_splice($params, 2, 0, [$data['vendor_id'] ?? null]);
        }

        if ($this->supportsRepeat()) {
            $repeat = $this->normalizeRepeatData($data, $data['due_date'] ?? null);
            $fields[] = 'repeat_type = ?';
            $fields[] = 'repeat_interval = ?';
            $fields[] = 'repeat_day = ?';
            $params[] = $repeat['repeat_type'];
            $params[] = $repeat['repeat_interval'];
            $params[] = $repeat['repeat_day'];

            if (array_key_exists('repeat_parent_id', $data)) {
                $fields[] = 'repeat_parent_id = ?';
                $params[] = $data['repeat_parent_id'];
            }
        }

        $params[] = $id;

        return $this->db->execute(
            "UPDATE bills SET " . implode(', ', $fields) . " WHERE id = ?",
            $params
        );
    }

    public function updateRepeatSettings($id, $data) {
        if (!$this->supportsRepeat()) {
            return 0;
        }

        $bill = $this->find($id);
        if (!$bill) {
            return 0;
        }

        $repeat = $this->normalizeRepeatData($data, $bill['due_date'] ?? null);
        $updated = $this->db->execute(
            "UPDATE bills
             SET repeat_type = ?, repeat_interval = ?, repeat_day = ?, updated_at = NOW()
             WHERE id = ?",
            [$repeat['repeat_type'], $repeat['repeat_interval'], $repeat['repeat_day'], $id]
        );

        $this->db->execute(
            "UPDATE bills
             SET repeat_type = ?, repeat_interval = ?, repeat_day = ?, updated_at = NOW()
             WHERE repeat_parent_id = ?",
            [$repeat['repeat_type'], $repeat['repeat_interval'], $repeat['repeat_day'], $id]
        );

        return $updated;
    }

    public function ensureRecurringForMonth($storeId, $month, $year) {
        if (!$this->supportsRepeat()) {
            return 0;
        }

        $targetEnd = sprintf('%04d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));
        $sources = $this->db->fetchAll(
            "SELECT *
             FROM bills
             WHERE store_id = ?
             AND repeat_parent_id IS NULL
             AND repeat_type = 'monthly'
             AND due_date <= ?",
            [$storeId, $targetEnd]
        );

        $created = 0;
        foreach ($sources as $source) {
            $targetDate = $this->buildRecurringDateForMonth($source, $month, $year);
            if (!$targetDate) {
                continue;
            }

            if (date('Y-m', strtotime($targetDate)) === date('Y-m', strtotime($source['due_date']))) {
                continue;
            }

            $exists = $this->db->fetch(
                "SELECT id
                 FROM bills
                 WHERE store_id = ?
                 AND (
                    (repeat_parent_id = ? AND YEAR(due_date) = ? AND MONTH(due_date) = ?)
                    OR (id = ? AND YEAR(due_date) = ? AND MONTH(due_date) = ?)
                 )
                 LIMIT 1",
                [$storeId, $source['id'], $year, $month, $source['id'], $year, $month]
            );

            if ($exists) {
                continue;
            }

            $this->create([
                'store_id' => $source['store_id'],
                'title' => $source['title'],
                'vendor' => $source['vendor'] ?? null,
                'vendor_id' => $source['vendor_id'] ?? null,
                'amount' => $source['amount'] ?? null,
                'due_date' => $targetDate,
                'status' => 'pending',
                'note' => $source['note'] ?? null,
                'color' => $source['color'] ?? null,
                'repeat_type' => $source['repeat_type'] ?? 'monthly',
                'repeat_interval' => $source['repeat_interval'] ?? 1,
                'repeat_day' => $source['repeat_day'] ?? date('j', strtotime($targetDate)),
                'repeat_parent_id' => $source['id'],
            ]);
            $created++;
        }

        return $created;
    }

    public function delete($id) {
        if ($this->supportsRepeat()) {
            $children = $this->db->fetchAll("SELECT id FROM bills WHERE repeat_parent_id = ?", [$id]);
            foreach ($children as $child) {
                $this->deleteSingle($child['id']);
            }
        }

        return $this->deleteSingle($id);
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

    public function duplicate($id, $targetDate) {
        $bill = $this->find($id);
        if (!$bill) {
            return null;
        }

        return $this->create([
            'store_id' => $bill['store_id'],
            'title' => $bill['title'],
            'vendor' => $bill['vendor'] ?? null,
            'vendor_id' => $bill['vendor_id'] ?? null,
            'amount' => $bill['amount'] ?? null,
            'due_date' => $targetDate,
            'status' => 'pending',
            'note' => $bill['note'] ?? null,
            'color' => $bill['color'] ?? null,
            'repeat_type' => 'none',
            'repeat_interval' => 1,
            'repeat_day' => null,
            'repeat_parent_id' => null,
        ]);
    }

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
             FROM bills
             WHERE store_id = ?
             AND MONTH(due_date) = ?
             AND YEAR(due_date) = ?",
            [$storeId, $month, $year]
        );
    }

    public function countByStore() {
        return $this->db->fetchAll(
            "SELECT store_id,
                    COUNT(*) AS total,
                    SUM(CASE WHEN status IN ('pending','overdue') THEN 1 ELSE 0 END) AS unpaid
             FROM bills
             GROUP BY store_id"
        );
    }

    public function getAttachments($billId) {
        if (!$this->hasBillAttachments()) {
            return [];
        }

        return $this->db->fetchAll("SELECT * FROM bill_attachments WHERE bill_id = ? ORDER BY created_at DESC", [$billId]);
    }

    public function addAttachment($data) {
        if (!$this->hasBillAttachments()) {
            return null;
        }

        return $this->db->insert(
            "INSERT INTO bill_attachments (bill_id, filename, original_name, file_size, mime_type, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$data['bill_id'], $data['filename'], $data['original_name'], $data['file_size'], $data['mime_type']]
        );
    }

    public function findAttachment($id) {
        if (!$this->hasBillAttachments()) {
            return null;
        }

        return $this->db->fetch("SELECT * FROM bill_attachments WHERE id = ?", [$id]);
    }

    public function deleteAttachment($id) {
        if (!$this->hasBillAttachments()) {
            return null;
        }

        $att = $this->findAttachment($id);
        if ($att) {
            $filepath = UPLOAD_DIR . $att['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            $this->db->execute("DELETE FROM bill_attachments WHERE id = ?", [$id]);
        }

        return $att;
    }

    public function updateOverdueStatuses() {
        return $this->db->execute("UPDATE bills SET status='overdue' WHERE status='pending' AND due_date < CURDATE()");
    }

    public function dueInDays($days) {
        return $this->db->fetchAll(
            "SELECT b.*, s.name AS store_name
             FROM bills b
             JOIN stores s ON s.id = b.store_id
             WHERE b.status = 'pending'
             AND b.due_date = DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND (b.reminded_at IS NULL OR DATE(b.reminded_at) <> CURDATE())",
            [$days]
        );
    }

    public function setReminded($billId) {
        return $this->db->execute("UPDATE bills SET reminded_at = NOW() WHERE id = ?", [$billId]);
    }
}
