<?php
require_once __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/helpers/audit.php';

if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$isAdmin = isYonetici();
if($isAdmin) {
    $firms = $pdo->query("SELECT id,name FROM firms WHERE status='active' ORDER BY name")->fetchAll();
    $firmId = (int)($_GET['firm_id'] ?? ($firms[0]['id'] ?? 0));
    $validIds = array_column($firms,'id');
    if(!in_array($firmId,$validIds)) { $firmId = $firms ? $firms[0]['id'] : 0; }
} else {
    $firms=[];
    $firmId = $_SESSION['user']['firm_id'];
}
requireFirm($firmId);
$sub = checkSubscription($pdo,$firmId);
if($sub['status']!=='active') { $_SESSION['flash']['danger']='Bu işlem için aboneliğiniz uygun değil.'; header('Location: products.php'.($isAdmin?'?firm_id='.$firmId:'')); exit; }

if($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $currency = $_POST['default_currency'] ?? 'TRY';
    $base = (float)($_POST['base_price'] ?? 0);
    $vat = (float)($_POST['vat_rate'] ?? 0);
    $active = isset($_POST['is_active']) ? 1 : 0;
    if($name==='' || $sku==='') {
        $_SESSION['flash']['danger']='İsim ve SKU gerekli';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE firm_id=? AND sku=?");
        $stmt->execute([$firmId,$sku]);
        if($stmt->fetch()) {
            $_SESSION['flash']['danger']='SKU zaten mevcut';
        } else {
            $pdo->prepare("INSERT INTO products (firm_id,name,sku,default_currency,base_price,vat_rate,is_active) VALUES (?,?,?,?,?,?,?)")
                ->execute([$firmId,$name,$sku,$currency,$base,$vat,$active]);
            $pid = (int)$pdo->lastInsertId();
            audit_log($pdo,$firmId,'product',$pid,'create',null,[
                'name'=>$name,'sku'=>$sku,'default_currency'=>$currency,'base_price'=>$base,'vat_rate'=>$vat,'is_active'=>$active]);
            $_SESSION['flash']['success']='Ürün eklendi';
            header('Location: products.php'.($isAdmin?'?firm_id='.$firmId:''));
            exit;
        }
    }
}
include __DIR__.'/partials/header.php';
?>
<h3 class="mb-3">Ürün Ekle</h3>
<form method="post">
    <?php echo csrf_field(); ?>
    <?php if($isAdmin): ?>
    <div class="mb-3">
        <label class="form-label">Firma</label>
        <select name="firm_id" class="form-select">
            <?php foreach($firms as $f): ?><option value="<?php echo $f['id']; ?>" <?php if($f['id']==$firmId) echo 'selected'; ?>><?php echo htmlspecialchars($f['name']); ?></option><?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="mb-3"><label class="form-label">Ad</label><input type="text" name="name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">SKU</label><input type="text" name="sku" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Para Birimi</label>
        <select name="default_currency" class="form-select">
            <?php foreach(['TRY','USD','EUR','GBP'] as $c): ?><option value="<?php echo $c; ?>" <?php if($c==='TRY') echo 'selected'; ?>><?php echo $c; ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3"><label class="form-label">Baz Fiyat</label><input type="number" step="0.01" name="base_price" class="form-control" value="0"></div>
    <div class="mb-3"><label class="form-label">KDV%</label><input type="number" step="0.01" name="vat_rate" class="form-control" value="20"></div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
        <label class="form-check-label" for="is_active">Aktif</label>
    </div>
    <button class="btn btn-primary">Kaydet</button>
    <a href="products.php<?php echo $isAdmin?'?firm_id='.$firmId:''; ?>" class="btn btn-secondary">İptal</a>
</form>
<?php include __DIR__.'/partials/footer.php'; ?>
