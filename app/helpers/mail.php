<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__.'/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__.'/../lib/PHPMailer/Exception.php';
require_once __DIR__.'/../lib/PHPMailer/SMTP.php';
function send_mail(PDO $pdo, int $firmId, string $to, string $subject, string $body, string $context): array {
    $stmt = $pdo->prepare('SELECT * FROM email_settings WHERE firm_id=?');
    $stmt->execute([$firmId]);
    $conf = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$conf){
        return ['success'=>false,'error'=>'SMTP yapılandırılmamış'];
    }
    try{
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $conf['smtp_host'];
        $mail->Port = (int)$conf['smtp_port'];
        $mail->SMTPSecure = $conf['smtp_secure'];
        $mail->SMTPAuth = true;
        $mail->Username = $conf['smtp_username'];
        $mail->Password = $conf['smtp_password'];
        $mail->setFrom($conf['from_email'], $conf['from_name']);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->isHTML(true);
        $mail->send();
        $status='success'; $error=null;
    }catch(Exception $e){
        $status='error'; $error=$e->getMessage();
    }
    $log = $pdo->prepare('INSERT INTO email_logs (firm_id,to_email,subject,body,context,status,error_message,created_at) VALUES (?,?,?,?,?,?,?,NOW())');
    $log->execute([$firmId,$to,$subject,$body,$context,$status,$error]);
    return ['success'=>$status==='success','error'=>$error];
}
