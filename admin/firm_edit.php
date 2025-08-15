<?php
session_start(); // ensure session available
$pdo = require __DIR__ . '/../app/config/config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /login.php');
    exit;
}
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: firms.php');
    exit;
}
$stmt = $pdo->prepare("SELECT u.id,u.name,u.email,s.end_date FROM users u JOIN subscriptions s ON s.user_id=u.id WHERE u.id=:id");
$stmt->execute(['id'=>$id]);
$firm = $stmt->fetch();
if (!$firm) {
    header('Location: firms.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name = trim($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $end = $_POST['end_date'];
    if ($name && $email && $end) {
        $upd = $pdo->prepare("UPDATE users SET name=:n,email=:e WHERE id=:id");
        $upd->execute(['n'=>$name,'e'=>$email,'id'=>$id]);
        $upd2 = $pdo->prepare("UPDATE subscriptions SET end_date=:end WHERE user_id=:id");
        $upd2->execute(['end'=>$end,'id'=>$id]);
        header('Location: firms.php');
        exit;
    }
}
include __DIR__ . '/../app/includes/header.php';
include __DIR__ . '/../app/includes/sidebar.php';
?>
<div class="container mt-4">
<h2>Firma Düzenle</h2>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Ad</label>
    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($firm['name']) ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">E-posta</label>
    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($firm['email']) ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Abonelik Bitiş</label>
    <input type="date" name="end_date" class="form-control" value="<?= $firm['end_date'] ?>" required>
  </div>
  <button class="btn btn-danger" type="submit">Kaydet</button>
</form>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
