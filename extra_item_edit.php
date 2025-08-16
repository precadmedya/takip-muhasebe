<?php
require_once __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/helpers/audit.php';

if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$id=(int)($_GET['id'] ?? 0);
$stmt=$pdo->prepare("SELECT * FROM extra_items WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$item=$stmt->fetch();
if(!$item){ die('Kayıt bulunamadı'); }
$firmId=(int)$item['firm_id'];
$isAdmin=isYonetici();
requireFirm($firmId);
$sub=checkSubscription($pdo,$firmId);
if($sub['status']!=='active'){ $_SESSION['flash']['danger']='Bu işlem için aboneliğiniz uygun değil.'; header('Location: extra_items.php'.($isAdmin?'?firm_id='.$firmId:'')); exit; }

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $title=trim($_POST['title'] ?? '');
    $amount=(float)($_POST['amount'] ?? 0);
    $notes=trim($_POST['notes'] ?? '');
    if($title===''){ $_SESSION['flash']['danger']='Başlık gerekli'; }
    else {
        $old=$item;
        $pdo->prepare("UPDATE extra_items SET title=?,amount=?,notes=? WHERE id=?")
            ->execute([$title,$amount,$notes?:null,$id]);
        audit_log($pdo,$firmId,'extra_item',$id,'update',$old,[
            'title'=>$title,'amount'=>$amount,'notes'=>$notes]);
        $_SESSION['flash']['success']='Güncellendi';
        header('Location: extra_items.php'.($isAdmin?'?firm_id='.$firmId:''));
        exit;
    }
}
include __DIR__.'/partials/header.php';
?>
<h3 class="mb-3">Ek Kalem Düzenle</h3>
<form method="post">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
        <label class="form-label">Başlık</label>
        <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($item['title']); ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Tutar</label>
        <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo htmlspecialchars($item['amount']); ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Notlar</label>
        <textarea name="notes" class="form-control"><?php echo htmlspecialchars($item['notes']); ?></textarea>
    </div>
    <button class="btn btn-primary" type="submit">Kaydet</button>
    <a class="btn btn-secondary" href="extra_items.php<?php echo $isAdmin?'?firm_id='.$firmId:''; ?>">İptal</a>
</form>
<?php include __DIR__.'/partials/footer.php'; ?>
