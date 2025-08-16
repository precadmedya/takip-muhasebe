<?php
require_once __DIR__.'/app/bootstrap.php';

if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id=?");
$stmt->execute([$id]);
$customer = $stmt->fetch();
if(!$customer) { die('Müşteri bulunamadı'); }
requireFirm((int)$customer['firm_id']);
$firmId = $customer['firm_id'];
$sub = checkSubscription($pdo,$firmId);
if($sub['status']==='expired') { die('Aboneliğiniz sona ermiş.'); }
include __DIR__.'/partials/header.php';
if($sub['status']==='grace') echo '<div class="alert alert-warning sticky-top">Aboneliğiniz yenilenmek üzere; kritik işlemler devre dışıdır.</div>';
?>
<h3 class="mb-3">Müşteri Detayı</h3>
<div class="card p-3">
    <p><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($customer['full_name']); ?></p>
    <p><strong>E-posta:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
    <p><strong>Telefon:</strong> <?php echo htmlspecialchars($customer['phone']); ?></p>
    <p><strong>Firma:</strong> <?php echo htmlspecialchars($customer['company']); ?></p>
    <p><strong>Vergi No:</strong> <?php echo htmlspecialchars($customer['tax_no']); ?></p>
    <p><strong>Adres:</strong> <?php echo nl2br(htmlspecialchars($customer['address'])); ?></p>
</div>
<a href="customers.php" class="btn btn-secondary mt-3">Geri</a>
<?php include __DIR__.'/partials/footer.php'; ?>
