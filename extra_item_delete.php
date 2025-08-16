<?php
require_once __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/helpers/audit.php';

if($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location: extra_items.php'); exit; }
verify_csrf();
if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$id=(int)($_POST['id'] ?? 0);
$stmt=$pdo->prepare("SELECT * FROM extra_items WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$row=$stmt->fetch();
if(!$row){ header('Location: extra_items.php'); exit; }
$firmId=(int)$row['firm_id'];
requireFirm($firmId);
$sub=checkSubscription($pdo,$firmId);
if($sub['status']!=='active'){ $_SESSION['flash']['danger']='Bu işlem için aboneliğiniz uygun değil.'; header('Location: extra_items.php'); exit; }
$pdo->prepare("DELETE FROM extra_items WHERE id=?")->execute([$id]);
audit_log($pdo,$firmId,'extra_item',$id,'delete',$row,null);
$_SESSION['flash']['success']='Silindi';
$isAdmin=isYonetici();
header('Location: extra_items.php'.($isAdmin?'?firm_id='.$firmId:''));
exit;
