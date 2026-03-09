<?php
$pageTitle = 'Vendor List';
$currentPage = 'admin-vendors';

function vendorNextBillLabel($vendor) {
    if (empty($vendor['next_due_date'])) return '—';

    $label = date('M d', strtotime($vendor['next_due_date']));
    if (!empty($vendor['next_store_name'])) {
        $label .= ' - ' . $vendor['next_store_name'];
    }
    return $label;
}

ob_start();
?>

<div class="grid grid-2 mb-4">
    <!-- Vendor List -->
    <div class="card">
        <div class="card-header"><h3>Danh sách Vendor (<?= count($vendors) ?>)</h3></div>
        <div class="card-body" style="padding:0">
            <?php if (empty($vendors)): ?>
                <div class="empty-state" style="padding:48px 20px">
                    <div class="icon">🏢</div>
                    <h3>Chưa có vendor</h3>
                    <p>Thêm vendor để quản lý thông tin thanh toán</p>
                </div>
            <?php else: ?>
                <table class="data-table vendor-table">
                    <thead>
                        <tr>
                            <th>Tên</th>
                            <th>Cửa hàng</th>
                            <th>Next Bill</th>
                            <th>Active</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($vendors as $v): ?>
                        <?php $storeNames = array_values(array_filter(explode('||', $v['store_names'] ?? ''))); ?>
                        <tr class="vendor-summary-row" onclick="toggleVendor(<?= $v['id'] ?>)">
                            <td>
                                <button type="button" class="vendor-name-cell" onclick="event.stopPropagation(); toggleVendor(<?= $v['id'] ?>)">
                                    <span class="vendor-avatar"><?= strtoupper(mb_substr($v['name'], 0, 1)) ?></span>
                                    <span class="vendor-name-stack">
                                        <span class="vendor-name-text"><?= e($v['name']) ?></span>
                                        <?php if (!empty($v['payment_url'])): ?>
                                            <span class="text-muted text-sm"><?= e(mb_substr($v['payment_url'], 0, 36)) ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="vendor-toggle-icon" id="vendor-icon-<?= $v['id'] ?>">▶</span>
                                </button>
                            </td>
                            <td>
                                <?php if (empty($storeNames)): ?>
                                    <span class="text-muted">—</span>
                                <?php else: ?>
                                    <div class="vendor-store-list">
                                        <?php foreach ($storeNames as $storeName): ?>
                                            <span class="vendor-store-chip"><?= e($storeName) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="vendor-next-bill"><?= e(vendorNextBillLabel($v)) ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $v['is_active'] ? 'active' : 'inactive' ?>"><?= $v['is_active'] ? 'Active' : 'Inactive' ?></span>
                            </td>
                        </tr>
                        <tr class="vendor-detail-row" id="vendor-body-<?= $v['id'] ?>" style="display:none">
                            <td colspan="4">
                                <div class="vendor-detail-panel">
                                    <form method="POST" action="<?= APP_URL ?>/admin/vendors/<?= $v['id'] ?>/update" class="mb-3">
                                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                        <div class="form-group">
                                            <label>Tên Vendor *</label>
                                            <input class="form-control" name="name" value="<?= e($v['name']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Link thanh toán</label>
                                            <input class="form-control" name="payment_url" value="<?= e($v['payment_url'] ?? '') ?>" placeholder="https://...">
                                        </div>
                                        <div class="form-group">
                                            <label>Thông tin đăng nhập</label>
                                            <textarea class="form-control" name="login_info" rows="2" placeholder="Username / Password / Account ID..."><?= e($v['login_info'] ?? '') ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Ghi chú</label>
                                            <textarea class="form-control" name="notes" rows="2"><?= e($v['notes'] ?? '') ?></textarea>
                                        </div>
                                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                                            <button type="submit" class="btn btn-primary btn-sm">Cập nhật</button>
                                            <a href="<?= APP_URL ?>/admin/vendors/<?= $v['id'] ?>/toggle" class="btn btn-secondary btn-sm"><?= $v['is_active'] ? 'Vô hiệu' : 'Kích hoạt' ?></a>
                                            <a href="<?= APP_URL ?>/admin/vendors/<?= $v['id'] ?>/delete" class="btn btn-ghost btn-sm" onclick="return confirm('Xóa vendor này?')" style="color:var(--neon-pink)">Xóa</a>
                                        </div>
                                    </form>

                                    <div class="vendor-attachments-wrap">
                                        <div style="font-weight:600;font-size:13px;margin-bottom:10px">📎 Đính kèm (<?= count($v['attachments']) ?>)</div>

                                        <?php if (!empty($v['attachments'])): ?>
                                        <div class="attachment-list">
                                            <?php foreach ($v['attachments'] as $att): ?>
                                            <div class="attachment-item">
                                                <a href="<?= APP_URL ?>/vendor-attachments/<?= $att['id'] ?>/download" class="attachment-name">
                                                    <?= e($att['original_name']) ?>
                                                </a>
                                                <span class="text-muted text-sm">(<?= round($att['file_size']/1024) ?>KB)</span>
                                                <a href="<?= APP_URL ?>/vendor-attachments/<?= $att['id'] ?>/delete" class="btn-ghost text-sm" onclick="return confirm('Xóa file?')" style="color:var(--neon-pink)">✕</a>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>

                                        <form class="upload-form" onsubmit="return uploadVendorFile(event, <?= $v['id'] ?>)">
                                            <input type="file" id="file-vendor-<?= $v['id'] ?>" name="file" accept="image/*,.pdf,.doc,.docx" style="display:none">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('file-vendor-<?= $v['id'] ?>').click()">+ Upload file</button>
                                            <span class="upload-status" id="upload-status-<?= $v['id'] ?>"></span>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Vendor Form -->
    <div class="card">
        <div class="card-header"><h3>➕ Thêm Vendor</h3></div>
        <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/admin/vendors">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <div class="form-group">
                    <label>Tên Vendor *</label>
                    <input class="form-control" name="name" required placeholder="Tên công ty / nhà cung cấp">
                </div>
                <div class="form-group">
                    <label>Link thanh toán</label>
                    <input class="form-control" name="payment_url" placeholder="https://billing.example.com">
                </div>
                <div class="form-group">
                    <label>Thông tin đăng nhập</label>
                    <textarea class="form-control" name="login_info" rows="3" placeholder="Username: ...&#10;Password: ...&#10;Account ID: ..."></textarea>
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea class="form-control" name="notes" rows="2" placeholder="Ghi chú thêm..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Tạo Vendor</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleVendor(id) {
    const body = document.getElementById('vendor-body-' + id);
    const icon = document.getElementById('vendor-icon-' + id);
    if (body.style.display === 'none') {
        body.style.display = 'block';
        icon.textContent = '▼';
    } else {
        body.style.display = 'none';
        icon.textContent = '▶';
    }
}

function uploadVendorFile(e, vendorId) {
    e.preventDefault();
    const input = document.getElementById('file-vendor-' + vendorId);
    if (!input.files.length) { input.click(); return false; }

    const fd = new FormData();
    fd.append('file', input.files[0]);

    const status = document.getElementById('upload-status-' + vendorId);
    status.textContent = 'Uploading...';

    fetch('<?= APP_URL ?>/admin/vendors/' + vendorId + '/upload', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            status.textContent = 'Done!';
            location.reload();
        } else {
            status.textContent = data.error || 'Error';
        }
    })
    .catch(() => { status.textContent = 'Upload failed'; });
    return false;
}

// Auto-trigger upload on file select
document.querySelectorAll('[id^="file-vendor-"]').forEach(input => {
    input.addEventListener('change', function() {
        const vendorId = this.id.replace('file-vendor-', '');
        const fd = new FormData();
        fd.append('file', this.files[0]);
        const status = document.getElementById('upload-status-' + vendorId);
        status.textContent = 'Uploading...';
        fetch('<?= APP_URL ?>/admin/vendors/' + vendorId + '/upload', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { location.reload(); }
            else { status.textContent = data.error || 'Error'; }
        })
        .catch(() => { status.textContent = 'Upload failed'; });
    });
});
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
