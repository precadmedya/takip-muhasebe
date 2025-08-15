<?php
session_start();
if(!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}
if($_SESSION['user']['role'] === 'yonetici') {
    header('Location: /admin/dashboard.php');
} else {
    header('Location: /firma/dashboard.php');
}
exit;
