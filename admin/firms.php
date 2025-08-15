<?php
session_start(); // ensure session started
$pdo = require __DIR__ . '/../app/config/config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /login.php');
    exit;
}
$stmt = $pdo->query("SELECT u.id, u.name, u.email, DATEDIFF(s.end_date, CURDATE()) AS kalan FROM users u JOIN subscriptions s ON s.user_id=u.id WHERE u.role='firma' AND s.status='active'");
$firms = $stmt->fetchAll();
include __DIR__ . '/../app/includes/header.php';
include __DIR__ . '/../app/includes/sidebar.php';
?>
<div class="container mt-4">
<h2>Firmalar</h2>
<table class="table table-striped">
  <thead>
    <tr><th>ID</th><th>İsim</th><th>E-posta</th><th>Kalan Gün</th><th>İşlem</th></tr>
  </thead>
  <tbody>
    <?php foreach($firms as $f): ?>
    <tr>
      <td><?= $f['id'] ?></td>
      <td><?= htmlspecialchars($f['name']) ?></td>
      <td><?= htmlspecialchars($f['email']) ?></td>
      <td><?= $f['kalan'] ?></td>
      <td><a class="btn btn-sm btn-danger" href="firm_edit.php?id=<?= $f['id'] ?>">Düzenle</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
