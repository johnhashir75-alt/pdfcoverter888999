<?php
require __DIR__.'/includes/bootstrap.php';
$u=require_user();
$err=$ok=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $pts=(int)($_POST['points']??0);
    $method=$_POST['method']??'easypaisa';
    $acc=trim($_POST['account_number']??'');
    $accName=trim($_POST['account_name']??'');
    $min=(int)setting('min_withdraw', setting('min_payout','500')); $max=(int)setting('max_payout','20000');
    $limit=(int)setting('daily_payout_limit','5000');
    $todayReq=(int)(fetch_one("SELECT COALESCE(SUM(points),0) s FROM payouts WHERE user_id=? AND DATE(created_at)=CURDATE() AND status<>'rejected'", 'i',[$u['id']])['s']);
    if (!in_array($method,['easypaisa','jazzcash','binance','bank'])) $err='Invalid method';
    elseif ($pts<$min) $err="Minimum $min points";
    elseif ($pts>$max) $err="Maximum $max points";
    elseif ($pts>(int)$u['points']) $err='Not enough points';
    elseif (($todayReq+$pts)>$limit) $err='Daily payout limit exceeded';
    elseif (!$acc) $err='Account number required';
    elseif (!$accName) $err='Account holder name required';
    else {
        $feePct=(float)setting('payout_fee_percent','0');
        $amount=points_to_amount($pts);
        $fee=round($amount*$feePct/100,2);
        q("INSERT INTO payouts(user_id,method,account_name,account_number,points,amount,fee) VALUES(?,?,?,?,?,?,?)",
          'isssidd',[$u['id'],$method,$accName,$acc,$pts,$amount,$fee]);
        add_ledger((int)$u['id'],'payout',-$pts,"Payout $method $acc");
        notify((int)$u['id'],'Redeem request',"Requested $pts points ($amount PKR).");
        $ok='Request submitted.';
    }
    $u=require_user();
}
$hist=fetch_all("SELECT * FROM payouts WHERE user_id=? ORDER BY id DESC LIMIT 20", 'i',[$u['id']]);
$ledger=fetch_all("SELECT * FROM ledger WHERE user_id=? ORDER BY id DESC LIMIT 30", 'i',[$u['id']]);
$rate = (int)(setting('points_rate') ?: 100);
include __DIR__.'/includes/header.php';?>
<div class="topbar">
  <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i></a>
  <div style="text-align:center;flex:1"><div class="welcome">Redeem</div><div class="uname">Withdraw</div></div>
  <a href="profile.php" class="bell"><i class="bi bi-person"></i></a>
</div>

<div class="small text-muted mb-2" style="text-align:center">
  <i class="bi bi-info-circle"></i> Conversion: <b><?=$rate?> pts = 1 PKR</b>
</div>

<div class="row g-3">
<div class="col-md-5">
<div class="vcard">
<h6><i class="bi bi-cash-coin"></i> <?=t('redeem_request')?></h6>
<div class="mb-2 small"><?=t('balance')?>: <b><?=number_format((int)$u['points'])?></b> pts <span class="text-muted">(≈ <?=number_format(points_to_amount((int)$u['points']),2)?> PKR)</span></div>
<?php if($err):?><div class="alert alert-danger"><?=e($err)?></div><?php endif;?>
<?php if($ok):?><div class="alert alert-success"><?=e($ok)?></div><?php endif;?>
<form method="post" class="row g-2"><?=csrf_field()?>
  <div class="col-6"><label class="form-label"><?=t('points')?></label><input type="number" name="points" class="form-control" required></div>
  <div class="col-6"><label class="form-label"><?=t('method')?></label>
    <select name="method" class="form-select">
      <option value="easypaisa">EasyPaisa</option>
      <option value="jazzcash">JazzCash</option>
      <option value="binance">Binance</option>
      <option value="bank">Bank</option>
    </select>
  </div>
  <div class="col-12"><label class="form-label">Account Holder Name</label><input name="account_name" class="form-control" required></div>
  <div class="col-12"><label class="form-label">Account / Wallet Number</label><input name="account_number" class="form-control" required></div>
  <div class="col-12"><button class="btn btn-primary w-100"><i class="bi bi-send-check"></i> <?=t('submit')?></button></div>
</form>
</div>
</div>
<div class="col-md-7">
<div class="vcard">
<h6><i class="bi bi-clock-history"></i> Payout History</h6>
<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>#</th><th>Method</th><th>Points</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php foreach($hist as $r):?><tr><td><?=(int)$r['id']?></td><td><?=e($r['method'])?></td><td><?=(int)$r['points']?></td><td><?=e($r['amount'])?></td>
<td><span class="badge bg-<?=$r['status']==='approved'?'success':($r['status']==='rejected'?'danger':'warning')?>"><?=e($r['status'])?></span></td><td><?=e($r['created_at'])?></td></tr><?php endforeach;?>
<?php if(!$hist) echo '<tr><td colspan=6 class="text-muted text-center">No requests yet.</td></tr>'; ?>
</tbody></table></div>
</div>

<div class="vcard mt-3">
<h6><i class="bi bi-list-ul"></i> Points History</h6>
<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Type</th><th>Points</th><th>Note</th><th>Date</th></tr></thead><tbody>
<?php foreach($ledger as $l):?><tr><td><?=e($l['type'])?></td><td class="<?=$l['points']<0?'text-danger':'text-success'?>"><?=(int)$l['points']?></td><td><?=e($l['note'])?></td><td><?=e($l['created_at'])?></td></tr><?php endforeach;?>
</tbody></table></div>
</div>
</div>
</div>
<?php include __DIR__.'/includes/footer.php';
