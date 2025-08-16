<?php
require_once __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/helpers/pagination.php';
require __DIR__.'/app/helpers/csv.php';
require __DIR__.'/app/helpers/upload.php';
require __DIR__.'/app/helpers/audit.php';

try {
    if (!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
    $isAdmin = isYonetici();
    if ($isAdmin) {
        $firms = $pdo->query("SELECT id,name FROM firms WHERE status='active' ORDER BY name")->fetchAll();
        $firmId = (int)($_GET['firm_id'] ?? ($firms[0]['id'] ?? 0));
        $valid = array_column($firms, 'id');
        if (!in_array($firmId, $valid)) { $firmId = $firms ? (int)$firms[0]['id'] : 0; }
    } else {
        $firms = [];
        $firmId = (int)$_SESSION['user']['firm_id'];
    }
    requireFirm($firmId);
    $sub = checkSubscription($pdo, $firmId);

    $search = trim($_GET['search'] ?? '');
    $perPage = (int)($_GET['per_page'] ?? 10); if (!in_array($perPage, [10,25,50])) { $perPage = 10; }
    $page = max(1, (int)($_GET['page'] ?? 1));
    $allowedSort = ['service_name','price','period','created_at'];
    $sort = $_GET['sort'] ?? 'service_name'; if (!in_array($sort, $allowedSort)) { $sort = 'service_name'; }
    $dir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

    $where = "WHERE firm_id = :fid";
    $params = ['fid' => $firmId];
    if ($search !== '') { $where .= " AND (service_name LIKE :q OR description LIKE :q)"; $params['q'] = "%$search%"; }

    if (isset($_GET['export']) && $_GET['export'] === '1') {
        $stmt = $pdo->prepare("SELECT id,service_name,description,price,period,created_at FROM services $where ORDER BY $sort $dir");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        output_csv(['id','service_name','description','price','period','created_at'], $rows, 'services.csv');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv'])) {
        verify_csrf();
        $messages=[]; $success=0; $error=0;
        if ($sub['status'] === 'grace') {
            $messages[] = 'Grace döneminde içe aktarma devre dışı.'; $error++;
        } else {
            $upload = upload_file('csv', __DIR__.'/assets/uploads', ['text/plain','text/csv','application/vnd.ms-excel'], 2*1024*1024);
            if ($upload['status']) {
                $rows = read_csv($upload['path']);
                @unlink($upload['path']);
                foreach ($rows as $i=>$row) {
                    $line = $i+2;
                    $name = trim($row['service_name'] ?? '');
                    if ($name === '') { $messages[] = "Satır $line: ad gerekli"; $error++; continue; }
                    $desc = $row['description'] ?? null;
                    $price = is_numeric($row['price'] ?? null) ? (float)$row['price'] : 0;
                    $period = in_array($row['period'] ?? '', ['ay','yil']) ? $row['period'] : 'ay';
                    $stmt = $pdo->prepare("SELECT id FROM services WHERE firm_id=:fid AND service_name=:name");
                    $stmt->execute(['fid'=>$firmId,'name'=>$name]);
                    if ($ex = $stmt->fetch()) {
                        $old = $pdo->query("SELECT * FROM services WHERE id=".(int)$ex['id'])->fetch();
                        $pdo->prepare("UPDATE services SET description=?, price=?, period=? WHERE id=?")
                            ->execute([$desc,$price,$period,$ex['id']]);
                        audit_log($pdo,$firmId,'service',(int)$ex['id'],'update',$old,[
                            'service_name'=>$name,'description'=>$desc,'price'=>$price,'period'=>$period]);
                        $messages[] = "Satır $line: güncellendi"; $success++;
                    } else {
                        $pdo->prepare("INSERT INTO services (firm_id,service_name,description,price,period) VALUES (?,?,?,?,?)")
                            ->execute([$firmId,$name,$desc,$price,$period]);
                        $sid = (int)$pdo->lastInsertId();
                        audit_log($pdo,$firmId,'service',$sid,'create',null,[
                            'service_name'=>$name,'description'=>$desc,'price'=>$price,'period'=>$period]);
                        $messages[] = "Satır $line: eklendi"; $success++;
                    }
                }
            } else { $messages[] = $upload['error']; $error++; }
        }
        $_SESSION['import_messages'] = $messages;
        header('Location: '.($_SERVER['PHP_SELF']).($isAdmin?'?firm_id='.$firmId:''));
        exit;
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM services $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pagination = paginate($total,$page,$perPage);
    $listStmt = $pdo->prepare("SELECT id,service_name,price,period,created_at FROM services $where ORDER BY $sort $dir LIMIT :o,:p");
    foreach ($params as $k=>$v) { $listStmt->bindValue(':'.$k,$v); }
    $listStmt->bindValue(':o',$pagination['offset'],PDO::PARAM_INT);
    $listStmt->bindValue(':p',$pagination['per_page'],PDO::PARAM_INT);
    $listStmt->execute();
    $rows = $listStmt->fetchAll();

    $queryBase = ['firm_id'=>$firmId,'search'=>$search,'sort'=>$sort,'dir'=>strtolower($dir),'per_page'=>$perPage];
    include __DIR__.'/partials/header.php';
    if (isset($_SESSION['import_messages'])) {
        echo '<div class="alert alert-info"><ul class="mb-0">';
        foreach ($_SESSION['import_messages'] as $m) echo '<li>'.htmlspecialchars($m).'</li>';
        echo '</ul></div>'; unset($_SESSION['import_messages']);
    }
?>
<div class="d-flex justify-content-between mb-3">
    <form class="d-flex" method="get">
        <?php if($isAdmin): ?>
            <select name="firm_id" class="form-select me-2" onchange="this.form.submit()">
                <?php foreach($firms as $f): ?>
                    <option value="<?=$f['id']?>" <?php if($f['id']==$firmId) echo 'selected'; ?>><?=htmlspecialchars($f['name'])?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <input type="search" name="search" value="<?=htmlspecialchars($search)?>" class="form-control me-2" placeholder="Ara">
        <select name="per_page" class="form-select me-2" onchange="this.form.submit()">
            <?php foreach([10,25,50] as $n): ?>
                <option value="<?=$n?>" <?php if($n==$perPage) echo 'selected'; ?>><?=$n?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary" type="submit">Filtrele</button>
    </form>
    <div>
        <a href="?<?=http_build_query(array_merge($queryBase,['export'=>1]))?>" class="btn btn-success me-2">Dışa aktar (CSV)</a>
        <button class="btn btn-primary" <?php if($sub['status']==='grace') echo 'disabled'; ?> onclick="location.href='service_add.php<?= $isAdmin?'?firm_id='.$firmId:'' ?>'">Hizmet Ekle</button>
    </div>
</div>
<form method="post" enctype="multipart/form-data" class="mb-3">
    <?=csrf_field()?>
    <div class="input-group">
        <input type="file" name="csv" accept="text/csv" class="form-control" <?php if($sub['status']==='grace') echo 'disabled'; ?>>
        <button class="btn btn-outline-secondary" name="import_csv" <?php if($sub['status']==='grace') echo 'disabled'; ?>>İçe aktar (CSV)</button>
    </div>
</form>
<div class="table-responsive">
<table class="table table-striped">
    <thead>
        <tr>
            <th><a href="?<?=http_build_query(array_merge($queryBase,['sort'=>'service_name','dir'=>$sort=='service_name'&&$dir=='ASC'?'desc':'asc']))?>">Ad</a></th>
            <th><a href="?<?=http_build_query(array_merge($queryBase,['sort'=>'price','dir'=>$sort=='price'&&$dir=='ASC'?'desc':'asc']))?>">Fiyat</a></th>
            <th><a href="?<?=http_build_query(array_merge($queryBase,['sort'=>'period','dir'=>$sort=='period'&&$dir=='ASC'?'desc':'asc']))?>">Dönem</a></th>
            <th><a href="?<?=http_build_query(array_merge($queryBase,['sort'=>'created_at','dir'=>$sort=='created_at'&&$dir=='ASC'?'desc':'asc']))?>">Kayıt</a></th>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
    <?php if(!$rows): ?>
        <tr><td colspan="5" class="text-center">Kayıt bulunamadı.</td></tr>
    <?php else: foreach($rows as $r): ?>
        <tr>
            <td><?=htmlspecialchars($r['service_name'])?></td>
            <td><?=htmlspecialchars(number_format((float)$r['price'],2,'.',''))?></td>
            <td><?=htmlspecialchars($r['period'])?></td>
            <td><?=htmlspecialchars($r['created_at'])?></td>
            <td>
                <a class="btn btn-sm btn-secondary" <?php if($sub['status']==='grace') echo 'disabled'; ?> href="service_edit.php?id=<?=$r['id']?>">Düzenle</a>
                <form method="post" action="service_delete.php" class="d-inline" onsubmit="return confirm('Silinsin mi?');">
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
    $code = 'EX'.substr(sha1($e->getMessage().$e->getFile().$e->getLine()),0,8);
    error_log('[SERVICES] '.$code.' '.$e->getMessage().' @'.$e->getFile().':'.$e->getLine());
    if(defined('APP_DEBUG') && APP_DEBUG){ echo '<pre>'.htmlspecialchars((string)$e).'</pre>'; }
    include __DIR__.'/errors/500.php';
    exit;
}
