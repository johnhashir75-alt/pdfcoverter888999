<?php include __DIR__.'/_layout.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $id  = (int)$_POST['id'];
    $act = $_POST['act'] ?? '';
    $note= trim($_POST['note'] ?? '');
    $d = fetch_one("SELECT * FROM deposits WHERE id=?", 'i',[$id]);
    if ($d && $d['status']==='pending') {
        if ($act==='approve') {
            q("UPDATE deposits SET status='approved', admin_note=?, processed_at=NOW() WHERE id=?", 'si',[$note,$id]);
            add_ledger((int)$d['user_id'],'admin_add',(int)$d['points'],'Deposit approved: '.$d['txn_id']);
            notify((int)$d['user_id'],'Deposit approved','Your deposit of '.$d['amount'].' PKR was approved. +'.$d['points'].' pts.');

            // Referral deposit commission
            if (setting('referral_deposit_enabled','0')==='1') {
                $refPct = (float)setting('referral_deposit_percent','0');
                if ($refPct > 0) {
                    $user = fetch_one("SELECT ref_by FROM users WHERE id=?", 'i',[$d['user_id']]);
                    if ($user && !empty($user['ref_by'])) {
                        $bonus = (int)round((int)$d['points'] * $refPct / 100);
                        if ($bonus > 0) {
                            add_ledger((int)$user['ref_by'],'referral',$bonus,'Referral deposit commission');
                            notify((int)$user['ref_by'],'Referral commission','You earned +'.$bonus.' pts from your referral\'s deposit.');
                        }
                    }
                }
            }
        } elseif ($act==='reject') {
            q("UPDATE deposits SET status='rejected', admin_note=?, processed_at=NOW() WHERE id=?", 'si',[$note,$id]);
            notify((int)$d['user_id'],'Deposit rejected','Your deposit was rejected.'.($note?' Reason: '.$note:''));
        }
    }
    redirect('deposits.php');
}
$rows = fetch_all("SELECT d.*, u.full_name, u.email FROM deposits d JOIN users u ON u.id=d.user_id ORDER BY d.id DESC LIMIT 200");
?>
<h3><i class="bi bi-plus-circle"></i> Deposits</h3>
<div class="card"><div class="card-body table-responsive">
<table class="table"><thead><tr><th>#</th><th>User</th><th>Method</th><th>Amount</th><th>Points</th><th>Txn</th><th>Status</th><th>Date</th><th>Action</th></tr></thead><tbody>
<?php foreach($rows as $r): ?>
<tr>
  <td><?=(int)$r['id']?></td>
  <td><?=e($r['full_name'])?><br><small class="text-muted"><?=e($r['email'])?></small></td>
  <td><?=e($r['method'])?><br><small class="text-muted"><?=e($r['sender_number'])?></small></td>
  <td><?=e($r['amount'])?></td>
  <td><?=(int)$r['points']?></td>
  <td><?=e($r['txn_id'])?></td>
  <td><span class="badge bg-<?=$r['status']==='approved'?'success':($r['status']==='rejected'?'danger':'warning')?>"><?=e($r['status'])?></span></td>
  <td><?=e($r['created_at'])?></td>
  <td>
    <?php if($r['status']==='pending'): ?>
    <form method="post" class="d-flex gap-1"><?=csrf_field()?>
      <input type="hidden" name="id" value="<?=(int)$r['id']?>">
      <input name="note" class="form-control form-control-sm" placeholder="Note" style="max-width:120px">
      <button name="act" value="approve" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i></button>
      <button name="act" value="reject"  class="btn btn-sm btn-danger"><i class="bi bi-x-lg"></i></button>
    </form>
    <?php else: ?>
      <small class="text-muted"><?=e($r['admin_note'])?></small>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; if(!$rows) echo '<tr><td colspan=9 class="text-muted">No deposit requests.</td></tr>';?>
</tbody></table></div></div>
<?php include __DIR__.'/_footer.php';
