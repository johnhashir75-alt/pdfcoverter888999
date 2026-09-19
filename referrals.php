<?php
require __DIR__.'/includes/bootstrap.php';
$u=require_user();
$link = SITE_URL.'/register.php?ref='.$u['ref_code'];
$refs = fetch_all("SELECT id,full_name,email,created_at FROM users WHERE ref_by=? ORDER BY id DESC", 'i',[$u['id']]);
$earned = fetch_one("SELECT COALESCE(SUM(points),0) s FROM ledger WHERE user_id=? AND type='referral'", 'i',[$u['id']])['s'];
include __DIR__.'/includes/header.php';?>
<h4><?=t('refer')?></h4>
<div class="card"><div class="card-body">
  <div class="mb-2"><?=t('ref_code')?>: <b><?=e($u['ref_code'])?></b></div>
  <div class="input-group">
    <input id="rl" class="form-control" readonly value="<?=e($link)?>">
    <button class="btn btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('rl').value)"><?=t('copy_link')?></button>
  </div>
  <div class="mt-3">Referral bonus: <b><?=e(setting('referral_bonus','50'))?></b> pts per signup · Total earned: <b><?=(int)$earned?></b></div>
</div></div>

<h5 class="mt-4"><?=t('my_referrals')?> (<?=count($refs)?>)</h5>
<div class="card"><div class="card-body table-responsive">
<table class="table mb-0"><thead><tr><th>#</th><th>Name</th><th>Email</th><th>Date</th></tr></thead><tbody>
<?php foreach($refs as $r): ?><tr><td><?=(int)$r['id']?></td><td><?=e($r['full_name'])?></td><td><?=e($r['email'])?></td><td><?=e($r['created_at'])?></td></tr><?php endforeach;?>
<?php if(!$refs)echo '<tr><td colspan=4 class="text-muted">No referrals yet.</td></tr>';?>
</tbody></table></div></div>
<?php include __DIR__.'/includes/footer.php';
