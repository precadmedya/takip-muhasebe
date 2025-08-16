<?php
$code = $errorCode ?? ($code ?? '500');
http_response_code(500);
$logTail = '';
if(defined('APP_DEBUG') && APP_DEBUG){
    $file = __DIR__.'/../app/logs/php_error.log';
    if(is_readable($file)){
        $lines = explode("\n", trim(@file_get_contents($file)));
        $logTail = implode("\n", array_slice($lines, -50));
    }
}
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>Hata</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
<div class="container text-center">
  <h1 class="display-4 mb-3">Üzgünüz</h1>
  <p class="lead">Bir şeyler ters gitti.</p>
  <p>Olay kodu: <?=htmlspecialchars($code)?></p>
  <?php if($logTail): ?>
  <pre class="text-start bg-white border rounded p-3 small" aria-label="Hata kaydı"><?=htmlspecialchars($logTail)?></pre>
  <?php endif; ?>
  <a href="/login.php" class="btn btn-primary mt-3">Giriş sayfasına dön</a>
  <a href="mailto:<?=htmlspecialchars(env('SUPPORT_EMAIL','destek@example.com'))?>" class="btn btn-link">Destek ile iletişime geçin</a>
</div>
</body>
</html>
