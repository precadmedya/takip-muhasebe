<?php
require __DIR__.'/app/config/config.php';
require __DIR__.'/app/config/csrf.php';
require __DIR__.'/app/config/auth.php';
require __DIR__.'/app/config/rbac.php';
require __DIR__.'/app/config/tenant_middleware.php';
require __DIR__.'/app/config/subscription_guard.php';
require __DIR__.'/app/helpers/audit.php';

if($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location: payments.php'); exit; }
verify_csrf();
if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$id=(int)($_POST['id'] ?? 0);
$stmt=$pdo->prepare("SELECT * FROM payments WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$row=$stmt->fetch();
if(!$row){ header('Location: payments.php'); exit; }
$firmId=(int)$row['firm_id'];
requireFirm($firmId);
$sub=checkSubscription($pdo,$firmId);
if($sub['status']!=='active'){ $_SESSION['flash']['danger']='Bu işlem için aboneliğiniz uygun değil.'; header('Location: payments.php'); exit; }
$pdo->prepare("DELETE FROM payments WHERE id=?")->execute([$id]);
audit_log($pdo,$firmId,'payment',$id,'delete',$row,null);
$_SESSION['flash']['success']='Silindi';
$isAdmin=isYonetici();
header('Location: payments.php'.($isAdmin?'?firm_id='.$firmId:''));
exit;
