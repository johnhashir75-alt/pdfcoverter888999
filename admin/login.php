<?php
require __DIR__.'/../includes/bootstrap.php';

// ========== LOGIN LOGIC ==========
$err = null;
$ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
$timestamp = date('Y-m-d H:i:s');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$admin_link = $protocol . "://" . $host . "/admin/index.php"; // Admin panel link

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    if (!rate_limit('admin_login', 10, 600)) {
        $err = 'Too many attempts.';
        // ⛔ NO TELEGRAM NOTIFICATION FOR RATE LIMIT
    } else {
        $user = trim($_POST['username'] ?? '');
        $pass = (string)($_POST['password'] ?? '');
        
        $a = fetch_one("SELECT * FROM admins WHERE username=? OR email=?", 'ss', [$user, $user]);
        
        if (!$a || !password_verify($pass, $a['password_hash'])) {
            $err = 'Invalid credentials';
            // ⛔ NO TELEGRAM NOTIFICATION FOR FAILED ATTEMPTS
        } else {
// Proceed with login
            login_admin((int)$a['id']);
            redirect('index.php');
        }
    }
}

// ========== PAGE RENDER ==========
$site = setting('site_name', 'VIP Rewards');
$logo = setting('logo', '');
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Login — <?= e($site) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#070913">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="logo">
                <?php if ($logo): ?>
                    <img src="<?= e($logo) ?>" alt="">
                <?php else: ?>
                    <i class="bi bi-shield-lock-fill"></i>
                <?php endif; ?>
            </div>
            <div class="site-name"><?= e($site) ?></div>
            <div class="tag">Admin Control Panel</div>
        </div>
        <div class="auth-title"><i class="bi bi-shield-lock"></i> Admin Login</div>
        
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= e($err) ?></div>
        <?php endif; ?>
        
        <form method="post" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-12">
                <label class="form-label">Username or Email</label>
                <input name="username" class="form-control" required autocomplete="username">
            </div>
            <div class="col-12">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required autocomplete="current-password">
            </div>
            <div class="col-12">
                <button class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Login</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>