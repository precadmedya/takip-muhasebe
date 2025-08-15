<?php
session_start(); // ensure session is available
$pdo = require __DIR__ . '/../app/config/config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /login.php');
    exit;
}
// Mark expired subscriptions
$pdo->exec("UPDATE subscriptions SET status='expired' WHERE end_date < CURDATE() AND status='active'");
$totalFirms = $pdo->query("SELECT COUNT(*) FROM users WHERE role='firma'")->fetchColumn();
$activeSubs = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status='active'")->fetchColumn();
$upcoming = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status='active' AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
include __DIR__ . '/../app/includes/header.php';
include __DIR__ . '/../app/includes/sidebar.php';
?>
<div class="container mt-4">
<h2>Admin Dashboard</h2>
<div class="row">
  <div class="col-md-4">
    <div class="card text-bg-danger mb-3">
      <div class="card-body">
        <h5 class="card-title">Toplam Firma</h5>
        <p class="card-text"><?= $totalFirms ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-bg-danger mb-3">
      <div class="card-body">
        <h5 class="card-title">Aktif Abonelik</h5>
        <p class="card-text"><?= $activeSubs ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-bg-danger mb-3">
      <div class="card-body">
        <h5 class="card-title">Yaklaşan Bitişler (7g)</h5>
        <p class="card-text"><?= $upcoming ?></p>
      </div>
    </div>
  </div>
</div>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
