<?php
function env(string $key, $default=null) {
    static $vars = null;
    if($vars === null) {
        $path = __DIR__.'/.env';
        $vars = [];
        if(file_exists($path)) {
            foreach(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if(str_starts_with(trim($line),'#') || !str_contains($line,'=')) continue;
                [$k,$v] = explode('=', $line, 2);
                $vars[trim($k)] = trim($v);
            }
        }
    }
    return $vars[$key] ?? $default;
}
