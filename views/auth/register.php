<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <meta name="theme-color" content="#dc2626">
</head>
<body class="login-page">
    <div class="login-box">
        <div class="logo">
            <h1>Task<span>Flow</span></h1>
            <p>Tạo tài khoản mới</p>
        </div>

        <?php if ($msg = flash('error')): ?>
            <div class="alert alert-error">❌ <?= e($msg) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/register">
            <div class="form-group">
                <label for="name">Họ tên</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Nguyễn Văn A" required autofocus>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="you@company.com" required>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự" required minlength="6">
            </div>
            <div class="form-group">
                <label for="password_confirm">Xác nhận mật khẩu</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="Nhập lại mật khẩu" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-full" style="justify-content:center">
                Đăng ký
            </button>
        </form>

        <p style="text-align:center;margin-top:20px;font-size:13px;color:var(--text-muted)">
            Đã có tài khoản? <a href="<?= APP_URL ?>/login" style="font-weight:600">Đăng nhập</a>
        </p>
    </div>
</body>
</html>
