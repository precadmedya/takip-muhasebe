<?php
require_once __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/helpers/pagination.php';
require __DIR__.'/app/helpers/audit.php';
require __DIR__.'/app/helpers/format.php';

try {
    if (!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
    $isAdmin = isYonetici();
    if ($isAdmin) {
        $firms = $pdo->query("SELECT id,name FROM firms WHERE status='active' ORDER BY name")->fetchAll();
        $firmId = (int)($_GET['firm_id'] ?? ($firms[0]['id'] ?? 0));
        $valid = array_column($firms,'id');
        if (!in_array($firmId,$valid)) { $firmId = $firms ? (int)$firms[0]['id'] : 0; }
    } else { $firms=[]; $firmId=(int)$_SESSION['user']['firm_id']; }
    requireFirm($firmId);
    $sub = checkSubscription($pdo,$firmId);

    $search = trim($_GET['search'] ?? '');
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';
    $currency = $_GET['currency'] ?? '';
    $type = $_GET['type'] ?? '';
    $perPage = (int)($_GET['per_page'] ?? 10); if(!in_array($perPage,[10,25,50])) $perPage=10;
    $page = max(1,(int)($_GET['page'] ?? 1));
    $allowedSort=['payment_date','created_at'];
    $sort=$_GET['sort'] ?? 'payment_date'; if(!in_array($sort,$allowedSort)) $sort='payment_date';
    $dir=strtolower($_GET['dir'] ?? 'desc')==='asc'?'ASC':'DESC';

    $where="WHERE p.firm_id=:fid"; $params=['fid'=>$firmId];
    if($search!==''){ $where.=" AND (c.full_name LIKE :q OR pr.name LIKE :q OR s.service_name LIKE :q OR e.title LIKE :q OR p.notes LIKE :q)"; $params['q']="%$search%"; }
    if($from!==''){ $where.=" AND p.payment_date >= :from"; $params['from']=$from; }
    if($to!==''){ $where.=" AND p.payment_date <= :to"; $params['to']=$to; }
    if($currency!=='' && in_array($currency,['TRY','USD','EUR','GBP'])){ $where.=" AND p.currency=:cur"; $params['cur']=$currency; }
    if($type==='customer'){ $where.=" AND p.customer_id IS NOT NULL"; }
    elseif($type==='product'){ $where.=" AND p.product_id IS NOT NULL"; }
    elseif($type==='service'){ $where.=" AND p.service_id IS NOT NULL"; }
    elseif($type==='extra'){ $where.=" AND p.extra_item_id IS NOT NULL"; }

    $countStmt=$pdo->prepare("SELECT COUNT(*) FROM payments p LEFT JOIN customers c ON p.customer_id=c.id AND c.firm_id=:fid LEFT JOIN products pr ON p.product_id=pr.id AND pr.firm_id=:fid LEFT JOIN services s ON p.service_id=s.id AND s.firm_id=:fid LEFT JOIN extra_items e ON p.extra_item_id=e.id AND e.firm_id=:fid $where");
    $countStmt->execute($params); $total=(int)$countStmt->fetchColumn();
    $pagination=paginate($total,$page,$perPage);

    $listStmt=$pdo->prepare("SELECT p.*, c.full_name AS customer_name, pr.name AS product_name, s.service_name AS service_name, e.title AS extra_title FROM payments p LEFT JOIN customers c ON p.customer_id=c.id AND c.firm_id=:fid LEFT JOIN products pr ON p.product_id=pr.id AND pr.firm_id=:fid LEFT JOIN services s ON p.service_id=s.id AND s.firm_id=:fid LEFT JOIN extra_items e ON p.extra_item_id=e.id AND e.firm_id=:fid $where ORDER BY $sort $dir LIMIT :o,:p");
    foreach($params as $k=>$v) $listStmt->bindValue(':'.$k,$v);
    $listStmt->bindValue(':o',$pagination['offset'],PDO::PARAM_INT);
    $listStmt->bindValue(':p',$pagination['per_page'],PDO::PARAM_INT);
    $listStmt->execute();
    $rows=$listStmt->fetchAll();

    $queryBase=['firm_id'=>$firmId,'search'=>$search,'from'=>$from,'to'=>$to,'currency'=>$currency,'type'=>$type,'sort'=>$sort,'dir'=>strtolower($dir),'per_page'=>$perPage];
    include __DIR__.'/partials/header.php';
?>
<div class="d-flex justify-content-between mb-3">
    <form class="row g-2" method="get">
        <?php if($isAdmin): ?>
        <div class="col-auto">
            <select name="firm_id" class="form-select" onchange="this.form.submit()">
                <?php foreach($firms as $f): ?>
                    <option value="<?=$f['id']?>" <?php if($f['id']==$firmId) echo 'selected'; ?>><?=htmlspecialchars($f['name'])?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="col-auto">
            <input type="search" name="search" value="<?=htmlspecialchars($search)?>" class="form-control" placeholder="Ara">
        </div>
        <div class="col-auto">
            <input type="date" name="from" value="<?=htmlspecialchars($from)?>" class="form-control">
        </div>
        <div class="col-auto">
            <input type="date" name="to" value="<?=htmlspecialchars($to)?>" class="form-control">
        </div>
        <div class="col-auto">
            <select name="currency" class="form-select">
                <option value="">Para (tümü)</option>
                <?php foreach(['TRY','USD','EUR','GBP'] as $cur): ?>
                    <option value="<?=$cur?>" <?php if($currency==$cur) echo 'selected'; ?>><?=$cur?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <select name="type" class="form-select">
                <option value="">Tip (tümü)</option>
                <option value="customer" <?php if($type=='customer') echo 'selected'; ?>>Müşteri</option>
                <option value="product" <?php if($type=='product') echo 'selected'; ?>>Ürün</option>
                <option value="service" <?php if($type=='service') echo 'selected'; ?>>Hizmet</option>
                <option value="extra" <?php if($type=='extra') echo 'selected'; ?>>Ek Kalem</option>
            </select>
        </div>
        <div class="col-auto">
            <select name="per_page" class="form-select" onchange="this.form.submit()">
                <?php foreach([10,25,50] as $n): ?>
                    <option value="<?=$n?>" <?php if($n==$perPage) echo 'selected'; ?>><?=$n?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-secondary" type="submit">Filtrele</button>
        </div>
    </form>
    <div>
        <button class="btn btn-primary" <?php if($sub['status']==='grace') echo 'disabled'; ?> onclick="location.href='payment_add.php<?= $isAdmin?'?firm_id='.$firmId:'' ?>'">Ödeme Ekle</button>
    </div>
</div>
<div class="table-responsive">
<table class="table table-striped">
    <thead>
        <tr>
            <th><a href="?<?=http_build_query(array_merge($queryBase,['sort'=>'payment_date','dir'=>$sort=='payment_date'&&$dir=='ASC'?'desc':'asc']))?>">Tarih</a></th>
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
            <td><?=htmlspecialchars(fmt_date_tr($r['payment_date']))?></td>
            <td><?=htmlspecialchars($r['customer_name'])?></td>
            <td><?=htmlspecialchars($item)?></td>
            <td><?=htmlspecialchars($r['currency'])?></td>
            <td><?=htmlspecialchars(fmt_money($r['amount'],$r['currency']))?></td>
            <td><?=htmlspecialchars(fmt_money($r['amount_try'],'TRY'))?></td>
            <td>
                <a class="btn btn-sm btn-secondary" <?php if($sub['status']==='grace') echo 'disabled'; ?> href="payment_edit.php?id=<?=$r['id']?>">Düzenle</a>
                <form method="post" action="payment_delete.php" class="d-inline" onsubmit="return confirm('Silinsin mi?');">
                    <?=csrf_field()?>
                    <input type="hidden" name="id" value="<?=$r['id']?>">
                    <button class="btn btn-sm btn-danger" <?php if($sub['status']==='grace') echo 'disabled'; ?>>Sil</button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>
<?=pagination_links($pagination,$queryBase);?>
<?php include __DIR__.'/partials/footer.php'; ?>
<?php
} catch (Throwable $e) {
    $code='EX'.substr(sha1($e->getMessage().$e->getFile().$e->getLine()),0,8);
    error_log('[PAYMENTS] '.$code.' '.$e->getMessage().' @'.$e->getFile().':'.$e->getLine());
    if(defined('APP_DEBUG') && APP_DEBUG){ echo '<pre>'.htmlspecialchars((string)$e).'</pre>'; }
    include __DIR__.'/errors/500.php';
    exit;
}
