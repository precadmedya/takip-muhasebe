<?php
require_once __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/app/helpers/mail.php';
if(!isset($_SESSION['user'])){ header('Location: /login.php'); exit; }
$subId = (int)($_GET['service_id'] ?? 0);
if(isFirma()){
    $firmId = $_SESSION['user']['firm_id'];
}else{
    $firmId = (int)($_GET['firm_id'] ?? 0);
}
$stmt = $pdo->prepare('SELECT fs.*, s.service_name, s.price, f.name AS firm_name, f.email FROM firm_subscriptions fs JOIN services s ON s.id=fs.service_id JOIN firms f ON fs.firm_id=f.id WHERE fs.id=? AND fs.firm_id=?');
$stmt->execute([$subId,$firmId]);
$sub = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$sub){ die('Kayıt bulunamadı'); }
$gun = (new DateTime())->diff(new DateTime($sub['end_date']))->days;
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $subject = $sub['service_name'].' abonelik hatırlatma';
    $body = str_replace(
        ['{{musteri_adi}}','{{hizmet_adi}}','{{bitis_tarihi}}','{{gun_kaldi}}','{{tutar}}','{{para_birimi}}'],
        [htmlspecialchars($sub['firm_name']),htmlspecialchars($sub['service_name']),$sub['end_date'],$gun,$sub['price'],'TRY'],
        '<p>Sayın {{musteri_adi}}, {{hizmet_adi}} aboneliğiniz {{bitis_tarihi}} tarihinde sona erecektir. Kalan gün: {{gun_kaldi}}. Tutar: {{tutar}} {{para_birimi}}</p>'
    );
    $res = send_mail($pdo,$firmId,$sub['email'],$subject,$body,'subscription_reminder');
    $msg = $res['success'] ? 'E-posta gönderildi' : 'Hata: '.$res['error'];
}
include __DIR__.'/partials/header.php';
?>
<h1>Abonelik Hatırlatma</h1>
<p>Firma: <?=htmlspecialchars($sub['firm_name'])?> | Hizmet: <?=htmlspecialchars($sub['service_name'])?> | Bitiş: <?=htmlspecialchars($sub['end_date'])?></p>
<?php if(isset($msg)): ?><div class="alert alert-info"><?=$msg?></div><?php endif; ?>
<form method="post">
    <?=csrf_field()?>
    <button class="btn btn-primary" type="submit">Hatırlatma Gönder</button>
</form>
<?php include __DIR__.'/partials/footer.php'; ?>
