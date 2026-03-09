<?php
$pageTitle = e($task['title']);
$currentPage = 'projects';
ob_start();
?>
<div style="max-width:800px">
    <div class="flex-between mb-4">
        <a href="<?= APP_URL ?>/projects/<?= $task['project_id'] ?>" class="btn btn-outline btn-sm">← <?= e($task['project_name']) ?></a>
        <div class="flex gap-2">
            <a href="<?= APP_URL ?>/tasks/<?= $task['id'] ?>/toggle" class="btn <?= $task['is_completed'] ? 'btn-secondary' : 'btn-primary' ?> btn-sm"><?= $task['is_completed'] ? '↩ Mở lại' : '✓ Hoàn thành' ?></a>
            <a href="<?= APP_URL ?>/tasks/<?= $task['id'] ?>/delete" class="btn btn-danger btn-sm" onclick="return confirm('Xóa?')">🗑 Xóa</a>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/tasks/<?= $task['id'] ?>">
                <div class="form-group"><label>Tiêu đề</label><input type="text" name="title" class="form-control" value="<?= e($task['title']) ?>" style="font-size:17px;font-weight:700"></div>
                <div class="form-group"><label>Mô tả</label><textarea name="description" class="form-control" rows="3"><?= e($task['description']) ?></textarea></div>
                <div class="grid grid-2">
                    <div class="form-group"><label>Trạng thái</label><select name="status" class="form-control"><?php foreach(['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'] as $k=>$v):?><option value="<?=$k?>" <?=$task['status']===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select></div>
                    <div class="form-group"><label>Ưu tiên</label><select name="priority" class="form-control"><?php foreach(['low'=>'Low','medium'=>'Medium','high'=>'High','urgent'=>'Urgent'] as $k=>$v):?><option value="<?=$k?>" <?=$task['priority']===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select></div>
                </div>
                <div class="grid grid-2">
                    <div class="form-group"><label>Người thực hiện</label><select name="assignee_id" class="form-control"><option value="">-- Chưa gán --</option><?php foreach($users as $u):?><option value="<?=$u['id']?>" <?=$task['assignee_id']==$u['id']?'selected':''?>><?=e($u['name'])?></option><?php endforeach;?></select></div>
                    <div class="form-group"><label>Deadline</label><input type="date" name="due_date" class="form-control" value="<?=$task['due_date']?>"></div>
                </div>
                <div class="form-group"><label>Section</label><select name="section_id" class="form-control"><?php foreach($sections as $s):?><option value="<?=$s['id']?>" <?=$task['section_id']==$s['id']?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select></div>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </form>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header"><h3>📎 Files (<?= count($attachments) ?>)</h3></div>
        <div class="card-body">
            <?php if($attachments): ?>
            <ul class="attachment-list"><?php foreach($attachments as $a):?>
            <li class="attachment-item"><span class="file-icon">📄</span><a href="<?=APP_URL?>/attachments/<?=$a['id']?>/download"><?=e($a['original_name'])?></a><span class="file-size"><?=number_format($a['file_size']/1024,1)?> KB</span><span class="text-muted text-sm"><?=e($a['user_name'])?></span><a href="<?=APP_URL?>/attachments/<?=$a['id']?>/delete" class="btn-ghost" onclick="return confirm('Xóa?')">🗑</a></li>
            <?php endforeach;?></ul>
            <?php endif;?>
            <form action="<?=APP_URL?>/tasks/<?=$task['id']?>/upload" method="POST" enctype="multipart/form-data" class="mt-3"><input type="file" name="file" class="form-control" onchange="this.form.submit()"></form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>💬 Bình luận (<?= count($comments) ?>)</h3></div>
        <div class="card-body">
            <?php foreach($comments as $c):?>
            <div class="comment-item">
                <div class="user-avatar" style="width:30px;height:30px;font-size:11px"><?=strtoupper(substr($c['user_name'],0,1))?></div>
                <div class="comment-body">
                    <div class="flex-between"><span class="comment-author"><?=e($c['user_name'])?></span>
                    <div class="flex-center gap-2"><span class="comment-time"><?=timeAgo($c['created_at'])?></span><?php if($c['user_id']==$_SESSION['user_id']||isAdmin()):?><a href="<?=APP_URL?>/comments/<?=$c['id']?>/delete" class="btn-ghost" onclick="return confirm('Xóa?')" style="font-size:11px">✕</a><?php endif;?></div></div>
                    <div class="comment-text"><?=nl2br(e($c['content']))?></div>
                </div>
            </div>
            <?php endforeach;?>
            <form method="POST" action="<?=APP_URL?>/tasks/<?=$task['id']?>/comments" class="comment-form"><input type="text" name="content" placeholder="Viết bình luận..." required><button type="submit" class="btn btn-primary btn-sm">Gửi</button></form>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
