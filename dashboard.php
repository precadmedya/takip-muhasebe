<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
include __DIR__.'/partials/header.php';
?>
<h1 class="mb-4">Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
<p>Rolünüz: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
<?php if($_SESSION['role'] === 'admin'): ?>
    <a href="#" class="btn btn-primary">Servisleri Yönet</a>
<?php else: ?>
    <h5>Abonelikleriniz</h5>
    <div class="alert alert-info">Henüz abonelik bulunmuyor.</div>
<?php endif; ?>
<?php include __DIR__.'/partials/footer.php'; ?>
