<?php include __DIR__.'/_layout.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $t=trim($_POST['title']); $b=trim($_POST['body']);
    q("INSERT INTO notifications(user_id,title,body) VALUES(NULL,?,?)", 'ss',[$t,$b]);
    redirect('notifications.php');
}
$rows=fetch_all("SELECT * FROM notifications WHERE user_id IS NULL ORDER BY id DESC LIMIT 100");
?>
<h3>Broadcast Notifications</h3>
<div class="card mb-3"><div class="card-body"><form method="post"><?=csrf_field()?>
<input class="form-control mb-2" name="title" placeholder="Title" required>
<textarea class="form-control mb-2" name="body" rows="2" placeholder="Message"></textarea>
<button class="btn btn-primary">Send to all users</button>
</form></div></div>
<?php foreach($rows as $r):?>
<div class="card mb-2"><div class="card-body"><b><?=e($r['title'])?></b><div class="small text-muted"><?=e($r['created_at'])?></div><div><?=e($r['body'])?></div></div></div>
<?php endforeach;?>
<?php include __DIR__.'/_footer.php';
