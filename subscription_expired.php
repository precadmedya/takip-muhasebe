<?php
require_once __DIR__.'/app/bootstrap.php';
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='firma') { header('Location: /login.php'); exit; }
$code = $_GET['code'] ?? '';
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>Abonelik Süresi Doldu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
<div class="container text-center">
<h1 class="display-6 mb-3">Aboneliğiniz sona erdi</h1>
<p class="lead">Lütfen aboneliğinizi yenileyin.</p>
<?php if($code): ?><p>Kod: <?=htmlspecialchars($code)?></p><?php endif; ?>
<a href="/firma/subscription.php" class="btn btn-primary">Yenile</a>
<a href="/logout.php" class="btn btn-link">Çıkış</a>
</div>
</body>
</html>
