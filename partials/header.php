<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    <div class="container">
        <a class="navbar-brand" href="/">Takip Muhasebe</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="/dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/customers.php">Müşteriler</a></li>
                    <li class="nav-item"><a class="nav-link" href="/products.php">Ürünler</a></li>
                    <li class="nav-item"><a class="nav-link" href="/services.php">Hizmetler</a></li>
                    <li class="nav-item"><a class="nav-link" href="/extra_items.php">Ek Kalemler</a></li>
                    <li class="nav-item"><a class="nav-link" href="/payments.php">Ödemeler</a></li>
                    <li class="nav-item"><a class="nav-link" href="/kur_update.php">Kurlar</a></li>
                    <li class="nav-item"><a class="nav-link" href="/logout.php">Çıkış</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/login.php">Giriş</a></li>
                    <li class="nav-item"><a class="nav-link" href="/register.php">Kayıt Ol</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container">
<?php include __DIR__.'/alerts.php'; ?>
