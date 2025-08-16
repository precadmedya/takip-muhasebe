<?php
require_once __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/helpers/audit.php';
require __DIR__.'/app/helpers/cache.php';

try {
    if (!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
    $isAdmin = isYonetici();
    if ($isAdmin) {
        $firms = $pdo->query("SELECT id,name FROM firms WHERE status='active' ORDER BY name")->fetchAll();
        $firmId = (int)($_GET['firm_id'] ?? ($firms[0]['id'] ?? 0));
        $valid = array_column($firms,'id');
        if (!in_array($firmId,$valid)) { $firmId = $firms ? (int)$firms[0]['id'] : 0; }
    } else { $firms=[]; $firmId=(int)$_SESSION['user']['firm_id']; }
    requireFirm($firmId);
    $sub = checkSubscription($pdo,$firmId);
    if ($sub['status']!=='active') { $_SESSION['flash']['danger']='Bu işlem için aboneliğiniz uygun değil.'; header('Location: payments.php'.($isAdmin?'?firm_id='.$firmId:'')); exit; }

    $customers=$pdo->prepare("SELECT id,full_name FROM customers WHERE firm_id=? ORDER BY full_name"); $customers->execute([$firmId]); $customers=$customers->fetchAll();
    $products=$pdo->prepare("SELECT id,name FROM products WHERE firm_id=? AND is_active=1 ORDER BY name"); $products->execute([$firmId]); $products=$products->fetchAll();
    $services=$pdo->prepare("SELECT id,service_name FROM services WHERE firm_id=? ORDER BY service_name"); $services->execute([$firmId]); $services=$services->fetchAll();
    $extras=$pdo->prepare("SELECT id,title FROM extra_items WHERE firm_id=? ORDER BY title"); $extras->execute([$firmId]); $extras=$extras->fetchAll();

    $rates = cache_get('latest_rates',300);
    if(!$rates){ $rates=$pdo->query("SELECT * FROM exchange_rates ORDER BY fetched_at DESC LIMIT 1")->fetch(); if($rates) cache_set('latest_rates',$rates); }

    if($_SERVER['REQUEST_METHOD']==='POST'){
        verify_csrf();
        $customer_id = (int)($_POST['customer_id'] ?? 0) ?: null;
        $product_id = (int)($_POST['product_id'] ?? 0) ?: null;
        $service_id = (int)($_POST['service_id'] ?? 0) ?: null;
        $extra_id = (int)($_POST['extra_item_id'] ?? 0) ?: null;
        $currency = $_POST['currency'] ?? 'TRY';
        $amount = $_POST['amount'] ?? '';
        $date = $_POST['payment_date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '');
        $errors=[];
        $count = ($customer_id?1:0)+($product_id?1:0)+($service_id?1:0)+($extra_id?1:0);
        if($count!=1) $errors[]='Bir ürün/hizmet seçiniz';
        if(!in_array($currency,['TRY','USD','EUR','GBP'])) $errors[]='Para birimi geçersiz';
        if($amount==='' || !is_numeric($amount) || $amount<=0) $errors[]='Tutar geçersiz';
        $dt=DateTime::createFromFormat('Y-m-d',$date); if(!$dt) $errors[]='Tarih geçersiz';
        $rateVal=1;
        if($currency!='TRY'){
            $rateKey=strtolower($currency);
            $rateVal=$rates[$rateKey] ?? 0;
            if($rateVal<=0) $errors[]='Kur tablosu boş, lütfen “Kur Güncelle” çalıştırın.';
        }
        if(!$errors){
            $amount_try = $currency=='TRY' ? (float)$amount : (float)$amount / $rateVal;
            $pdo->prepare("INSERT INTO payments (firm_id,customer_id,product_id,service_id,extra_item_id,currency,amount,amount_try,payment_date,notes) VALUES (?,?,?,?,?,?,?,?,?,?)")
                ->execute([$firmId,$customer_id,$product_id,$service_id,$extra_id,$currency,(float)$amount,$amount_try,$date,$notes?:null]);
            $pid=(int)$pdo->lastInsertId();
            audit_log($pdo,$firmId,'payment',$pid,'create',null,[
                'customer_id'=>$customer_id,'product_id'=>$product_id,'service_id'=>$service_id,'extra_item_id'=>$extra_id,
                'currency'=>$currency,'amount'=>(float)$amount,'amount_try'=>$amount_try,'payment_date'=>$date,'notes'=>$notes
            ]);
            $_SESSION['flash']['success']='Ödeme eklendi';
            header('Location: payments.php'.($isAdmin?'?firm_id='.$firmId:'')); exit;
        } else {
            $_SESSION['flash']['danger']=implode(' | ',$errors);
        }
    }

    include __DIR__.'/partials/header.php';
?>
<h3 class="mb-3">Ödeme Ekle</h3>
<form method="post">
    <?=csrf_field()?>
    <?php if($isAdmin): ?>
    <div class="mb-3">
        <label class="form-label">Firma</label>
        <select name="firm_id" class="form-select" onchange="location.href='payment_add.php?firm_id='+this.value;">
            <?php foreach($firms as $f): ?>
                <option value="<?=$f['id']?>" <?php if($f['id']==$firmId) echo 'selected'; ?>><?=htmlspecialchars($f['name'])?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="mb-3">
        <label class="form-label">Müşteri</label>
        <select name="customer_id" class="form-select">
            <option value="">Seçiniz</option>
            <?php foreach($customers as $c): ?>
                <option value="<?=$c['id']?>"><?=htmlspecialchars($c['full_name'])?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Ürün</label>
        <select name="product_id" class="form-select">
            <option value="">Seçiniz</option>
            <?php foreach($products as $p): ?>
                <option value="<?=$p['id']?>"><?=htmlspecialchars($p['name'])?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Hizmet</label>
        <select name="service_id" class="form-select">
            <option value="">Seçiniz</option>
            <?php foreach($services as $s): ?>
                <option value="<?=$s['id']?>"><?=htmlspecialchars($s['service_name'])?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Ek Kalem</label>
        <select name="extra_item_id" class="form-select">
            <option value="">Seçiniz</option>
            <?php foreach($extras as $e): ?>
                <option value="<?=$e['id']?>"><?=htmlspecialchars($e['title'])?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Para Birimi</label>
            <select name="currency" class="form-select">
                <?php foreach(['TRY','USD','EUR','GBP'] as $cur): ?>
                    <option value="<?=$cur?>"><?=$cur?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Tutar</label>
            <input type="number" step="0.01" name="amount" class="form-control" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Tarih</label>
            <input type="date" name="payment_date" value="<?=date('Y-m-d')?>" class="form-control" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Notlar</label>
        <textarea name="notes" class="form-control"></textarea>
    </div>
    <button class="btn btn-primary" type="submit">Kaydet</button>
    <a class="btn btn-secondary" href="payments.php<?= $isAdmin?'?firm_id='.$firmId:'' ?>">İptal</a>
</form>
<?php include __DIR__.'/partials/footer.php'; ?>
<?php
} catch (Throwable $e) {
    $code='EX'.substr(sha1($e->getMessage().$e->getFile().$e->getLine()),0,8);
    error_log('[PAYMENT_ADD] '.$code.' '.$e->getMessage().' @'.$e->getFile().':'.$e->getLine());
    if(defined('APP_DEBUG') && APP_DEBUG){ echo '<pre>'.htmlspecialchars((string)$e).'</pre>'; }
    include __DIR__.'/errors/500.php';
    exit;
}
