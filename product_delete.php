<?php
require_once __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/helpers/audit.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: products.php'); exit; }
if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if(!$product) { header('Location: products.php'); exit; }
requireFirm((int)$product['firm_id']);
$firmId = (int)$product['firm_id'];
$sub = checkSubscription($pdo,$firmId);
if($sub['status']!=='active') { $_SESSION['flash']['danger']='Bu işlem için aboneliğiniz uygun değil.'; header('Location: products.php'); exit; }
$pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
audit_log($pdo,$firmId,'product',$id,'delete',$product,null);
$_SESSION['flash']['success']='Ürün silindi';
$isAdmin = isYonetici();
header('Location: products.php'.($isAdmin?'?firm_id='.$firmId:''));
exit;
