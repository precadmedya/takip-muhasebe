<?php
require __DIR__.'/app/config/config.php';
require __DIR__.'/app/config/csrf.php';
require __DIR__.'/app/config/auth.php';
require __DIR__.'/app/config/rbac.php';
require __DIR__.'/app/config/tenant_middleware.php';
require __DIR__.'/app/config/subscription_guard.php';
require __DIR__.'/app/helpers/pagination.php';
require __DIR__.'/app/helpers/csv.php';
require __DIR__.'/app/helpers/audit.php';

if(!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$isAdmin = isYonetici();
// firm selection
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

// handle CSV export
$search = trim($_GET['search'] ?? '');
$perPage = (int)($_GET['per_page'] ?? 10); if(!in_array($perPage,[10,25,50])) $perPage=10;
$page = max(1,(int)($_GET['page'] ?? 1));
$allowedSort = ['full_name','email','phone','company','created_at'];
$sort = $_GET['sort'] ?? 'full_name'; if(!in_array($sort,$allowedSort)) $sort='full_name';
$dir = strtolower($_GET['dir'] ?? 'asc')==='desc'?'DESC':'ASC';

$where = "WHERE firm_id=:firm_id";
$params = ['firm_id'=>$firmId];
if($search!=='') {
    $where .= " AND (full_name LIKE :q OR email LIKE :q OR company LIKE :q)";
    $params['q'] = "%$search%";
}

if(isset($_GET['export']) && $_GET['export']=='1') {
    $stmt = $pdo->prepare("SELECT id,full_name,email,phone,company,tax_no,address,created_at FROM customers $where ORDER BY $sort $dir");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    output_csv(['id','full_name','email','phone','company','tax_no','address','created_at'],$rows,'customers.csv');
}

// CSV import
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['import_csv'])) {
    verify_csrf();
    $messages=[]; $success=0; $error=0;
    if($sub['status']==='grace') {
        $messages[]='Grace döneminde içe aktarma devre dışı.'; $error++;
    } else {
        $file = $_FILES['csv'] ?? null;
        if($file && $file['error']===UPLOAD_ERR_OK && $file['size'] <= 2*1024*1024) {
            $rows = read_csv($file['tmp_name']);
            foreach($rows as $i=>$row) {
                $line = $i+2; // header offset
                $full = trim($row['full_name'] ?? '');
                if($full==='') { $messages[]="Satır $line: isim eksik"; $error++; continue; }
                $email = trim($row['email'] ?? '');
                $phone = $row['phone'] ?? null;
                $company = $row['company'] ?? null;
                $tax = $row['tax_no'] ?? null;
                $addr = $row['address'] ?? null;
                if($email!=='') {
                    $stmt = $pdo->prepare("SELECT * FROM customers WHERE firm_id=? AND email=? LIMIT 1");
                    $stmt->execute([$firmId,$email]);
                    if($existing = $stmt->fetch()) {
                        $pdo->prepare("UPDATE customers SET full_name=?, phone=?, company=?, tax_no=?, address=? WHERE id=?")
                            ->execute([$full,$phone,$company,$tax,$addr,$existing['id']]);
                        audit_log($pdo,$firmId,'customer',(int)$existing['id'],'update',$existing,[
                            'full_name'=>$full,'email'=>$email,'phone'=>$phone,'company'=>$company,'tax_no'=>$tax,'address'=>$addr]);
                        $messages[]="Satır $line: güncellendi"; $success++;
                        continue;
                    }
                }
                $pdo->prepare("INSERT INTO customers (firm_id,full_name,email,phone,company,tax_no,address) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$firmId,$full,$email?:null,$phone,$company,$tax,$addr]);
                $cid = (int)$pdo->lastInsertId();
                audit_log($pdo,$firmId,'customer',$cid,'create',null,[
                    'full_name'=>$full,'email'=>$email,'phone'=>$phone,'company'=>$company,'tax_no'=>$tax,'address'=>$addr]);
                $messages[]="Satır $line: eklendi"; $success++;
            }
        } else {
            $messages[]='Geçerli bir CSV dosyası seçin'; $error++;
        }
    }
    $_SESSION['import_messages']=$messages;
    header('Location: '.($_SERVER['PHP_SELF']).($isAdmin?'?firm_id='.$firmId:''));
    exit;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM customers $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pagination = paginate($total,$page,$perPage);
$listStmt = $pdo->prepare("SELECT * FROM customers $where ORDER BY $sort $dir LIMIT :offset,:pp");
foreach($params as $k=>$v) $listStmt->bindValue(':'.$k,$v);
$listStmt->bindValue(':offset',$pagination['offset'],PDO::PARAM_INT);
$listStmt->bindValue(':pp',$pagination['per_page'],PDO::PARAM_INT);
$listStmt->execute();
$customers = $listStmt->fetchAll();

$queryBase = ['firm_id'=>$firmId,'search'=>$search,'sort'=>$sort,'dir'=>strtolower($dir),'per_page'=>$perPage];
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
        <button class="btn btn-primary" <?php if($sub['status']==='grace') echo 'disabled'; ?> onclick="location.href='customer_add.php<?php echo $isAdmin?'?firm_id='.$firmId:''; ?>'">Müşteri Ekle</button>
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
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'full_name','dir'=>$sort=='full_name' && $dir=='ASC'?'desc':'asc'])); ?>">Ad Soyad</a></th>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'email','dir'=>$sort=='email' && $dir=='ASC'?'desc':'asc'])); ?>">E-posta</a></th>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'phone','dir'=>$sort=='phone' && $dir=='ASC'?'desc':'asc'])); ?>">Telefon</a></th>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'company','dir'=>$sort=='company' && $dir=='ASC'?'desc':'asc'])); ?>">Firma</a></th>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'created_at','dir'=>$sort=='created_at' && $dir=='ASC'?'desc':'asc'])); ?>">Kayıt</a></th>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
    <?php if(!$customers): ?>
        <tr><td colspan="6" class="text-center">Kayıt bulunamadı.</td></tr>
    <?php else: foreach($customers as $c): ?>
        <tr>
            <td><?php echo htmlspecialchars($c['full_name']); ?></td>
            <td><?php echo htmlspecialchars($c['email']); ?></td>
            <td><?php echo htmlspecialchars($c['phone']); ?></td>
            <td><?php echo htmlspecialchars($c['company']); ?></td>
            <td><?php echo htmlspecialchars($c['created_at']); ?></td>
            <td>
                <a class="btn btn-sm btn-info" href="customer.php?id=<?php echo $c['id']; ?>">Görüntüle</a>
                <a class="btn btn-sm btn-secondary" <?php if($sub['status']==='grace') echo 'disabled'; ?> href="customer_edit.php?id=<?php echo $c['id']; ?>">Düzenle</a>
                <form method="post" action="customer_delete.php" class="d-inline" onsubmit="return confirm('Silinsin mi?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
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
