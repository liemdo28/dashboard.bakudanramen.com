<!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('page.register')) ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <meta name="theme-color" content="#dc2626">
</head>
<body class="login-page">
    <div class="login-lang-switcher" style="position:absolute;top:20px;right:20px;display:flex;gap:8px;z-index:3">
        <a href="<?= e(language_switch_url('vi')) ?>" class="login-lang-chip <?= current_locale() === 'vi' ? 'active' : '' ?>">VI</a>
        <a href="<?= e(language_switch_url('en')) ?>" class="login-lang-chip <?= current_locale() === 'en' ? 'active' : '' ?>">EN</a>
    </div>
    <div class="login-box">
        <div class="logo">
            <h1>Task<span>Flow</span></h1>
            <p><?= e(t('auth.create_account')) ?></p>
        </div>

        <?php if ($msg = flash('error')): ?>
            <div class="alert alert-error">❌ <?= e($msg) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/register">
            <div class="form-group">
                <label for="name"><?= e(t('auth.full_name')) ?></label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Nguyễn Văn A" required autofocus>
            </div>
            <div class="form-group">
                <label for="email"><?= e(t('auth.email')) ?></label>
                <input type="email" id="email" name="email" class="form-control" placeholder="you@company.com" required>
            </div>
            <div class="form-group">
                <label for="password"><?= e(t('auth.password')) ?></label>
                <input type="password" id="password" name="password" class="form-control" placeholder="<?= e(t('auth.password_min')) ?>" required minlength="6">
            </div>
            <div class="form-group">
                <label for="password_confirm"><?= e(t('auth.confirm_password')) ?></label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="<?= e(t('auth.reenter_password')) ?>" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-full" style="justify-content:center">
                <?= e(t('auth.register')) ?>
            </button>
        </form>

        <p style="text-align:center;margin-top:20px;font-size:13px;color:var(--text-muted)">
            <?= e(t('auth.have_account')) ?> <a href="<?= APP_URL ?>/login" style="font-weight:600"><?= e(t('auth.sign_in')) ?></a>
        </p>
    </div>
</body>
</html>
