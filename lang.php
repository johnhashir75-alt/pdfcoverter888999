<?php
function current_lang(): string {
    if (isset($_GET['lang']) && in_array($_GET['lang'],['en','ur'],true)) {
        setcookie('lang',$_GET['lang'],time()+86400*365,'/');
        return $_GET['lang'];
    }
    return $_COOKIE['lang'] ?? setting('lang_default','en');
}
function t(string $key): string {
    static $dict=null;
    if ($dict===null) {
        $l = current_lang();
        $file = __DIR__.'/../lang/'.$l.'.php';
        $dict = file_exists($file) ? require $file : [];
    }
    return $dict[$key] ?? $key;
}
function is_rtl(): bool { return current_lang()==='ur'; }
