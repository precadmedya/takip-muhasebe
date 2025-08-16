<?php
require __DIR__.'/app/config/config.php';
require __DIR__.'/app/config/csrf.php';
session_start();
if(isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit;
}
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';
    if($password !== $confirm) {
        $_SESSION['flash']['danger'] = 'Parolalar eşleşmiyor';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if($stmt->fetch()) {
            $_SESSION['flash']['danger'] = 'Bu e-posta zaten kayıtlı';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name,email,password_hash,role) VALUES (?,?,?,?)");
            $stmt->execute([$name,$email,$hash,'firma']);
            $user_id = $pdo->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = 'firma';
            $_SESSION['name'] = $name;
            $_SESSION['user'] = [
                'id' => $user_id,
                'role' => 'firma',
                'full_name' => $name,
                'firm_id' => null,
                'firm_name' => null
            ];
            header('Location: /dashboard.php');
            exit;
        }
    }
}
include __DIR__.'/partials/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-4">
        <form method="post" class="card p-4">
            <h3 class="mb-3">Kayıt Ol</h3>
            <?php echo csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label">Ad Soyad</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">E-posta</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Parola</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Parola (Tekrar)</label>
                <input type="password" name="password_confirm" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Kayıt Ol</button>
        </form>
    </div>
</div>
<?php include __DIR__.'/partials/footer.php'; ?>
