<?php
// Global bootstrap file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/env.php';
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', (int) env('APP_DEBUG', 0));
}
$timezone = env('TIMEZONE', 'Europe/Istanbul');
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set($timezone);
}
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}
$logFile = $logDir . '/php_error.log';
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', $logFile);
}
set_error_handler(function ($severity, $message, $file, $line) {
    $entry = sprintf('[%s] %s:%d %s', date('c'), $file, $line, $message);
    error_log($entry);
    if (APP_DEBUG) {
        return false;
    }
    return true;
});
set_exception_handler(function ($e) {
    $entry = sprintf('[%s] Uncaught exception: %s in %s:%d', date('c'), $e->getMessage(), $e->getFile(), $e->getLine());
    error_log($entry);
    if (APP_DEBUG) {
        throw $e;
    }
    http_response_code(500);
    $errorCode = 'EX' . substr(md5($e->getMessage()), 0, 8);
    include dirname(__DIR__) . '/errors/500.php';
    exit;
});
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $entry = sprintf('[%s] Fatal error: %s in %s:%d', date('c'), $err['message'], $err['file'], $err['line']);
        error_log($entry);
        if (!APP_DEBUG) {
            http_response_code(500);
            $errorCode = 'SHUTDOWN';
            include dirname(__DIR__) . '/errors/500.php';
        }
    }
});
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/rbac.php';
require_once __DIR__ . '/config/tenant_middleware.php';
require_once __DIR__ . '/config/subscription_guard.php';
