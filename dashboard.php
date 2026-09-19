<?php
require __DIR__.'/includes/bootstrap.php';
$u = require_user();

$pts   = (int)$u['points'];
$pkr   = points_to_amount($pts);
$earn  = (int)$u['total_earned'];
$today = date('Y-m-d');

$base = (int)setting('checkin_points','10');
$activeToday = fetch_all(
    "SELECT * FROM user_plans
     WHERE user_id=? AND status='active'
       AND (expires_at IS NULL OR expires_at >= CURDATE())
       AND (last_paid_date IS NULL OR last_paid_date < CURDATE())",
    'i',[$u['id']]);
$hasActive = !!fetch_one("SELECT id FROM user_plans WHERE user_id=? AND status='active' LIMIT 1", 'i',[$u['id']]);
$projected = $hasActive ? $base : 0;
foreach ($activeToday as $p) $projected += (int)$p['daily_return_points'];

$recent = fetch_all("SELECT points FROM ledger WHERE user_id=? ORDER BY id DESC LIMIT 12", 'i',[$u['id']]);
$bars = [];
foreach (array_reverse($recent) as $r) $bars[] = (int)$r['points'];
while (count($bars) < 12) array_unshift($bars, 0);
$maxAbs = max(1, max(array_map('abs',$bars)));

$gainPts = 0;
foreach ($bars as $b) if ($b>0) $gainPts += $b;
$gainPct = $earn>0 ? round($gainPts/$earn*100,1) : 0;

$topPlans = fetch_all("SELECT * FROM plans WHERE status=1 ORDER BY price_points ASC LIMIT 3");
$initials = strtoupper(mb_substr(trim($u['full_name']?:$u['email']),0,2));
// Latest admin broadcasts / user notifications for the home notice bar
$notices = fetch_all(
    "SELECT title, body, created_at FROM notifications
     WHERE user_id=? OR user_id IS NULL
     ORDER BY id DESC LIMIT 3", 'i',[$u['id']]);
include __DIR__.'/includes/header.php';
?>
<div class="topbar">
  <div class="d-flex align-items-center gap-3">
    <div class="avatar"><?=e($initials)?></div>
    <div>
      <div class="welcome">Welcome Back</div>
      <div class="uname"><?=e($u['full_name'] ?: 'Member')?></div>
    </div>
  </div>
  <a href="profile.php" class="bell" title="Profile">
    <i class="bi bi-bell"></i><span class="dot"></span>
  </a>
</div>

<?php if (!empty($notices)): ?>
<div class="notice-bar">
  <div class="notice-ic"><i class="bi bi-megaphone-fill"></i></div>
  <div class="notice-body">
    <div class="notice-title"><?=e($notices[0]["title"])?></div>
    <div class="notice-text"><?=e($notices[0]["body"])?></div>
  </div>
  <a href="notifications.php" class="notice-more">All</a>
</div>
<?php endif; ?>

<div class="portfolio-label">Total Portfolio</div>
<?php
  $intPart = number_format((int)$pkr);
  $decPart = str_pad((string)round(($pkr - (int)$pkr)*100), 2, '0', STR_PAD_LEFT);
?>
<div class="portfolio-value">
  <?=$intPart?><span class="decimal">.<?=$decPart?> PKR</span>
</div>
<div class="gain-pill"><i class="bi bi-arrow-up-right"></i> +<?=number_format($gainPts)?> pts (<?=$gainPct?>%)</div>

<div class="ad-banner">
  <div style="min-width:0;flex:1">
    <div class="ad-title"><i class="bi bi-megaphone-fill"></i> Featured</div>
    <div class="ad-body ad-marquee"><span>Invite friends &amp; earn bonus • New VIP plans available • Daily check-in rewards active</span></div>
  </div>
  <a href="plans.php" class="ad-cta">Explore</a>
</div>

<div class="vcard chartbox mt-3">
  <div class="chart-head">
    <div class="range-tabs">
      <span>1H</span><span class="active">1D</span><span>1W</span><span>1M</span>
    </div>
    <div class="chart-price">
      <div class="p"><?=number_format($pts)?></div>
      <div class="c">+<?=$gainPct?>%</div>
    </div>
  </div>
  <div class="bars">
    <?php foreach ($bars as $b):
      $h = max(18, (int)round(abs($b)/$maxAbs*130));
      $cls = $b >= 0 ? 'up' : 'dn';
      if ($b === 0) { $h = 25 + (crc32((string)$b.microtime()) % 40); }
    ?>
      <div class="bar <?=$cls?>" style="height:<?=$h?>px"></div>
    <?php endforeach; ?>
  </div>
</div>

<div class="section-title">
  <h5>Markets</h5>
  <span class="live">LIVE</span>
</div>
<div class="market-row">
  <?php foreach ($topPlans as $p):
    $ret = (int)$p['daily_return_points'];
    $pct = $p['price_points']>0 ? round($ret/(int)$p['price_points']*100,1) : 0;
  ?>
  <a href="plans.php" class="market-chip text-decoration-none">
    <div class="sym"><?=e(strtoupper(mb_substr($p['name'],0,8)))?></div>
    <div class="val"><?=number_format((int)$p['price_points'])?></div>
    <div class="chg">+<?=$pct?>%/d</div>
  </a>
  <?php endforeach; ?>
  <?php if (!$topPlans): ?>
    <div class="market-chip"><div class="sym">—</div><div class="val">No plans</div></div>
  <?php endif; ?>
</div>

<div class="section-title">
  <h5>Quick Actions</h5>
</div>
<div class="market-row">
  <a href="deposit.php" class="market-chip text-decoration-none">
    <div class="sym"><i class="bi bi-plus-circle"></i> DEPOSIT</div>
    <div class="val">Add</div>
    <div class="chg">Fund account</div>
  </a>
  <a href="wallet.php" class="market-chip text-decoration-none">
    <div class="sym"><i class="bi bi-cash-coin"></i> WITHDRAW</div>
    <div class="val">Cash</div>
    <div class="chg">Redeem PKR</div>
  </a>
  <a href="checkin.php" class="market-chip text-decoration-none">
    <div class="sym"><i class="bi bi-check2-circle"></i> CHECK-IN</div>
    <div class="val">+<?=$projected?></div>
    <div class="chg"><?=$hasActive?'Claim daily':'Buy plan'?></div>
  </a>
</div>

<?php include __DIR__.'/includes/footer.php';
