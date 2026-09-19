<?php
require __DIR__.'/includes/bootstrap.php';
$u=require_user();
$err=$ok=null;
$view = $_GET['view'] ?? 'main';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    if (($_POST['action']??'')==='profile') {
        $name=trim($_POST['full_name']??''); $phone=trim($_POST['phone']??'');
        q("UPDATE users SET full_name=?, phone=? WHERE id=?", 'ssi',[$name,$phone,$u['id']]);
        $ok='Profile updated';
    } elseif (($_POST['action']??'')==='password') {
        $cur=(string)$_POST['current']; $new=(string)$_POST['new'];
        if (!password_verify($cur,$u['password_hash'])) $err='Wrong current password';
        elseif (strlen($new)<6) $err='Password too short';
        else { q("UPDATE users SET password_hash=? WHERE id=?", 'si',[password_hash($new,PASSWORD_BCRYPT),$u['id']]); $ok='Password changed'; }
    }
    $u=require_user();
}

$initials = strtoupper(mb_substr(trim($u['full_name']?:$u['email']),0,2));
$refLink = (defined('SITE_URL')?SITE_URL:app_url('')).'/register.php?ref='.$u['ref_code'];
$refCount = (int)(fetch_one("SELECT COUNT(*) c FROM users WHERE ref_by=?", 'i',[$u['id']])['c'] ?? 0);
$support = setting('support_url','') ?: setting('support_email','');
include __DIR__.'/includes/header.php';
?>
<div class="topbar">
  <?php if ($view!=='main'): ?>
    <a href="profile.php" class="back-btn"><i class="bi bi-arrow-left"></i></a>
  <?php else: ?>
    <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i></a>
  <?php endif; ?>
  <div style="text-align:center;flex:1"><div class="welcome">Account</div><div class="uname">Profile</div></div>
  <a href="?lang=<?=current_lang()==='en'?'ur':'en'?>" class="bell" style="width:auto;padding:0 14px;font-size:12px;font-weight:700">
    <?=current_lang()==='en'?'اردو':'EN'?>
  </a>
</div>

<?php if($ok):?><div class="alert alert-success"><?=e($ok)?></div><?php endif;?>
<?php if($err):?><div class="alert alert-danger"><?=e($err)?></div><?php endif;?>

<?php if ($view==='edit'): ?>
  <div class="vcard">
    <h5><i class="bi bi-pencil-square"></i> Edit Profile</h5>
    <form method="post" class="mt-3"><?=csrf_field()?><input type="hidden" name="action" value="profile">
      <div class="mb-3"><label class="form-label">Full name</label><input class="form-control" name="full_name" value="<?=e($u['full_name'])?>"></div>
      <div class="mb-3"><label class="form-label">Email</label><input class="form-control" value="<?=e($u['email'])?>" disabled></div>
      <div class="mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?=e($u['phone'])?>"></div>
      <button class="btn btn-primary w-100"><i class="bi bi-save"></i> Save Changes</button>
    </form>
  </div>

<?php elseif ($view==='password'): ?>
  <div class="vcard">
    <h5><i class="bi bi-shield-lock"></i> Change Password</h5>
    <form method="post" class="mt-3"><?=csrf_field()?><input type="hidden" name="action" value="password">
      <div class="mb-3"><label class="form-label">Current password</label><input class="form-control" type="password" name="current" required></div>
      <div class="mb-3"><label class="form-label">New password</label><input class="form-control" type="password" name="new" required minlength="6"></div>
      <button class="btn btn-primary w-100"><i class="bi bi-check2"></i> Update Password</button>
    </form>
  </div>

<?php else: ?>
  <div class="profile-hero">
    <div class="profile-avatar"><?=e($initials)?></div>
    <div class="profile-name"><?=e($u['full_name'] ?: 'Member')?></div>
    <div class="profile-email"><?=e($u['email'])?></div>
  </div>

  <div class="balance-block">
    <div class="lbl">Wallet Balance</div>
    <div class="val"><?=number_format((int)$u['points'])?> <span style="font-size:14px;color:var(--muted)">pts</span></div>
    <div class="sub">≈ <?=number_format(points_to_amount((int)$u['points']),2)?> PKR</div>
    <div class="pgrid mt-3" style="gap:8px">
      <a href="deposit.php" class="btn btn-primary" style="padding:10px;font-size:13px"><i class="bi bi-plus-circle"></i> Deposit</a>
      <a href="wallet.php" class="btn btn-outline-success" style="padding:10px;font-size:13px"><i class="bi bi-cash-coin"></i> Withdraw</a>
    </div>
  </div>

  <div class="section-title"><h5>Wallet</h5></div>
  <div class="pgrid">
    <a href="deposit.php" class="pbtn primary">
      <span class="ico"><i class="bi bi-plus-circle"></i></span><span>Deposit<br><span class="small text-muted">Add funds</span></span>
    </a>
    <a href="wallet.php" class="pbtn primary">
      <span class="ico"><i class="bi bi-cash-coin"></i></span><span>Withdraw<br><span class="small text-muted">Cash out</span></span>
    </a>
    <a href="deposit.php#history" class="pbtn">
      <span class="ico"><i class="bi bi-inbox-fill"></i></span><span>Deposit History<br><span class="small text-muted">Past deposits</span></span>
    </a>
    <a href="wallet.php#history" class="pbtn">
      <span class="ico"><i class="bi bi-clock-history"></i></span><span>Withdraw History<br><span class="small text-muted">Past payouts</span></span>
    </a>
  </div>

  <div class="section-title"><h5>Referral</h5></div>
  <div class="vcard">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <div class="small text-muted">Your code</div>
        <div style="font-size:20px;font-weight:800;letter-spacing:.1em;color:var(--brand)"><?=e($u['ref_code'])?></div>
      </div>
      <div class="text-end">
        <div class="small text-muted">Referrals</div>
        <div style="font-size:20px;font-weight:800"><?=$refCount?></div>
      </div>
    </div>
    <div class="ref-box mt-3">
      <input id="rl" readonly value="<?=e($refLink)?>">
      <button onclick="navigator.clipboard.writeText(document.getElementById('rl').value);this.innerText='Copied ✓'"><i class="bi bi-clipboard"></i> Copy</button>
    </div>
    <a href="referrals.php" class="btn btn-outline-secondary w-100 mt-3"><i class="bi bi-people"></i> View Referrals</a>
  </div>

  <div class="section-title"><h5>Account</h5></div>
  <div class="pgrid one">
    <a href="profile.php?view=edit" class="pbtn">
      <span class="ico"><i class="bi bi-pencil-square"></i></span><span>Edit Profile</span>
    </a>
    <a href="profile.php?view=password" class="pbtn">
      <span class="ico"><i class="bi bi-shield-lock"></i></span><span>Change Password</span>
    </a>
    <?php if ($support): ?>
    <a href="<?=e(filter_var($support,FILTER_VALIDATE_EMAIL)?'mailto:'.$support:$support)?>" class="pbtn gold">
      <span class="ico"><i class="bi bi-headset"></i></span><span>Support Center</span>
    </a>
    <?php else: ?>
    <a href="#" class="pbtn gold" onclick="return false">
      <span class="ico"><i class="bi bi-headset"></i></span><span>Support Center<br><span class="small text-muted">Coming soon</span></span>
    </a>
    <?php endif; ?>
    <a href="logout.php" class="pbtn danger" onclick="return confirm('Log out?')">
      <span class="ico"><i class="bi bi-box-arrow-right"></i></span><span>Logout</span>
    </a>
  </div>
<?php endif; ?>

<?php include __DIR__.'/includes/footer.php';
