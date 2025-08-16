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
    $valid = array_column($firms,'id');
    if(!in_array($firmId,$valid)) { $firmId = $firms ? $firms[0]['id'] : 0; }
} else {
    $firms=[]; $firmId=$_SESSION['user']['firm_id'];
}
requireFirm($firmId);
$sub = checkSubscription($pdo,$firmId);

$search = trim($_GET['search'] ?? '');
$perPage = (int)($_GET['per_page'] ?? 10); if(!in_array($perPage,[10,25,50])) $perPage=10;
$page = max(1,(int)($_GET['page'] ?? 1));
$allowedSort=['title','amount','created_at'];
$sort=$_GET['sort'] ?? 'created_at'; if(!in_array($sort,$allowedSort)) $sort='created_at';
$dir=strtolower($_GET['dir'] ?? 'desc')==='asc'?'ASC':'DESC';

$where="WHERE firm_id=:fid"; $params=['fid'=>$firmId];
if($search!==''){ $where.=" AND title LIKE :q"; $params['q']="%$search%"; }

if(isset($_GET['export']) && $_GET['export']=='1') {
    $stmt=$pdo->prepare("SELECT id,title,amount,notes,created_at FROM extra_items $where ORDER BY $sort $dir");
    $stmt->execute($params);
    $rows=$stmt->fetchAll();
    output_csv(['id','title','amount','notes','created_at'],$rows,'extra_items.csv');
}

$countStmt=$pdo->prepare("SELECT COUNT(*) FROM extra_items $where");
$countStmt->execute($params); $total=(int)$countStmt->fetchColumn();
$pagination=paginate($total,$page,$perPage);
$listStmt=$pdo->prepare("SELECT * FROM extra_items $where ORDER BY $sort $dir LIMIT :o,:p");
foreach($params as $k=>$v) $listStmt->bindValue(':'.$k,$v);
$listStmt->bindValue(':o',$pagination['offset'],PDO::PARAM_INT);
$listStmt->bindValue(':p',$pagination['per_page'],PDO::PARAM_INT);
$listStmt->execute();
$rows=$listStmt->fetchAll();

$queryBase=['firm_id'=>$firmId,'search'=>$search,'sort'=>$sort,'dir'=>strtolower($dir),'per_page'=>$perPage];
include __DIR__.'/partials/header.php';
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
        <button class="btn btn-primary" <?php if($sub['status']==='grace') echo 'disabled'; ?> onclick="location.href='extra_item_add.php<?php echo $isAdmin?'?firm_id='.$firmId:''; ?>'">Ek Kalem Ekle</button>
    </div>
</div>
<div class="table-responsive">
<table class="table table-striped">
    <thead>
        <tr>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'title','dir'=>$sort=='title' && $dir=='ASC'?'desc':'asc'])); ?>">Başlık</a></th>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'amount','dir'=>$sort=='amount' && $dir=='ASC'?'desc':'asc'])); ?>">Tutar</a></th>
            <th><a href="?<?php echo http_build_query(array_merge($queryBase,['sort'=>'created_at','dir'=>$sort=='created_at' && $dir=='ASC'?'desc':'asc'])); ?>">Kayıt</a></th>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
    <?php if(!$rows): ?>
        <tr><td colspan="4" class="text-center">Kayıt bulunamadı.</td></tr>
    <?php else: foreach($rows as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['title']); ?></td>
            <td><?php echo htmlspecialchars($r['amount']); ?></td>
            <td><?php echo htmlspecialchars($r['created_at']); ?></td>
            <td>
                <a class="btn btn-sm btn-secondary" <?php if($sub['status']==='grace') echo 'disabled'; ?> href="extra_item_edit.php?id=<?php echo $r['id']; ?>">Düzenle</a>
                <form method="post" action="extra_item_delete.php" class="d-inline" onsubmit="return confirm('Silinsin mi?');">
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
