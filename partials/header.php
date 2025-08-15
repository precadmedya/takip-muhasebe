<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Takip Muhasebe</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/assets/css/theme.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="/"><img src="/assets/img/logo.png" alt="Logo"></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if(isset($_SESSION['user'])): ?>
        <li class="nav-item me-3"><span class="nav-link">
          <?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>
          <span class="badge bg-secondary"><?php echo $_SESSION['user']['role']; ?></span>
          <?php if($_SESSION['user']['role']==='firma'): ?>
            <span class="badge bg-info text-dark"><?php echo htmlspecialchars($_SESSION['user']['firm_name']); ?></span>
          <?php endif; ?>
        </span></li>
        <li class="nav-item"><a class="nav-link" href="/logout.php">Çıkış</a></li>
        <?php else: ?>
        <li class="nav-item"><a class="nav-link" href="/login.php">Giriş</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container">
<?php include __DIR__.'/alerts.php'; ?>
