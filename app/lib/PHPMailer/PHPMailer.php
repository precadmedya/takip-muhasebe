<?php
namespace PHPMailer\PHPMailer;
class PHPMailer {
    public $Host='';
    public $Port=587;
    public $SMTPAuth=true;
    public $Username='';
    public $Password='';
    public $SMTPSecure='tls';
    public $From='';
    public $FromName='';
    public $Subject='';
    public $Body='';
    public $isHTML=true;
    private $to=[];
    public function isSMTP(){}
    public function setFrom($addr,$name=''){ $this->From=$addr; $this->FromName=$name; }
    public function addAddress($addr){ $this->to[]=$addr; }
    public function send(){
        $headers="From: {$this->FromName} <{$this->From}>\r\n";
        if($this->isHTML){
            $headers .= "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        }
        $to=implode(',', $this->to);
        if($this->Host){
            $protocol = ($this->SMTPSecure==='ssl') ? 'ssl://' : '';
            $host = $protocol.$this->Host;
            $fp = @fsockopen($host, $this->Port, $errno, $errstr, 10);
            if(!$fp){ throw new Exception('SMTP bağlantısı başarısız: '.$errstr); }
            fwrite($fp, "HELO localhost\r\n");
            if($this->SMTPAuth){
                fwrite($fp, "AUTH LOGIN\r\n".
                    base64_encode($this->Username)."\r\n".
                    base64_encode($this->Password)."\r\n");
            }
            fwrite($fp, "MAIL FROM:<{$this->From}>\r\n");
            foreach($this->to as $addr){ fwrite($fp, "RCPT TO:<{$addr}>\r\n"); }
            fwrite($fp, "DATA\r\nSubject: {$this->Subject}\r\n{$headers}\r\n{$this->Body}\r\n.\r\nQUIT\r\n");
            fclose($fp);
            return true;
        }
        return mail($to,$this->Subject,$this->Body,$headers);
    }
}
class Exception extends \Exception {}
