<?php
$pageTitle = 'Cài đặt: ' . e($project['name']);
$currentPage = 'projects';
ob_start();
?>
<div class="grid grid-2">
    <div class="card">
        <div class="card-header"><h3>Thông tin Project</h3></div>
        <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/projects/<?= $project['id'] ?>/edit">
                <div class="form-group"><label>Tên</label><input type="text" name="name" class="form-control" value="<?= e($project['name']) ?>" required></div>
                <div class="form-group"><label>Mô tả</label><textarea name="description" class="form-control"><?= e($project['description']) ?></textarea></div>
                <div class="form-group"><label>Màu</label>
                    <div class="color-options">
                        <?php foreach (['#DC2626','#EA580C','#D97706','#16A34A','#2563EB','#7C3AED','#DB2777','#475569'] as $c): ?>
                        <label class="color-option <?= $c === $project['color'] ? 'selected' : '' ?>" style="background:<?= $c ?>" onclick="this.querySelector('input').checked=true;document.querySelectorAll('.color-option').forEach(e=>e.classList.remove('selected'));this.classList.add('selected')">
                            <input type="radio" name="color" value="<?= $c ?>" <?= $c === $project['color'] ? 'checked' : '' ?> style="display:none">
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group"><label>Trạng thái</label>
                    <select name="status" class="form-control">
                        <option value="active" <?= $project['status']==='active'?'selected':'' ?>>Active</option>
                        <option value="completed" <?= $project['status']==='completed'?'selected':'' ?>>Completed</option>
                        <option value="archived" <?= $project['status']==='archived'?'selected':'' ?>>Archived</option>
                    </select>
                </div>
                <div class="flex gap-2"><button type="submit" class="btn btn-primary">Lưu</button><a href="<?= APP_URL ?>/projects/<?= $project['id'] ?>" class="btn btn-secondary">Quay lại</a></div>
            </form>
        </div>
    </div>
    <div>
        <div class="card mb-4">
            <div class="card-header"><h3>Thành viên</h3></div>
            <div class="card-body">
                <?php foreach ($members as $m): ?>
                <div class="flex-between" style="padding:8px 0;border-bottom:1px solid var(--border)">
                    <div class="flex-center gap-2">
                        <div class="user-avatar" style="width:30px;height:30px;font-size:11px"><?= strtoupper(substr($m['name'],0,1)) ?></div>
                        <div><div style="font-size:13px;font-weight:600"><?= e($m['name']) ?></div><div class="text-sm text-muted"><?= e($m['email']) ?></div></div>
                    </div>
                    <div class="flex-center gap-2">
                        <span class="badge badge-<?= $m['role']==='owner'?'admin':'member' ?>"><?= ucfirst($m['role']) ?></span>
                        <?php if ($m['role'] !== 'owner'): ?>
                        <a href="<?= APP_URL ?>/projects/<?= $project['id'] ?>/members/<?= $m['id'] ?>/remove" class="btn-ghost" onclick="return confirm('Xóa?')">✕</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <form method="POST" action="<?= APP_URL ?>/projects/<?= $project['id'] ?>/members" class="mt-3 flex gap-2">
                    <select name="user_id" class="form-control" style="flex:1">
                        <option value="">-- Thêm thành viên --</option>
                        <?php foreach ($users as $u):
                            if (in_array($u['id'], array_column($members, 'id'))) continue;
                        ?>
                        <option value="<?= $u['id'] ?>"><?= e($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Thêm</button>
                </form>
            </div>
        </div>
        <div class="card" style="border-color:var(--accent-border)">
            <div class="card-header" style="background:var(--accent-bg)"><h3 style="color:var(--accent)">Vùng nguy hiểm</h3></div>
            <div class="card-body">
                <p class="text-sm text-muted mb-3">Xóa project sẽ xóa toàn bộ dữ liệu. Không thể hoàn tác.</p>
                <a href="<?= APP_URL ?>/projects/<?= $project['id'] ?>/delete" class="btn btn-danger" onclick="return confirm('Chắc chắn xóa?')">Xóa Project</a>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
