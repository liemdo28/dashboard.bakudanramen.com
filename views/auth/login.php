<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>TaskFlow - Hoang Le Team</title>

<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="manifest" href="<?= APP_URL ?>/manifest.json">

<meta name="theme-color" content="#dc2626">
</head>

<body class="login-page">

<div class="login-compact">
    <div class="login-box login-focus-card">
        <div class="login-brand">
            <div class="login-brand-mark">TF</div>
            <div class="login-brand-meta">
                <h1>Task<span>Flow</span></h1>
                <p>Hoang Le Team</p>
            </div>
        </div>

        <div class="login-panel-note">
            <h2>Đăng nhập để tiếp tục</h2>
            <p>Task, bill và calendar trong một chỗ, nhưng lần này nhìn rõ hơn.</p>
        </div>

        <?php if ($msg = flash('error')): ?>
        <div class="alert alert-error"><?= e($msg) ?></div>
        <?php endif; ?>

        <?php if ($msg = flash('success')): ?>
        <div class="alert alert-success"><?= e($msg) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/login" class="auth-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="you@company.com"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="••••••••"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-full auth-submit">
                Đăng nhập
            </button>
        </form>

        <div class="auth-footer login-compact-footer">
            <span>Chưa có tài khoản?</span>
            <a href="<?= APP_URL ?>/register">Đăng ký ngay</a>
        </div>
    </div>
</div>

</body>
</html>
