<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit;
}
include __DIR__.'/partials/header.php';
?>
<div class="text-center py-5">
    <h1 class="mb-4">Takip / Muhasebe Sistemi</h1>
    <p class="mb-4">Hoş geldiniz. Lütfen giriş yapın veya kayıt olun.</p>
    <a class="btn btn-primary me-2" href="/login.php">Giriş</a>
    <a class="btn btn-secondary" href="/register.php">Kayıt Ol</a>
</div>
<?php include __DIR__.'/partials/footer.php'; ?>
