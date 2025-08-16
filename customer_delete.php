<?php
require __DIR__.'/app/config/config.php';
require __DIR__.'/app/config/csrf.php';
require __DIR__.'/app/config/auth.php';
require __DIR__.'/app/config/rbac.php';
require __DIR__.'/app/config/tenant_middleware.php';
require __DIR__.'/app/config/subscription_guard.php';
require __DIR__.'/app/helpers/audit.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: customers.php'); exit; }
if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id=?");
$stmt->execute([$id]);
$customer = $stmt->fetch();
if(!$customer) { header('Location: customers.php'); exit; }
requireFirm((int)$customer['firm_id']);
$firmId = (int)$customer['firm_id'];
$sub = checkSubscription($pdo,$firmId);
if($sub['status']!=='active') { $_SESSION['flash']['danger']='Bu işlem için aboneliğiniz uygun değil.'; header('Location: customers.php'); exit; }
$pdo->prepare("DELETE FROM customers WHERE id=?")->execute([$id]);
audit_log($pdo,$firmId,'customer',$id,'delete',$customer,null);
$_SESSION['flash']['success']='Müşteri silindi';
$isAdmin = isYonetici();
header('Location: customers.php'.($isAdmin?'?firm_id='.$firmId:''));
exit;
