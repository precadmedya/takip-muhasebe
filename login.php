<?php
require __DIR__.'/app/config/config.php';
require __DIR__.'/app/config/csrf.php';
require __DIR__.'/app/config/auth.php';
require __DIR__.'/app/config/rbac.php';
if(isset($_SESSION['user'])) {
    header('Location: /dashboard.php');
    exit;
}
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $result = login($pdo, $email, $password);
    if($result === true) {
        if(isYonetici()) {
            header('Location: /admin/dashboard.php');
        } else {
            header('Location: /firma/dashboard.php');
        }
        exit;
    } else {
        $_SESSION['flash']['danger'] = $result;
    }
}
include __DIR__.'/partials/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-4">
    <form method="post" class="card p-4">
      <h3 class="mb-3">Giriş Yap</h3>
      <?php echo csrf_field(); ?>
      <div class="mb-3">
        <label class="form-label">E-posta</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Parola</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Giriş</button>
    </form>
  </div>
</div>
<?php include __DIR__.'/partials/footer.php'; ?>
