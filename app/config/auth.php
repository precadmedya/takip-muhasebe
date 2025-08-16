<?php
if(session_status() === PHP_SESSION_NONE) session_start();
function login(PDO $pdo, string $email, string $password) {
    $stmt = $pdo->prepare("SELECT u.*, f.name AS firm_name FROM users u LEFT JOIN firms f ON u.firm_id=f.id WHERE u.email=? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if(!$user || !$user['is_active']) {
        return 'Geçersiz kullanıcı';
    }
    if($user['failed_attempts'] >= 5 && strtotime($user['updated_at']) > time()-900) {
        return 'Hesap geçici olarak kilitli';
    }
    if(!password_verify($password, $user['password_hash'])) {
        $pdo->prepare("UPDATE users SET failed_attempts = failed_attempts + 1, updated_at=NOW() WHERE id=?")->execute([$user['id']]);
        return 'Hatalı giriş';
    }
    $pdo->prepare("UPDATE users SET failed_attempts=0, last_login_at=NOW(), updated_at=NOW() WHERE id=?")->execute([$user['id']]);
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'role' => $user['role'],
        'full_name' => $user['full_name'],
        'firm_id' => $user['firm_id'],
        'firm_name' => $user['firm_name']
    ];
    return true;
}
function logout(): void {
    if(session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION = [];
    session_destroy();
}
