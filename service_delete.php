<?php
require_once __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/helpers/audit.php';

try {
    if ($_SERVER['REQUEST_METHOD']!=='POST') { header('Location: services.php'); exit; }
    verify_csrf();
    if (!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) { header('Location: services.php'); exit; }
    $firmId = (int)$row['firm_id'];
    requireFirm($firmId);
    $sub = checkSubscription($pdo,$firmId);
    if ($sub['status']!=='active') { $_SESSION['flash']['danger']='Bu işlem için aboneliğiniz uygun değil.'; header('Location: services.php'); exit; }
    $pdo->prepare("DELETE FROM services WHERE id=? AND firm_id=?")->execute([$id,$firmId]);
    audit_log($pdo,$firmId,'service',$id,'delete',$row,null);
    $_SESSION['flash']['success']='Silindi';
    $isAdmin=isYonetici();
    header('Location: services.php'.($isAdmin?'?firm_id='.$firmId:''));
    exit;
} catch (Throwable $e) {
    $code='EX'.substr(sha1($e->getMessage().$e->getFile().$e->getLine()),0,8);
    error_log('[SERVICE_DELETE] '.$code.' '.$e->getMessage().' @'.$e->getFile().':'.$e->getLine());
    if(defined('APP_DEBUG') && APP_DEBUG){ echo '<pre>'.htmlspecialchars((string)$e).'</pre>'; }
    include __DIR__.'/errors/500.php';
    exit;
}
