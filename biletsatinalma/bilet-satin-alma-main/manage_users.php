<?php
// manage_users.php
// Admin -> kullanıcı yönetimi: listeleme, bakiye ekleme, rol değiştirme, silme
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$db = new PDO("sqlite:bilet_sistemi.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = "";

/* ------------------ KULLANICIYA BAKIYE EKLE ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_balance'])) {
    $user_id = trim($_POST['user_id']);
    $amount = (float)str_replace(',', '.', $_POST['amount']);
    if ($user_id && $amount > 0) {
        $stmt = $db->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $user_id]);
        $message = "<div class='alert success'>Kullanıcının bakiyesi +".number_format($amount,2)." ₺ eklendi.</div>";
    } else {
        $message = "<div class='alert error'>Geçerli kullanıcı ve pozitif miktar girin.</div>";
    }
}

/* ------------------ KULLANICI ROL GÜNCELLE ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $user_id = trim($_POST['user_id_role']);
    $new_role = trim($_POST['new_role']);
    $company_id = trim($_POST['company_id']) ?: null;
    if ($user_id && $new_role) {
        $stmt = $db->prepare("UPDATE User SET role = ?, company_id = ? WHERE id = ?");
        $stmt->execute([$new_role, $company_id, $user_id]);
        $message = "<div class='alert success'>Kullanıcı rolü güncellendi.</div>";
    } else {
        $message = "<div class='alert error'>Geçerli veriler girin.</div>";
    }
}

/* ------------------ KULLANICI SİL ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int) $_POST['delete_user'];
    $stmt = $db->prepare("DELETE FROM User WHERE id = ?");
    $stmt->execute([$user_id]);
    $message = "<div class='alert success'>Kullanıcı silindi.</div>";
}

/* ------------------ VERİLERİ ÇEK ------------------ */
$users = $db->query("SELECT id, full_name, email, role, company_id, balance, created_at FROM User ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$companies = $db->query("SELECT id, name FROM Bus_Company ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Kullanıcı Yönetimi</title>
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
  <header><h1>Kullanıcı Yönetimi (Admin)</h1><nav><a href="admin_panel.php">Admin Paneli</a> | <a href="logout.php">Çıkış</a></nav></header>
  <main>
    <?= $message ?>

    <h2>Yeni/Seçili Kullanıcıya Bakiye Ekle</h2>
    <form method="post">
      <label>Kullanıcı:
        <select name="user_id" required>
          <?php foreach ($users as $u): ?>
            <option value="<?= h($u['id']) ?>"><?= h($u['full_name']) ?> (<?= h($u['email']) ?>) - <?= number_format($u['balance'],2) ?>₺</option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Miktar (₺): <input type="number" step="0.01" name="amount" required></label>
      <button class="btn" type="submit" name="add_balance">Ekle</button>
    </form>

    <h2>Kullanıcı Rolü / Firma Atama</h2>
    <form method="post">
      <label>Kullanıcı:
        <select name="user_id_role" required>
          <?php foreach ($users as $u): ?>
            <option value="<?= h($u['id']) ?>"><?= h($u['full_name']) ?> (<?= h($u['email']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Yeni Rol:
        <select name="new_role" required>
          <option value="user">user</option>
          <option value="company">company</option>
          <option value="admin">admin</option>
        </select>
      </label>
      <label>Firma (company rolü için opsiyonel):
        <select name="company_id">
          <option value="">- Firma yok -</option>
          <?php foreach ($companies as $c): ?>
            <option value="<?= h($c['id']) ?>"><?= h($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="btn" type="submit" name="change_role">Güncelle</button>
    </form>

    <h2>Tüm Kullanıcılar</h2>
    <table>
      <thead><tr><th>ID</th><th>Ad Soyad</th><th>Email</th><th>Rol</th><th>Firma ID</th><th>Bakiye</th><th>Oluşturma</th><th>İşlem</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= h($u['id']) ?></td>
            <td><?= h($u['full_name']) ?></td>
            <td><?= h($u['email']) ?></td>
            <td><?= h($u['role']) ?></td>
            <td><?= h($u['company_id']) ?></td>
            <td><?= number_format($u['balance'],2) ?> ₺</td>
            <td><?= h($u['created_at']) ?></td>
            <td>
              <form method="post" style="display:inline-block" onsubmit="return confirm('Kullanıcıyı silmek istediğine emin misin?')">
                <input type="hidden" name="delete_user" value="<?= h($u['id']) ?>">
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