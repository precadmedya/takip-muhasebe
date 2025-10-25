<?php
require_once __DIR__ . '/env.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$timezone = env('TIMEZONE', 'Europe/Istanbul');
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set($timezone);
}
if (!extension_loaded('pdo_mysql')) {
    throw new RuntimeException('pdo_mysql not installed');
}
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', env('DB_HOST', 'localhost'), env('DB_NAME', ''));
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
];
try {
    $pdo = new PDO($dsn, env('DB_USER', ''), env('DB_PASS', ''), $options);
} catch (PDOException $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die('DB bağlanamadı: ' . $e->getMessage());
    }
    error_log('[DB_CONNECT] ' . $e->getMessage());
    $code = 'E_DB_CONNECT';
    http_response_code(500);
    $errorCode = $code;
    include dirname(__DIR__, 2) . '/errors/500.php';
    exit;
}
function db_alive(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1');
        return true;
    } catch (Exception $e) {
        return false;
    }
}
