<?php
require __DIR__.'/includes/bootstrap.php';
$u=require_user();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $tid=(int)($_POST['task_id']??0);
    $t=fetch_one("SELECT * FROM tasks WHERE id=? AND status=1", 'i',[$tid]);
    $done=fetch_one("SELECT id FROM task_completions WHERE user_id=? AND task_id=?", 'ii',[$u['id'],$tid]);
    if ($t && !$done) {
        q("INSERT INTO task_completions(user_id,task_id) VALUES(?,?)", 'ii',[$u['id'],$tid]);
        add_ledger((int)$u['id'],'task',(int)$t['points'],'Task: '.$t['title']);
        notify((int)$u['id'],'Task completed','You earned '.$t['points'].' points.');
    }
    redirect('tasks.php');
}
$tasks=fetch_all("SELECT t.*, tc.id AS done FROM tasks t LEFT JOIN task_completions tc ON tc.task_id=t.id AND tc.user_id=? WHERE t.status=1 ORDER BY t.id DESC", 'i',[$u['id']]);
include __DIR__.'/includes/header.php';?>
<h4><?=t('tasks')?></h4>
<div class="row g-3">
<?php foreach($tasks as $t): ?>
<div class="col-md-6"><div class="card"><div class="card-body">
  <div class="d-flex justify-content-between"><h6><?=e($t['title'])?></h6><span class="badge bg-primary">+<?=e($t['points'])?></span></div>
  <p class="small text-muted"><?=e($t['description'])?></p>
  <?php if($t['link']):?><a target="_blank" href="<?=e($t['link'])?>" class="btn btn-sm btn-outline-primary">Open Link</a><?php endif;?>
  <?php if($t['done']):?><span class="badge bg-success">Done</span>
  <?php else:?>
    <form method="post" class="d-inline"><?=csrf_field()?><input type="hidden" name="task_id" value="<?=(int)$t['id']?>">
      <button class="btn btn-sm btn-primary"><?=t('claim')?></button>
    </form>
  <?php endif;?>
</div></div></div>
<?php endforeach; if(!$tasks)echo '<div class="text-muted">No active tasks.</div>';?>
</div>
<?php include __DIR__.'/includes/footer.php';
