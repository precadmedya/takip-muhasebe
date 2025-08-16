<?php
require_once __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/helpers/audit.php';

if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id=?");
$stmt->execute([$id]);
$customer = $stmt->fetch();
if(!$customer) { die('Müşteri bulunamadı'); }
requireFirm((int)$customer['firm_id']);
$isAdmin = isYonetici();
$firmId = $customer['firm_id'];
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
        $old = $customer;
        $stmt = $pdo->prepare("UPDATE customers SET full_name=?, email=?, phone=?, company=?, tax_no=?, address=? WHERE id=?");
        $stmt->execute([$full,$email?:null,$phone?:null,$company?:null,$tax?:null,$addr?:null,$id]);
        audit_log($pdo,$firmId,'customer',$id,'update',$old,[
            'full_name'=>$full,'email'=>$email,'phone'=>$phone,'company'=>$company,'tax_no'=>$tax,'address'=>$addr]);
        $_SESSION['flash']['success']='Müşteri güncellendi';
        header('Location: customers.php'.($isAdmin?'?firm_id='.$firmId:''));
        exit;
    }
}
include __DIR__.'/partials/header.php';
?>
<h3 class="mb-3">Müşteri Düzenle</h3>
<form method="post">
    <?php echo csrf_field(); ?>
    <div class="mb-3"><label class="form-label">Ad Soyad</label><input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($customer['full_name']); ?>" required></div>
    <div class="mb-3"><label class="form-label">E-posta</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer['email']); ?>"></div>
    <div class="mb-3"><label class="form-label">Telefon</label><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone']); ?>"></div>
    <div class="mb-3"><label class="form-label">Firma</label><input type="text" name="company" class="form-control" value="<?php echo htmlspecialchars($customer['company']); ?>"></div>
    <div class="mb-3"><label class="form-label">Vergi No</label><input type="text" name="tax_no" class="form-control" value="<?php echo htmlspecialchars($customer['tax_no']); ?>"></div>
    <div class="mb-3"><label class="form-label">Adres</label><textarea name="address" class="form-control"><?php echo htmlspecialchars($customer['address']); ?></textarea></div>
    <button class="btn btn-primary">Kaydet</button>
    <a href="customers.php<?php echo $isAdmin?'?firm_id='.$firmId:''; ?>" class="btn btn-secondary">İptal</a>
</form>
<?php include __DIR__.'/partials/footer.php'; ?>
