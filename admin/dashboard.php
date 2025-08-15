<?php
require __DIR__.'/../app/config/config.php';
require __DIR__.'/../app/config/auth.php';
require __DIR__.'/../app/config/rbac.php';
if(!isYonetici()) { header('Location: /login.php'); exit; }
$active = $pdo->query("SELECT COUNT(*) FROM firms WHERE status='active'")->fetchColumn();
$renew = $pdo->query("SELECT COUNT(*) FROM firm_subscriptions WHERE end_date = CURDATE()")->fetchColumn();
$suspended = $pdo->query("SELECT COUNT(*) FROM firms WHERE status='suspended'")->fetchColumn();
include __DIR__.'/../partials/header.php';
?>
<div class="row g-4">
  <div class="col-md-4">
    <div class="card text-center p-3">
      <h5>Aktif Firmalar</h5>
      <p class="display-6"><?php echo $active; ?></p>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-center p-3">
      <h5>Bugün Yenilenecek</h5>
      <p class="display-6"><?php echo $renew; ?></p>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-center p-3">
      <h5>Askıdaki Firmalar</h5>
      <p class="display-6"><?php echo $suspended; ?></p>
    </div>
  </div>
</div>
<?php include __DIR__.'/../partials/footer.php'; ?>
