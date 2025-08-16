<?php
require __DIR__.'/app/config/config.php';
require __DIR__.'/app/config/csrf.php';
require __DIR__.'/app/config/auth.php';
require __DIR__.'/app/config/rbac.php';
require __DIR__.'/app/config/tenant_middleware.php';
require __DIR__.'/app/config/subscription_guard.php';
require __DIR__.'/app/helpers/audit.php';

if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$isAdmin = isYonetici();
if($isAdmin) {
    $firms = $pdo->query("SELECT id,name FROM firms WHERE status='active' ORDER BY name")->fetchAll();
    $firmId = (int)($_GET['firm_id'] ?? ($firms[0]['id'] ?? 0));
    $valid = array_column($firms,'id');
    if(!in_array($firmId,$valid)) { $firmId = $firms ? $firms[0]['id'] : 0; }
} else {
    $firms=[];
    $firmId = $_SESSION['user']['firm_id'];
}
requireFirm($firmId);
$sub = checkSubscription($pdo,$firmId);
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
        $pdo->prepare("INSERT INTO services (firm_id,name,description,unit_price,vat_rate,is_active) VALUES (?,?,?,?,?,?)")
            ->execute([$firmId,$name,$desc?:null,$price,$vat,$active]);
        $sid=(int)$pdo->lastInsertId();
        audit_log($pdo,$firmId,'service',$sid,'create',null,[
            'name'=>$name,'description'=>$desc,'unit_price'=>$price,'vat_rate'=>$vat,'is_active'=>$active]);
        $_SESSION['flash']['success']='Hizmet eklendi';
        header('Location: services.php'.($isAdmin?'?firm_id='.$firmId:''));
        exit;
    }
}
include __DIR__.'/partials/header.php';
?>
<h3 class="mb-3">Hizmet Ekle</h3>
<form method="post">
    <?php echo csrf_field(); ?>
    <?php if($isAdmin): ?>
    <div class="mb-3">
        <label class="form-label">Firma</label>
        <select name="firm_id" class="form-select" onchange="location.href='service_add.php?firm_id='+this.value;">
            <?php foreach($firms as $f): ?>
                <option value="<?php echo $f['id']; ?>" <?php if($f['id']==$firmId) echo 'selected'; ?>><?php echo htmlspecialchars($f['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="mb-3">
        <label class="form-label">Ad</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Açıklama</label>
        <textarea name="description" class="form-control"></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Birim Fiyat</label>
        <input type="number" step="0.01" name="unit_price" class="form-control" value="0">
    </div>
    <div class="mb-3">
        <label class="form-label">KDV%</label>
        <input type="number" step="0.01" name="vat_rate" class="form-control" value="20">
    </div>
    <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" name="is_active" checked id="active">
        <label class="form-check-label" for="active">Aktif</label>
    </div>
    <button class="btn btn-primary" type="submit">Kaydet</button>
    <a class="btn btn-secondary" href="services.php<?php echo $isAdmin?'?firm_id='.$firmId:''; ?>">İptal</a>
</form>
<?php include __DIR__.'/partials/footer.php'; ?>
