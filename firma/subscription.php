<?php
require_once __DIR__.'/../app/bootstrap.php';
if(!isFirma()){ header('Location: /login.php'); exit; }
$firmId = $_SESSION['user']['firm_id'];
$stmt = $pdo->prepare('SELECT fs.*, s.service_name, s.price FROM firm_subscriptions fs JOIN services s ON s.id=fs.service_id WHERE fs.firm_id=?');
$stmt->execute([$firmId]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
$kalan = $data ? (new DateTime())->diff(new DateTime($data['end_date']))->days : null;
include __DIR__.'/../partials/header.php';
?>
<h1>Abonelik</h1>
<?php if($data): ?>
<p>Plan: <?=htmlspecialchars($data['plan'])?> | Bitiş: <?=htmlspecialchars($data['end_date'])?> | Kalan Gün: <?=$kalan?></p>
<p>Oto Yenile: <?=$data['auto_renew'] ? 'Evet' : 'Hayır'?></p>
<?php if($kalan !== null && $kalan <= 7): ?><div class="alert alert-warning">Aboneliğiniz bitmek üzere</div><?php endif; ?>
<?php else: ?><p>Abonelik bulunamadı</p><?php endif; ?>
<?php include __DIR__.'/../partials/footer.php'; ?>
