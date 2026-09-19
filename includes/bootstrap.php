<?php
declare(strict_types=1);
if (!file_exists(__DIR__.'/../config.php') || !file_exists(__DIR__.'/.installed')) {
    header('Location: install.php'); exit;
}
require __DIR__.'/../config.php';
require __DIR__.'/db.php';
require __DIR__.'/functions.php';
require __DIR__.'/csrf.php';
require __DIR__.'/lang.php';
require __DIR__.'/auth.php';
require __DIR__.'/migrate.php';

session_set_cookie_params(['lifetime'=>0,'path'=>'/','httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])]);
session_start();

// XSS/security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Maintenance mode
$maint = setting('maintenance_mode','0');
$path = basename($_SERVER['SCRIPT_NAME']);
if ($maint==='1' && !str_starts_with($_SERVER['REQUEST_URI'],'/admin') && !in_array($path,['login.php','logout.php'])) {
    if (empty($_SESSION['admin_id'])) { echo '<h2 style="text-align:center;margin-top:20vh">Under Maintenance</h2>'; exit; }
}
