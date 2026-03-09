<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>TaskFlow - Hoang Le Team</title>

<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="manifest" href="<?= APP_URL ?>/manifest.json">

<meta name="theme-color" content="#dc2626">

<style>

body.login-page{
    background:#f5f6f8;
    display:flex;
    align-items:center;
    justify-content:center;
    height:100vh;
    margin:0;
    font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
}

.login-box{
    width:380px;
    background:white;
    border-radius:12px;
    padding:32px;
    box-shadow:0 10px 40px rgba(0,0,0,.08);
}

.logo{
    text-align:center;
    margin-bottom:24px;
}

.logo h1{
    margin:0;
    font-size:28px;
    font-weight:700;
}

.logo span{
    color:#dc2626;
}

.logo p{
    margin-top:6px;
    color:#666;
    font-size:14px;
}

.team-name{
    margin-top:4px;
    font-size:13px;
    color:#888;
}

.form-group{
    margin-bottom:16px;
}

.form-group label{
    font-size:14px;
    font-weight:600;
    display:block;
    margin-bottom:6px;
}

.form-control{
    width:100%;
    padding:10px 12px;
    border:1px solid #ddd;
    border-radius:6px;
    font-size:14px;
}

.form-control:focus{
    border-color:#dc2626;
    outline:none;
}

.btn{
    width:100%;
    padding:12px;
    border:none;
    border-radius:6px;
    font-size:15px;
    cursor:pointer;
}

.btn-primary{
    background:#dc2626;
    color:white;
}

.btn-primary:hover{
    background:#b91c1c;
}

.alert{
    padding:10px 12px;
    border-radius:6px;
    font-size:14px;
    margin-bottom:12px;
}

.alert-error{
    background:#fee2e2;
    color:#991b1b;
}

.alert-success{
    background:#dcfce7;
    color:#166534;
}

.login-footer{
    text-align:center;
    margin-top:18px;
    font-size:13px;
    color:#666;
}

.login-footer a{
    color:#dc2626;
    text-decoration:none;
    font-weight:600;
}

.login-footer a:hover{
    text-decoration:underline;
}

</style>

</head>

<body class="login-page">

<div class="login-box">

<div class="logo">
<h1>Task<span>Flow</span></h1>
<p class="team-name">Hoang Le Team</p>
<p>Đăng nhập để tiếp tục</p>
</div>

<?php if ($msg = flash('error')): ?>
<div class="alert alert-error"><?= e($msg) ?></div>
<?php endif; ?>

<?php if ($msg = flash('success')): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<form method="POST" action="<?= APP_URL ?>/login">

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

<button type="submit" class="btn btn-primary">
Đăng nhập
</button>

</form>

<div class="login-footer">
Chưa có tài khoản?
<a href="<?= APP_URL ?>/register">Đăng ký ngay</a>
</div>

</div>

</body>
</html>