<?php
require __DIR__.'/app/config/config.php';
require __DIR__.'/app/config/csrf.php';
require __DIR__.'/app/config/auth.php';
require __DIR__.'/app/config/rbac.php';
require __DIR__.'/app/config/tenant_middleware.php';
require __DIR__.'/app/config/subscription_guard.php';
require __DIR__.'/app/helpers/pagination.php';
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
if($sub['status']==='expired'){ die('Aboneliğiniz sona ermiş.'); }

$search=trim($_GET['search'] ?? '');
$from=$_GET['from'] ?? '';
$to=$_GET['to'] ?? '';
$perPage=(int)($_GET['per_page'] ?? 10); if(!in_array($perPage,[10,25,50])) $perPage=10;
$page=max(1,(int)($_GET['page'] ?? 1));
$allowedSort=['payment_date','created_at'];
$sort=$_GET['sort'] ?? 'payment_date'; if(!in_array($sort,$allowedSort)) $sort='payment_date';
$dir=strtolower($_GET['dir'] ?? 'desc')==='asc'?'ASC':'DESC';

$where="WHERE p.firm_id=:fid"; $params=['fid'=>$firmId];
if($search!==''){ $where.=" AND (c.full_name LIKE :q OR pr.name LIKE :q OR s.name LIKE :q OR e.title LIKE :q)"; $params['q']="%$search%"; }
if($from!==''){ $where.=" AND p.payment_date >= :from"; $params['from']=$from; }
if($to!==''){ $where.=" AND p.payment_date <= :to"; $params['to']=$to; }

$countStmt=$pdo->prepare("SELECT COUNT(*) FROM payments p LEFT JOIN customers c ON p.customer_id=c.id LEFT JOIN products pr ON p.product_id=pr.id LEFT JOIN services s ON p.service_id=s.id LEFT JOIN extra_items e ON p.extra_item_id=e.id $where");
$countStmt->execute($params); $total=(int)$countStmt->fetchColumn();
$pagination=paginate($total,$page,$perPage);
$listStmt=$pdo->prepare("SELECT p.*, c.full_name AS customer_name, pr.name AS product_name, s.name AS service_name, e.title AS extra_title FROM payments p LEFT JOIN customers c ON p.customer_id=c.id LEFT JOIN products pr ON p.product_id=pr.id LEFT JOIN services s ON p.service_id=s.id LEFT JOIN extra_items e ON p.extra_item_id=e.id $where ORDER BY $sort $dir LIMIT :o,:p");
foreach($params as $k=>$v) $listStmt->bindValue(':'.$k,$v);
$listStmt->bindValue(':o',$pagination['offset'],PDO::PARAM_INT);
$listStmt->bindValue(':p',$pagination['per_page'],PDO::PARAM_INT);
$listStmt->execute();
$rows=$listStmt->fetchAll();

$queryBase=['firm_id'=>$firmId,'search'=>$search,'from'=>$from,'to'=>$to,'sort'=>$sort,'dir'=>strtolower($dir),'per_page'=>$perPage];
include __DIR__.'/partials/header.php';
if($sub['status']==='grace') echo '<div class="alert alert-warning sticky-top">Aboneliğiniz yenilenmek üzere; kritik işlemler devre dışıdır.</div>';
?>
<div class="d-flex justify-content-between mb-3">
    <form class="row g-2" method="get">
        <?php if($isAdmin): ?>
        <div class="col-auto">
            <select name="firm_id" class="form-select" onchange="this.form.submit()">
                <?php foreach($firms as $f): ?>
                    <option value="<?php echo $f['id']; ?>" <?php if($f['id']==$firmId) echo 'selected'; ?>><?php echo htmlspecialchars($f['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="col-auto">
            <input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Ara">
        </div>
        <div class="col-auto">
            <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" class="form-control">
        </div>
        <div class="col-auto">
            <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>" class="form-control">
        </div>
        <div class="col-auto">
            <select name="per_page" class="form-select" onchange="this.form.submit()">
                <?php foreach([10,25,50] as $n): ?>
                    <option value="<?php echo $n; ?>" <?php if($n==$perPage) echo 'selected'; ?>><?php echo $n; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-secondary" type="submit">Filtrele</button>
        </div>
    </form>
    <div>
        <button class="btn btn-primary" <?php if($sub['status']==='grace') echo 'disabled'; ?> onclick="location.href='payment_add.php<?php echo $isAdmin?'?firm_id='.$firmId:''; ?>'">Ödeme Ekle</button>
    </div>
</div>
<div class="table-responsive">
<table class="table table-striped">
    <thead>
        <tr>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'payment_date','dir'=>$sort=='payment_date' && $dir=='ASC'?'desc':'asc'])); ?>">Tarih</a></th>
            <th>Müşteri</th>
            <th>Kalem</th>
            <th>Para</th>
            <th>Tutar</th>
            <th>TL Karşılık</th>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
    <?php if(!$rows): ?>
        <tr><td colspan="7" class="text-center">Kayıt bulunamadı.</td></tr>
    <?php else: foreach($rows as $r): $item=$r['product_name']?:($r['service_name']?:$r['extra_title']); ?>
        <tr>
            <td><?php echo htmlspecialchars($r['payment_date']); ?></td>
            <td><?php echo htmlspecialchars($r['customer_name']); ?></td>
            <td><?php echo htmlspecialchars($item); ?></td>
            <td><?php echo htmlspecialchars($r['currency']); ?></td>
            <td><?php echo htmlspecialchars($r['amount']); ?></td>
            <td><?php echo htmlspecialchars($r['amount_try']); ?></td>
            <td>
                <a class="btn btn-sm btn-secondary" <?php if($sub['status']==='grace') echo 'disabled'; ?> href="payment_edit.php?id=<?php echo $r['id']; ?>">Düzenle</a>
                <form method="post" action="payment_delete.php" class="d-inline" onsubmit="return confirm('Silinsin mi?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                    <button class="btn btn-sm btn-danger" <?php if($sub['status']==='grace') echo 'disabled'; ?>>Sil</button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>
<?php echo pagination_links($pagination,$queryBase); ?>
<?php include __DIR__.'/partials/footer.php'; ?>
