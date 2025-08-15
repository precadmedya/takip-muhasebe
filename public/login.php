<?php
// Login script
session_start(); // start session before accessing $_SESSION
$pdo = require __DIR__ . '/../app/config/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare('SELECT id, name, password, role FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            header('Location: /dashboard.php');
            exit;
        } else {
            $error = 'Geçersiz e-posta veya şifre';
        }
    } else {
        $error = 'Lütfen tüm alanları doldurun';
    }
}

include __DIR__ . '/../app/includes/header.php';
include __DIR__ . '/../app/includes/menu.php';
?>
<div class="container mt-4">
<h2>Giriş</h2>
<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="post" action="">
  <div class="mb-3">
    <label for="email" class="form-label">E-posta</label>
    <input type="email" class="form-control" name="email" id="email" required>
  </div>
  <div class="mb-3">
    <label for="password" class="form-label">Şifre</label>
    <input type="password" class="form-control" name="password" id="password" required>
  </div>
  <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
</form>
<p class="mt-3 text-center"><a href="/register.php">Kayıt Ol</a></p>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
