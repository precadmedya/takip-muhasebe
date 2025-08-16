<?php
require_once __DIR__.'/../app/bootstrap.php';
if(!isYonetici()){ header('Location: /login.php'); exit; }

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $firmId = (int)($_POST['firm_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $old = $pdo->prepare('SELECT * FROM firm_subscriptions WHERE firm_id=?');
    $old->execute([$firmId]);
    $before = $old->fetch(PDO::FETCH_ASSOC);
    if($action==='extend_month'){
        $pdo->prepare("UPDATE firm_subscriptions SET end_date=DATE_ADD(end_date,INTERVAL 1 MONTH) WHERE firm_id=?")->execute([$firmId]);
    }elseif($action==='extend_year'){
        $pdo->prepare("UPDATE firm_subscriptions SET end_date=DATE_ADD(end_date,INTERVAL 1 YEAR) WHERE firm_id=?")->execute([$firmId]);
    }elseif($action==='suspend'){
        $pdo->prepare("UPDATE firm_subscriptions SET status='suspended' WHERE firm_id=?")->execute([$firmId]);
    }elseif($action==='resume'){
        $pdo->prepare("UPDATE firm_subscriptions SET status='active' WHERE firm_id=?")->execute([$firmId]);
    }
    $new = $pdo->prepare('SELECT * FROM firm_subscriptions WHERE firm_id=?');
    $new->execute([$firmId]);
    $after = $new->fetch(PDO::FETCH_ASSOC);
    audit_log($pdo,$firmId,'subscription',$firmId,'update',$before,$after);
    header('Location: firms.php'); exit;
}

$stmt = $pdo->query("SELECT f.id,f.name,f.status,fs.plan,fs.end_date,fs.status AS sub_status, DATEDIFF(fs.end_date,CURDATE()) AS kalan FROM firms f LEFT JOIN firm_subscriptions fs ON fs.firm_id=f.id");
$firms = $stmt->fetchAll(PDO::FETCH_ASSOC);
include __DIR__.'/../partials/header.php';
?>
<h1>Firmalar</h1>
<table class="table table-striped table-responsive">
<thead><tr><th>Ad</th><th>Durum</th><th>Plan</th><th>Bitiş</th><th>Kalan Gün</th><th>İşlemler</th></tr></thead>
<tbody>
<?php foreach($firms as $f): ?>
<tr>
<td><?=htmlspecialchars($f['name'])?></td>
<td><?=htmlspecialchars($f['status'])?></td>
<td><?=htmlspecialchars($f['plan'] ?? '-')?></td>
<td><?=htmlspecialchars($f['end_date'] ?? '-')?></td>
<td><?=is_null($f['kalan']) ? '-' : $f['kalan']?></td>
<td>
<form method="post" class="d-inline">
    <?=csrf_field()?>
    <input type="hidden" name="firm_id" value="<?=$f['id']?>">
    <button name="action" value="extend_month" class="btn btn-sm btn-success">+1 Ay</button>
    <button name="action" value="extend_year" class="btn btn-sm btn-primary">+1 Yıl</button>
    <?php if(($f['sub_status'] ?? '')==='suspended'): ?>
        <button name="action" value="resume" class="btn btn-sm btn-warning">Geri Al</button>
    <?php else: ?>
        <button name="action" value="suspend" class="btn btn-sm btn-warning">Askıya Al</button>
    <?php endif; ?>
</form>
<a href="firm_subscription_edit.php?firm_id=<?=$f['id']?>" class="btn btn-sm btn-secondary">Düzenle</a>
</td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php include __DIR__.'/../partials/footer.php'; ?>
