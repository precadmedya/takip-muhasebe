<?php
require __DIR__.'/app/config/config.php';
require __DIR__.'/app/config/csrf.php';
require __DIR__.'/app/config/auth.php';
require __DIR__.'/app/config/rbac.php';

if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$message=null;
if($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $json=@file_get_contents('https://api.exchangerate.host/latest?base=TRY&symbols=USD,EUR,GBP');
    if($json){
        $data=json_decode($json,true);
        $usd=$data['rates']['USD'] ?? null;
        $eur=$data['rates']['EUR'] ?? null;
        $gbp=$data['rates']['GBP'] ?? null;
        $stmt=$pdo->prepare("INSERT INTO exchange_rates (usd,eur,gbp) VALUES (?,?,?)");
        $stmt->execute([$usd,$eur,$gbp]);
        $message='Kurlar güncellendi.';
    } else {
        $message='API erişimi başarısız';
    }
}
$rates=$pdo->query("SELECT * FROM exchange_rates ORDER BY fetched_at DESC LIMIT 10")->fetchAll();
include __DIR__.'/partials/header.php';
?>
<h3 class="mb-3">Kur Güncelle</h3>
<?php if($message) echo '<div class="alert alert-info">'.htmlspecialchars($message).'</div>'; ?>
<form method="post" class="mb-3">
    <?php echo csrf_field(); ?>
    <button class="btn btn-primary" type="submit" name="fetch">Kurları Güncelle</button>
</form>
<div class="table-responsive">
<table class="table table-bordered">
    <thead><tr><th>Tarih</th><th>USD</th><th>EUR</th><th>GBP</th></tr></thead>
    <tbody>
    <?php foreach($rates as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['fetched_at']); ?></td>
            <td><?php echo htmlspecialchars($r['usd']); ?></td>
            <td><?php echo htmlspecialchars($r['eur']); ?></td>
            <td><?php echo htmlspecialchars($r['gbp']); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php include __DIR__.'/partials/footer.php'; ?>
