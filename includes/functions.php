<?php
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function setting(string $k, ?string $default=null): ?string {
    if (!array_key_exists('_settings_cache', $GLOBALS) || $GLOBALS['_settings_cache'] === null) {
        $GLOBALS['_settings_cache']=[];
        foreach (fetch_all("SELECT k,v FROM settings") as $r) $GLOBALS['_settings_cache'][$r['k']]=$r['v'];
    }
    return $GLOBALS['_settings_cache'][$k] ?? $default;
}
function set_setting(string $k, string $v): void {
    q("INSERT INTO settings(k,v) VALUES(?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)", 'ss', [$k,$v]);
    if (!array_key_exists('_settings_cache', $GLOBALS) || $GLOBALS['_settings_cache'] === null) {
        $GLOBALS['_settings_cache'] = [];
    }
    $GLOBALS['_settings_cache'][$k] = $v;
}

function app_url(string $path=''): string {
    $base = rtrim((string)setting('site_url', defined('SITE_URL') ? SITE_URL : ''), '/');
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = $scheme.'://'.$host;
    }
    return $base.'/'.ltrim($path, '/');
}

function points_to_amount(int $points): float {
    // Admin-configurable: points_rate = how many points equal 1 PKR (e.g. 10 => 10pts = 1 PKR)
    $rate = (float)(setting('points_rate') ?: 100);
    if ($rate <= 0) $rate = 100;
    return round($points / $rate, 2);
}

function add_ledger(int $uid, string $type, int $pts, string $note=''): void {
    q("INSERT INTO ledger(user_id,type,points,note) VALUES(?,?,?,?)", 'isis',[$uid,$type,$pts,$note]);
    if ($type==='payout' || $type==='admin_remove') {
        q("UPDATE users SET points=points+?, total_redeemed=total_redeemed+? WHERE id=?", 'iii',[$pts, abs($pts), $uid]);
    } else {
        q("UPDATE users SET points=points+?, total_earned=total_earned+GREATEST(?,0) WHERE id=?", 'iii', [$pts,$pts,$uid]);
    }
}
function notify(int $uid, string $title, string $body): void {
    q("INSERT INTO notifications(user_id,title,body) VALUES(?,?,?)", 'iss', [$uid,$title,$body]);
}

function rate_limit(string $scope, int $max, int $seconds): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    q("DELETE FROM rate_limits WHERE created_at < (NOW() - INTERVAL ? SECOND)", 'i', [$seconds]);
    $n = fetch_one("SELECT COUNT(*) c FROM rate_limits WHERE scope=? AND ip=?", 'ss', [$scope,$ip])['c'] ?? 0;
    if ((int)$n >= $max) return false;
    q("INSERT INTO rate_limits(scope,ip) VALUES(?,?)", 'ss', [$scope,$ip]);
    return true;
}

function redirect(string $url): void { header("Location: $url"); exit; }
function flash(string $k, ?string $v=null) {
    if ($v===null) { $x=$_SESSION['flash'][$k] ?? null; unset($_SESSION['flash'][$k]); return $x; }
    $_SESSION['flash'][$k]=$v;
}
