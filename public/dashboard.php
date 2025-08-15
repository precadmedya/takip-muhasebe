<?php
// Dashboard - require login
session_start(); // access session data
$pdo = require __DIR__ . '/../app/config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if ($_SESSION['role'] === 'admin') {
    header('Location: /admin/dashboard.php');
    exit;
}

if ($_SESSION['role'] === 'firma') {
    header('Location: /firma/dashboard.php');
    exit;
}

header('Location: /login.php');
exit;
