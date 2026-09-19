<?php
function require_user(): array {
    if (empty($_SESSION['uid'])) redirect('login.php');
    $u = fetch_one("SELECT * FROM users WHERE id=?", 'i', [$_SESSION['uid']]);
    if (!$u || $u['is_banned']) { session_destroy(); redirect('login.php'); }
    return $u;
}
function require_admin(): array {
    if (empty($_SESSION['admin_id'])) redirect('login.php');
    $a = fetch_one("SELECT * FROM admins WHERE id=?", 'i', [$_SESSION['admin_id']]);
    if (!$a) { unset($_SESSION['admin_id']); redirect('login.php'); }
    return $a;
}
function login_user(int $id): void {
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
}
function login_admin(int $id): void {
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $id;
}
function gen_ref_code(): string {
    do {
        $c = strtoupper(substr(bin2hex(random_bytes(4)),0,8));
        $e = fetch_one("SELECT id FROM users WHERE ref_code=?", 's', [$c]);
    } while ($e);
    return $c;
}
