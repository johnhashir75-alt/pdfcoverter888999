<?php include __DIR__.'/_layout.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $id=(int)$_POST['id']; $act=$_POST['act']; $note=trim($_POST['note']??'');
    $p=fetch_one("SELECT * FROM payouts WHERE id=?", 'i',[$id]);
    if ($p && $p['status']==='pending') {
        if ($act==='approve') {
            q("UPDATE payouts SET status='approved', admin_note=?, processed_at=NOW() WHERE id=?", 'si',[$note,$id]);
            notify((int)$p['user_id'],'Payout approved','Your request for '.$p['amount'].' PKR was approved.');
        } elseif ($act==='reject') {
            q("UPDATE payouts SET status='rejected', admin_note=?, processed_at=NOW() WHERE id=?", 'si',[$note,$id]);
            add_ledger((int)$p['user_id'],'admin_add',(int)$p['points'],'Payout rejected: refund');
            notify((int)$p['user_id'],'Payout rejected','Your request was rejected. Points refunded.');
        }
    }
    redirect('payouts.php');
}
$rows=fetch_all("SELECT p.*, u.full_name, u.email FROM payouts p JOIN users u ON u.id=p.user_id ORDER BY p.id DESC LIMIT 300");
?>
<h3>Payout Requests</h3>
<div class="card"><div class="card-body table-responsive">
<table class="table table-sm"><thead><tr><th>ID</th><th>User</th><th>Method</th><th>Account</th><th>Points</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r):?>
<tr>
<td><?=(int)$r['id']?></td>
<td><?=e($r['full_name'])?><br><small class="text-muted"><?=e($r['email'])?></small></td>
<td><?=e($r['method'])?></td>
<td><?=e($r['account_name'])?><br><b><?=e($r['account_number'])?></b></td>
<td><?=(int)$r['points']?></td><td><?=e($r['amount'])?></td>
<td><span class="badge bg-<?=$r['status']==='approved'?'success':($r['status']==='rejected'?'danger':'warning')?>"><?=e($r['status'])?></span></td>
<td><?=e($r['created_at'])?></td>
<td><?php if($r['status']==='pending'):?>
<form method="post"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>">
<input class="form-control form-control-sm mb-1" name="note" placeholder="Note">
<button name="act" value="approve" class="btn btn-sm btn-success">Approve</button>
<button name="act" value="reject" class="btn btn-sm btn-danger">Reject</button>
</form>
<?php else: echo e($r['admin_note']); endif;?></td>
</tr>
<?php endforeach;?>
</tbody></table></div></div>
<?php include __DIR__.'/_footer.php';
