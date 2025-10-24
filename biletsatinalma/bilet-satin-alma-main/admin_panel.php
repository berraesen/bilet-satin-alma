<?php
// admin_panel.php
// Admin işlemleri: Bus_Company CRUD ve company admin ekleme
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$db = new PDO("sqlite:bilet_sistemi.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = "";

/* ------------------ FİRMA CRUD ------------------ */

// Firma ekleme
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_company'])) {
    $company_name = trim($_POST['company_name']);
    if ($company_name !== "") {
        $stmt = $db->prepare("INSERT INTO Bus_Company (name, created_at) VALUES (?, datetime('now'))");
        $stmt->execute([$company_name]);
        $message = "<div class='alert success'>Firma başarıyla eklendi ✅</div>";
    } else {
        $message = "<div class='alert error'>Firma adı boş olamaz!</div>";
    }
}

// Firma silme (POST ile)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete'])) {
    $id = (int) $_POST['delete'];
    $stmt = $db->prepare("DELETE FROM Bus_Company WHERE id = ?");
    $stmt->execute([$id]);
    $message = "<div class='alert success'>Firma silindi ❌</div>";
}

// Firma düzenleme
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_company'])) {
    $id = (int) $_POST['company_id'];
    $new_name = trim($_POST['new_name']);
    if ($new_name !== "") {
        $stmt = $db->prepare("UPDATE Bus_Company SET name = ? WHERE id = ?");
        $stmt->execute([$new_name, $id]);
        $message = "<div class='alert success'>Firma güncellendi ✨</div>";
    } else {
        $message = "<div class='alert error'>Firma adı boş olamaz!</div>";
    }
}

/* ------------------ FİRMA ADMIN EKLEME ------------------ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_company_admin'])) {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $company_id = (int) $_POST['company_id'];

    if ($full_name !== "" && $email !== "" && $password !== "") {
        // Email kontrolü
        $stmt = $db->prepare("SELECT id FROM User WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            $message = "<div class='alert error'>Bu email zaten kayıtlı!</div>";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO User (full_name, email, password, role, company_id, balance, created_at)  
                                  VALUES (?, ?, ?, 'company', ?, 0, datetime('now'))");
            $stmt->execute([$full_name, $email, $hashed, $company_id]);
            $message = "<div class='alert success'>Firma Admin başarıyla eklendi ✅</div>";
        }
    } else {
        $message = "<div class='alert error'>Tüm alanları doldurmalısınız!</div>";
    }
}

/* ------------------ VERİLERİ ÇEK ------------------ */
$companies = $db->query("SELECT * FROM Bus_Company ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Admin Paneli</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; font-weight: bold; }
    .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .btn { padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; font-weight: bold; }
    .btn:hover { opacity: 0.9; }
    .btn.secondary { background: #457b9d; color: white; }
    .btn.danger { background: #e63946; color: white; }
    .btn.primary { background: #2a9d8f; color: white; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    th { background: #2a9d8f; color: white; }
  </style>
</head>
<body>
  <header class="header">
    <div class="container">
      <div class="brand"><div class="logo">⚙️</div><h1>Admin Paneli</h1></div>
      <nav class="nav">
        <a href="index.php">Ana Sayfa</a>
        <a href="logout.php">Çıkış Yap</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <?= $message ?>

    <!-- Firma Ekle -->
    <div class="card">
      <h3>🏢 Yeni Firma Ekle</h3>
      <form method="post">
        <label>Firma Adı:</label>
        <input type="text" name="company_name" required>
        <button class="btn primary" type="submit" name="add_company">Ekle</button>
      </form>
    </div>

    <!-- Firma Listesi -->
    <div class="card">
      <h3>📋 Mevcut Firmalar</h3>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Firma Adı</th>
            <th>Oluşturulma</th>
            <th>İşlemler</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($companies as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['id']) ?></td>
              <td><?= htmlspecialchars($c['name']) ?></td>
              <td><?= htmlspecialchars($c['created_at']) ?></td>
              <td>
                <!-- Düzenleme formu -->
                <form method="post" style="display:inline-block;">
                  <input type="hidden" name="company_id" value="<?= $c['id'] ?>">
                  <input type="text" name="new_name" placeholder="Yeni ad" required>
                  <button class="btn secondary" type="submit" name="edit_company">Güncelle</button>
                </form>
                <!-- Silme formu (POST) -->
                <form method="post" style="display:inline-block;" onsubmit="return confirm('Silmek istediğine emin misin?')">
                  <input type="hidden" name="delete" value="<?= $c['id'] ?>">
                  <button class="btn danger" type="submit">Sil</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Firma Admin Ekle -->
    <div class="card">
      <h3>👤 Firma Admin Kullanıcısı Ekle</h3>
      <form method="post">
        <label>Ad Soyad:</label>
        <input type="text" name="full_name" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Şifre:</label>
        <input type="password" name="password" required>

        <label>Firma Seç:</label>
        <select name="company_id" required>
          <?php foreach ($companies as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <button class="btn primary" type="submit" name="add_company_admin">Ekle</button>
      </form>
    </div>
  </main>

  <footer class="footer">
    © <?= date('Y') ?> Otobüs Bilet Sistemi • Admin Paneli
  </footer>
</body>
</html>