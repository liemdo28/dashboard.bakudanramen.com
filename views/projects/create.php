<?php
$pageTitle = 'Tạo Project Mới';
$currentPage = 'projects';
ob_start();
?>
<div class="card" style="max-width:600px">
    <div class="card-body">
        <form method="POST" action="<?= APP_URL ?>/projects">
            <div class="form-group"><label>Tên Project *</label><input type="text" name="name" class="form-control" placeholder="VD: Website Redesign" required></div>
            <div class="form-group"><label>Mô tả</label><textarea name="description" class="form-control" placeholder="Mô tả ngắn..."></textarea></div>
            <div class="form-group"><label>Màu</label>
                <div class="color-options">
                    <?php foreach (['#DC2626','#EA580C','#D97706','#16A34A','#2563EB','#7C3AED','#DB2777','#475569'] as $c): ?>
                    <label class="color-option <?= $c === '#DC2626' ? 'selected' : '' ?>" style="background:<?= $c ?>" onclick="this.querySelector('input').checked=true;document.querySelectorAll('.color-option').forEach(e=>e.classList.remove('selected'));this.classList.add('selected')">
                        <input type="radio" name="color" value="<?= $c ?>" <?= $c === '#DC2626' ? 'checked' : '' ?> style="display:none">
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if (!empty($users) && count($users) > 1): ?>
            <div class="form-group"><label>Thêm thành viên</label>
                <?php foreach ($users as $u): if ($u['id'] == $_SESSION['user_id']) continue; ?>
                <label style="display:flex;align-items:center;gap:8px;padding:6px 0;cursor:pointer;font-size:13px;color:var(--text-secondary)"><input type="checkbox" name="members[]" value="<?= $u['id'] ?>"><?= e($u['name']) ?> <span class="text-muted text-sm">(<?= e($u['email']) ?>)</span></label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="flex gap-2 mt-4"><button type="submit" class="btn btn-primary">Tạo Project</button><a href="<?= APP_URL ?>/projects" class="btn btn-secondary">Hủy</a></div>
        </form>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
