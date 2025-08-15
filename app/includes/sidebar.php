<?php
// Responsive sidebar menu for logged in users
$role = $_SESSION['role'] ?? '';
if (!$role) {
    return; // no sidebar if role not set
}
$themeClass = $role === 'admin' ? 'sidebar-admin' : 'sidebar-firma';
$navBg = $role === 'admin' ? 'bg-danger' : 'bg-primary';
?>
<nav class="navbar navbar-expand <?php echo $navBg; ?> navbar-dark">
  <div class="container-fluid">
    <button class="btn btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">☰</button>
    <a class="navbar-brand" href="#">Takip Muhasebe</a>
    <a class="btn btn-outline-light" href="/logout.php">Çıkış</a>
  </div>
</nav>
<div class="offcanvas offcanvas-start <?php echo $themeClass; ?> text-white" tabindex="-1" id="sidebar">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Menü</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <ul class="nav flex-column">
      <?php if ($role === 'admin'): ?>
        <li class="nav-item"><a class="nav-link text-white" href="/admin/dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="/admin/firms.php">Firmalar</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="/admin/services.php">Hizmetler</a></li>
      <?php else: ?>
        <li class="nav-item"><a class="nav-link text-white" href="/firma/dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="/firma/subscriptions.php">Aboneliklerim</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="/firma/profile.php">Profil</a></li>
      <?php endif; ?>
    </ul>
  </div>
</div>
