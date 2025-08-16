<?php
require_once __DIR__.'/env.php';
if(session_status() === PHP_SESSION_NONE) session_start();
$timezone = env('TIMEZONE','Europe/Istanbul');
if(function_exists('date_default_timezone_set')) {
    date_default_timezone_set($timezone);
}
$dsn = 'mysql:host='.env('DB_HOST','localhost').';dbname='.env('DB_NAME','').';charset=utf8mb4';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
try {
    $pdo = new PDO($dsn, env('DB_USER',''), env('DB_PASS',''), $options);
} catch (PDOException $e) {
    die('Veritabanına bağlanılamadı.');
}
