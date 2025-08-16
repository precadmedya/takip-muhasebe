<?php
require_once __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/helpers/audit.php';

if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$isAdmin=isYonetici();
if($isAdmin){
    $firms=$pdo->query("SELECT id,name FROM firms WHERE status='active' ORDER BY name")->fetchAll();
    $firmId=(int)($_GET['firm_id'] ?? ($firms[0]['id'] ?? 0));
    $valid=array_column($firms,'id');
    if(!in_array($firmId,$valid)){ $firmId=$firms?$firms[0]['id']:0; }
}else{
    $firms=[]; $firmId=$_SESSION['user']['firm_id'];
}
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
        $pdo->prepare("INSERT INTO extra_items (firm_id,title,amount,notes) VALUES (?,?,?,?)")
            ->execute([$firmId,$title,$amount,$notes?:null]);
        $eid=(int)$pdo->lastInsertId();
        audit_log($pdo,$firmId,'extra_item',$eid,'create',null,[
            'title'=>$title,'amount'=>$amount,'notes'=>$notes]);
        $_SESSION['flash']['success']='Ek kalem eklendi';
        header('Location: extra_items.php'.($isAdmin?'?firm_id='.$firmId:''));
        exit;
    }
}
include __DIR__.'/partials/header.php';
?>
<h3 class="mb-3">Ek Kalem Ekle</h3>
<form method="post">
    <?php echo csrf_field(); ?>
    <?php if($isAdmin): ?>
    <div class="mb-3">
        <label class="form-label">Firma</label>
        <select name="firm_id" class="form-select" onchange="location.href='extra_item_add.php?firm_id='+this.value;">
            <?php foreach($firms as $f): ?>
                <option value="<?php echo $f['id']; ?>" <?php if($f['id']==$firmId) echo 'selected'; ?>><?php echo htmlspecialchars($f['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="mb-3">
        <label class="form-label">Başlık</label>
        <input type="text" name="title" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Tutar</label>
        <input type="number" step="0.01" name="amount" class="form-control" value="0">
    </div>
    <div class="mb-3">
        <label class="form-label">Notlar</label>
        <textarea name="notes" class="form-control"></textarea>
    </div>
    <button class="btn btn-primary" type="submit">Kaydet</button>
    <a class="btn btn-secondary" href="extra_items.php<?php echo $isAdmin?'?firm_id='.$firmId:''; ?>">İptal</a>
</form>
<?php include __DIR__.'/partials/footer.php'; ?>
