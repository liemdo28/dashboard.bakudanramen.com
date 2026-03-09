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

        $schemaChanges = [
            ['due_time', "ALTER TABLE bills ADD COLUMN due_time TIME NULL"],
            ['repeat_type', "ALTER TABLE bills ADD COLUMN repeat_type VARCHAR(20) NOT NULL DEFAULT 'none'"],
            ['repeat_interval', "ALTER TABLE bills ADD COLUMN repeat_interval INT NULL DEFAULT 1"],
            ['repeat_day', "ALTER TABLE bills ADD COLUMN repeat_day TINYINT UNSIGNED NULL"],
            ['repeat_parent_id', "ALTER TABLE bills ADD COLUMN repeat_parent_id INT NULL"],
        ];

        foreach ($schemaChanges as [$column, $sql]) {
            if ($this->db->columnExists('bills', $column)) {
                continue;
            }

            try {
                $this->db->execute($sql);
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

    private function hasDueTimeColumn() {
        return $this->db->columnExists('bills', 'due_time');
    }

    private function supportsRepeat() {
        return $this->db->columnExists('bills', 'repeat_type')
            && $this->db->columnExists('bills', 'repeat_interval')
            && $this->db->columnExists('bills', 'repeat_day')
            && $this->db->columnExists('bills', 'repeat_parent_id');
    }

    private function normalizeDueTime($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        return null;
    }

    private function clampRepeatInterval($type, $value) {
        $limits = [
            'hourly' => 24,
            'daily' => 30,
            'weekly' => 12,
            'monthly' => 12,
            'yearly' => 10,
        ];

        $max = $limits[$type] ?? 12;
        return max(1, min($max, (int) $value));
    }

    private function normalizeRepeatData($data, $referenceBill = null) {
        $allowedTypes = ['none', 'hourly', 'daily', 'weekly', 'monthly', 'yearly'];
        $type = strtolower(trim((string) ($data['repeat_type'] ?? 'none')));
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'none';
        }

        $dueDate = $data['due_date'] ?? ($referenceBill['due_date'] ?? null);
        $dueTs = $dueDate ? strtotime($dueDate) : false;
        $hourAnchor = (int) ($data['repeat_anchor'] ?? ($referenceBill['repeat_day'] ?? 0));
        $existingDueTime = $referenceBill['due_time'] ?? null;
        $dueTime = $this->normalizeDueTime($data['due_time'] ?? $existingDueTime);

        if ($type === 'none') {
            return [
                'repeat_type' => 'none',
                'repeat_interval' => 1,
                'repeat_day' => null,
                'due_time' => null,
            ];
        }

        $interval = $this->clampRepeatInterval($type, $data['repeat_interval'] ?? ($referenceBill['repeat_interval'] ?? 1));
        $repeatDay = null;

        switch ($type) {
            case 'hourly':
                $hourAnchor = max(0, min(23, $hourAnchor));
                $repeatDay = $hourAnchor;
                $dueTime = sprintf('%02d:00:00', $hourAnchor);
                break;
            case 'weekly':
                $repeatDay = $dueTs ? (int) date('N', $dueTs) : ((int) ($referenceBill['repeat_day'] ?? 1) ?: 1);
                $dueTime = null;
                break;
            case 'monthly':
                $repeatDay = $dueTs ? (int) date('j', $dueTs) : ((int) ($referenceBill['repeat_day'] ?? 1) ?: 1);
                $dueTime = null;
                break;
            case 'daily':
            case 'yearly':
                $repeatDay = null;
                $dueTime = null;
                break;
        }

        return [
            'repeat_type' => $type,
            'repeat_interval' => $interval,
            'repeat_day' => $repeatDay,
            'due_time' => $type === 'hourly' ? $dueTime : $this->normalizeDueTime($dueTime),
        ];
    }

    private function sourceDateTime($bill) {
        $date = $bill['due_date'] ?? date('Y-m-d');
        $time = $this->normalizeDueTime($bill['due_time'] ?? null) ?: '00:00:00';
        return new DateTimeImmutable($date . ' ' . $time);
    }

    private function occurrenceDateTimeByIndex($sourceBill, $index) {
        $source = $this->sourceDateTime($sourceBill);
        $type = $sourceBill['repeat_type'] ?? 'none';
        $interval = max(1, (int) ($sourceBill['repeat_interval'] ?? 1));
        $steps = $index * $interval;

        switch ($type) {
            case 'hourly':
                return $source->modify('+' . $steps . ' hours');
            case 'daily':
                return $source->modify('+' . $steps . ' days');
            case 'weekly':
                return $source->modify('+' . ($steps * 7) . ' days');
            case 'monthly':
                $year = (int) $source->format('Y');
                $month = (int) $source->format('n') + $steps;
                $targetYear = $year + (int) floor(($month - 1) / 12);
                $targetMonth = (($month - 1) % 12) + 1;
                $day = (int) ($sourceBill['repeat_day'] ?? $source->format('j'));
                $lastDay = cal_days_in_month(CAL_GREGORIAN, $targetMonth, $targetYear);
                $safeDay = min($day, $lastDay);
                return new DateTimeImmutable(sprintf(
                    '%04d-%02d-%02d %s',
                    $targetYear,
                    $targetMonth,
                    $safeDay,
                    $source->format('H:i:s')
                ));
            case 'yearly':
                $targetYear = (int) $source->format('Y') + $steps;
                $targetMonth = (int) $source->format('n');
                $targetDay = (int) $source->format('j');
                $lastDay = cal_days_in_month(CAL_GREGORIAN, $targetMonth, $targetYear);
                $safeDay = min($targetDay, $lastDay);
                return new DateTimeImmutable(sprintf(
                    '%04d-%02d-%02d %s',
                    $targetYear,
                    $targetMonth,
                    $safeDay,
                    $source->format('H:i:s')
                ));
            default:
                return $source;
        }
    }

    private function firstOccurrenceIndexForMonth($sourceBill, DateTimeImmutable $monthStart) {
        $source = $this->sourceDateTime($sourceBill);
        if ($source >= $monthStart) {
            return 0;
        }

        $type = $sourceBill['repeat_type'] ?? 'none';
        $interval = max(1, (int) ($sourceBill['repeat_interval'] ?? 1));

        switch ($type) {
            case 'hourly':
                $stepSeconds = $interval * 3600;
                $diffSeconds = max(0, $monthStart->getTimestamp() - $source->getTimestamp());
                return (int) floor($diffSeconds / $stepSeconds);
            case 'daily':
                $stepDays = $interval;
                $diffDays = max(0, (int) floor(($monthStart->getTimestamp() - $source->getTimestamp()) / 86400));
                return (int) floor($diffDays / $stepDays);
            case 'weekly':
                $stepDays = $interval * 7;
                $diffDays = max(0, (int) floor(($monthStart->getTimestamp() - $source->getTimestamp()) / 86400));
                return (int) floor($diffDays / $stepDays);
            case 'monthly':
                $monthsDiff = (((int) $monthStart->format('Y') - (int) $source->format('Y')) * 12)
                    + ((int) $monthStart->format('n') - (int) $source->format('n'));
                return max(0, (int) floor($monthsDiff / $interval));
            case 'yearly':
                $yearsDiff = (int) $monthStart->format('Y') - (int) $source->format('Y');
                return max(0, (int) floor($yearsDiff / $interval));
            default:
                return 0;
        }
    }

    private function occurrencesForMonth($sourceBill, $month, $year) {
        if (($sourceBill['repeat_type'] ?? 'none') === 'none') {
            return [];
        }

        $monthStart = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $monthEnd = $monthStart->modify('+1 month');
        $index = $this->firstOccurrenceIndexForMonth($sourceBill, $monthStart);
        $occurrences = [];
        $guard = 0;

        while ($guard < 1200) {
            $occurrence = $this->occurrenceDateTimeByIndex($sourceBill, $index);
            if ($occurrence >= $monthEnd) {
                break;
            }

            if ($occurrence < $monthStart) {
                $index++;
                $guard++;
                continue;
            }

            $occurrences[] = $occurrence;
            $index++;
            $guard++;
        }

        return $occurrences;
    }

    private function sameOccurrence(DateTimeImmutable $occurrence, $bill) {
        $billTime = $this->normalizeDueTime($bill['due_time'] ?? null) ?: '00:00:00';
        return $occurrence->format('Y-m-d') === ($bill['due_date'] ?? '')
            && $occurrence->format('H:i:s') === $billTime;
    }

    private function occurrenceExists($storeId, $sourceId, DateTimeImmutable $occurrence) {
        $date = $occurrence->format('Y-m-d');
        $time = $this->hasDueTimeColumn() ? $occurrence->format('H:i:s') : null;

        if ($this->hasDueTimeColumn()) {
            return (bool) $this->db->fetch(
                "SELECT id
                 FROM bills
                 WHERE store_id = ?
                 AND ((repeat_parent_id = ?) OR (id = ?))
                 AND due_date = ?
                 AND (due_time <=> ?)
                 LIMIT 1",
                [$storeId, $sourceId, $sourceId, $date, $time]
            );
        }

        return (bool) $this->db->fetch(
            "SELECT id
             FROM bills
             WHERE store_id = ?
             AND ((repeat_parent_id = ?) OR (id = ?))
             AND due_date = ?
             LIMIT 1",
            [$storeId, $sourceId, $sourceId, $date]
        );
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

        $timeOrder = $this->hasDueTimeColumn() ? ", COALESCE(b.due_time, '23:59:59') ASC" : '';

        return $this->db->fetchAll(
            "SELECT b.*, s.name AS store_name, s.color AS store_color,
                    $vendorFields
             FROM bills b
             JOIN stores s ON s.id = b.store_id
             $vendorJoin
             $where
             ORDER BY b.due_date ASC{$timeOrder}, FIELD(b.status,'overdue','pending','paid') ASC, b.title ASC",
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
        $repeat = $this->normalizeRepeatData($data);

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

        if ($this->hasDueTimeColumn()) {
            $fields[] = 'due_time';
            $placeholders[] = '?';
            $params[] = $repeat['due_time'];
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
        $current = $this->find($id);
        $repeat = $this->normalizeRepeatData($data, $current);

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

        if ($this->hasDueTimeColumn()) {
            $fields[] = 'due_time = ?';
            $params[] = $repeat['due_time'];
        }

        if ($this->supportsRepeat()) {
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

        $repeat = $this->normalizeRepeatData(array_merge($bill, $data), $bill);
        $params = [
            $repeat['repeat_type'],
            $repeat['repeat_interval'],
            $repeat['repeat_day'],
        ];
        $fields = [
            'repeat_type = ?',
            'repeat_interval = ?',
            'repeat_day = ?',
        ];

        if ($this->hasDueTimeColumn()) {
            $fields[] = 'due_time = ?';
            $params[] = $repeat['due_time'];
        }

        $params[] = $id;
        $updated = $this->db->execute(
            "UPDATE bills SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?",
            $params
        );

        $childParams = [
            $repeat['repeat_type'],
            $repeat['repeat_interval'],
            $repeat['repeat_day'],
        ];
        $childFields = [
            'repeat_type = ?',
            'repeat_interval = ?',
            'repeat_day = ?',
        ];
        if ($this->hasDueTimeColumn()) {
            $childFields[] = 'due_time = ?';
            $childParams[] = $repeat['due_time'];
        }
        $childParams[] = $id;

        $this->db->execute(
            "UPDATE bills SET " . implode(', ', $childFields) . ", updated_at = NOW() WHERE repeat_parent_id = ?",
            $childParams
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
             AND repeat_type <> 'none'
             AND due_date <= ?",
            [$storeId, $targetEnd]
        );

        $created = 0;
        foreach ($sources as $source) {
            foreach ($this->occurrencesForMonth($source, $month, $year) as $occurrence) {
                if ($this->sameOccurrence($occurrence, $source)) {
                    continue;
                }

                if ($this->occurrenceExists($storeId, $source['id'], $occurrence)) {
                    continue;
                }

                $this->create([
                    'store_id' => $source['store_id'],
                    'title' => $source['title'],
                    'vendor' => $source['vendor'] ?? null,
                    'vendor_id' => $source['vendor_id'] ?? null,
                    'amount' => $source['amount'] ?? null,
                    'due_date' => $occurrence->format('Y-m-d'),
                    'due_time' => $occurrence->format('H:i:s'),
                    'status' => 'pending',
                    'note' => $source['note'] ?? null,
                    'color' => $source['color'] ?? null,
                    'repeat_type' => $source['repeat_type'] ?? 'none',
                    'repeat_interval' => $source['repeat_interval'] ?? 1,
                    'repeat_anchor' => $source['repeat_day'] ?? null,
                    'repeat_parent_id' => $source['id'],
                ]);
                $created++;
            }
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
            'due_time' => $bill['due_time'] ?? null,
            'status' => 'pending',
            'note' => $bill['note'] ?? null,
            'color' => $bill['color'] ?? null,
            'repeat_type' => 'none',
            'repeat_interval' => 1,
            'repeat_anchor' => null,
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
