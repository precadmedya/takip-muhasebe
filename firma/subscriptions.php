<?php
session_start(); // start session for subscription view
$pdo = require __DIR__ . '/../app/config/config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'firma') {
    header('Location: /login.php');
    exit;
}
$stmt = $pdo->prepare('SELECT sv.service_name, s.start_date, s.end_date, s.status FROM subscriptions s JOIN services sv ON sv.id=s.service_id WHERE s.user_id=:uid');
$stmt->execute(['uid'=>$_SESSION['user_id']]);
$subs = $stmt->fetchAll();
include __DIR__ . '/../app/includes/header.php';
include __DIR__ . '/../app/includes/sidebar.php';
?>
<div class="container mt-4">
<h2>Aboneliklerim</h2>
<table class="table table-striped">
  <thead><tr><th>Hizmet</th><th>Başlangıç</th><th>Bitiş</th><th>Durum</th></tr></thead>
  <tbody>
    <?php foreach($subs as $s): ?>
    <tr>
      <td><?= htmlspecialchars($s['service_name']) ?></td>
      <td><?= $s['start_date'] ?></td>
      <td><?= $s['end_date'] ?></td>
      <td><?= $s['status'] ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
