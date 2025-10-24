<?php
// manage_companies.php
// Admin -> firma yönetimi: listeleme, ekleme, düzenleme, silme
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$db = new PDO("sqlite:bilet_sistemi.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = "";

/* ------------------ FİRMA EKLE ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_company'])) {
    $name = trim($_POST['company_name']);
    if ($name !== '') {
        $stmt = $db->prepare("INSERT INTO Bus_Company (name, created_at) VALUES (?, datetime('now'))");
        $stmt->execute([$name]);
        $message = "<div class='alert success'>Firma eklendi.</div>";
    } else {
        $message = "<div class='alert error'>Firma adı boş olamaz.</div>";
    }
}

/* ------------------ FİRMA DÜZENLE ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_company'])) {
    $id = (int) $_POST['company_id'];
    $new = trim($_POST['new_name']);
    if ($new !== '') {
        $stmt = $db->prepare("UPDATE Bus_Company SET name = ? WHERE id = ?");
        $stmt->execute([$new, $id]);
        $message = "<div class='alert success'>Firma güncellendi.</div>";
    } else {
        $message = "<div class='alert error'>Yeni isim boş olamaz.</div>";
    }
}

/* ------------------ FİRMA SİL ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_company'])) {
    $id = (int) $_POST['delete_company'];
    $stmt = $db->prepare("DELETE FROM Bus_Company WHERE id = ?");
    $stmt->execute([$id]);
    $message = "<div class='alert success'>Firma silindi.</div>";
}

/* ------------------ VERİLERİ ÇEK ------------------ */
$companies = $db->query("SELECT id, name, created_at FROM Bus_Company ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Firma Yönetimi</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .alert{padding:10px;border-radius:6px;margin-bottom:12px}
    .alert.success{background:#d4edda;color:#155724}
    .alert.error{background:#f8d7da;color:#721c24}
    table{width:100%;border-collapse:collapse}
    th,td{border:1px solid #ccc;padding:8px;text-align:center}
    .btn{padding:6px 10px;border-radius:6px;background:#2a9d8f;color:#fff;border:none;cursor:pointer}
  </style>
</head>
<body>
  <header><h1>Firma Yönetimi (Admin)</h1><nav><a href="admin_panel.php">Admin Paneli</a> | <a href="logout.php">Çıkış</a></nav></header>
  <main>
    <?= $message ?>

    <h2>Yeni Firma Ekle</h2>
    <form method="post">
      <input type="text" name="company_name" placeholder="Firma adı" required>
      <button class="btn" type="submit" name="add_company">Ekle</button>
    </form>

    <h2>Mevcut Firmalar</h2>
    <table>
      <thead><tr><th>ID</th><th>Firma Adı</th><th>Oluşturulma</th><th>İşlemler</th></tr></thead>
      <tbody>
        <?php foreach ($companies as $c): ?>
          <tr>
            <td><?= h($c['id']) ?></td>
            <td><?= h($c['name']) ?></td>
            <td><?= h($c['created_at']) ?></td>
            <td>
              <form method="post" style="display:inline-block;">
                <input type="hidden" name="company_id" value="<?= h($c['id']) ?>">
                <input type="text" name="new_name" placeholder="Yeni ad" required>
                <button class="btn" type="submit" name="edit_company">Güncelle</button>
              </form>
              <form method="post" style="display:inline-block;" onsubmit="return confirm('Silmek istediğine emin misin?')">
                <input type="hidden" name="delete_company" value="<?= h($c['id']) ?>">
                <button class="btn" type="submit">Sil</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  </main>
</body>
</html>