<?php include __DIR__.'/_layout.php';
$err=$ok=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';
    if ($act==='save') {
        $id     = (int)($_POST['id'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        $price  = (int)($_POST['price_points'] ?? 0);
        $daily  = (int)($_POST['daily_return_points'] ?? 0);
        $days   = (int)($_POST['duration_days'] ?? 30);
        $status = isset($_POST['status']) ? 1 : 0;
        if (!$name || $price<0 || $daily<0 || $days<=0) $err='Please fill all fields correctly.';
        else {
            if ($id>0) {
                q("UPDATE plans SET name=?, price_points=?, daily_return_points=?, duration_days=?, status=? WHERE id=?",
                   'siiiii',[$name,$price,$daily,$days,$status,$id]);
                $ok='Plan updated.';
            } else {
                q("INSERT INTO plans(name,price_points,daily_return_points,duration_days,status) VALUES(?,?,?,?,?)",
                   'siiii',[$name,$price,$daily,$days,$status]);
                $ok='Plan added.';
            }
        }
    } elseif ($act==='delete') {
        $id=(int)$_POST['id'];
        q("DELETE FROM plans WHERE id=?", 'i',[$id]);
        $ok='Plan deleted.';
    } elseif ($act==='toggle') {
        $id=(int)$_POST['id'];
        q("UPDATE plans SET status=1-status WHERE id=?", 'i',[$id]);
        $ok='Status updated.';
    }
}
$plans = fetch_all("SELECT * FROM plans ORDER BY id DESC");
$editing = null;
if (isset($_GET['edit'])) $editing = fetch_one("SELECT * FROM plans WHERE id=?", 'i',[(int)$_GET['edit']]);
?>
<h3>Plans</h3>
<?php if($err):?><div class="alert alert-danger"><?=e($err)?></div><?php endif;?>
<?php if($ok):?><div class="alert alert-success"><?=e($ok)?></div><?php endif;?>

<div class="row g-3">
<div class="col-md-4">
<div class="card"><div class="card-body">
<h6><?=$editing?'Edit plan':'Add plan'?></h6>
<form method="post"><?=csrf_field()?>
<input type="hidden" name="act" value="save">
<input type="hidden" name="id" value="<?=(int)($editing['id']??0)?>">
<div class="mb-2"><label>Name</label><input name="name" class="form-control" required value="<?=e($editing['name']??'')?>"></div>
<div class="mb-2"><label>Price (points)</label><input type="number" name="price_points" class="form-control" required value="<?=(int)($editing['price_points']??0)?>"></div>
<div class="mb-2"><label>Daily payback (points)</label><input type="number" name="daily_return_points" class="form-control" required value="<?=(int)($editing['daily_return_points']??0)?>"></div>
<div class="mb-2"><label>Duration (days)</label><input type="number" name="duration_days" class="form-control" required value="<?=(int)($editing['duration_days']??30)?>"></div>
<div class="form-check mb-2"><input type="checkbox" class="form-check-input" name="status" id="stt" <?=(!isset($editing) || (int)($editing['status']??1)===1)?'checked':''?>><label class="form-check-label" for="stt">Active</label></div>
<button class="btn btn-primary w-100"><?=$editing?'Save changes':'Add plan'?></button>
<?php if($editing):?><a href="plans.php" class="btn btn-link w-100">Cancel</a><?php endif;?>
</form>
</div></div>
</div>

<div class="col-md-8">
<div class="card"><div class="card-body table-responsive">
<table class="table"><thead><tr><th>#</th><th>Name</th><th>Price</th><th>Daily</th><th>Days</th><th>Total return</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach($plans as $p): ?>
<tr>
  <td><?=(int)$p['id']?></td>
  <td><?=e($p['name'])?></td>
  <td><?=(int)$p['price_points']?></td>
  <td>+<?=(int)$p['daily_return_points']?></td>
  <td><?=(int)$p['duration_days']?></td>
  <td><?=((int)$p['daily_return_points'])*((int)$p['duration_days'])?></td>
  <td>
    <form method="post" class="d-inline"><?=csrf_field()?><input type="hidden" name="act" value="toggle"><input type="hidden" name="id" value="<?=(int)$p['id']?>">
    <button class="btn btn-sm btn-<?=$p['status']?'success':'secondary'?>"><?=$p['status']?'Active':'Off'?></button></form>
  </td>
  <td>
    <a href="plans.php?edit=<?=(int)$p['id']?>" class="btn btn-sm btn-outline-primary">Edit</a>
    <form method="post" class="d-inline" onsubmit="return confirm('Delete this plan?')"><?=csrf_field()?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?=(int)$p['id']?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form>
  </td>
</tr>
<?php endforeach; if(!$plans) echo '<tr><td colspan=8 class="text-muted">No plans yet.</td></tr>';?>
</tbody></table>
</div></div>
</div>
</div>
<?php include __DIR__.'/_footer.php';
