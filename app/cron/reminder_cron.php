<?php
require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/../helpers/mail.php';
// subscription reminders
$firms = $pdo->query("SELECT f.id,f.email,u.email AS user_email,fs.end_date,s.service_name,s.price, rp.subscription_days_before FROM firms f JOIN users u ON u.firm_id=f.id AND u.role='firma' JOIN firm_subscriptions fs ON fs.firm_id=f.id JOIN services s ON s.id=fs.service_id LEFT JOIN reminder_policies rp ON rp.firm_id=f.id")->fetchAll(PDO::FETCH_ASSOC);
foreach($firms as $f){
    $days = json_decode($f['subscription_days_before'] ?? '[14,7,3,1]', true);
    $diff = (new DateTime())->diff(new DateTime($f['end_date']))->days;
    if(in_array($diff,$days)){
        $body = '<p>Sayın '.$f['email'].', '.$f['service_name'].' aboneliğiniz '.$f['end_date'].' tarihinde sona erecektir. Kalan gün: '.$diff.'</p>';
        send_mail($pdo,$f['id'],$f['user_email'],'Abonelik Hatırlatma',$body,'subscription_reminder');
    }
}
?>
