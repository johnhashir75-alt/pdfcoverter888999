<?php include __DIR__.'/_layout.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $id=(int)($_POST['id']??0); $act=$_POST['act']??'';
    if ($act==='ban') q("UPDATE users SET is_banned=1 WHERE id=?", 'i',[$id]);
    if ($act==='unban') q("UPDATE users SET is_banned=0 WHERE id=?", 'i',[$id]);
    if ($act==='delete') { q("DELETE FROM ledger WHERE user_id=?",'i',[$id]); q("DELETE FROM users WHERE id=?",'i',[$id]); }
    if ($act==='addpts') {
        $p=(int)$_POST['pts']; if ($p!==0) { add_ledger($id, $p>0?'admin_add':'admin_remove', $p, 'Admin adjust'); }
    }
    redirect('users.php');
}
$s=trim($_GET['q']??'');
$where = $s ? "WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ?" : '';
$rows = $s
  ? fetch_all("SELECT * FROM users $where ORDER BY id DESC LIMIT 200", 'sss',["%$s%","%$s%","%$s%"])
  : fetch_all("SELECT * FROM users ORDER BY id DESC LIMIT 200");
?>
<h3>Users</h3>
<form class="mb-3"><input name="q" class="form-control" placeholder="Search name/email/phone" value="<?=e($s)?>"></form>
<div class="card"><div class="card-body table-responsive">
<table class="table table-sm"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Points</th><th>Ref</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($rows as $r):?>
<tr><td><?=(int)$r['id']?></td><td><?=e($r['full_name'])?></td><td><?=e($r['email'])?></td><td><?=e($r['phone'])?></td>
<td><?=(int)$r['points']?></td><td><?=e($r['ref_code'])?></td>
<td><?=$r['is_banned']?'<span class="badge bg-danger">Banned</span>':($r['is_verified']?'<span class="badge bg-success">Active</span>':'<span class="badge bg-warning">Unverified</span>')?></td>
<td>
<form method="post" class="d-inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>">
  <input type="number" name="pts" style="width:80px" class="form-control form-control-sm d-inline-block" placeholder="±pts">
  <button name="act" value="addpts" class="btn btn-sm btn-outline-primary">Adjust</button>
  <?php if($r['is_banned']):?><button name="act" value="unban" class="btn btn-sm btn-success">Unban</button>
  <?php else:?><button name="act" value="ban" class="btn btn-sm btn-warning">Ban</button><?php endif;?>
  <button name="act" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Del</button>
</form>
</td></tr>
<?php endforeach;?>
</tbody></table></div></div>
<?php include __DIR__.'/_footer.php';
