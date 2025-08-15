<?php
// Dashboard - require login
$pdo = require __DIR__ . '/../app/config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

include __DIR__ . '/../app/includes/header.php';
?>
<h2>Dashboard</h2>
<p>Hoş geldiniz, <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>!</p>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
