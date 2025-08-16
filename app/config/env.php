<?php
if (!function_exists('env')) {
    /**
     * Environment variable loader with multi-path search.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        static $vars = null;
        if ($vars === null) {
            $paths = [
                __DIR__ . '/.env',
                dirname(__DIR__, 2) . '/.env',
                dirname(__DIR__, 2) . '/.env.example'
            ];
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    $vars = [];
                    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                        $line = trim($line);
                        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                            continue;
                        }
                        [$k, $v] = explode('=', $line, 2);
                        $k = trim($k);
                        $v = trim($v, " \"'\xEF\xBB\xBF");
                        $vars[$k] = $v;
                    }
                    break;
                }
            }
            if ($vars === null) {
                $vars = [];
            }
        }
        return $vars[$key] ?? $default;
    }
}
