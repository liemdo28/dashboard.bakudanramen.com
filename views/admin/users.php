<?php
$pageTitle = 'Quản lý Users';
$currentPage = 'admin-users';
ob_start();
?>
<div class="grid grid-2">
    <div class="card">
        <div class="card-header"><h3>Danh sách (<?= count($users) ?>)</h3></div>
        <div class="card-body" style="padding:0">
            <table class="data-table">
                <thead><tr><th>Tên</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach($users as $u):?>
                <tr>
                    <td><div class="flex-center gap-2"><div class="user-avatar" style="width:26px;height:26px;font-size:10px"><?=strtoupper(substr($u['name'],0,1))?></div><span style="font-weight:600"><?=e($u['name'])?></span></div></td>
                    <td class="text-sm text-muted"><?=e($u['email'])?></td>
                    <td><span class="badge badge-<?=$u['role']?>"><?=ucfirst($u['role'])?></span></td>
                    <td><span class="badge badge-<?=$u['is_active']?'active':'inactive'?>"><?=$u['is_active']?'Active':'Inactive'?></span></td>
                    <td><?php if($u['id']!=$_SESSION['user_id']):?><div class="flex gap-2"><a href="<?=APP_URL?>/admin/users/<?=$u['id']?>/toggle" class="btn-ghost" title="Toggle"><?=$u['is_active']?'🔒':'🔓'?></a><a href="<?=APP_URL?>/admin/users/<?=$u['id']?>/delete" class="btn-ghost" onclick="return confirm('Xóa?')">🗑</a></div><?php else:?><span class="text-muted text-sm">Bạn</span><?php endif;?></td>
                </tr>
                <?php endforeach;?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Tạo tài khoản</h3></div>
        <div class="card-body">
            <form method="POST" action="<?=APP_URL?>/admin/users">
                <div class="form-group"><label>Họ tên *</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" required></div>
                <div class="form-group"><label>Mật khẩu *</label><input type="password" name="password" class="form-control" required minlength="6"></div>
                <div class="form-group"><label>Vai trò</label><select name="role" class="form-control"><option value="member">Member</option><option value="admin">Admin</option></select></div>
                <button type="submit" class="btn btn-primary">Tạo</button>
            </form>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
