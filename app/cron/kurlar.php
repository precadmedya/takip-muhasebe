<?php
require __DIR__.'/../config/config.php';
$json=@file_get_contents('https://api.exchangerate.host/latest?base=TRY&symbols=USD,EUR,GBP');
if($json){
    $data=json_decode($json,true);
    $usd=$data['rates']['USD'] ?? null;
    $eur=$data['rates']['EUR'] ?? null;
    $gbp=$data['rates']['GBP'] ?? null;
    $stmt=$pdo->prepare("INSERT INTO exchange_rates (usd,eur,gbp) VALUES (?,?,?)");
    $stmt->execute([$usd,$eur,$gbp]);
}
