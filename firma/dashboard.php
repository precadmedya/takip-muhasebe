<?php
session_start(); // start session for role check
$pdo = require __DIR__ . '/../app/config/config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'firma') {
    header('Location: /login.php');
    exit;
}
$pdo->prepare("UPDATE subscriptions SET status='expired' WHERE end_date < CURDATE() AND status='active' AND user_id=:uid")->execute(['uid'=>$_SESSION['user_id']]);
$stmt = $pdo->prepare("SELECT s.end_date, DATEDIFF(s.end_date, CURDATE()) AS kalan, sv.service_name FROM subscriptions s JOIN services sv ON sv.id=s.service_id WHERE s.user_id=:uid AND s.status='active'");
$stmt->execute(['uid'=>$_SESSION['user_id']]);
$subs = $stmt->fetchAll();
include __DIR__ . '/../app/includes/header.php';
include __DIR__ . '/../app/includes/sidebar.php';
?>
<div class="container mt-4">
<h2>Firma Dashboard</h2>
<div class="row">
<?php foreach($subs as $s): ?>
  <div class="col-md-4">
    <div class="card text-bg-primary mb-3">
      <div class="card-body">
        <h5 class="card-title"><?= htmlspecialchars($s['service_name']) ?></h5>
        <p class="card-text">Kalan Gün: <?= $s['kalan'] ?></p>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
