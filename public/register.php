<?php
// Registration script
$pdo = require __DIR__ . '/../app/config/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $period = $_POST['period'] ?? 'ay';
    // accept only allowed periods
    if (!in_array($period, ['ay','yil'], true)) {
        $period = 'ay';
    }

    if ($name && $email && $password && $confirm) {
        if ($password !== $confirm) {
            $error = 'Şifreler eşleşmiyor';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $error = 'Bu e-posta zaten kayıtlı';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
                $stmt->execute(['name' => $name, 'email' => $email, 'password' => $hash, 'role' => 'firma']);
                $userId = $pdo->lastInsertId();
                $start = date('Y-m-d');
                $end = date('Y-m-d', strtotime($period === 'yil' ? '+1 year' : '+1 month'));
                $sub = $pdo->prepare('INSERT INTO subscriptions (user_id, service_id, start_date, end_date, status) VALUES (:uid, :sid, :start, :end, :status)');
                $sub->execute(['uid' => $userId, 'sid' => 1, 'start' => $start, 'end' => $end, 'status' => 'active']);
                $pdo->commit();
                header('Location: /login.php');
                exit;
            }
        }
    } else {
        $error = 'Lütfen tüm alanları doldurun';
    }
}

include __DIR__ . '/../app/includes/header.php';
include __DIR__ . '/../app/includes/menu.php';
?>
<div class="container mt-4">
<h2>Kayıt Ol</h2>
<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="post" action="">
  <div class="mb-3">
    <label for="name" class="form-label">Adınız</label>
    <input type="text" class="form-control" name="name" id="name" required>
  </div>
  <div class="mb-3">
    <label for="email" class="form-label">E-posta</label>
    <input type="email" class="form-control" name="email" id="email" required>
  </div>
  <div class="mb-3">
    <label for="password" class="form-label">Şifre</label>
    <input type="password" class="form-control" name="password" id="password" required>
  </div>
  <div class="mb-3">
    <label for="confirm" class="form-label">Şifre Tekrar</label>
    <input type="password" class="form-control" name="confirm" id="confirm" required>
  </div>
  <div class="mb-3">
    <label for="period" class="form-label">Kiralama Tipi</label>
    <select name="period" id="period" class="form-select">
      <option value="ay">Aylık</option>
      <option value="yil">Yıllık</option>
    </select>
  </div>
  <button type="submit" class="btn btn-success w-100">Kayıt Ol</button>
</form>
<p class="mt-3 text-center"><a href="/login.php">Giriş Yap</a></p>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
