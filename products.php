<?php
require_once __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/helpers/pagination.php';
require __DIR__.'/app/helpers/csv.php';
require __DIR__.'/app/helpers/audit.php';

if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$isAdmin = isYonetici();
if($isAdmin) {
    $firms = $pdo->query("SELECT id,name FROM firms WHERE status='active' ORDER BY name")->fetchAll();
    $firmId = (int)($_GET['firm_id'] ?? ($firms[0]['id'] ?? 0));
    $validIds = array_column($firms,'id');
    if(!in_array($firmId,$validIds)) { $firmId = $firms ? $firms[0]['id'] : 0; }
} else {
    $firms = [];
    $firmId = $_SESSION['user']['firm_id'];
}
requireFirm($firmId);
$sub = checkSubscription($pdo,$firmId);
if($sub['status']==='expired') { die('Aboneliğiniz sona ermiş.'); }

$search = trim($_GET['search'] ?? '');
$perPage = (int)($_GET['per_page'] ?? 10); if(!in_array($perPage,[10,25,50])) $perPage=10;
$page = max(1,(int)($_GET['page'] ?? 1));
$filterActive = $_GET['active'] ?? 'all';
$allowedSort = ['name','sku','default_currency','base_price','vat_rate','is_active','created_at'];
$sort = $_GET['sort'] ?? 'name'; if(!in_array($sort,$allowedSort)) $sort='name';
$dir = strtolower($_GET['dir'] ?? 'asc')==='desc'?'DESC':'ASC';

$where = "WHERE firm_id=:firm_id";
$params = ['firm_id'=>$firmId];
if($search!=='') {
    $where .= " AND (name LIKE :q OR sku LIKE :q)";
    $params['q']="%$search%";
}
if($filterActive==='1') { $where.=" AND is_active=1"; }
elseif($filterActive==='0') { $where.=" AND is_active=0"; }

if(isset($_GET['export']) && $_GET['export']=='1') {
    $stmt = $pdo->prepare("SELECT id,name,sku,default_currency,base_price,vat_rate,is_active,created_at FROM products $where ORDER BY $sort $dir");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    output_csv(['id','name','sku','default_currency','base_price','vat_rate','is_active','created_at'],$rows,'products.csv');
}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['import_csv'])) {
    verify_csrf();
    $messages=[]; $success=0; $error=0;
    if($sub['status']==='grace') {
        $messages[]='Grace döneminde içe aktarma devre dışı.'; $error++;
    } else {
        $file = $_FILES['csv'] ?? null;
        if($file && $file['error']===UPLOAD_ERR_OK && $file['size']<=2*1024*1024) {
            $rows = read_csv($file['tmp_name']);
            $currs = ['TRY','USD','EUR','GBP'];
            foreach($rows as $i=>$row) {
                $line=$i+2; $name=trim($row['name']??''); $sku=trim($row['sku']??'');
                if($name=='' || $sku=='') { $messages[]="Satır $line: isim veya SKU eksik"; $error++; continue; }
                $currency = strtoupper($row['default_currency'] ?? 'TRY');
                if(!in_array($currency,$currs)) { $messages[]="Satır $line: geçersiz para birimi"; $error++; continue; }
                $base = is_numeric($row['base_price'] ?? '') ? $row['base_price'] : 0;
                $vat = is_numeric($row['vat_rate'] ?? '') ? $row['vat_rate'] : 0;
                $active = isset($row['is_active']) ? (int)$row['is_active'] : 1;
                $stmt = $pdo->prepare("SELECT * FROM products WHERE firm_id=? AND sku=? LIMIT 1");
                $stmt->execute([$firmId,$sku]);
                if($existing = $stmt->fetch()) {
                    $pdo->prepare("UPDATE products SET name=?, default_currency=?, base_price=?, vat_rate=?, is_active=? WHERE id=?")
                        ->execute([$name,$currency,$base,$vat,$active,$existing['id']]);
                    audit_log($pdo,$firmId,'product',(int)$existing['id'],'update',$existing,[
                        'name'=>$name,'sku'=>$sku,'default_currency'=>$currency,'base_price'=>$base,'vat_rate'=>$vat,'is_active'=>$active]);
                    $messages[]="Satır $line: güncellendi"; $success++;
                } else {
                    $pdo->prepare("INSERT INTO products (firm_id,name,sku,default_currency,base_price,vat_rate,is_active) VALUES (?,?,?,?,?,?,?)")
                        ->execute([$firmId,$name,$sku,$currency,$base,$vat,$active]);
                    $pid=(int)$pdo->lastInsertId();
                    audit_log($pdo,$firmId,'product',$pid,'create',null,[
                        'name'=>$name,'sku'=>$sku,'default_currency'=>$currency,'base_price'=>$base,'vat_rate'=>$vat,'is_active'=>$active]);
                    $messages[]="Satır $line: eklendi"; $success++;
                }
            }
        } else {
            $messages[]='Geçerli bir CSV dosyası seçin'; $error++;
        }
    }
    $_SESSION['import_messages']=$messages;
    header('Location: '.($_SERVER['PHP_SELF']).($isAdmin?'?firm_id='.$firmId:''));
    exit;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pagination = paginate($total,$page,$perPage);
$listStmt = $pdo->prepare("SELECT * FROM products $where ORDER BY $sort $dir LIMIT :offset,:pp");
foreach($params as $k=>$v) $listStmt->bindValue(':'.$k,$v);
$listStmt->bindValue(':offset',$pagination['offset'],PDO::PARAM_INT);
$listStmt->bindValue(':pp',$pagination['per_page'],PDO::PARAM_INT);
$listStmt->execute();
$products = $listStmt->fetchAll();

$queryBase=['firm_id'=>$firmId,'search'=>$search,'sort'=>$sort,'dir'=>strtolower($dir),'per_page'=>$perPage,'active'=>$filterActive];
include __DIR__.'/partials/header.php';
if($sub['status']==='grace') echo '<div class="alert alert-warning sticky-top">Aboneliğiniz yenilenmek üzere; kritik işlemler devre dışıdır.</div>';
if(isset($_SESSION['import_messages'])) { echo '<div class="alert alert-info"><ul class="mb-0">'; foreach($_SESSION['import_messages'] as $m) echo '<li>'.htmlspecialchars($m).'</li>'; echo '</ul></div>'; unset($_SESSION['import_messages']); }
?>
<div class="d-flex justify-content-between mb-3">
    <form class="d-flex" method="get">
        <?php if($isAdmin): ?>
            <select name="firm_id" class="form-select me-2" onchange="this.form.submit()">
                <?php foreach($firms as $f): ?><option value="<?php echo $f['id']; ?>" <?php if($f['id']==$firmId) echo 'selected'; ?>><?php echo htmlspecialchars($f['name']); ?></option><?php endforeach; ?>
            </select>
        <?php endif; ?>
        <input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control me-2" placeholder="Ara">
        <select name="active" class="form-select me-2" onchange="this.form.submit()">
            <option value="all" <?php if($filterActive==='all') echo 'selected'; ?>>Tümü</option>
            <option value="1" <?php if($filterActive==='1') echo 'selected'; ?>>Aktif</option>
            <option value="0" <?php if($filterActive==='0') echo 'selected'; ?>>Pasif</option>
        </select>
        <select name="per_page" class="form-select me-2" onchange="this.form.submit()">
            <?php foreach([10,25,50] as $n): ?><option value="<?php echo $n; ?>" <?php if($n==$perPage) echo 'selected'; ?>><?php echo $n; ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary" type="submit">Filtrele</button>
    </form>
    <div>
        <a href="?<?php echo http_build_query(array_merge($queryBase,['export'=>1])); ?>" class="btn btn-success me-2">Dışa aktar (CSV)</a>
        <button class="btn btn-primary" <?php if($sub['status']==='grace') echo 'disabled'; ?> onclick="location.href='product_add.php<?php echo $isAdmin?'?firm_id='.$firmId:''; ?>'">Ürün Ekle</button>
    </div>
</div>
<form method="post" enctype="multipart/form-data" class="mb-3">
    <?php echo csrf_field(); ?>
    <div class="input-group">
        <input type="file" name="csv" accept="text/csv" class="form-control" <?php if($sub['status']==='grace') echo 'disabled'; ?>>
        <button class="btn btn-outline-secondary" name="import_csv" <?php if($sub['status']==='grace') echo 'disabled'; ?>>İçe aktar (CSV)</button>
    </div>
</form>
<div class="table-responsive">
<table class="table table-striped">
    <thead>
        <tr>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'name','dir'=>$sort=='name' && $dir=='ASC'?'desc':'asc'])); ?>">Ad</a></th>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'sku','dir'=>$sort=='sku' && $dir=='ASC'?'desc':'asc'])); ?>">SKU</a></th>
            <th>Para</th>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'base_price','dir'=>$sort=='base_price' && $dir=='ASC'?'desc':'asc'])); ?>">Fiyat</a></th>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'vat_rate','dir'=>$sort=='vat_rate' && $dir=='ASC'?'desc':'asc'])); ?>">KDV%</a></th>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'is_active','dir'=>$sort=='is_active' && $dir=='ASC'?'desc':'asc'])); ?>">Aktif</a></th>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'created_at','dir'=>$sort=='created_at' && $dir=='ASC'?'desc':'asc'])); ?>">Kayıt</a></th>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
    <?php if(!$products): ?><tr><td colspan="8" class="text-center">Kayıt bulunamadı.</td></tr><?php else: foreach($products as $p): ?>
        <tr>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td><?php echo htmlspecialchars($p['sku']); ?></td>
            <td><?php echo htmlspecialchars($p['default_currency']); ?></td>
            <td><?php echo htmlspecialchars($p['base_price']); ?></td>
            <td><?php echo htmlspecialchars($p['vat_rate']); ?></td>
            <td><?php echo $p['is_active'] ? 'Evet' : 'Hayır'; ?></td>
            <td><?php echo htmlspecialchars($p['created_at']); ?></td>
            <td>
                <a class="btn btn-sm btn-secondary" <?php if($sub['status']==='grace') echo 'disabled'; ?> href="product_edit.php?id=<?php echo $p['id']; ?>">Düzenle</a>
                <form method="post" action="product_delete.php" class="d-inline" onsubmit="return confirm('Silinsin mi?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
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
