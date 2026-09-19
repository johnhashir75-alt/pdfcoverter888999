<?php include __DIR__.'/_layout.php';
$dir=__DIR__.'/../database/';
if (($_GET['do']??'')==='backup') {
    $file='backup_'.date('Ymd_His').'.sql';
    $out=fopen($dir.$file,'w');
    $tables=fetch_all("SHOW TABLES");
    foreach($tables as $t) {
        $tn=array_values($t)[0];
        $c=fetch_one("SHOW CREATE TABLE `$tn`");
        fwrite($out, "DROP TABLE IF EXISTS `$tn`;\n".array_values($c)[1].";\n\n");
        $rows=fetch_all("SELECT * FROM `$tn`");
        foreach($rows as $r) {
            $vals=array_map(fn($v)=>$v===null?'NULL':"'".db()->real_escape_string((string)$v)."'",$r);
            fwrite($out,"INSERT INTO `$tn` VALUES(".implode(',',$vals).");\n");
        }
    }
    fclose($out);
    redirect('backup.php?ok='.urlencode($file));
}
$files=array_reverse(glob($dir.'backup_*.sql') ?: []);
?>
<h3>Backup / Restore</h3>
<?php if(!empty($_GET['ok'])):?><div class="alert alert-success">Backup created: <?=e($_GET['ok'])?></div><?php endif;?>
<a href="?do=backup" class="btn btn-primary mb-3">Create Backup</a>
<div class="card"><div class="card-body">
<ul class="list-group">
<?php foreach($files as $f): $b=basename($f); ?>
<li class="list-group-item d-flex justify-content-between"><?=e($b)?><a class="btn btn-sm btn-outline-primary" href="../database/<?=e($b)?>" download>Download</a></li>
<?php endforeach; if(!$files) echo '<li class="list-group-item text-muted">No backups.</li>';?>
</ul>
</div></div>
<?php include __DIR__.'/_footer.php';
