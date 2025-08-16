<?php
require_once __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/helpers/audit.php';

try {
    if (!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
    $isAdmin = isYonetici();
    if ($isAdmin) {
        $firms = $pdo->query("SELECT id,name FROM firms WHERE status='active' ORDER BY name")->fetchAll();
        $firmId = (int)($_GET['firm_id'] ?? ($firms[0]['id'] ?? 0));
        $valid = array_column($firms,'id');
        if (!in_array($firmId,$valid)) { $firmId = $firms ? (int)$firms[0]['id'] : 0; }
    } else {
        $firms = [];
        $firmId = (int)$_SESSION['user']['firm_id'];
    }
    requireFirm($firmId);
    $sub = checkSubscription($pdo,$firmId);
    if ($sub['status'] !== 'active') { $_SESSION['flash']['danger']='Bu işlem için aboneliğiniz uygun değil.'; header('Location: services.php'.($isAdmin?'?firm_id='.$firmId:'')); exit; }

    if ($_SERVER['REQUEST_METHOD']==='POST') {
        verify_csrf();
        $name = trim($_POST['service_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $price = $_POST['price'] ?? '';
        $period = $_POST['period'] ?? 'ay';
        $errors=[];
        if ($name==='') $errors[]='Ad gerekli';
        if ($price==='' || !is_numeric($price) || $price<0) $errors[]='Fiyat geçersiz';
        if (!in_array($period,['ay','yil'])) $errors[]='Dönem geçersiz';
        if (!$errors) {
            $pdo->prepare("INSERT INTO services (firm_id,service_name,description,price,period) VALUES (?,?,?,?,?)")
                ->execute([$firmId,$name,$desc?:null,(float)$price,$period]);
            $sid=(int)$pdo->lastInsertId();
            audit_log($pdo,$firmId,'service',$sid,'create',null,['service_name'=>$name,'description'=>$desc,'price'=>(float)$price,'period'=>$period]);
            $_SESSION['flash']['success']='Hizmet eklendi';
            header('Location: services.php'.($isAdmin?'?firm_id='.$firmId:'')); exit;
        } else {
            $_SESSION['flash']['danger']=implode(' | ',$errors);
        }
    }
    include __DIR__.'/partials/header.php';
?>
<h3 class="mb-3">Hizmet Ekle</h3>
<form method="post">
    <?=csrf_field()?>
    <?php if($isAdmin): ?>
    <div class="mb-3">
        <label class="form-label">Firma</label>
        <select name="firm_id" class="form-select" onchange="location.href='service_add.php?firm_id='+this.value;">
            <?php foreach($firms as $f): ?>
                <option value="<?=$f['id']?>" <?php if($f['id']==$firmId) echo 'selected'; ?>><?=htmlspecialchars($f['name'])?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="mb-3">
        <label class="form-label">Ad</label>
        <input type="text" name="service_name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Açıklama</label>
        <textarea name="description" class="form-control"></textarea>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Fiyat</label>
            <input type="number" step="0.01" name="price" class="form-control" value="0" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Dönem</label>
            <select name="period" class="form-select">
                <option value="ay">Ay</option>
                <option value="yil">Yıl</option>
            </select>
        </div>
    </div>
    <button class="btn btn-primary" type="submit">Kaydet</button>
    <a class="btn btn-secondary" href="services.php<?= $isAdmin?'?firm_id='.$firmId:'' ?>">İptal</a>
</form>
<?php include __DIR__.'/partials/footer.php'; ?>
<?php
} catch (Throwable $e) {
    $code='EX'.substr(sha1($e->getMessage().$e->getFile().$e->getLine()),0,8);
    error_log('[SERVICE_ADD] '.$code.' '.$e->getMessage().' @'.$e->getFile().':'.$e->getLine());
    if(defined('APP_DEBUG') && APP_DEBUG){ echo '<pre>'.htmlspecialchars((string)$e).'</pre>'; }
    include __DIR__.'/errors/500.php';
    exit;
}
