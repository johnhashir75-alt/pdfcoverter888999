<?php
require __DIR__.'/includes/bootstrap.php';
$u = require_user();
$err=$ok=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $pid=(int)($_POST['plan_id']??0);
    $p = fetch_one("SELECT * FROM plans WHERE id=? AND status=1", 'i',[$pid]);
    // Block re-buy if the SAME plan is still active/unexpired
    $activeSame = fetch_one(
        "SELECT id FROM user_plans
         WHERE user_id=? AND plan_id=? AND status='active'
           AND (expires_at IS NULL OR expires_at >= CURDATE())
         LIMIT 1", 'ii',[$u['id'],$pid]);
    if (!$p) $err='Plan not found.';
    elseif ($activeSame) $err='This plan is already active. Wait for it to expire before re-buying.';
    elseif ((int)$u['points'] < (int)$p['price_points']) $err='Not enough points to buy this plan.';
    else {
        add_ledger((int)$u['id'],'admin_remove',-(int)$p['price_points'],'Bought plan: '.$p['name']);
        $expires = date('Y-m-d', strtotime('+'.(int)$p['duration_days'].' days'));
        q("INSERT INTO user_plans(user_id,plan_id,name,price_points,daily_return_points,duration_days,expires_at)
           VALUES(?,?,?,?,?,?,?)",
           'iisiiis',[$u['id'],$p['id'],$p['name'],(int)$p['price_points'],(int)$p['daily_return_points'],(int)$p['duration_days'],$expires]);
        notify((int)$u['id'],'Plan purchased','You bought plan: '.$p['name']);
        $ok='Plan purchased successfully.';
    }
    $u = require_user();
}
$plans = fetch_all("SELECT * FROM plans WHERE status=1 ORDER BY price_points ASC");
// Build map of user's active plan_ids
$activeIds = [];
foreach (fetch_all("SELECT plan_id FROM user_plans WHERE user_id=? AND status='active' AND (expires_at IS NULL OR expires_at >= CURDATE())", 'i',[$u['id']]) as $ap) {
    $activeIds[(int)$ap['plan_id']] = true;
}
include __DIR__.'/includes/header.php';
?>
<div class="topbar">
  <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i></a>
  <div style="text-align:center;flex:1"><div class="welcome">Investment</div><div class="uname">VIP Plans</div></div>
  <a href="profile.php" class="bell"><i class="bi bi-person"></i></a>
</div>

<div class="balance-block" style="margin-top:10px">
  <div class="lbl">Available Balance</div>
  <div class="val"><?=number_format((int)$u['points'])?> <span style="font-size:14px;color:var(--muted)">pts</span></div>
  <div class="sub">≈ <?=number_format(points_to_amount((int)$u['points']),2)?> PKR</div>
</div>

<?php if($err):?><div class="alert alert-danger mt-2"><?=e($err)?></div><?php endif;?>
<?php if($ok):?><div class="alert alert-success mt-2"><?=e($ok)?></div><?php endif;?>

<div class="plan-grid">
<?php
$i = 0;
foreach($plans as $p):
  $i++;
  $isVip = (int)$p['price_points'] >= 5000 || stripos($p['name'],'vip')!==false || stripos($p['name'],'gold')!==false;
  $totalReturn = ((int)$p['daily_return_points']) * ((int)$p['duration_days']);
  $roi = $p['price_points']>0 ? round(($totalReturn - (int)$p['price_points']) / (int)$p['price_points'] * 100) : 0;
  $isActive = !empty($activeIds[(int)$p['id']]);
?>
<div class="plan-card <?=$isVip?'vip':''?>">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <div style="font-size:11px;color:var(--muted);letter-spacing:.14em;text-transform:uppercase"><i class="bi bi-gem"></i> Plan #<?=$i?></div>
    <?php if($isActive): ?><span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Active</span><?php endif; ?>
  </div>
  <h5><?=e($p['name'])?></h5>
  <div class="plan-price"><?=number_format((int)$p['price_points'])?><span class="u">pts</span></div>
  <ul class="plan-feats">
    <li><span>Daily Return</span><b>+<?=(int)$p['daily_return_points']?> pts</b></li>
    <li><span>Duration</span><b><?=(int)$p['duration_days']?> days</b></li>
    <li><span>Total Return</span><b><?=number_format($totalReturn)?> pts</b></li>
    <li><span>ROI</span><b><?=$roi>=0?'+':''?><?=$roi?>%</b></li>
  </ul>
  <?php if($isActive): ?>
    <button class="btn btn-outline-success w-100" disabled><i class="bi bi-lock-fill"></i> Already Active</button>
  <?php else: ?>
    <button type="button"
            class="btn <?=$isVip?'btn-gold':'btn-primary'?> w-100 js-buy"
            data-name="<?=e($p['name'])?>"
            data-price="<?=(int)$p['price_points']?>"
            data-daily="<?=(int)$p['daily_return_points']?>"
            data-days="<?=(int)$p['duration_days']?>"
            data-pid="<?=(int)$p['id']?>">
      <?=$isVip?'<i class="bi bi-star-fill"></i> Activate VIP':'<i class="bi bi-bag-check"></i> Buy Plan'?>
    </button>
  <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php if(!$plans) echo '<div class="vcard text-center text-muted">No plans available.</div>'; ?>

<!-- Confirm modal -->
<div class="modal fade" id="buyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--card);border:1px solid var(--line-2);border-radius:20px;color:var(--text)">
      <div class="modal-header" style="border-bottom:1px solid var(--line)">
        <h5 class="modal-title"><i class="bi bi-shield-check" style="color:var(--brand)"></i> Confirm Purchase</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-3">
          <div style="font-size:12px;color:var(--muted);letter-spacing:.16em;text-transform:uppercase">You are buying</div>
          <div id="bmName" style="font-size:22px;font-weight:800;margin-top:4px"></div>
        </div>
        <div class="plan-feats" style="border-top:1px dashed var(--line)">
          <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:var(--line)!important"><span class="text-muted">Price</span><b><span id="bmPrice"></span> pts</b></div>
          <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:var(--line)!important"><span class="text-muted">Daily Return</span><b>+<span id="bmDaily"></span> pts</b></div>
          <div class="d-flex justify-content-between py-2"><span class="text-muted">Duration</span><b><span id="bmDays"></span> days</b></div>
        </div>
      </div>
      <div class="modal-footer" style="border-top:1px solid var(--line)">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="post" id="bmForm" class="d-inline"><?=csrf_field()?>
          <input type="hidden" name="plan_id" id="bmPid">
          <button class="btn btn-primary"><i class="bi bi-check2-circle"></i> Confirm & Buy</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('.js-buy').forEach(b=>{
  b.addEventListener('click',()=>{
    document.getElementById('bmName').textContent=b.dataset.name;
    document.getElementById('bmPrice').textContent=Number(b.dataset.price).toLocaleString();
    document.getElementById('bmDaily').textContent=b.dataset.daily;
    document.getElementById('bmDays').textContent=b.dataset.days;
    document.getElementById('bmPid').value=b.dataset.pid;
    new bootstrap.Modal(document.getElementById('buyModal')).show();
  });
});
</script>

<?php include __DIR__.'/includes/footer.php';
