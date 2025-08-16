<?php
require_once __DIR__.'/../app/bootstrap.php';
if(($_GET['token'] ?? '') !== env('DEBUG_TOKEN')) { http_response_code(403); echo 'Yetkisiz'; exit; }
$file = __DIR__.'/../app/sql/schema_patch_services.sql';
$log = [];
if(!file_exists($file)) { die('SQL dosyası bulunamadı'); }
$sql = file_get_contents($file);
$commands = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
foreach($commands as $cmd){
    try{
        if($cmd !== ''){
            $pdo->exec($cmd);
            $log[] = 'OK: '.htmlspecialchars($cmd);
        }
    }catch(PDOException $e){
        $log[] = 'HATA/ATLANDI: '.htmlspecialchars($e->getMessage());
    }
}
?><!doctype html>
<html lang="tr"><head><meta charset="utf-8"><title>Services Repair</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><h1>Services Tablosu Onarımı</h1><ul class="list-group">
<?php foreach($log as $line): ?><li class="list-group-item"><?=$line?></li><?php endforeach; ?>
</ul></body></html>
