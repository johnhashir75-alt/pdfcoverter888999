<?php
if (!file_exists(__DIR__ . '/config.php') || !file_exists(__DIR__ . '/includes/.installed')) {
    header('Location: install.php');
    exit;
}
require __DIR__ . '/includes/bootstrap.php';
if (!empty($_SESSION['uid'])) {
    header('Location: dashboard.php'); exit;
}
header('Location: login.php'); exit;
