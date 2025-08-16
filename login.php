<?php
require_once __DIR__.'/app/bootstrap.php';
if(isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit;
}
$configError = null;
if(!env('DB_NAME')) {
    $configError = 'Konfigürasyon eksik: DB_NAME';
    error_log($configError);
}
if($_SERVER['REQUEST_METHOD'] === 'POST' && !$configError) {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT u.id, u.full_name, u.password_hash, u.role, u.firm_id, f.name AS firm_name FROM users u LEFT JOIN firms f ON u.firm_id=f.id WHERE u.email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'] === 'yonetici' ? 'admin' : 'firma';
        $_SESSION['name'] = $user['full_name'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'role' => $user['role'],
            'full_name' => $user['full_name'],
            'firm_id' => $user['firm_id'],
            'firm_name' => $user['firm_name']
        ];
        header('Location: /dashboard.php');
        exit;
    } else {
        $_SESSION['flash']['danger'] = 'Geçersiz e-posta veya parola';
    }
}
include __DIR__.'/partials/header.php';
?>
<?php if($configError): ?>
<div class="alert alert-danger"><?=htmlspecialchars($configError)?></div>
<?php endif; ?>
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
