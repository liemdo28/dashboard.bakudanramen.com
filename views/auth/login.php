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

<div class="auth-shell">
    <section class="auth-hero">
        <span class="auth-badge">Neon ops workspace</span>

        <div class="auth-brand">
            <h1>Task<span>Flow</span></h1>
            <p class="auth-team">Hoang Le Team</p>
        </div>

        <p class="auth-lead">
            Theo dõi task, deadline, notification và bill trong một không gian neon gọn mắt hơn.
        </p>

        <div class="auth-highlights">
            <div class="auth-highlight">
                <strong>Task Control</strong>
                <span>Kanban, calendar, deadline trong một luồng làm việc.</span>
            </div>
            <div class="auth-highlight">
                <strong>Bill Tracking</strong>
                <span>Due date, trạng thái thanh toán và reminder rõ ràng.</span>
            </div>
            <div class="auth-highlight">
                <strong>Team Signal</strong>
                <span>Inbox thông báo và update theo thời gian thực.</span>
            </div>
        </div>
    </section>

    <div class="login-box auth-panel">
        <div class="auth-panel-head">
            <p class="auth-kicker">Sign in</p>
            <h2>Đăng nhập để tiếp tục</h2>
            <p class="auth-subtitle">Dùng tài khoản team để vào dashboard.</p>
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

        <div class="auth-footer">
            <span>Chưa có tài khoản?</span>
            <a href="<?= APP_URL ?>/register">Đăng ký ngay</a>
        </div>
    </div>
</div>

</body>
</html>
