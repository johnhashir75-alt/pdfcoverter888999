<?php
require __DIR__.'/includes/bootstrap.php';
$u=require_user();
$rows=fetch_all(
  "SELECT title,body,created_at,user_id FROM notifications
   WHERE user_id=? OR user_id IS NULL
   ORDER BY id DESC LIMIT 100", 'i',[$u['id']]);
include __DIR__.'/includes/header.php';?>
<div class="topbar">
  <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i></a>
  <div style="text-align:center;flex:1"><div class="welcome">Inbox</div><div class="uname">Notifications</div></div>
  <a href="profile.php" class="bell"><i class="bi bi-person"></i></a>
</div>
<div class="vcard" style="padding:0">
<?php if(!$rows): ?><div class="text-center text-muted p-4"><i class="bi bi-inbox"></i> No notifications yet.</div><?php endif; ?>
<?php foreach($rows as $r): ?>
  <div style="padding:14px 16px;border-bottom:1px solid var(--line)">
    <div style="display:flex;justify-content:space-between;gap:8px">
      <b><?php if($r['user_id']===null): ?><span class="badge bg-success me-1">Broadcast</span><?php endif; ?><?=e($r['title'])?></b>
      <span class="small text-muted"><?=e($r['created_at'])?></span>
    </div>
    <div class="small mt-1"><?=nl2br(e($r['body']))?></div>
  </div>
<?php endforeach; ?>
</div>
<?php include __DIR__.'/includes/footer.php';
