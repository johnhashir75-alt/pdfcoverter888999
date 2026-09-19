<?php include __DIR__.'/_layout.php';
$totalUsers=(int)fetch_one("SELECT COUNT(*) c FROM users")['c'];
$verified =(int)fetch_one("SELECT COUNT(*) c FROM users WHERE is_verified=1")['c'];
$pendingPayouts=(int)fetch_one("SELECT COUNT(*) c FROM payouts WHERE status='pending'")['c'];
$pointsIssued=(int)fetch_one("SELECT COALESCE(SUM(points),0) s FROM ledger WHERE points>0")['s'];
$pointsPaid  =(int)fetch_one("SELECT COALESCE(SUM(ABS(points)),0) s FROM ledger WHERE type='payout'")['s'];
?>
<h3>Dashboard</h3>
<div class="row g-3">
  <div class="col-md-3"><div class="stat-card"><div class="label">Users</div><div class="value"><?=$totalUsers?></div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="label">Verified</div><div class="value"><?=$verified?></div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="label">Pending payouts</div><div class="value"><?=$pendingPayouts?></div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="label">Points issued</div><div class="value"><?=$pointsIssued?></div></div></div>
</div>
<?php include __DIR__.'/_footer.php';
