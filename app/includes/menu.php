<?php $loggedIn = isset($_SESSION['user_id']); ?>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
  <div class="container">
    <a class="navbar-brand" href="/dashboard.php">Takip Muhasebe</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php if($loggedIn): ?>
          <li class="nav-item"><a class="nav-link" href="/dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="/logout.php">Çıkış</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="/login.php">Giriş</a></li>
          <li class="nav-item"><a class="nav-link" href="/register.php">Kayıt Ol</a></li>
        <?php endif; ?>
        <li class="nav-item"><button class="btn btn-sm btn-outline-secondary ms-3" id="themeToggle" type="button">Tema</button></li>
      </ul>
    </div>
  </div>
</nav>
