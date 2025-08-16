<?php
require_once __DIR__ . '/../app/config/env.php';
$token = $_GET['token'] ?? '';
if ($token !== env('DEBUG_TOKEN')) {
    http_response_code(403);
    exit('Yetkisiz erişim');
}
$envPaths = [
    __DIR__ . '/../app/config/.env',
    __DIR__ . '/../.env',
    __DIR__ . '/../.env.example'
];
$envLoaded = [];
foreach ($envPaths as $p) {
    if (file_exists($p)) {
        $envLoaded = [];
        foreach (file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $envLoaded[trim($k)] = trim($v);
        }
        break;
    }
}
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', env('DB_HOST'), env('DB_NAME'));
$dbStatus = 'bağlantı başarısız';
$tables = [];
try {
    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('pdo_mysql yüklü değil');
    }
    $pdo = new PDO($dsn, env('DB_USER'), env('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->query('SELECT 1');
    $dbStatus = 'bağlantı başarılı';
    $stmt = $pdo->prepare('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?');
    $stmt->execute([env('DB_NAME')]);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $dbStatus = 'hata: ' . $e->getMessage();
}
$logFile = __DIR__ . '/../app/logs/php_error.log';
$logTail = file_exists($logFile) ? implode("", array_slice(file($logFile), -20)) : 'log yok';
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>Teşhis</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3">
<h1>Diagnostics</h1>
<p>PHP Sürümü: <?=phpversion()?></p>
<p>pdo_mysql: <?=extension_loaded('pdo_mysql')?'yüklü':'yüklü değil'?></p>
<h2>.env Dosyaları</h2>
<ul>
<?php foreach ($envPaths as $p): ?>
<li><?=htmlspecialchars($p)?> <?=file_exists($p)?'var':'yok'?></li>
<?php endforeach; ?>
</ul>
<h2>Env Değerleri</h2>
<table class="table table-sm">
<thead><tr><th>Anahtar</th><th>Değer</th></tr></thead>
<tbody>
<?php foreach ($envLoaded as $k=>$v): $val = str_contains(strtolower($k),'pass') ? '***' : $v; ?>
<tr><td><?=htmlspecialchars($k)?></td><td><?=htmlspecialchars($val)?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
<h2>Veritabanı</h2>
<p>DSN: <?=htmlspecialchars($dsn)?></p>
<p>Durum: <?=htmlspecialchars($dbStatus)?></p>
<?php if ($tables): ?>
<ul>
<?php foreach ($tables as $t): ?>
<li><?=htmlspecialchars($t)?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<h2>Log son 20 satır</h2>
<pre><?=htmlspecialchars($logTail)?></pre>
</body>
</html>
