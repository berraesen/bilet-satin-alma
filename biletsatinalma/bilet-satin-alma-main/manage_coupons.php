<?php
// manage_coupons.php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$db = new PDO("sqlite:bilet_sistemi.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = "";

/* ------------------ KUPON EKLE ------------------ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_coupon'])) {
    $code   = trim($_POST['code']);
    $rate   = (int) $_POST['rate'];
    $limit  = (int) $_POST['usage_limit'];
    $expiry = $_POST['expiry_date'];

    if ($code !== "" && $rate > 0 && $limit > 0 && $expiry !== "") {
        $stmt = $db->prepare("INSERT INTO Coupons (code, discount, usage_limit, expire_date, created_at) 
                              VALUES (?, ?, ?, ?, datetime('now'))");
        $stmt->execute([$code, $rate, $limit, $expiry]);
        $message = "<div class='alert success'>Kupon başarıyla eklendi ✅</div>";
    } else {
        $message = "<div class='alert error'>Tüm alanları doldurmalısınız!</div>";
    }
}

/* ------------------ KUPON SİL ------------------ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_coupon'])) {
    $id = (int) $_POST['delete_coupon'];
    $stmt = $db->prepare("DELETE FROM Coupons WHERE id = ?");
    $stmt->execute([$id]);
    $message = "<div class='alert success'>Kupon silindi ❌</div>";
}

/* ------------------ KUPON GÜNCELLE ------------------ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_coupon'])) {
    $id     = (int) $_POST['coupon_id'];
    $code   = trim($_POST['code']);
    $rate   = (int) $_POST['rate'];
    $limit  = (int) $_POST['usage_limit'];
    $expiry = $_POST['expiry_date'];

    if ($code !== "" && $rate > 0 && $limit > 0 && $expiry !== "") {
        $stmt = $db->prepare("UPDATE Coupons SET code=?, discount=?, usage_limit=?, expire_date=? WHERE id=?");
        $stmt->execute([$code, $rate, $limit, $expiry, $id]);
        $message = "<div class='alert success'>Kupon güncellendi ✨</div>";
    } else {
        $message = "<div class='alert error'>Tüm alanları doldurmalısınız!</div>";
    }
}

/* ------------------ VERİLERİ ÇEK ------------------ */
$coupons = $db->query("SELECT * FROM Coupons ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Kupon Yönetimi</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="header">
    <div class="container">
      <div class="brand"><div class="logo">🎟️</div><h1>Kupon Yönetimi</h1></div>
      <nav class="nav">
        <a href="admin_panel.php">Admin Paneli</a>
        <a href="logout.php">Çıkış Yap</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <?= $message ?>

    <!-- Kupon Ekle -->
    <div class="card">
      <h3>➕ Yeni Kupon Ekle</h3>
      <form method="post">
        <label>Kod:</label>
        <input type="text" name="code" required>

        <label>İndirim Oranı (%):</label>
        <input type="number" name="rate" min="1" max="100" required>

        <label>Kullanım Limiti:</label>
        <input type="number" name="usage_limit" min="1" required>

        <label>Son Kullanma Tarihi:</label>
        <input type="date" name="expiry_date" required>

        <button class="btn primary" type="submit" name="add_coupon">Ekle</button>
      </form>
    </div>

    <!-- Kupon Listesi -->
    <div class="card">
      <h3>📋 Mevcut Kuponlar</h3>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Kod</th>
            <th>Oran (%)</th>
            <th>Kullanım Limiti</th>
            <th>Son Tarih</th>
            <th>İşlemler</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($coupons as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['id']) ?></td>
              <td><?= htmlspecialchars($c['code']) ?></td>
              <td><?= htmlspecialchars($c['discount']) ?></td>
              <td><?= htmlspecialchars($c['usage_limit']) ?></td>
              <td><?= htmlspecialchars($c['expire_date']) ?></td>
              <td>
                <!-- Düzenleme -->
                <form method="post" style="display:inline-block;">
                  <input type="hidden" name="coupon_id" value="<?= $c['id'] ?>">
                  <input type="text" name="code" value="<?= htmlspecialchars($c['code']) ?>" required>
                  <input type="number" name="rate" value="<?= htmlspecialchars($c['discount']) ?>" min="1" max="100" required>
                  <input type="number" name="usage_limit" value="<?= htmlspecialchars($c['usage_limit']) ?>" min="1" required>
                  <input type="date" name="expiry_date" value="<?= htmlspecialchars($c['expire_date']) ?>" required>
                  <button class="btn secondary" type="submit" name="edit_coupon">Güncelle</button>
                </form>
                <!-- Silme -->
                <form method="post" style="display:inline-block;" onsubmit="return confirm('Kuponu silmek istediğine emin misin?')">
                  <input type="hidden" name="delete_coupon" value="<?= $c['id'] ?>">
                  <button class="btn danger" type="submit">Sil</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

  <footer class="footer">
    © <?= date('Y') ?> Otobüs Bilet Sistemi • Admin Paneli
  </footer>
</body>
</html>