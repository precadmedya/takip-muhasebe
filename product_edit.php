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
$stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if(!$product) { die('Ürün bulunamadı'); }
requireFirm((int)$product['firm_id']);
$isAdmin = isYonetici();
$firmId = $product['firm_id'];
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
        $stmt = $pdo->prepare("SELECT id FROM products WHERE firm_id=? AND sku=? AND id<>?");
        $stmt->execute([$firmId,$sku,$id]);
        if($stmt->fetch()) {
            $_SESSION['flash']['danger']='SKU zaten mevcut';
        } else {
            $old = $product;
            $pdo->prepare("UPDATE products SET name=?, sku=?, default_currency=?, base_price=?, vat_rate=?, is_active=? WHERE id=?")
                ->execute([$name,$sku,$currency,$base,$vat,$active,$id]);
            audit_log($pdo,$firmId,'product',$id,'update',$old,[
                'name'=>$name,'sku'=>$sku,'default_currency'=>$currency,'base_price'=>$base,'vat_rate'=>$vat,'is_active'=>$active]);
            $_SESSION['flash']['success']='Ürün güncellendi';
            header('Location: products.php'.($isAdmin?'?firm_id='.$firmId:''));
            exit;
        }
    }
}
include __DIR__.'/partials/header.php';
?>
<h3 class="mb-3">Ürün Düzenle</h3>
<form method="post">
    <?php echo csrf_field(); ?>
    <div class="mb-3"><label class="form-label">Ad</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required></div>
    <div class="mb-3"><label class="form-label">SKU</label><input type="text" name="sku" class="form-control" value="<?php echo htmlspecialchars($product['sku']); ?>" required></div>
    <div class="mb-3"><label class="form-label">Para Birimi</label>
        <select name="default_currency" class="form-select">
            <?php foreach(['TRY','USD','EUR','GBP'] as $c): ?><option value="<?php echo $c; ?>" <?php if($c==$product['default_currency']) echo 'selected'; ?>><?php echo $c; ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3"><label class="form-label">Baz Fiyat</label><input type="number" step="0.01" name="base_price" class="form-control" value="<?php echo htmlspecialchars($product['base_price']); ?>"></div>
    <div class="mb-3"><label class="form-label">KDV%</label><input type="number" step="0.01" name="vat_rate" class="form-control" value="<?php echo htmlspecialchars($product['vat_rate']); ?>"></div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php if($product['is_active']) echo 'checked'; ?>>
        <label class="form-check-label" for="is_active">Aktif</label>
    </div>
    <button class="btn btn-primary">Kaydet</button>
    <a href="products.php<?php echo $isAdmin?'?firm_id='.$firmId:''; ?>" class="btn btn-secondary">İptal</a>
</form>
<?php include __DIR__.'/partials/footer.php'; ?>
