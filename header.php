<?php $u = $u ?? (empty($_SESSION['uid']) ? null : fetch_one("SELECT * FROM users WHERE id=?", 'i', [$_SESSION['uid']])); ?>
<!doctype html>
<html lang="<?=current_lang()?>" dir="<?=is_rtl()?'rtl':'ltr'?>">
<head>
<meta charset="utf-8">
<title><?=e(setting('site_name','VIP Rewards'))?></title>
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#070913">
<link rel="icon" href="<?=e(setting('favicon','/assets/img/favicon.ico'))?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php $splashLogo = setting('splash_logo','') ?: setting('logo',''); ?>
<div id="splash">
  <?php if ($splashLogo): ?>
    <div class="splash-logo-wrap">
      <div class="splash-ring"></div>
      <img src="<?=e($splashLogo)?>" alt="<?=e(setting('site_name','VIP'))?>" class="splash-logo">
    </div>
  <?php else: ?>
    <div class="splash-logo-wrap">
      <div class="splash-ring"></div>
      <div class="splash-fallback"><?=e(strtoupper(substr(setting('site_name','V'),0,1)))?></div>
    </div>
  <?php endif; ?>
</div>
<main class="container py-2">
