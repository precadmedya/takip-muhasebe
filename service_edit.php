<?php
require __DIR__.'/app/config/config.php';
require __DIR__.'/app/config/csrf.php';
require __DIR__.'/app/config/auth.php';
require __DIR__.'/app/config/rbac.php';
require __DIR__.'/app/config/tenant_middleware.php';
require __DIR__.'/app/config/subscription_guard.php';
require __DIR__.'/app/helpers/audit.php';

if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$id = (int)($_GET['id'] ?? 0);
$stmt=$pdo->prepare("SELECT * FROM services WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$service=$stmt->fetch();
if(!$service) { die('Kayıt bulunamadı'); }
$firmId=(int)$service['firm_id'];
$isAdmin=isYonetici();
if($isAdmin) {
    $firms=$pdo->query("SELECT id,name FROM firms WHERE status='active' ORDER BY name")->fetchAll();
} else {
    $firms=[];
}
requireFirm($firmId);
$sub=checkSubscription($pdo,$firmId);
if($sub['status']!=='active') { $_SESSION['flash']['danger']='Bu işlem için aboneliğiniz uygun değil.'; header('Location: services.php'.($isAdmin?'?firm_id='.$firmId:'')); exit; }

if($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = (float)($_POST['unit_price'] ?? 0);
    $vat = (float)($_POST['vat_rate'] ?? 20);
    $active = isset($_POST['is_active']) ? 1 : 0;
    if($name==='') {
        $_SESSION['flash']['danger']='Ad gerekli';
    } else {
        $old=$service;
        $pdo->prepare("UPDATE services SET name=?,description=?,unit_price=?,vat_rate=?,is_active=? WHERE id=?")
            ->execute([$name,$desc?:null,$price,$vat,$active,$id]);
        audit_log($pdo,$firmId,'service',$id,'update',$old,[
            'name'=>$name,'description'=>$desc,'unit_price'=>$price,'vat_rate'=>$vat,'is_active'=>$active]);
        $_SESSION['flash']['success']='Hizmet güncellendi';
        header('Location: services.php'.($isAdmin?'?firm_id='.$firmId:''));
        exit;
    }
}
include __DIR__.'/partials/header.php';
?>
<h3 class="mb-3">Hizmet Düzenle</h3>
<form method="post">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
        <label class="form-label">Ad</label>
        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($service['name']); ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Açıklama</label>
        <textarea name="description" class="form-control"><?php echo htmlspecialchars($service['description']); ?></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Birim Fiyat</label>
        <input type="number" step="0.01" name="unit_price" class="form-control" value="<?php echo htmlspecialchars($service['unit_price']); ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">KDV%</label>
        <input type="number" step="0.01" name="vat_rate" class="form-control" value="<?php echo htmlspecialchars($service['vat_rate']); ?>">
    </div>
    <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" name="is_active" id="active" <?php if($service['is_active']) echo 'checked'; ?>>
        <label class="form-check-label" for="active">Aktif</label>
    </div>
    <button class="btn btn-primary" type="submit">Kaydet</button>
    <a class="btn btn-secondary" href="services.php<?php echo $isAdmin?'?firm_id='.$firmId:''; ?>">İptal</a>
</form>
<?php include __DIR__.'/partials/footer.php'; ?>
