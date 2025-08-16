<?php
function cache_get(string $key, int $ttl)
{
    $file = __DIR__.'/../cache/'.md5($key).'.cache';
    if(!file_exists($file)) return null;
    if(filemtime($file) + $ttl < time()) { @unlink($file); return null; }
    return unserialize(file_get_contents($file));
}
function cache_set(string $key, $content): void
{
    $dir = __DIR__.'/../cache';
    if(!is_dir($dir)) { @mkdir($dir,0775,true); }
    $file = $dir.'/'.md5($key).'.cache';
    file_put_contents($file, serialize($content), LOCK_EX);
}
?>
