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
    $validIds = array_column($firms,'id');
    if(!in_array($firmId,$validIds)) { $firmId = $firms ? $firms[0]['id'] : 0; }
} else {
    $firms = [];
    $firmId = $_SESSION['user']['firm_id'];
}
requireFirm($firmId);
$sub = checkSubscription($pdo,$firmId);
if($sub['status']!=='active') { $_SESSION['flash']['danger']='Bu işlem için aboneliğiniz uygun değil.'; header('Location: customers.php'.($isAdmin?'?firm_id='.$firmId:'')); exit; }

if($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $full = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $tax = trim($_POST['tax_no'] ?? '');
    $addr = trim($_POST['address'] ?? '');
    if($full==='') {
        $_SESSION['flash']['danger'] = 'Ad Soyad gerekli';
    } else {
        $stmt = $pdo->prepare("INSERT INTO customers (firm_id,full_name,email,phone,company,tax_no,address) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$firmId,$full,$email?:null,$phone?:null,$company?:null,$tax?:null,$addr?:null]);
        $cid = (int)$pdo->lastInsertId();
        audit_log($pdo,$firmId,'customer',$cid,'create',null,[
            'full_name'=>$full,'email'=>$email,'phone'=>$phone,'company'=>$company,'tax_no'=>$tax,'address'=>$addr]);
        $_SESSION['flash']['success']='Müşteri eklendi';
        header('Location: customers.php'.($isAdmin?'?firm_id='.$firmId:''));
        exit;
    }
}
include __DIR__.'/partials/header.php';
?>
<h3 class="mb-3">Müşteri Ekle</h3>
<form method="post">
    <?php echo csrf_field(); ?>
    <?php if($isAdmin): ?>
    <div class="mb-3">
        <label class="form-label">Firma</label>
        <select name="firm_id" class="form-select">
            <?php foreach($firms as $f): ?>
            <option value="<?php echo $f['id']; ?>" <?php if($f['id']==$firmId) echo 'selected'; ?>><?php echo htmlspecialchars($f['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="mb-3"><label class="form-label">Ad Soyad</label><input type="text" name="full_name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">E-posta</label><input type="email" name="email" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Telefon</label><input type="text" name="phone" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Firma</label><input type="text" name="company" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Vergi No</label><input type="text" name="tax_no" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Adres</label><textarea name="address" class="form-control"></textarea></div>
    <button class="btn btn-primary">Kaydet</button>
    <a href="customers.php<?php echo $isAdmin?'?firm_id='.$firmId:''; ?>" class="btn btn-secondary">İptal</a>
</form>
<?php include __DIR__.'/partials/footer.php'; ?>
