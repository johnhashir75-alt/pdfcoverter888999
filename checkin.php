<?php
require __DIR__.'/includes/bootstrap.php';
$u = require_user();
$today = date('Y-m-d');

// Must have at least one active plan to check in
$hasActive = !!fetch_one("SELECT id FROM user_plans WHERE user_id=? AND status='active' LIMIT 1", 'i',[$u['id']]);
$canCheckin = $hasActive && ($u['last_checkin']!==$today);
$msg=null;

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='checkin') {
    csrf_check();
    if (!$hasActive) {
        redirect('plans.php');
    } elseif ($canCheckin) {
        $base=(int)setting('checkin_points','10');
        $total=0;
        if ($base>0) {
            add_ledger((int)$u['id'],'checkin',$base,'Daily check-in');
            $total += $base;
        }
        $activePlans = fetch_all(
            "SELECT * FROM user_plans
             WHERE user_id=? AND status='active'
               AND (expires_at IS NULL OR expires_at >= CURDATE())
               AND (last_paid_date IS NULL OR last_paid_date < CURDATE())",
            'i',[$u['id']]);
        foreach ($activePlans as $p) {
            $pts = (int)$p['daily_return_points'];
            if ($pts>0) {
                add_ledger((int)$u['id'],'bonus',$pts,'Plan daily: '.$p['name']);
                $total += $pts;
            }
            $days = (int)$p['days_paid'] + 1;
            $status = ($days >= (int)$p['duration_days']) ? 'completed' : 'active';
            q("UPDATE user_plans SET days_paid=?, last_paid_date=CURDATE(), status=? WHERE id=?",
               'isi',[$days,$status,$p['id']]);
        }
        q("UPDATE users SET last_checkin=CURDATE() WHERE id=?", 'i',[$u['id']]);
        notify((int)$u['id'],'Check-in','You earned '.$total.' points today.');
        redirect('checkin.php?claimed='.$total);
    }
}

$claimed = isset($_GET['claimed']) ? (int)$_GET['claimed'] : null;
$base=(int)setting('checkin_points','10');
$activeToday = fetch_all(
    "SELECT * FROM user_plans
     WHERE user_id=? AND status='active'
       AND (expires_at IS NULL OR expires_at >= CURDATE())
       AND (last_paid_date IS NULL OR last_paid_date < CURDATE())",
    'i',[$u['id']]);
$myPlans = fetch_all("SELECT * FROM user_plans WHERE user_id=? AND status='active' ORDER BY id DESC", 'i',[$u['id']]);
$projected = $hasActive ? $base : 0;
foreach ($activeToday as $p) $projected += (int)$p['daily_return_points'];
include __DIR__.'/includes/header.php';
?>
<div class="topbar">
  <a href="dashboard.php" class="back-btn" title="Back"><i class="bi bi-arrow-left"></i></a>
  <div style="text-align:center;flex:1"><div class="welcome">Daily</div><div class="uname">Check-in</div></div>
  <a href="profile.php" class="bell"><i class="bi bi-person"></i></a>
</div>

<?php if ($claimed !== null): ?>
  <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Claimed +<?=$claimed?> points today.</div>
<?php endif; ?>

<?php if (!$hasActive): ?>
  <div class="vcard text-center" style="padding:36px 20px">
    <div style="font-size:44px;color:var(--brand)"><i class="bi bi-lock-fill"></i></div>
    <h5 class="mt-2">No Active Plan</h5>
    <div class="small text-muted">Daily check-in unlocks once you buy any plan.</div>
    <a href="plans.php" class="btn btn-primary mt-3 w-100"><i class="bi bi-gem"></i> Browse Plans</a>
  </div>
<?php else: ?>
  <div class="vcard gradient text-center" style="padding:28px 20px">
    <div style="font-size:12px;letter-spacing:.16em;color:var(--muted);text-transform:uppercase">Today's Reward</div>
    <div style="font-size:52px;font-weight:800;color:var(--brand);margin:8px 0">
      +<?=number_format($projected)?>
    </div>
    <div style="font-size:13px;color:var(--muted)">
      Base +<?=$base?> pts · <?=count($activeToday)?> plan payout(s) pending
    </div>
    <div class="mt-3">
      <?php if ($canCheckin): ?>
        <form method="post"><?=csrf_field()?><input type="hidden" name="action" value="checkin">
          <button class="btn btn-primary w-100" style="padding:14px;font-size:16px"><i class="bi bi-check2-circle"></i> Claim Now +<?=$projected?></button>
        </form>
      <?php else: ?>
        <button class="btn btn-outline-success w-100" disabled style="padding:14px;font-size:16px"><i class="bi bi-check-lg"></i> Already Claimed Today</button>
        <div class="mt-2 small text-muted">Come back tomorrow.</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="section-title"><h5>Active Plans</h5></div>
  <?php foreach ($myPlans as $m):
    $paidToday = ($m['last_paid_date'] === $today);
    $progress = min(100, round((int)$m['days_paid']/max(1,(int)$m['duration_days'])*100));
  ?>
    <div class="vcard mb-2">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div style="font-size:16px;font-weight:700"><i class="bi bi-gem" style="color:var(--brand)"></i> <?=e($m['name'])?></div>
          <div class="small text-muted">Daily +<?=(int)$m['daily_return_points']?> pts · <?=(int)$m['days_paid']?>/<?=(int)$m['duration_days']?> days</div>
        </div>
        <span class="badge <?=$paidToday?'bg-success':'bg-warning'?>">
          <?=$paidToday?'Paid today':'Pending'?>
        </span>
      </div>
      <div style="height:6px;background:var(--card-2);border-radius:99px;overflow:hidden;margin-top:12px">
        <div style="height:100%;width:<?=$progress?>%;background:linear-gradient(90deg,var(--brand),#4ade80)"></div>
      </div>
      <div class="small text-muted mt-2 d-flex justify-content-between">
        <span>Expires: <?=e($m['expires_at'] ?: '—')?></span>
        <span><?=$progress?>%</span>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__.'/includes/footer.php';
