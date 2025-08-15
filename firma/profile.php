<?php
session_start(); // start session for profile access
$pdo = require __DIR__ . '/../app/config/config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'firma') {
    header('Location: /login.php');
    exit;
}
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT name,email FROM users WHERE id=:id');
$stmt->execute(['id'=>$userId]);
$user = $stmt->fetch();
$message = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name = trim($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if ($name && $email) {
        $u = $pdo->prepare('UPDATE users SET name=:n,email=:e WHERE id=:id');
        $u->execute(['n'=>$name,'e'=>$email,'id'=>$userId]);
    }
    if (!empty($_POST['new_password']) && $_POST['new_password'] === $_POST['confirm_password']) {
        $hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $p = $pdo->prepare('UPDATE users SET password=:p WHERE id=:id');
        $p->execute(['p'=>$hash,'id'=>$userId]);
    }
    $message = 'Bilgiler güncellendi';
    $stmt->execute(['id'=>$userId]);
    $user = $stmt->fetch();
}
include __DIR__ . '/../app/includes/header.php';
include __DIR__ . '/../app/includes/sidebar.php';
?>
<div class="container mt-4">
<h2>Profil</h2>
<?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Ad</label>
    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">E-posta</label>
    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Yeni Şifre</label>
    <input type="password" name="new_password" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">Yeni Şifre Tekrar</label>
    <input type="password" name="confirm_password" class="form-control">
  </div>
  <button class="btn btn-primary" type="submit">Güncelle</button>
</form>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
