<?php
session_start(); // start session for access control
$pdo = require __DIR__ . '/../app/config/config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /login.php');
    exit;
}
// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $del = $pdo->prepare('DELETE FROM services WHERE id=:id');
    $del->execute(['id'=>$id]);
    header('Location: services.php');
    exit;
}
// Handle add/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['service_name']);
    $desc = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $period = $_POST['period'];
    $id = $_POST['id'] ?? '';
    if ($name && $price && in_array($period, ['ay','yil'])) {
        if ($id) {
            $upd = $pdo->prepare('UPDATE services SET service_name=:n, description=:d, price=:p, period=:per WHERE id=:id');
            $upd->execute(['n'=>$name,'d'=>$desc,'p'=>$price,'per'=>$period,'id'=>$id]);
        } else {
            $ins = $pdo->prepare('INSERT INTO services (service_name, description, price, period) VALUES (:n,:d,:p,:per)');
            $ins->execute(['n'=>$name,'d'=>$desc,'p'=>$price,'per'=>$period]);
        }
        header('Location: services.php');
        exit;
    }
}
$editService = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM services WHERE id=:id');
    $stmt->execute(['id'=>$id]);
    $editService = $stmt->fetch();
}
$services = $pdo->query('SELECT * FROM services')->fetchAll();
include __DIR__ . '/../app/includes/header.php';
include __DIR__ . '/../app/includes/sidebar.php';
?>
<div class="container mt-4">
<h2>Hizmetler</h2>
<table class="table table-striped">
<thead><tr><th>ID</th><th>Ad</th><th>Fiyat</th><th>Dönem</th><th>İşlem</th></tr></thead>
<tbody>
<?php foreach($services as $s): ?>
<tr>
  <td><?= $s['id'] ?></td>
  <td><?= htmlspecialchars($s['service_name']) ?></td>
  <td><?= $s['price'] ?></td>
  <td><?= $s['period'] ?></td>
  <td>
    <a class="btn btn-sm btn-danger" href="services.php?edit=<?= $s['id'] ?>">Düzenle</a>
    <a class="btn btn-sm btn-outline-danger" href="services.php?delete=<?= $s['id'] ?>" onclick="return confirm('Silinsin mi?')">Sil</a>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<h3><?= $editService ? 'Hizmeti Güncelle' : 'Yeni Hizmet' ?></h3>
<form method="post">
  <input type="hidden" name="id" value="<?= $editService['id'] ?? '' ?>">
  <div class="mb-3"><label class="form-label">Ad</label><input type="text" name="service_name" class="form-control" value="<?= htmlspecialchars($editService['service_name'] ?? '') ?>" required></div>
  <div class="mb-3"><label class="form-label">Açıklama</label><textarea name="description" class="form-control"><?= htmlspecialchars($editService['description'] ?? '') ?></textarea></div>
  <div class="mb-3"><label class="form-label">Fiyat</label><input type="number" step="0.01" name="price" class="form-control" value="<?= $editService['price'] ?? '' ?>" required></div>
  <div class="mb-3"><label class="form-label">Dönem</label>
    <select name="period" class="form-select">
      <option value="ay" <?= (isset($editService['period']) && $editService['period']=='ay') ? 'selected' : '' ?>>Aylık</option>
      <option value="yil" <?= (isset($editService['period']) && $editService['period']=='yil') ? 'selected' : '' ?>>Yıllık</option>
    </select>
  </div>
  <button class="btn btn-danger" type="submit">Kaydet</button>
</form>
</div>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
