<?php include __DIR__.'/_layout.php';
$me=require_admin();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    if ($me['role']!=='super') exit('Only super admin.');
    if (($_POST['act']??'')==='add') {
        $u=trim($_POST['username']); $em=trim($_POST['email']); $pw=(string)$_POST['password']; $role=$_POST['role']==='super'?'super':'manager';
        if ($u && filter_var($em,FILTER_VALIDATE_EMAIL) && strlen($pw)>=6) {
            q("INSERT IGNORE INTO admins(username,email,password_hash,role) VALUES(?,?,?,?)", 'ssss',[$u,$em,password_hash($pw,PASSWORD_BCRYPT),$role]);
        }
    } elseif (($_POST['act']??'')==='delete') {
        $id=(int)$_POST['id']; if ($id!==(int)$me['id']) q("DELETE FROM admins WHERE id=?", 'i',[$id]);
    }
    redirect('admins.php');
}
$rows=fetch_all("SELECT id,username,email,role,created_at FROM admins ORDER BY id");
?>
<h3>Admins</h3>
<?php if($me['role']==='super'):?>
<div class="card mb-3"><div class="card-body"><form method="post" class="row g-2"><?=csrf_field()?><input type="hidden" name="act" value="add">
<div class="col-md-3"><input class="form-control" name="username" placeholder="Username" required></div>
<div class="col-md-3"><input class="form-control" name="email" placeholder="Email" required></div>
<div class="col-md-3"><input class="form-control" name="password" placeholder="Password" required></div>
<div class="col-md-2"><select class="form-select" name="role"><option value="manager">manager</option><option value="super">super</option></select></div>
<div class="col-md-1"><button class="btn btn-primary">Add</button></div>
</form></div></div>
<?php endif;?>
<div class="card"><div class="card-body">
<table class="table"><thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Created</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r):?>
<tr><td><?=(int)$r['id']?></td><td><?=e($r['username'])?></td><td><?=e($r['email'])?></td><td><?=e($r['role'])?></td><td><?=e($r['created_at'])?></td>
<td><?php if($me['role']==='super' && $r['id']!=$me['id']):?><form method="post"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><button name="act" value="delete" class="btn btn-sm btn-danger">Del</button></form><?php endif;?></td></tr>
<?php endforeach;?>
</tbody></table>
</div></div>
<?php include __DIR__.'/_footer.php';
