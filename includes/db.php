<?php
function db(): mysqli {
    static $c = null;
    if ($c) return $c;
    // Suppress default warning; we handle the error below.
    mysqli_report(MYSQLI_REPORT_OFF);
    $c = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($c->connect_errno) {
        error_log('DB connect failed: '.$c->connect_error);
        http_response_code(500);
        exit('Database connection failed. Please check config.php or re-run install.php.');
    }
    $c->set_charset('utf8mb4');
    return $c;
}
function q(string $sql, string $types='', array $params=[]): mysqli_stmt {
    $st = db()->prepare($sql);
    if (!$st) { throw new RuntimeException('SQL prepare failed: '.db()->error); }
    if ($types) $st->bind_param($types, ...$params);
    $st->execute();
    return $st;
}
function fetch_one(string $sql, string $t='', array $p=[]): ?array {
    $r = q($sql,$t,$p)->get_result()->fetch_assoc();
    return $r ?: null;
}
function fetch_all(string $sql, string $t='', array $p=[]): array {
    return q($sql,$t,$p)->get_result()->fetch_all(MYSQLI_ASSOC);
}
