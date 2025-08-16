<?php
require_once __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/helpers/audit.php';

try {
    if (!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $service = $stmt->fetch();
    if (!$service) { header('Location: services.php'); exit; }
    $firmId = (int)$service['firm_id'];
    $isAdmin = isYonetici();
    requireFirm($firmId);
    $sub = checkSubscription($pdo,$firmId);

    if ($_SERVER['REQUEST_METHOD']==='POST') {
        verify_csrf();
        if ($sub['status']!=='active') { $_SESSION['flash']['danger']='Bu işlem için aboneliğiniz uygun değil.'; header('Location: services.php'.($isAdmin?'?firm_id='.$firmId:'')); exit; }
        $name = trim($_POST['service_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $price = $_POST['price'] ?? '';
        $period = $_POST['period'] ?? 'ay';
        $errors=[];
        if ($name==='') $errors[]='Ad gerekli';
        if ($price==='' || !is_numeric($price) || $price<0) $errors[]='Fiyat geçersiz';
        if (!in_array($period,['ay','yil'])) $errors[]='Dönem geçersiz';
        if (!$errors) {
            $old = $service;
            $pdo->prepare("UPDATE services SET service_name=?, description=?, price=?, period=? WHERE id=? AND firm_id=?")
                ->execute([$name,$desc?:null,(float)$price,$period,$id,$firmId]);
            audit_log($pdo,$firmId,'service',$id,'update',$old,['service_name'=>$name,'description'=>$desc,'price'=>(float)$price,'period'=>$period]);
            $_SESSION['flash']['success']='Hizmet güncellendi';
            header('Location: services.php'.($isAdmin?'?firm_id='.$firmId:'')); exit;
        } else {
            $_SESSION['flash']['danger']=implode(' | ',$errors);
        }
    }
    include __DIR__.'/partials/header.php';
?>
<h3 class="mb-3">Hizmet Düzenle</h3>
<form method="post">
    <?=csrf_field()?>
    <div class="mb-3">
        <label class="form-label">Ad</label>
        <input type="text" name="service_name" class="form-control" value="<?=htmlspecialchars($service['service_name'])?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Açıklama</label>
        <textarea name="description" class="form-control"><?=htmlspecialchars($service['description'])?></textarea>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Fiyat</label>
            <input type="number" step="0.01" name="price" class="form-control" value="<?=htmlspecialchars($service['price'])?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Dönem</label>
            <select name="period" class="form-select">
                <?php foreach(['ay'=>'Ay','yil'=>'Yıl'] as $k=>$v): ?>
                    <option value="<?=$k?>" <?php if($service['period']==$k) echo 'selected'; ?>><?=$v?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <button class="btn btn-primary" type="submit" <?php if($sub['status']==='grace') echo 'disabled'; ?>>Kaydet</button>
    <a class="btn btn-secondary" href="services.php<?= $isAdmin?'?firm_id='.$firmId:'' ?>">İptal</a>
</form>
<?php include __DIR__.'/partials/footer.php'; ?>
<?php
} catch (Throwable $e) {
    $code='EX'.substr(sha1($e->getMessage().$e->getFile().$e->getLine()),0,8);
    error_log('[SERVICE_EDIT] '.$code.' '.$e->getMessage().' @'.$e->getFile().':'.$e->getLine());
    if(defined('APP_DEBUG') && APP_DEBUG){ echo '<pre>'.htmlspecialchars((string)$e).'</pre>'; }
    include __DIR__.'/errors/500.php';
    exit;
}
