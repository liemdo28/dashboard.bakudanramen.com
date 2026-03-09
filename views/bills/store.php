<?php
$pageTitle = 'Tracking Bill - ' . ($store['name'] ?? 'Store');
$currentPage = 'bills';

$monthNames = ['','Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'];

// Color palette
$colorPalette = [
    '#ff2bd6' => 'Pink',
    '#00f5ff' => 'Cyan',
    '#b6ff00' => 'Lime',
    '#7c3cff' => 'Purple',
    '#ffea00' => 'Yellow',
    '#ff6b2b' => 'Orange',
    '#ff1744' => 'Red',
    '#2979ff' => 'Blue',
    '#1de9b6' => 'Teal',
    '#ffffff' => 'White',
];

// Calendar setup
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$startDow = (date('N', $firstDay)); // 1=Monday
$today = date('Y-m-d');

// Format currency helper
function fmtAmount($amt) {
    if ($amt === null || $amt === '' || $amt == 0) return '';
    return number_format((float)$amt, 0, ',', '.');
}

ob_start();
?>
<div class="flex-between mb-3">
    <div style="display:flex;gap:10px;align-items:center">
        <span class="store-dot" style="width:12px;height:12px;border-radius:99px;background:<?= e($store['color'] ?: 'var(--neon-cyan)') ?>"></span>
        <div>
            <h2 style="margin:0;font-size:18px;font-weight:800"><?= e($store['name']) ?></h2>
            <div class="text-muted text-sm"><?= e($store['address'] ?? '') ?></div>
        </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
        <a class="btn btn-ghost btn-sm" href="<?= APP_URL ?>/bills">← Stores</a>
        <a class="btn btn-ghost btn-sm" href="<?= APP_URL ?>/bills/store/<?= $store['id'] ?>?month=<?= $prevMonth ?>&year=<?= $prevYear ?>">‹</a>
        <div class="badge"><?= $monthNames[$month] ?> <?= (int)$year ?></div>
        <a class="btn btn-ghost btn-sm" href="<?= APP_URL ?>/bills/store/<?= $store['id'] ?>?month=<?= $nextMonth ?>&year=<?= $nextYear ?>">›</a>
    </div>
</div>

<?php if ($msg = flash('success')): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash('error')): ?>
<div class="alert alert-error"><?= e($msg) ?></div>
<?php endif; ?>

<!-- Monthly Summary Stats -->
<?php if ($summary && $summary['total_bills'] > 0): ?>
<div class="grid grid-4 mb-4">
    <div class="stat-card">
        <div class="stat-icon dark">📋</div>
        <div><div class="stat-value"><?= (int)$summary['total_bills'] ?></div><div class="stat-label">Tổng bill</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">⏳</div>
        <div><div class="stat-value"><?= (int)$summary['pending_count'] + (int)$summary['overdue_count'] ?></div><div class="stat-label">Chưa thanh toán</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div><div class="stat-value"><?= (int)$summary['paid_count'] ?></div><div class="stat-label">Đã thanh toán</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">💰</div>
        <div>
            <div class="stat-value" style="font-size:18px"><?= fmtAmount($summary['total_amount']) ?: '0' ?></div>
            <div class="stat-label">
                <?php if ($summary['unpaid_amount'] > 0): ?>
                    Còn nợ: <span style="color:var(--neon-pink);font-weight:700"><?= fmtAmount($summary['unpaid_amount']) ?></span>
                <?php else: ?>
                    Tổng amount
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-2 mb-4">
    <!-- Add Bill Form -->
    <div class="card">
        <div class="card-header">
            <h3>➕ Thêm Bill</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/bills/create" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="store_id" value="<?= (int)$store['id'] ?>">

                <div class="form-group">
                    <label>Tên bill *</label>
                    <input class="form-control" name="title" required placeholder="Water / Electric / Internet...">
                </div>
                <div class="grid grid-2" style="gap:10px">
                    <div class="form-group">
                        <label>Due date *</label>
                        <input class="form-control" type="date" name="due_date" required value="<?= e($today) ?>">
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <input class="form-control" type="number" step="0.01" name="amount" placeholder="0.00">
                    </div>
                </div>

                <!-- Vendor Selector -->
                <div class="form-group">
                    <label>Vendor</label>
                    <select class="form-control" name="vendor_id" id="vendor-select-create" onchange="toggleNewVendor('create')">
                        <option value="">-- Chọn vendor --</option>
                        <?php foreach ($vendors as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= e($v['name']) ?></option>
                        <?php endforeach; ?>
                        <option value="new">+ Tạo mới...</option>
                    </select>
                    <input class="form-control mt-1" name="vendor_new" id="vendor-new-create" placeholder="Tên vendor mới..." style="display:none">
                </div>

                <!-- Color Picker -->
                <div class="form-group">
                    <label>Màu</label>
                    <input type="hidden" name="color" id="color-create" value="">
                    <div class="color-options">
                        <?php foreach ($colorPalette as $hex => $name): ?>
                        <div class="color-option" style="background:<?= $hex ?>" title="<?= $name ?>" onclick="selectColor('create', '<?= $hex ?>', this)"></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Note</label>
                    <input class="form-control" name="note" placeholder="...">
                </div>

                <!-- File Upload -->
                <div class="form-group">
                    <label>Đính kèm (PDF / Hình ảnh)</label>
                    <input class="form-control" type="file" name="bill_file" accept=".pdf,.png,.jpg,.jpeg,.gif,.webp">
                </div>

                <button class="btn btn-primary" type="submit">Add bill</button>
                <div class="text-muted text-sm" style="margin-top:10px">
                    Reminder: hệ thống sẽ notify trước hạn <b>3 ngày</b>.
                </div>
            </form>
        </div>
    </div>

    <!-- Bill List -->
    <div class="card">
        <div class="card-header">
            <h3>📋 Bill list (<?= $monthNames[$month] ?>)</h3>
            <span class="text-muted text-sm"><?= count($bills) ?> bills</span>
        </div>
        <div class="card-body">
            <?php if (empty($bills)): ?>
                <div class="empty-state">
                    <div class="icon">💳</div>
                    <h3>Chưa có bill</h3>
                    <p>Thêm bill để hiển thị trên calendar</p>
                </div>
            <?php else: ?>
                <div class="bill-list">
                    <?php foreach ($bills as $b):
                        $urgencyColor = dueColor($b['due_date'], $b['status']);
                    ?>
                    <div
                        class="bill-row bill-row-clickable"
                        data-bill-trigger="<?= (int)$b['id'] ?>"
                        tabindex="0"
                        role="button"
                        aria-label="Chỉnh sửa bill <?= e($b['title']) ?>"
                    >
                        <div class="bill-left">
                            <span class="bill-dot" style="background:<?= e($urgencyColor) ?>"></span>
                            <div>
                                <div class="bill-title"><?= e($b['title']) ?></div>
                                <div class="text-muted text-sm">
                                    <?= e($b['vendor'] ?? '') ?>
                                    <?php if (!empty($b['amount']) && $b['amount'] > 0): ?>
                                        <?php if (!empty($b['vendor'])): ?> · <?php endif; ?>
                                        <span style="color:var(--neon-yellow);font-weight:700"><?= fmtAmount($b['amount']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="bill-right">
                            <?php if (!empty($b['attachments'])): ?>
                                <span class="text-muted text-sm" title="<?= count($b['attachments']) ?> file(s)">📎<?= count($b['attachments']) ?></span>
                            <?php endif; ?>
                            <span class="chip" style="border-color:<?= e($urgencyColor) ?>40;color:<?= e($urgencyColor) ?>"><?= e(date('d/m', strtotime($b['due_date']))) ?></span>
                            <button type="button" class="btn btn-outline btn-sm bill-edit-action" data-bill-trigger="<?= (int)$b['id'] ?>">Edit</button>
                            <?php if ($b['status'] === 'paid'): ?>
                                <span class="pill pill-ok">Paid</span>
                            <?php elseif ($b['status'] === 'overdue'): ?>
                                <span class="badge-overdue" style="font-size:11px">Quá hạn</span>
                                <a class="btn btn-ghost btn-sm" data-no-modal="true" href="<?= APP_URL ?>/bills/<?= (int)$b['id'] ?>/paid">Pay</a>
                            <?php else: ?>
                                <a class="btn btn-ghost btn-sm" data-no-modal="true" href="<?= APP_URL ?>/bills/<?= (int)$b['id'] ?>/paid">Pay</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Color Legend -->
            <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;font-size:11px;color:var(--text-muted)">
                <span style="display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:2px;background:#2979ff;display:inline-block"></span> >3 ngày</span>
                <span style="display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:2px;background:#ffea00;display:inline-block"></span> 1-3 ngày</span>
                <span style="display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:2px;background:#ff2bd6;display:inline-block"></span> Hôm nay</span>
                <span style="display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:2px;background:#ff1744;display:inline-block"></span> Quá hạn</span>
                <span style="display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:2px;background:#333333;display:inline-block"></span> Đã thanh toán</span>
            </div>
        </div>
    </div>
</div>

<!-- Calendar -->
<div class="card">
    <div class="card-header">
        <h3>🗓️ Calendar</h3>
        <span class="text-muted text-sm">Bill sẽ xuất hiện đúng ngày đến hạn (màu theo thời gian thanh toán)</span>
    </div>
    <div class="card-body" style="padding:0">
        <div class="calendar-wrap">
            <div class="cal-head">
                <div class="cal-dow">Mon</div><div class="cal-dow">Tue</div><div class="cal-dow">Wed</div><div class="cal-dow">Thu</div><div class="cal-dow">Fri</div><div class="cal-dow">Sat</div><div class="cal-dow">Sun</div>
            </div>
            <div class="cal-grid">
                <?php
                $cell = 1;
                $day = 1;
                $totalCells = ceil(($startDow - 1 + $daysInMonth) / 7) * 7;
                while ($cell <= $totalCells):
                    $isBlank = ($cell < $startDow) || ($day > $daysInMonth);
                    if ($isBlank) {
                        echo '<div class="cal-cell cal-blank"></div>';
                    } else {
                        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $isToday = ($date === $today);
                        $dayBills = $billsByDate[$date] ?? [];
                        $primaryBillId = !empty($dayBills) ? (int)$dayBills[0]['id'] : 0;
                        echo '<div class="cal-cell '.($isToday?'cal-today ':'').(!empty($dayBills)?'cal-cell-interactive':'').'"'.($primaryBillId ? ' data-bill-trigger="'.$primaryBillId.'" tabindex="0" role="button"' : '').'>';
                        echo '<div class="cal-day">'.$day.'</div>';
                        if (!empty($dayBills)) {
                            echo '<div class="cal-items">';
                            foreach (array_slice($dayBills, 0, 4) as $b) {
                                $urgColor = dueColor($b['due_date'], $b['status']);
                                $amtLabel = (!empty($b['amount']) && $b['amount'] > 0) ? ' · '.fmtAmount($b['amount']) : '';
                                echo '<button type="button" class="cal-item cal-item-button" data-bill-trigger="'.(int)$b['id'].'" style="border-left-color:'.e($urgColor).';background:'.e($urgColor).'15">';
                                echo '<span class="cal-item-title">'.e($b['title']).$amtLabel.'</span>';
                                echo '</button>';
                            }
                            if (count($dayBills) > 4) echo '<div class="text-muted text-sm" style="margin-top:6px">+'.(count($dayBills)-4).' more</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                        $day++;
                    }
                    $cell++;
                endwhile;
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Bill Modal -->
<div class="modal-overlay" id="editModal" onclick="if(event.target===this)closeEditModal()">
    <div class="modal-content" style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:16px;width:100%;max-width:560px;max-height:85vh;overflow-y:auto;box-shadow:var(--shadow-lg)">
        <div class="modal-header">
            <h3 id="editModalTitle">Chỉnh sửa Bill</h3>
            <button class="btn-ghost" onclick="closeEditModal()" style="font-size:18px">✕</button>
        </div>
        <form method="POST" id="editForm" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <div class="modal-body" style="padding:22px">
                <div class="form-group">
                    <label>Tên bill *</label>
                    <input class="form-control" name="title" id="edit-title" required>
                </div>
                <div class="grid grid-2" style="gap:10px">
                    <div class="form-group">
                        <label>Due date *</label>
                        <input class="form-control" type="date" name="due_date" id="edit-due_date" required>
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <input class="form-control" type="number" step="0.01" name="amount" id="edit-amount">
                    </div>
                </div>

                <!-- Status selector (THE FIX for editing old bills) -->
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select class="form-control" name="status" id="edit-status">
                        <option value="pending">Pending (Chưa thanh toán)</option>
                        <option value="paid">Paid (Đã thanh toán)</option>
                        <option value="overdue">Overdue (Quá hạn)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Vendor</label>
                    <select class="form-control" name="vendor_id" id="edit-vendor_id" onchange="toggleNewVendor('edit')">
                        <option value="">-- Chọn vendor --</option>
                        <?php foreach ($vendors as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= e($v['name']) ?></option>
                        <?php endforeach; ?>
                        <option value="new">+ Tạo mới...</option>
                    </select>
                    <input class="form-control mt-1" name="vendor_new" id="vendor-new-edit" placeholder="Tên vendor mới..." style="display:none">
                </div>

                <div class="form-group">
                    <label>Màu</label>
                    <input type="hidden" name="color" id="color-edit" value="">
                    <div class="color-options">
                        <?php foreach ($colorPalette as $hex => $name): ?>
                        <div class="color-option" style="background:<?= $hex ?>" title="<?= $name ?>" onclick="selectColor('edit', '<?= $hex ?>', this)"></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Note</label>
                    <input class="form-control" name="note" id="edit-note">
                </div>

                <!-- Existing Attachments -->
                <div id="edit-attachments"></div>

                <div class="form-group">
                    <label>Đính kèm thêm file</label>
                    <input class="form-control" type="file" name="bill_file" accept=".pdf,.png,.jpg,.jpeg,.gif,.webp">
                </div>
            </div>
            <div class="modal-footer" style="padding:14px 22px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                <div style="display:flex;gap:8px">
                    <a class="btn btn-ghost btn-sm" id="edit-delete-btn" onclick="return confirm('Xóa bill này?')" style="color:var(--neon-pink)">Xóa</a>
                    <a class="btn btn-ghost btn-sm" id="edit-duplicate-btn" style="color:var(--neon-cyan)" title="Duplicate bill sang tháng sau">Duplicate →</a>
                </div>
                <div style="display:flex;gap:8px">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Bill data for edit modal
const billData = <?= json_encode(array_values($bills), JSON_UNESCAPED_UNICODE) ?>;

function openEditModal(billId) {
    const bill = billData.find(b => b.id == billId);
    if (!bill) return;

    document.getElementById('editForm').action = '<?= APP_URL ?>/bills/' + billId + '/update';
    document.getElementById('edit-title').value = bill.title || '';
    document.getElementById('edit-due_date').value = bill.due_date || '';
    document.getElementById('edit-amount').value = bill.amount || '';
    document.getElementById('edit-note').value = bill.note || '';
    document.getElementById('edit-delete-btn').href = '<?= APP_URL ?>/bills/' + billId + '/delete';
    document.getElementById('edit-duplicate-btn').href = '<?= APP_URL ?>/bills/' + billId + '/duplicate';

    // Set status
    const statusSelect = document.getElementById('edit-status');
    statusSelect.value = bill.status || 'pending';

    // Set vendor
    const vendorSelect = document.getElementById('edit-vendor_id');
    const vendorNewInput = document.getElementById('vendor-new-edit');
    vendorNewInput.value = '';
    if (bill.vendor_id) {
        vendorSelect.value = bill.vendor_id;
    } else if (bill.vendor) {
        vendorSelect.value = 'new';
        vendorNewInput.value = bill.vendor;
    } else {
        vendorSelect.value = '';
    }
    toggleNewVendor('edit');

    // Set color
    const colorInput = document.getElementById('color-edit');
    colorInput.value = bill.color || '';
    document.querySelectorAll('#editModal .color-option').forEach(el => {
        el.classList.toggle('selected', el.style.background === bill.color || rgbToHex(el.style.backgroundColor) === (bill.color || '').toLowerCase());
    });

    // Show attachments
    const attDiv = document.getElementById('edit-attachments');
    if (bill.attachments && bill.attachments.length > 0) {
        let html = '<div class="form-group"><label>Đính kèm hiện tại</label><div class="attachment-list">';
        bill.attachments.forEach(att => {
            html += `<div class="attachment-item">
                <a href="<?= APP_URL ?>/bill-attachments/${att.id}/download" class="attachment-name">${escHtml(att.original_name)}</a>
                <span class="text-muted text-sm">(${Math.round(att.file_size/1024)}KB)</span>
                <a href="<?= APP_URL ?>/bill-attachments/${att.id}/delete" class="btn-ghost text-sm" onclick="return confirm('Xóa file?')" style="color:var(--neon-pink)">✕</a>
            </div>`;
        });
        html += '</div></div>';
        attDiv.innerHTML = html;
    } else {
        attDiv.innerHTML = '';
    }

    // Update modal title with status indicator
    const statusColors = { pending: 'var(--neon-yellow)', paid: 'var(--green)', overdue: 'var(--neon-pink)' };
    const statusLabels = { pending: 'Pending', paid: 'Paid', overdue: 'Overdue' };
    document.getElementById('editModalTitle').innerHTML = 'Chỉnh sửa Bill <span style="font-size:11px;padding:2px 8px;border-radius:8px;background:' + (statusColors[bill.status] || 'var(--text-muted)') + '20;color:' + (statusColors[bill.status] || 'var(--text-muted)') + ';font-weight:700;margin-left:8px">' + (statusLabels[bill.status] || bill.status) + '</span>';

    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

function selectColor(context, hex, el) {
    document.getElementById('color-' + context).value = hex;
    el.parentElement.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
}

function toggleNewVendor(context) {
    const select = document.getElementById('vendor-select-' + context) || document.getElementById(context === 'edit' ? 'edit-vendor_id' : 'vendor-select-create');
    const newInput = document.getElementById('vendor-new-' + context);
    if (select && newInput) {
        newInput.style.display = select.value === 'new' ? 'block' : 'none';
    }
}

function rgbToHex(rgb) {
    if (!rgb || rgb.startsWith('#')) return rgb;
    const m = rgb.match(/\d+/g);
    if (!m || m.length < 3) return '';
    return '#' + m.slice(0,3).map(x => parseInt(x).toString(16).padStart(2,'0')).join('');
}

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// Close modal on Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEditModal(); });

document.addEventListener('click', e => {
    if (e.target.closest('[data-no-modal="true"]')) return;

    const trigger = e.target.closest('[data-bill-trigger]');
    if (!trigger) return;

    e.preventDefault();
    openEditModal(trigger.getAttribute('data-bill-trigger'));
});

document.addEventListener('keydown', e => {
    if (e.key !== 'Enter' && e.key !== ' ') return;

    const trigger = e.target.closest('.bill-row-clickable, .cal-cell-interactive');
    if (!trigger || trigger !== e.target) return;

    e.preventDefault();
    openEditModal(trigger.getAttribute('data-bill-trigger'));
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
