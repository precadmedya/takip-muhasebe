<?php
require_once __DIR__.'/../app/bootstrap.php';
if(!isFirma()) { header('Location: /login.php'); exit; }
$sub = checkSubscription($pdo, $_SESSION['user']['firm_id']);
include __DIR__.'/../partials/header.php';
if($sub['status'] !== 'expired' && $sub['days_left'] <= 7) {
    echo '<div class="alert alert-warning">Aboneliğiniz bitmek üzere...</div>';
}
?>
<div class="mb-3">
  <span class="badge bg-success">Kalan: <?php echo $sub['days_left']; ?> gün</span>
</div>
<div class="card p-4">
  <h5>Yaklaşan Hizmetler</h5>
  <p>Henüz bir veri yok.</p>
</div>
<?php include __DIR__.'/../partials/footer.php'; ?>
