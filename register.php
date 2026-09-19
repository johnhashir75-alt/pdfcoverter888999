<?php
require __DIR__.'/includes/bootstrap.php';
$err=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    if (!rate_limit('register',5,600)) { $err='Too many attempts. Try later.'; }
    else {
      $name=trim($_POST['full_name']??''); $email=trim($_POST['email']??'');
      $phone=trim($_POST['phone']??''); $pass=(string)($_POST['password']??'');
      $ref=trim($_POST['ref_code']??'');
      $refReq = setting('referral_required','0')==='1';
      if (!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($pass)<6||!$phone) $err='Please fill all fields correctly.';
      elseif ($refReq && !$ref) $err='Referral code is required.';
      elseif (fetch_one("SELECT id FROM users WHERE email=?", 's',[$email])) $err='Email already registered.';
      else {
          $refBy=null;
          if ($ref) {
              $r=fetch_one("SELECT id FROM users WHERE ref_code=?", 's',[$ref]);
              if (!$r && $refReq) { $err='Invalid referral code.'; }
              else if ($r) $refBy=(int)$r['id'];
          }
          if (!$err) {
              $hash=password_hash($pass,PASSWORD_BCRYPT);
              $code=gen_ref_code();
              q("INSERT INTO users(full_name,email,phone,password_hash,ref_code,ref_by,is_verified) VALUES(?,?,?,?,?,?,1)",
                 'sssssi',[$name,$email,$phone,$hash,$code,$refBy]);
              $uid=(int)db()->insert_id;
              if ($refBy) {
                  $bonus=(int)setting('referral_bonus','0');
                  if ($bonus>0){ add_ledger($refBy,'referral',$bonus,"Referred $email"); notify($refBy,'Referral Bonus',"You earned $bonus points."); }
              }
              login_user($uid); redirect('dashboard.php');
          }
      }
    }
}
$logo = setting('logo','');
$site = setting('site_name','VIP Rewards');
include __DIR__.'/includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-brand">
      <div class="logo">
        <?php if($logo): ?><img src="<?=e($logo)?>" alt=""><?php else: ?><i class="bi bi-gem"></i><?php endif; ?>
      </div>
      <div class="site-name"><?=e($site)?></div>
      <div class="tag">Create your account</div>
    </div>
    <div class="auth-title"><?=t('register')?></div>
    <?php if ($err): ?><div class="alert alert-danger"><?=e($err)?></div><?php endif;?>
    <form method="post" class="row g-3">
      <?=csrf_field()?>
      <div class="col-12"><label class="form-label"><?=t('full_name')?></label><input class="form-control" name="full_name" required></div>
      <div class="col-12"><label class="form-label"><?=t('email')?></label><input type="email" class="form-control" name="email" required></div>
      <div class="col-6"><label class="form-label"><?=t('phone')?></label><input class="form-control" name="phone" required></div>
      <div class="col-6"><label class="form-label"><?=t('password')?></label><input type="password" class="form-control" name="password" required minlength="6"></div>
      <div class="col-12"><label class="form-label"><?=t('ref_code')?> <span class="text-muted">(optional)</span></label><input class="form-control" name="ref_code" value="<?=e($_GET['ref']??'')?>"></div>
      <div class="col-12"><button class="btn btn-primary w-100"><i class="bi bi-person-plus"></i> <?=t('register')?></button></div>
      <div class="col-12 auth-links" style="justify-content:center">
        <a href="login.php"><i class="bi bi-box-arrow-in-right"></i> Already have an account? <?=t('login')?></a>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__.'/includes/footer.php';
