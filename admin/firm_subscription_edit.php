<?php
require_once __DIR__.'/../app/bootstrap.php';
if(!isYonetici()){ header('Location: /login.php'); exit; }
$firmId = (int)($_GET['firm_id'] ?? 0);
$stmt = $pdo->prepare('SELECT f.name, fs.* FROM firms f LEFT JOIN firm_subscriptions fs ON fs.firm_id=f.id WHERE f.id=?');
$stmt->execute([$firmId]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$data){ die('Firma bulunamadı'); }
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $plan = $_POST['plan'] ?? 'monthly';
    $start = $_POST['start_date'] ?? date('Y-m-d');
    $end = $_POST['end_date'] ?? date('Y-m-d');
    $auto = isset($_POST['auto_renew']) ? 1 : 0;
    $grace = (int)($_POST['grace_days'] ?? 7);
    $old = $data;
    $pdo->prepare('UPDATE firm_subscriptions SET plan=?,start_date=?,end_date=?,auto_renew=?,grace_days=? WHERE firm_id=?')->execute([$plan,$start,$end,$auto,$grace,$firmId]);
    $stmt->execute([$firmId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    audit_log($pdo,$firmId,'subscription',$firmId,'update',$old,$data);
    header('Location: firm_subscription_edit.php?firm_id='.$firmId.'&saved=1'); exit;
}
$kalan = $data['end_date'] ? (new DateTime())->diff(new DateTime($data['end_date']))->days : null;
include __DIR__.'/../partials/header.php';
?>
<h1>Abonelik Düzenle - <?=htmlspecialchars($data['name'])?></h1>
<?php if(isset($_GET['saved'])): ?><div class="alert alert-success">Kaydedildi</div><?php endif; ?>
<p>Kalan gün: <?=$kalan?></p>
<form method="post" class="row g-3">
    <?=csrf_field()?>
    <div class="col-md-6">
        <label class="form-label">Plan</label>
        <select name="plan" class="form-select">
            <option value="monthly" <?=$data['plan']==='monthly'?'selected':''?>>Aylık</option>
            <option value="yearly" <?=$data['plan']==='yearly'?'selected':''?>>Yıllık</option>
        </select>
    </div>
    <div class="col-md-6"><label class="form-label">Başlangıç</label><input type="date" name="start_date" value="<?=$data['start_date']?>" class="form-control"></div>
    <div class="col-md-6"><label class="form-label">Bitiş</label><input type="date" name="end_date" value="<?=$data['end_date']?>" class="form-control"></div>
    <div class="col-md-6"><label class="form-label">Grace Gün</label><input type="number" name="grace_days" value="<?=$data['grace_days']?>" class="form-control"></div>
    <div class="col-12 form-check"><input class="form-check-input" type="checkbox" name="auto_renew" value="1" <?=$data['auto_renew']?'checked':''?>> <label class="form-check-label">Otomatik Yenile</label></div>
    <div class="col-12"><button class="btn btn-primary" type="submit">Kaydet</button></div>
</form>
<?php include __DIR__.'/../partials/footer.php'; ?>
