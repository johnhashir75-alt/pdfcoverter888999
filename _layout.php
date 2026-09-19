<?php
require __DIR__.'/../includes/bootstrap.php';
$a = require_admin();
$self = basename($_SERVER['PHP_SELF'] ?? '');
function __anav($f,$c){return $f===$c?'active':'';}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Admin — <?=e(setting('site_name'))?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head><body class="admin">
<nav class="navbar sticky-top">
<div class="container-fluid">
  <a class="navbar-brand" href="index.php"><i class="bi bi-gem" style="color:var(--brand)"></i> <?=e(setting('site_name'))?> <span class="badge ms-2">ADMIN</span></a>
  <div class="ms-auto d-flex align-items-center gap-2">
    <span class="me-2 text-muted small"><i class="bi bi-person-circle"></i> <?=e($a['username'])?></span>
    <a href="logout.php" class="btn btn-sm btn-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>
</div></nav>
<div class="container-fluid"><div class="row">
<aside class="col-md-2 py-3" style="min-height:calc(100vh - 56px)">
  <div class="list-group list-group-flush">
    <a class="list-group-item <?=__anav('index.php',$self)?>" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a class="list-group-item <?=__anav('users.php',$self)?>" href="users.php"><i class="bi bi-people-fill"></i> Users</a>
    <a class="list-group-item <?=__anav('plans.php',$self)?>" href="plans.php"><i class="bi bi-gem"></i> Plans</a>
    <a class="list-group-item <?=__anav('tasks.php',$self)?>" href="tasks.php"><i class="bi bi-list-check"></i> Tasks</a>
    <a class="list-group-item <?=__anav('deposits.php',$self)?>" href="deposits.php"><i class="bi bi-plus-circle"></i> Deposits</a>
    <a class="list-group-item <?=__anav('payouts.php',$self)?>" href="payouts.php"><i class="bi bi-cash-coin"></i> Payouts</a>
    <a class="list-group-item <?=__anav('activity.php',$self)?>" href="activity.php"><i class="bi bi-megaphone"></i> Activity</a>
    <a class="list-group-item <?=__anav('notifications.php',$self)?>" href="notifications.php"><i class="bi bi-bell"></i> Notifications</a>
    <a class="list-group-item <?=__anav('admins.php',$self)?>" href="admins.php"><i class="bi bi-shield-lock"></i> Admins</a>
    <a class="list-group-item <?=__anav('settings.php',$self)?>" href="settings.php"><i class="bi bi-gear-fill"></i> Settings</a>
    <a class="list-group-item <?=__anav('backup.php',$self)?>" href="backup.php"><i class="bi bi-download"></i> Backup</a>
  </div>
</aside>
<section class="col-md-10 py-3">
