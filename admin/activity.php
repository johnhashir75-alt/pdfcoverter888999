<?php include __DIR__.'/_layout.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    if (($_POST['act']??'')==='save') {
        $t=trim($_POST['title']); $b=trim($_POST['body']); $img='';
        if (!empty($_FILES['image']['name'])) {
            $ext=preg_replace('/[^a-z0-9]/i','',pathinfo($_FILES['image']['name'],PATHINFO_EXTENSION));
            $img='uploads/post_'.time().'.'.$ext;
            move_uploaded_file($_FILES['image']['tmp_name'], __DIR__.'/../'.$img);
        }
        q("INSERT INTO activity_posts(title,body,image) VALUES(?,?,?)", 'sss',[$t,$b,$img]);
    } elseif (($_POST['act']??'')==='delete') { q("DELETE FROM activity_posts WHERE id=?", 'i',[(int)$_POST['id']]); }
    redirect('activity.php');
}
$rows=fetch_all("SELECT * FROM activity_posts ORDER BY id DESC");
?>
<h3>News / Activity</h3>
<div class="card mb-3"><div class="card-body"><form method="post" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="act" value="save">
<input class="form-control mb-2" name="title" placeholder="Title" required>
<textarea class="form-control mb-2" name="body" rows="3" placeholder="Body"></textarea>
<input class="form-control mb-2" name="image" type="file">
<button class="btn btn-primary">Publish</button>
</form></div></div>
<?php foreach($rows as $r):?>
<div class="card mb-2"><div class="card-body">
<div class="d-flex justify-content-between"><h6><?=e($r['title'])?></h6>
<form method="post"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><button name="act" value="delete" class="btn btn-sm btn-danger">Del</button></form>
</div>
<div class="small text-muted"><?=e($r['created_at'])?></div>
<div><?=nl2br(e($r['body']))?></div>
</div></div>
<?php endforeach;?>
<?php include __DIR__.'/_footer.php';
