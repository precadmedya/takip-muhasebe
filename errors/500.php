<?php
$code = $errorCode ?? ($code ?? '500');
http_response_code(500);
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>Hata</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6 text-center">
      <h1 class="display-4 mb-3">Üzgünüz</h1>
      <p class="lead">Bir şeyler ters gitti.</p>
      <p>Olay kodu: <?=htmlspecialchars($code)?></p>
      <?php if (defined('APP_DEBUG') && APP_DEBUG && isset($e)) : ?>
        <pre class="text-start bg-white border rounded p-3 small"><?=htmlspecialchars($e->getMessage())?>
<?=htmlspecialchars($e->getTraceAsString())?></pre>
      <?php endif; ?>
      <a href="/" class="btn btn-primary mt-3">Ana Sayfa</a>
    </div>
  </div>
</div>
</body>
</html>
