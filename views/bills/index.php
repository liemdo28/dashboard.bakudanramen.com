<?php
$pageTitle = 'Tracking Bill';
$currentPage = 'bills';

ob_start();
?>
<div class="flex-between mb-3">
    <div>
        <h2 style="margin:0;font-size:18px;font-weight:800">Tracking Bill</h2>
        <div class="text-muted text-sm">Theo dõi hóa đơn (Water / Electric / Internet…) theo từng cửa hàng</div>
    </div>
</div>

<?php if ($msg = flash('success')): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash('error')): ?>
<div class="alert alert-error"><?= e($msg) ?></div>
<?php endif; ?>

<div class="grid grid-2 mb-4">
    <div class="card">
        <div class="card-header">
            <h3>🏪 Danh sách cửa hàng</h3>
            <span class="text-muted text-sm"><?= count($stores) ?> stores</span>
        </div>
        <div class="card-body">
            <?php if (empty($stores)): ?>
                <div class="empty-state">
                    <div class="icon">🏪</div>
                    <h3>Chưa có cửa hàng</h3>
                    <p>Tạo cửa hàng để bắt đầu tracking bill</p>
                </div>
            <?php else: ?>
                <div class="store-list">
                    <?php foreach ($stores as $s):
                        $bc = $billCountMap[$s['id']] ?? null;
                        $totalBills = $bc ? (int)$bc['total'] : 0;
                        $unpaidBills = $bc ? (int)$bc['unpaid'] : 0;
                    ?>
                        <div
                            class="store-card store-card-clickable"
                            style="position:relative"
                            data-store-link="<?= APP_URL ?>/bills/store/<?= $s['id'] ?>"
                            tabindex="0"
                            role="link"
                            aria-label="Mở cửa hàng <?= e($s['name']) ?>"
                        >
                            <div style="display:flex;align-items:center;gap:12px;flex:1;text-decoration:none;color:var(--text)">
                                <span class="store-dot" style="background:<?= e($s['color'] ?: 'var(--neon-cyan)') ?>"></span>
                                <div class="store-main">
                                    <div class="store-name"><?= e($s['name']) ?></div>
                                    <div class="text-muted text-sm"><?= e($s['address'] ?? '') ?></div>
                                </div>
                                <div style="display:flex;gap:6px;align-items:center">
                                    <?php if ($unpaidBills > 0): ?>
                                        <span class="badge-overdue" style="font-size:10px"><?= $unpaidBills ?> unpaid</span>
                                    <?php endif; ?>
                                    <?php if ($totalBills > 0): ?>
                                        <span class="badge" style="background:var(--bg-tertiary);color:var(--text-muted);font-size:10px"><?= $totalBills ?> bills</span>
                                    <?php endif; ?>
                                </div>
                                <div class="store-arrow">→</div>
                            </div>
                            <div style="display:flex;gap:4px;margin-left:8px" data-no-store-nav="true">
                                <button class="btn-ghost btn-sm" type="button" onclick="openStoreEditModal(<?= $s['id'] ?>, <?= e(json_encode($s['name'])) ?>, <?= e(json_encode($s['address'] ?? '')) ?>, <?= e(json_encode($s['color'] ?? '')) ?>)" title="Edit" style="font-size:14px;padding:4px 6px">✏️</button>
                                <a class="btn-ghost btn-sm" data-no-store-nav="true" href="<?= APP_URL ?>/bills/store/<?= $s['id'] ?>/delete" onclick="return confirm('Xóa store này? (Store sẽ bị ẩn)')" title="Delete" style="font-size:14px;padding:4px 6px;color:var(--neon-pink)">🗑️</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>➕ Thêm cửa hàng</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/bills/store/create">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <div class="form-group">
                    <label>Tên cửa hàng *</label>
                    <input class="form-control" name="name" required placeholder="Bakudan Ramen - Location A">
                </div>
                <div class="form-group">
                    <label>Địa chỉ</label>
                    <input class="form-control" name="address" placeholder="...">
                </div>
                <div class="form-group">
                    <label>Màu (neon)</label>
                    <input class="form-control" name="color" placeholder="#00f5ff (neon cyan)">
                </div>
                <button class="btn btn-primary" type="submit">Tạo cửa hàng</button>
            </form>
            <div class="text-muted text-sm" style="margin-top:10px">
                Tip: đặt màu để phân biệt store trên calendar.
            </div>
        </div>
    </div>
</div>

<!-- Store Edit Modal -->
<div class="modal-overlay" id="storeEditModal" style="display:none" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:16px;width:100%;max-width:440px;box-shadow:var(--shadow-lg)">
        <div class="modal-header">
            <h3>Chỉnh sửa Store</h3>
            <button class="btn-ghost" onclick="document.getElementById('storeEditModal').style.display='none'" style="font-size:18px">✕</button>
        </div>
        <form method="POST" id="storeEditForm">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <div style="padding:22px">
                <div class="form-group">
                    <label>Tên cửa hàng *</label>
                    <input class="form-control" name="name" id="store-edit-name" required>
                </div>
                <div class="form-group">
                    <label>Địa chỉ</label>
                    <input class="form-control" name="address" id="store-edit-address">
                </div>
                <div class="form-group">
                    <label>Màu</label>
                    <input class="form-control" name="color" id="store-edit-color" placeholder="#00f5ff">
                </div>
            </div>
            <div style="padding:14px 22px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:8px">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('storeEditModal').style.display='none'">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<script>
function openStoreEditModal(id, name, address, color) {
    document.getElementById('storeEditForm').action = '<?= APP_URL ?>/bills/store/' + id + '/update';
    document.getElementById('store-edit-name').value = name || '';
    document.getElementById('store-edit-address').value = address || '';
    document.getElementById('store-edit-color').value = color || '';
    document.getElementById('storeEditModal').style.display = 'flex';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') document.getElementById('storeEditModal').style.display = 'none'; });

document.addEventListener('click', e => {
    if (e.target.closest('[data-no-store-nav="true"]')) return;

    const card = e.target.closest('[data-store-link]');
    if (!card) return;

    window.location.href = card.getAttribute('data-store-link');
});

document.addEventListener('keydown', e => {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const card = e.target.closest('.store-card-clickable');
    if (!card || card !== e.target) return;

    e.preventDefault();
    window.location.href = card.getAttribute('data-store-link');
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
