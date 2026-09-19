<?php include __DIR__.'/_layout.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $act=$_POST['act']??'';
    if ($act==='save') {
        $id=(int)($_POST['id']??0);
        $title=trim($_POST['title']); $desc=trim($_POST['description']); $link=trim($_POST['link']);
        $pts=(int)$_POST['points']; $status=(int)($_POST['status']??1);
        $img='';
        if (!empty($_FILES['image']['name'])) {
            $ext=pathinfo($_FILES['image']['name'],PATHINFO_EXTENSION);
            $img='uploads/task_'.time().'.'.preg_replace('/[^a-z0-9]/i','',$ext);
            move_uploaded_file($_FILES['image']['tmp_name'], __DIR__.'/../'.$img);
        }
        if ($id) {
            if ($img) q("UPDATE tasks SET title=?,description=?,link=?,points=?,status=?,image=? WHERE id=?", 'sssiisi',[$title,$desc,$link,$pts,$status,$img,$id]);
            else     q("UPDATE tasks SET title=?,description=?,link=?,points=?,status=? WHERE id=?", 'sssiii',[$title,$desc,$link,$pts,$status,$id]);
        } else {
            q("INSERT INTO tasks(title,description,link,points,status,image) VALUES(?,?,?,?,?,?)", 'sssiis',[$title,$desc,$link,$pts,$status,$img]);
        }
    } elseif ($act==='delete') { q("DELETE FROM tasks WHERE id=?", 'i',[(int)$_POST['id']]); }
    redirect('tasks.php');
}
$rows=fetch_all("SELECT * FROM tasks ORDER BY id DESC");
?>
<h3>Tasks</h3>
<div class="card mb-3"><div class="card-body">
<form method="post" enctype="multipart/form-data" class="row g-2"><?=csrf_field()?><input type="hidden" name="act" value="save">
<div class="col-md-4"><input class="form-control" name="title" placeholder="Title" required></div>
<div class="col-md-2"><input class="form-control" name="points" type="number" placeholder="Points" required></div>
<div class="col-md-3"><input class="form-control" name="link" placeholder="URL (optional)"></div>
<div class="col-md-3"><input class="form-control" name="image" type="file"></div>
<div class="col-md-9"><input class="form-control" name="description" placeholder="Description"></div>
<div class="col-md-2"><select name="status" class="form-select"><option value="1">Enabled</option><option value="0">Disabled</option></select></div>
<div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
</form>
</div></div>
<div class="card"><div class="card-body table-responsive">
<table class="table"><thead><tr><th>ID</th><th>Title</th><th>Points</th><th>Status</th><th>Link</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r):?>
<tr><td><?=(int)$r['id']?></td><td><?=e($r['title'])?></td><td><?=(int)$r['points']?></td>
<td><?=$r['status']?'<span class="badge bg-success">On</span>':'<span class="badge bg-secondary">Off</span>'?></td>
<td><?=e($r['link'])?></td>
<td><form method="post" class="d-inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><button name="act" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Del</button></form></td>
</tr>
<?php endforeach;?>
</tbody></table></div></div>
<?php include __DIR__.'/_footer.php';
