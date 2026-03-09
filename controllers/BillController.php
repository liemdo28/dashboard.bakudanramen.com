<?php
class BillController {
    private $storeModel;
    private $billModel;
    private $vendorModel;

    public function __construct() {
        $this->storeModel = new Store();
        $this->billModel = new Bill();
        $this->vendorModel = new Vendor();
    }

    public function index() {
        $this->vendorModel->syncFromBills();
        $stores = $this->storeModel->allActive();
        $billCounts = $this->billModel->countByStore();
        // Index by store_id for easy lookup
        $billCountMap = [];
        foreach ($billCounts as $bc) {
            $billCountMap[$bc['store_id']] = $bc;
        }
        require __DIR__ . '/../views/bills/index.php';
    }

    public function storeView($storeId) {
        $this->vendorModel->syncFromBills();
        $store = $this->storeModel->find($storeId);
        if (!$store) { flash('error','Store not found'); redirect('bills'); }

        // Month/year navigation
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
        $year  = isset($_GET['year']) ? (int)$_GET['year']  : (int)date('Y');

        if ($month < 1) { $month = 12; $year--; }
        if ($month > 12) { $month = 1; $year++; }

        $prevMonth = $month - 1; $prevYear = $year;
        if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
        $nextMonth = $month + 1; $nextYear = $year;
        if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

        $bills = $this->billModel->getByStore($storeId, $month, $year);

        // Load attachments for each bill
        foreach ($bills as &$b) {
            $b['attachments'] = $this->billModel->getAttachments($b['id']);
        }
        unset($b);

        // Group by date
        $billsByDate = [];
        foreach ($bills as $b) {
            $billsByDate[$b['due_date']][] = $b;
        }

        // Monthly summary stats
        $summary = $this->billModel->monthlySummary($storeId, $month, $year);

        // Vendors for dropdown
        $vendors = $this->vendorModel->getAllActive();

        require __DIR__ . '/../views/bills/store.php';
    }

    public function createStore() {
        if (!verify_csrf($_POST['csrf'] ?? '')) { flash('error','Invalid CSRF'); redirect('bills'); }
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { flash('error','Store name is required'); redirect('bills'); }
        $this->storeModel->create([
            'name' => $name,
            'address' => trim($_POST['address'] ?? ''),
            'color' => trim($_POST['color'] ?? '')
        ]);
        flash('success','Store created');
        redirect('bills');
    }

    public function updateStore($storeId) {
        if (!verify_csrf($_POST['csrf'] ?? '')) { flash('error','Invalid CSRF'); redirect('bills'); }
        $store = $this->storeModel->find($storeId);
        if (!$store) { flash('error','Store not found'); redirect('bills'); }
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { flash('error','Store name is required'); redirect('bills'); }
        $this->storeModel->update($storeId, [
            'name' => $name,
            'address' => trim($_POST['address'] ?? ''),
            'color' => trim($_POST['color'] ?? '')
        ]);
        flash('success','Store updated');
        redirect('bills');
    }

    public function deleteStore($storeId) {
        $store = $this->storeModel->find($storeId);
        if (!$store) { flash('error','Store not found'); redirect('bills'); }
        $this->storeModel->delete($storeId);
        flash('success','Store deleted');
        redirect('bills');
    }

    public function createBill() {
        if (!verify_csrf($_POST['csrf'] ?? '')) { flash('error','Invalid CSRF'); redirect('bills'); }
        $storeId = (int)($_POST['store_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $due = $_POST['due_date'] ?? '';
        if (!$storeId || $title === '' || $due === '') { flash('error','Missing required fields'); redirect('bills'); }

        // Handle vendor: either vendor_id or new vendor name
        $vendorId = null;
        $vendorName = '';
        $vendorSelect = $_POST['vendor_id'] ?? '';
        if ($vendorSelect === 'new') {
            $newVendorName = trim($_POST['vendor_new'] ?? '');
            if ($newVendorName !== '') {
                $vendorId = $this->vendorModel->quickCreate($newVendorName);
                $vendorName = $newVendorName;
            }
        } elseif ($vendorSelect !== '') {
            $vendorId = (int)$vendorSelect;
            $v = $this->vendorModel->find($vendorId);
            $vendorName = $v ? $v['name'] : '';
        }

        $billId = $this->billModel->create([
            'store_id' => $storeId,
            'title' => $title,
            'vendor' => $vendorName,
            'vendor_id' => $vendorId,
            'amount' => $_POST['amount'] ?? null,
            'due_date' => $due,
            'note' => trim($_POST['note'] ?? ''),
            'color' => trim($_POST['color'] ?? ''),
        ]);

        // Handle file upload if present
        if (!empty($_FILES['bill_file']) && $_FILES['bill_file']['error'] === UPLOAD_ERR_OK) {
            $this->handleBillFileUpload($billId, $_FILES['bill_file']);
        }

        flash('success','Bill added');
        redirect('bills/store/'.$storeId.'?month='.date('m',strtotime($due)).'&year='.date('Y',strtotime($due)));
    }

    public function updateBill($billId) {
        if (!verify_csrf($_POST['csrf'] ?? '')) { flash('error','Invalid CSRF'); redirect('bills'); }
        $bill = $this->billModel->find($billId);
        if (!$bill) { flash('error','Bill not found'); redirect('bills'); }

        $title = trim($_POST['title'] ?? '');
        $due = $_POST['due_date'] ?? '';
        if ($title === '' || $due === '') { flash('error','Missing required fields'); redirect('bills/store/'.$bill['store_id']); }

        // Handle vendor
        $vendorId = null;
        $vendorName = '';
        $vendorSelect = $_POST['vendor_id'] ?? '';
        if ($vendorSelect === 'new') {
            $newVendorName = trim($_POST['vendor_new'] ?? '');
            if ($newVendorName !== '') {
                $vendorId = $this->vendorModel->quickCreate($newVendorName);
                $vendorName = $newVendorName;
            }
        } elseif ($vendorSelect !== '') {
            $vendorId = (int)$vendorSelect;
            $v = $this->vendorModel->find($vendorId);
            $vendorName = $v ? $v['name'] : '';
        }

        // Handle status - allow user to change status
        $status = $_POST['status'] ?? $bill['status'];
        $allowedStatuses = ['pending', 'paid', 'overdue'];
        if (!in_array($status, $allowedStatuses)) $status = 'pending';

        // Auto-reset to pending if due_date changed to future and was overdue
        if ($bill['status'] === 'overdue' && $status === 'overdue' && strtotime($due) >= strtotime('today')) {
            $status = 'pending';
        }

        $this->billModel->update($billId, [
            'title' => $title,
            'vendor' => $vendorName,
            'vendor_id' => $vendorId,
            'amount' => $_POST['amount'] ?? null,
            'due_date' => $due,
            'status' => $status,
            'note' => trim($_POST['note'] ?? ''),
            'color' => trim($_POST['color'] ?? ''),
        ]);

        // If status changed to paid, track paid_at
        if ($status === 'paid' && $bill['status'] !== 'paid') {
            $this->billModel->markPaid($billId);
        }
        // If status changed from paid back to pending, clear paid_at
        if ($status !== 'paid' && $bill['status'] === 'paid') {
            $this->billModel->markPending($billId);
        }

        // Handle file upload if present
        if (!empty($_FILES['bill_file']) && $_FILES['bill_file']['error'] === UPLOAD_ERR_OK) {
            $this->handleBillFileUpload($billId, $_FILES['bill_file']);
        }

        flash('success','Bill updated');
        redirect('bills/store/'.$bill['store_id'].'?month='.date('m',strtotime($due)).'&year='.date('Y',strtotime($due)));
    }

    public function deleteBill($billId) {
        $bill = $this->billModel->find($billId);
        if (!$bill) { flash('error','Bill not found'); redirect('bills'); }
        $this->billModel->delete($billId);
        flash('success','Bill deleted');
        redirect('bills/store/'.$bill['store_id']);
    }

    public function markPaid($billId) {
        $bill = $this->billModel->find($billId);
        if (!$bill) { flash('error','Bill not found'); redirect('bills'); }
        $this->billModel->markPaid($billId);
        flash('success','Marked as paid');
        $referer = $_SERVER['HTTP_REFERER'] ?? APP_URL . '/bills/store/' . $bill['store_id'];
        header("Location: $referer");
        exit;
    }

    public function duplicateBill($billId) {
        $bill = $this->billModel->find($billId);
        if (!$bill) { flash('error','Bill not found'); redirect('bills'); }

        // Duplicate to next month, same day
        $dueDate = $bill['due_date'];
        $nextMonth = date('Y-m-d', strtotime($dueDate . ' +1 month'));

        $newId = $this->billModel->duplicate($billId, $nextMonth);
        if ($newId) {
            flash('success', 'Bill duplicated to ' . date('d/m/Y', strtotime($nextMonth)));
            redirect('bills/store/'.$bill['store_id'].'?month='.date('m',strtotime($nextMonth)).'&year='.date('Y',strtotime($nextMonth)));
        } else {
            flash('error', 'Failed to duplicate bill');
            redirect('bills/store/'.$bill['store_id']);
        }
    }

    public function uploadBillFile($billId) {
        $bill = $this->billModel->find($billId);
        if (!$bill) json_response(['error' => 'Bill not found'], 404);

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            json_response(['error' => 'Upload failed'], 400);
        }

        $file = $_FILES['file'];
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            json_response(['error' => 'File too large (max 10MB)'], 400);
        }

        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $ext;

        if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
            $attId = $this->billModel->addAttachment([
                'bill_id' => $billId,
                'filename' => $filename,
                'original_name' => $file['name'],
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
            ]);
            json_response(['success' => true, 'id' => $attId, 'filename' => $file['name']]);
        }
        json_response(['error' => 'Upload failed'], 500);
    }

    public function deleteBillAttachment($attId) {
        $att = $this->billModel->deleteAttachment($attId);
        if (!$att) { flash('error', 'Attachment not found'); redirect('bills'); }
        flash('success', 'Attachment deleted');
        // Redirect back
        $referer = $_SERVER['HTTP_REFERER'] ?? APP_URL . '/bills';
        header("Location: $referer");
        exit;
    }

    public function downloadBillAttachment($attId) {
        $att = $this->billModel->findAttachment($attId);
        if (!$att) { http_response_code(404); echo 'Not found'; exit; }

        $filepath = UPLOAD_DIR . $att['filename'];
        if (!file_exists($filepath)) { http_response_code(404); echo 'File not found'; exit; }

        header('Content-Type: ' . ($att['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $att['original_name'] . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }

    private function handleBillFileUpload($billId, $file) {
        if ($file['size'] > MAX_UPLOAD_SIZE) return;
        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $ext;

        if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
            $this->billModel->addAttachment([
                'bill_id' => $billId,
                'filename' => $filename,
                'original_name' => $file['name'],
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
            ]);
        }
    }
}
