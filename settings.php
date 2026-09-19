<?php include __DIR__.'/_layout.php';

$groups = [
  'Branding'  => ['site_name','site_url','logo','favicon','splash_logo','primary_color','lang_default'],
  'Economy'   => ['points_rate','currency_symbol','checkin_points','plans_enabled','maintenance_mode'],
  'Referral'  => ['referral_bonus','referral_required','referral_deposit_enabled','referral_deposit_percent'],
  'Withdraw'  => ['min_withdraw','min_payout','max_payout','payout_fee_percent','daily_payout_limit'],
  'Deposit'   => ['min_deposit','deposit_easypaisa','deposit_easypaisa_name','deposit_jazzcash','deposit_jazzcash_name','deposit_bank_details'],
  'Support'   => ['support_url','support_email'],
];
$labels = [
  'points_rate' => 'Points per 1 PKR (e.g. 10 = 10pts=1PKR)',
  'currency_symbol' => 'Currency symbol (e.g. PKR)',
  'referral_bonus' => 'Signup referral bonus in points (0 = disabled)',
  'referral_required' => 'Require referral code at signup (1/0)',
  'referral_deposit_enabled' => 'Pay referrer on each approved deposit (1/0)',
  'referral_deposit_percent' => 'Referral commission % on referred user deposit (e.g. 10)',
  'checkin_points' => 'Base daily check-in points',
  'min_deposit' => 'Minimum deposit (PKR)',
  'min_withdraw' => 'Minimum withdraw (points)',
];
$longKeys = ['deposit_bank_details'];

$allKeys = [];
foreach ($groups as $ks) foreach ($ks as $k) $allKeys[]=$k;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    foreach ($allKeys as $k) if (isset($_POST[$k])) set_setting($k, (string)$_POST[$k]);
    foreach (['logo','favicon','splash_logo'] as $f) {
        if (!empty($_FILES[$f]['name'])) {
            $ext=preg_replace('/[^a-z0-9]/i','',pathinfo($_FILES[$f]['name'],PATHINFO_EXTENSION));
            $p='uploads/'.$f.'_'.time().'.'.$ext;
            move_uploaded_file($_FILES[$f]['tmp_name'], __DIR__.'/../'.$p);
            set_setting($f, '/'.$p);
        }
    }
    redirect('settings.php?ok=1');
}
?>
<h3><i class="bi bi-gear-fill"></i> Settings</h3>
<?php if(isset($_GET['ok'])):?><div class="alert alert-success">Saved successfully</div><?php endif;?>

<form method="post" enctype="multipart/form-data"><?=csrf_field()?>
<?php foreach($groups as $gname=>$keys): ?>
<div class="card mb-3"><div class="card-body">
  <h5 class="mb-3" style="color:var(--brand)"><?=e($gname)?></h5>
  <div class="row g-3">
  <?php foreach($keys as $k):
    $isLong = in_array($k,$longKeys);
    $label = $labels[$k] ?? $k;
  ?>
    <div class="col-md-4"><label class="form-label"><?=e($label)?></label>
      <?php if($isLong): ?>
        <textarea class="form-control" name="<?=$k?>" rows="2"><?=e(setting($k,''))?></textarea>
      <?php else: ?>
        <input class="form-control" name="<?=$k?>" value="<?=e(setting($k,''))?>">
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>
</div></div>
<?php endforeach; ?>

<div class="card mb-3"><div class="card-body">
  <h5 class="mb-3" style="color:var(--brand)">Uploads</h5>
  <div class="row g-3">
    <div class="col-md-4"><label class="form-label">Logo image</label><input type="file" name="logo" class="form-control"></div>
    <div class="col-md-4"><label class="form-label">Favicon</label><input type="file" name="favicon" class="form-control"></div>
    <div class="col-md-4"><label class="form-label">Splash logo</label><input type="file" name="splash_logo" class="form-control"></div>
  </div>
</div></div>

<button class="btn btn-primary btn-lg"><i class="bi bi-save"></i> Save all settings</button>
</form>

<?php include __DIR__.'/_footer.php';
