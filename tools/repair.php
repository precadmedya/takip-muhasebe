<?php
require_once __DIR__ . '/../app/bootstrap.php';
$token = $_GET['token'] ?? '';
if ($token !== env('DEBUG_TOKEN')) {
    http_response_code(403);
    exit('Yetkisiz erişim');
}

$actions = [];
$baseFile = __DIR__ . '/../app/sql/schema_base.sql';
if (file_exists($baseFile)) {
    $sql = file_get_contents($baseFile);
    $stmts = array_filter(array_map('trim', explode(";\n", $sql)));
    foreach ($stmts as $stmt) {
        try {
            $pdo->exec($stmt);
            if (preg_match('/CREATE TABLE IF NOT EXISTS\s+`?(\w+)`?/i', $stmt, $m)) {
                $actions[] = 'Tablo kontrol edildi: ' . $m[1];
            } else {
                $actions[] = 'Çalıştırıldı: ' . substr($stmt, 0, 30);
            }
        } catch (Throwable $e) {
            $actions[] = 'Hata: ' . $e->getMessage();
        }
    }
}

try {
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $col = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='services' AND COLUMN_NAME=?");
    $idx = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME='services' AND COLUMN_NAME='service_name' AND NON_UNIQUE=0");

    $col->execute([$db, 'service_name']);
    $hasServiceName = $col->fetchColumn();
    $col->execute([$db, 'name']);
    $hasName = $col->fetchColumn();
    if (!$hasServiceName && $hasName) {
        $pdo->exec("ALTER TABLE services CHANGE name service_name VARCHAR(150) NOT NULL");
        $actions[] = 'Kolon adı değiştirildi: service_name';
    } else {
        $actions[] = 'service_name kolon kontrol edildi';
    }

    $col->execute([$db, 'price']);
    $hasPrice = $col->fetchColumn();
    if (!$hasPrice) {
        $pdo->exec("ALTER TABLE services ADD COLUMN price DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER description");
        $actions[] = 'price kolonu eklendi';
        $col->execute([$db, 'unit_price']);
        if ($col->fetchColumn()) {
            $pdo->exec("UPDATE services SET price = unit_price WHERE price = 0 OR price IS NULL");
            $actions[] = 'unit_price değerleri price\'a taşındı';
        }
    } else {
        $actions[] = 'price kolon kontrol edildi';
    }

    $col->execute([$db, 'period']);
    if (!$col->fetchColumn()) {
        $pdo->exec("ALTER TABLE services ADD COLUMN period ENUM('ay','yil') NOT NULL DEFAULT 'ay'");
        $actions[] = 'period kolonu eklendi';
    } else {
        $actions[] = 'period kolon kontrol edildi';
    }

    $idx->execute([$db]);
    if (!$idx->fetchColumn()) {
        $pdo->exec("ALTER TABLE services ADD UNIQUE KEY uq_services_service_name (service_name)");
        $actions[] = 'service_name için UNIQUE eklendi';
    } else {
        $actions[] = 'UNIQUE index kontrol edildi';
    }

    $pdo->exec("ALTER TABLE services CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $actions[] = 'services tablosu charset normalize edildi';
} catch (Throwable $e) {
    $actions[] = 'Patch error: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>Repair</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3">
<h1>Repair sonuçları</h1>
<ul>
<?php foreach ($actions as $a): ?>
<li><?=htmlspecialchars($a)?></li>
<?php endforeach; ?>
</ul>
</body>
</html>
