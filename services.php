<?php
require_once __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/helpers/pagination.php';
require __DIR__.'/app/helpers/csv.php';
require __DIR__.'/app/helpers/audit.php';

if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$isAdmin = isYonetici();
// firm selection
if($isAdmin) {
    $firms = $pdo->query("SELECT id,name FROM firms WHERE status='active' ORDER BY name")->fetchAll();
    $firmId = (int)($_GET['firm_id'] ?? ($firms[0]['id'] ?? 0));
    $valid = array_column($firms,'id');
    if(!in_array($firmId,$valid)) { $firmId = $firms ? $firms[0]['id'] : 0; }
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
$allowedSort = ['name','unit_price','vat_rate','is_active','created_at'];
$sort = $_GET['sort'] ?? 'name'; if(!in_array($sort,$allowedSort)) $sort='name';
$dir = strtolower($_GET['dir'] ?? 'asc')==='desc'?'DESC':'ASC';

$where = "WHERE firm_id=:fid";
$params = ['fid'=>$firmId];
if($search!=='') { $where .= " AND (name LIKE :q OR description LIKE :q)"; $params['q']="%$search%"; }

// export
if(isset($_GET['export']) && $_GET['export']=='1') {
    $stmt = $pdo->prepare("SELECT id,name,description,unit_price,vat_rate,is_active,created_at FROM services $where ORDER BY $sort $dir");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    output_csv(['id','name','description','unit_price','vat_rate','is_active','created_at'],$rows,'services.csv');
}

// import
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['import_csv'])) {
    verify_csrf();
    $messages=[]; $success=0; $error=0;
    if($sub['status']==='grace') { $messages[]='Grace döneminde içe aktarma devre dışı.'; $error++; }
    else {
        $file = $_FILES['csv'] ?? null;
        if($file && $file['error']===UPLOAD_ERR_OK && $file['size']<=2*1024*1024) {
            $rows = read_csv($file['tmp_name']);
            foreach($rows as $i=>$row) {
                $line=$i+2;
                $name=trim($row['name']??'');
                if($name===''){ $messages[]="Satır $line: isim eksik"; $error++; continue; }
                $desc=$row['description']??null;
                $price=isset($row['unit_price'])?(float)$row['unit_price']:0;
                $vat=isset($row['vat_rate'])?(float)$row['vat_rate']:20;
                $active=isset($row['is_active'])?(int)$row['is_active']:1;
                $stmt=$pdo->prepare("SELECT * FROM services WHERE firm_id=? AND name=? LIMIT 1");
                $stmt->execute([$firmId,$name]);
                if($ex=$stmt->fetch()) {
                    $pdo->prepare("UPDATE services SET description=?, unit_price=?, vat_rate=?, is_active=? WHERE id=?")
                        ->execute([$desc,$price,$vat,$active,$ex['id']]);
                    audit_log($pdo,$firmId,'service',(int)$ex['id'],'update',$ex,[
                        'name'=>$name,'description'=>$desc,'unit_price'=>$price,'vat_rate'=>$vat,'is_active'=>$active]);
                    $messages[]="Satır $line: güncellendi"; $success++;
                } else {
                    $pdo->prepare("INSERT INTO services (firm_id,name,description,unit_price,vat_rate,is_active) VALUES (?,?,?,?,?,?)")
                        ->execute([$firmId,$name,$desc,$price,$vat,$active]);
                    $sid=(int)$pdo->lastInsertId();
                    audit_log($pdo,$firmId,'service',$sid,'create',null,[
                        'name'=>$name,'description'=>$desc,'unit_price'=>$price,'vat_rate'=>$vat,'is_active'=>$active]);
                    $messages[]="Satır $line: eklendi"; $success++;
                }
            }
        } else { $messages[]='Geçerli bir CSV dosyası seçin'; $error++; }
    }
    $_SESSION['import_messages']=$messages;
    header('Location: '.($_SERVER['PHP_SELF']).($isAdmin?'?firm_id='.$firmId:''));
    exit;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM services $where");
$countStmt->execute($params);
$total=(int)$countStmt->fetchColumn();
$pagination=paginate($total,$page,$perPage);
$listStmt=$pdo->prepare("SELECT * FROM services $where ORDER BY $sort $dir LIMIT :o,:p");
foreach($params as $k=>$v) $listStmt->bindValue(':'.$k,$v);
$listStmt->bindValue(':o',$pagination['offset'],PDO::PARAM_INT);
$listStmt->bindValue(':p',$pagination['per_page'],PDO::PARAM_INT);
$listStmt->execute();
$rows=$listStmt->fetchAll();

$queryBase=['firm_id'=>$firmId,'search'=>$search,'sort'=>$sort,'dir'=>strtolower($dir),'per_page'=>$perPage];
include __DIR__.'/partials/header.php';
if($sub['status']==='grace') echo '<div class="alert alert-warning sticky-top">Aboneliğiniz yenilenmek üzere; kritik işlemler devre dışıdır.</div>';
if(isset($_SESSION['import_messages'])) {
    echo '<div class="alert alert-info"><ul class="mb-0">';
    foreach($_SESSION['import_messages'] as $m) echo '<li>'.htmlspecialchars($m).'</li>';
    echo '</ul></div>'; unset($_SESSION['import_messages']);
}
?>
<div class="d-flex justify-content-between mb-3">
    <form class="d-flex" method="get">
        <?php if($isAdmin): ?>
            <select name="firm_id" class="form-select me-2" onchange="this.form.submit()">
                <?php foreach($firms as $f): ?>
                    <option value="<?php echo $f['id']; ?>" <?php if($f['id']==$firmId) echo 'selected'; ?>><?php echo htmlspecialchars($f['name']); ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control me-2" placeholder="Ara">
        <select name="per_page" class="form-select me-2" onchange="this.form.submit()">
            <?php foreach([10,25,50] as $n): ?>
                <option value="<?php echo $n; ?>" <?php if($n==$perPage) echo 'selected'; ?>><?php echo $n; ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary" type="submit">Filtrele</button>
    </form>
    <div>
        <a href="?<?php echo http_build_query(array_merge($queryBase,['export'=>1])); ?>" class="btn btn-success me-2">Dışa aktar (CSV)</a>
        <button class="btn btn-primary" <?php if($sub['status']==='grace') echo 'disabled'; ?> onclick="location.href='service_add.php<?php echo $isAdmin?'?firm_id='.$firmId:''; ?>'">Hizmet Ekle</button>
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
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'unit_price','dir'=>$sort=='unit_price' && $dir=='ASC'?'desc':'asc'])); ?>">Birim Fiyat</a></th>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'vat_rate','dir'=>$sort=='vat_rate' && $dir=='ASC'?'desc':'asc'])); ?>">KDV%</a></th>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'is_active','dir'=>$sort=='is_active' && $dir=='ASC'?'desc':'asc'])); ?>">Aktif</a></th>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'created_at','dir'=>$sort=='created_at' && $dir=='ASC'?'desc':'asc'])); ?>">Kayıt</a></th>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
    <?php if(!$rows): ?>
        <tr><td colspan="6" class="text-center">Kayıt bulunamadı.</td></tr>
    <?php else: foreach($rows as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['name']); ?></td>
            <td><?php echo htmlspecialchars($r['unit_price']); ?></td>
            <td><?php echo htmlspecialchars($r['vat_rate']); ?></td>
            <td><?php echo $r['is_active']?'Evet':'Hayır'; ?></td>
            <td><?php echo htmlspecialchars($r['created_at']); ?></td>
            <td>
                <a class="btn btn-sm btn-secondary" <?php if($sub['status']==='grace') echo 'disabled'; ?> href="service_edit.php?id=<?php echo $r['id']; ?>">Düzenle</a>
                <form method="post" action="service_delete.php" class="d-inline" onsubmit="return confirm('Silinsin mi?');">
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
