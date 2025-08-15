<?php
if(session_status() === PHP_SESSION_NONE) session_start();
function csrf_token(): string {
    if(empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="'.csrf_token().'">';
}
function verify_csrf(): void {
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if(!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            die('CSRF doğrulaması başarısız');
        }
    }
}
