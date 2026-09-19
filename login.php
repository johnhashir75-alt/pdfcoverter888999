<?php
require __DIR__.'/includes/bootstrap.php';
$err=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    if (!rate_limit('login',10,600)) { $err='Too many attempts.'; }
    else {
      $email=trim($_POST['email']??''); $pass=(string)($_POST['password']??'');
      $u=fetch_one("SELECT * FROM users WHERE email=?", 's',[$email]);
      if (!$u || !password_verify($pass,$u['password_hash'])) $err=t('invalid');
      elseif ($u['is_banned']) $err='Account banned.';
      else { login_user((int)$u['id']); redirect('dashboard.php'); }
    }
}
$logo = setting('logo','');
$site = setting('site_name','VIP Rewards');
include __DIR__.'/includes/header.php';?>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-brand">
      <div class="logo">
        <?php if($logo): ?><img src="<?=e($logo)?>" alt=""><?php else: ?><i class="bi bi-gem"></i><?php endif; ?>
      </div>
      <div class="site-name"><?=e($site)?></div>
      <div class="tag">Welcome back</div>
    </div>
    <div class="auth-title"><?=t('login')?></div>
    <?php if($err):?><div class="alert alert-danger"><?=e($err)?></div><?php endif;?>
    <form method="post" class="row g-3">
      <?=csrf_field()?>
      <div class="col-12">
        <label class="form-label"><?=t('email')?></label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
      </div>
      <div class="col-12">
        <label class="form-label"><?=t('password')?></label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <div class="col-12">
        <button class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> <?=t('login')?></button>
      </div>
      <div class="col-12 auth-links">
        <a href="register.php"><i class="bi bi-person-plus"></i> <?=t('register')?></a>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__.'/includes/footer.php';
