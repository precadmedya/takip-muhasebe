<?php
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/helpers/mail.php';
if(!isFirma()){ header('Location: /login.php'); exit; }
$firmId = $_SESSION['user']['firm_id'];
$stmt = $pdo->prepare('SELECT * FROM email_settings WHERE firm_id=?');
$stmt->execute([$firmId]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
$message = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $data = [
        'from_name'=>$_POST['from_name'] ?? '',
        'from_email'=>$_POST['from_email'] ?? '',
        'smtp_host'=>$_POST['smtp_host'] ?? '',
        'smtp_port'=>(int)($_POST['smtp_port'] ?? 587),
        'smtp_username'=>$_POST['smtp_username'] ?? '',
        'smtp_password'=>$_POST['smtp_password'] ?? '',
        'smtp_secure'=>$_POST['smtp_secure'] ?? 'tls'
    ];
    if($settings){
        $pdo->prepare('UPDATE email_settings SET from_name=?,from_email=?,smtp_host=?,smtp_port=?,smtp_username=?,smtp_password=?,smtp_secure=?,updated_at=NOW() WHERE firm_id=?')
            ->execute([$data['from_name'],$data['from_email'],$data['smtp_host'],$data['smtp_port'],$data['smtp_username'],$data['smtp_password'],$data['smtp_secure'],$firmId]);
    }else{
        $pdo->prepare('INSERT INTO email_settings (firm_id,from_name,from_email,smtp_host,smtp_port,smtp_username,smtp_password,smtp_secure,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())')
            ->execute([$firmId,$data['from_name'],$data['from_email'],$data['smtp_host'],$data['smtp_port'],$data['smtp_username'],$data['smtp_password'],$data['smtp_secure']]);
    }
    $settings = $data;
    $message = 'Kaydedildi';
    audit_log($pdo,$firmId,'email_settings',$firmId,'update',null,$data);
    if(isset($_POST['test_email'])){
        $userMail = $_SESSION['user']['email'];
        $res = send_mail($pdo,$firmId,$userMail,'Test E-Posta','<p>Test mesajıdır</p>','test');
        $message .= $res['success'] ? ' - Test gönderildi' : ' - Hata: '.$res['error'];
    }
}
include __DIR__.'/../partials/header.php';
?>
<h1>E-posta Ayarları</h1>
<?php if($message): ?><div class="alert alert-info"><?=$message?></div><?php endif; ?>
<form method="post" class="row g-3">
    <?=csrf_field()?>
    <div class="col-md-6"><label class="form-label">Gönderen Adı</label><input name="from_name" value="<?=htmlspecialchars($settings['from_name']??'')?>" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Gönderen E-posta</label><input name="from_email" value="<?=htmlspecialchars($settings['from_email']??'')?>" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">SMTP Host</label><input name="smtp_host" value="<?=htmlspecialchars($settings['smtp_host']??'')?>" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">SMTP Port</label><input name="smtp_port" type="number" value="<?=htmlspecialchars($settings['smtp_port']??587)?>" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">SMTP Kullanıcı</label><input name="smtp_username" value="<?=htmlspecialchars($settings['smtp_username']??'')?>" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">SMTP Parola</label><input name="smtp_password" type="password" value="<?=htmlspecialchars($settings['smtp_password']??'')?>" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Güvenlik</label><select name="smtp_secure" class="form-select">
        <option value="tls" <?=($settings['smtp_secure']??'')==='tls'?'selected':''?>>TLS</option>
        <option value="ssl" <?=($settings['smtp_secure']??'')==='ssl'?'selected':''?>>SSL</option>
        <option value="none" <?=($settings['smtp_secure']??'')==='none'?'selected':''?>>None</option>
    </select></div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary">Kaydet</button>
        <button type="submit" name="test_email" value="1" class="btn btn-secondary">Test E-posta Gönder</button>
    </div>
</form>
<?php include __DIR__.'/../partials/footer.php'; ?>
