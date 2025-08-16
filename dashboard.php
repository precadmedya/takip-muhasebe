<?php
require __DIR__.'/app/config/config.php';
require __DIR__.'/app/config/auth.php';
require __DIR__.'/app/config/rbac.php';
require __DIR__.'/app/config/tenant_middleware.php';
require __DIR__.'/app/config/subscription_guard.php';

if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$isAdmin=isYonetici();
if($isAdmin){
    $firms=$pdo->query("SELECT id,name FROM firms WHERE status='active' ORDER BY name")->fetchAll();
    $firmId=(int)($_GET['firm_id'] ?? ($firms[0]['id'] ?? 0));
    $valid=array_column($firms,'id');
    if(!in_array($firmId,$valid)) $firmId=$firms?$firms[0]['id']:0;
} else { $firms=[]; $firmId=$_SESSION['user']['firm_id']; }
requireFirm($firmId);
$sub=checkSubscription($pdo,$firmId);

// metrics
$totalCustomers=$pdo->prepare("SELECT COUNT(*) FROM customers WHERE firm_id=?");$totalCustomers->execute([$firmId]);$totalCustomers=$totalCustomers->fetchColumn();
$totalProducts=$pdo->prepare("SELECT COUNT(*) FROM products WHERE firm_id=?");$totalProducts->execute([$firmId]);$totalProducts=$totalProducts->fetchColumn();
$totalServices=$pdo->prepare("SELECT COUNT(*) FROM services WHERE firm_id=?");$totalServices->execute([$firmId]);$totalServices=$totalServices->fetchColumn();
$payStats=$pdo->prepare("SELECT COUNT(*) as cnt, SUM(amount_try) as sum_try FROM payments WHERE firm_id=?");$payStats->execute([$firmId]);$payStats=$payStats->fetch();
$byCurrency=$pdo->prepare("SELECT currency, SUM(amount) as total FROM payments WHERE firm_id=? GROUP BY currency");$byCurrency->execute([$firmId]);$chartData=$byCurrency->fetchAll();
$upcoming=$pdo->prepare("SELECT payment_date, amount, currency FROM payments WHERE firm_id=? AND payment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY payment_date LIMIT 10");$upcoming->execute([$firmId]);$upcoming=$upcoming->fetchAll();

include __DIR__.'/partials/header.php';
if($sub['status']==='expired'){ echo '<div class="alert alert-danger">Aboneliğiniz sona ermiş.</div>'; include __DIR__.'/partials/footer.php'; exit; }
if($sub['status']==='grace') echo '<div class="alert alert-warning sticky-top">Aboneliğiniz yenilenmek üzere; kritik işlemler devre dışıdır.</div>';
?>
<?php if($isAdmin): ?>
<form method="get" class="mb-3">
    <select name="firm_id" class="form-select w-auto" onchange="this.form.submit()">
        <?php foreach($firms as $f): ?>
            <option value="<?php echo $f['id']; ?>" <?php if($f['id']==$firmId) echo 'selected'; ?>><?php echo htmlspecialchars($f['name']); ?></option>
        <?php endforeach; ?>
    </select>
</form>
<?php endif; ?>
<div class="row mb-4">
    <div class="col-md-3 mb-2"><div class="card text-center"><div class="card-body"><h5>Müşteriler</h5><p class="fs-4"><?php echo $totalCustomers; ?></p></div></div></div>
    <div class="col-md-3 mb-2"><div class="card text-center"><div class="card-body"><h5>Ürünler</h5><p class="fs-4"><?php echo $totalProducts; ?></p></div></div></div>
    <div class="col-md-3 mb-2"><div class="card text-center"><div class="card-body"><h5>Hizmetler</h5><p class="fs-4"><?php echo $totalServices; ?></p></div></div></div>
    <div class="col-md-3 mb-2"><div class="card text-center"><div class="card-body"><h5>Ödemeler</h5><p class="fs-5"><?php echo $payStats['cnt']; ?> adet<br>Toplam <?php echo number_format((float)$payStats['sum_try'],2); ?> TL</p></div></div></div>
</div>
<canvas id="currencyChart" height="100"></canvas>
<?php if($upcoming): ?>
<h4 class="mt-4">Yaklaşan Ödemeler</h4>
<ul class="list-group">
    <?php foreach($upcoming as $u): ?>
        <li class="list-group-item d-flex justify-content-between"><span><?php echo htmlspecialchars($u['payment_date']); ?></span><span><?php echo htmlspecialchars($u['amount']).' '.htmlspecialchars($u['currency']); ?></span></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx=document.getElementById('currencyChart');
const data={labels:[<?php foreach($chartData as $c){echo "'".$c['currency']."',";}?>],datasets:[{data:[<?php foreach($chartData as $c){echo (float)$c['total'].",";}?>],backgroundColor:['#1d4ed8','#16a34a','#f59e0b','#dc2626']} ]};
new Chart(ctx,{type:'pie',data:data});
</script>
<?php include __DIR__.'/partials/footer.php'; ?>
