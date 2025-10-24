<?php
// company_panel.php - firma admin paneli (yeni şema role 'company' kullanılıyor)
// NOT: GET ile silme linkleri korundu, eklemeler transaction ve prepared statement ile yapılır.
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'company') {
    header("Location: index.php");
    exit;
}

$db = new PDO("sqlite:bilet_sistemi.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = "";
$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'] ?? null;

if (!$company_id) {
    die("Firma bilgisi yok.");
}

/* ------------------ SEFER EKLE ------------------ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_trip'])) {
    $departure_city = trim($_POST['departure_city']);
    $destination_city = trim($_POST['destination_city']);
    $departure_time = $_POST['departure_time']; // datetime-local string
    $arrival_time   = $_POST['arrival_time'];
    $price          = (float) $_POST['price'];
    $capacity       = (int) $_POST['capacity'];

    if ($departure_city && $destination_city && $departure_time && $arrival_time && $price > 0 && $capacity > 0) {
        // Kaydet (Trips tablosunda datetime formatı kabul ediliyor)
        $stmt = $db->prepare("INSERT INTO Trips (company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity, created_date) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))");
        $stmt->execute([$company_id, $departure_city, $destination_city, $departure_time, $arrival_time, $price, $capacity]);
        $message = "<p class='success'>Sefer başarıyla eklendi ✅</p>";
    } else {
        $message = "<p class='error'>Tüm alanları doldurun!</p>";
    }
}

/* ------------------ SEFER SİL (GET) - orijinal dosyadaki mantık korundu ------------------ */
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$id, $company_id]);
    $message = "<p class='success'>Sefer silindi ❌</p>";
}

/* ------------------ SEFER DÜZENLE (POST) ------------------ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_trip'])) {
    $id = (int) $_POST['trip_id'];
    $new_price = (float) $_POST['new_price'];
    if ($new_price > 0) {
        $stmt = $db->prepare("UPDATE Trips SET price = ? WHERE id = ? AND company_id = ?");
        $stmt->execute([$new_price, $id, $company_id]);
        $message = "<p class='success'>Sefer güncellendi ✨</p>";
    } else {
        $message = "<p class='error'>Geçerli bir fiyat girin!</p>";
    }
}

/* ------------------ KUUPON İŞLEMLERİ ------------------ */
// Kupon ekleme
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_coupon'])) {
    $code        = strtoupper(trim($_POST['code']));
    $discount    = (int) $_POST['discount'];
    $limit       = (int) $_POST['usage_limit'];
    $expiry_date = $_POST['expire_date'];

    if ($code && $discount > 0 && $limit > 0 && $expiry_date) {
        $stmt = $db->prepare("INSERT INTO Coupons (code, discount, usage_limit, expire_date, company_id, created_at) 
                              VALUES (?, ?, ?, ?, ?, datetime('now'))");
        $stmt->execute([$code, $discount, $limit, $expiry_date, $company_id]);
        $message = "<p class='success'>Kupon başarıyla eklendi ✅</p>";
    } else {
        $message = "<p class='error'>Tüm alanları doldurun!</p>";
    }
}

// Kupon silme (GET link korundu)
if (isset($_GET['delete_coupon'])) {
    $id = (int) $_GET['delete_coupon'];
    $stmt = $db->prepare("DELETE FROM Coupons WHERE id = ? AND company_id = ?");
    $stmt->execute([$id, $company_id]);
    $message = "<p class='success'>Kupon silindi ❌</p>";
}

// Kupon düzenleme
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_coupon'])) {
    $id     = (int) $_POST['coupon_id'];
    $rate   = (int) $_POST['new_rate'];
    $limit  = (int) $_POST['new_limit'];

    if ($rate > 0 && $limit > 0) {
        $stmt = $db->prepare("UPDATE Coupons SET discount = ?, usage_limit = ? WHERE id = ? AND company_id = ?");
        $stmt->execute([$rate, $limit, $id, $company_id]);
        $message = "<p class='success'>Kupon güncellendi ✨</p>";
    } else {
        $message = "<p class='error'>Geçerli değerler girin!</p>";
    }
}

/* ------------------ VERİLER ------------------ */
$stmt = $db->prepare("SELECT * FROM Trips WHERE company_id = ? ORDER BY departure_time ASC");
$stmt->execute([$company_id]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM Coupons WHERE company_id = ? ORDER BY created_at DESC");
$stmt->execute([$company_id]);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Firma adminine ait tüm biletleri listele */
$stmt = $db->prepare("
  SELECT T.id AS ticket_id, U.full_name, U.email, T.total_price, T.status, T.created_at,
         NULL AS seat_number, TR.departure_city, TR.destination_city
  FROM Tickets T
  JOIN User U ON U.id = T.user_id
  JOIN Trips TR ON TR.id = T.trip_id
  WHERE TR.company_id = ?
  ORDER BY T.created_at DESC
");
$stmt->execute([$company_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Firma Paneli</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <h1>Firma Paneli</h1>
  <div class="container">
    <?= $message ?>

    <!-- Yeni Sefer Ekle -->
    <h2>Yeni Sefer Ekle</h2>
    <form method="post">
      <label>Kalkış Şehri:</label>
      <input type="text" name="departure_city" required>

      <label>Varış Şehri:</label>
      <input type="text" name="destination_city" required>

      <label>Kalkış Zamanı:</label>
      <input type="datetime-local" name="departure_time" required>

      <label>Varış Zamanı:</label>
      <input type="datetime-local" name="arrival_time" required>

      <label>Fiyat (₺):</label>
      <input type="number" step="0.01" name="price" required>

      <label>Koltuk Sayısı:</label>
      <input type="number" name="capacity" required>

      <button type="submit" name="add_trip">Ekle</button>
    </form>

    <!-- Sefer Listesi -->
    <h2>Mevcut Seferler</h2>
    <table>
      <tr><th>ID</th><th>Kalkış</th><th>Varış</th><th>Kalkış Saati</th><th>Varış Saati</th><th>Fiyat</th><th>Koltuk</th><th>İşlemler</th></tr>
      <?php foreach ($trips as $t): ?>
      <tr>
        <td><?= htmlspecialchars($t['id']) ?></td>
        <td><?= htmlspecialchars($t['departure_city']) ?></td>
        <td><?= htmlspecialchars($t['destination_city']) ?></td>
        <td><?= htmlspecialchars($t['departure_time']) ?></td>
        <td><?= htmlspecialchars($t['arrival_time']) ?></td>
        <td><?= htmlspecialchars($t['price']) ?> ₺</td>
        <td><?= htmlspecialchars($t['capacity']) ?></td>
        <td>
          <form method="post" style="display:inline-block;">
            <input type="hidden" name="trip_id" value="<?= htmlspecialchars($t['id']) ?>">
            <input type="number" step="0.01" name="new_price" placeholder="Yeni fiyat" required>
            <button type="submit" name="edit_trip">Güncelle</button>
          </form>

          <a class="action" href="company_panel.php?delete=<?= (int)$t['id'] ?>" onclick="return confirm('Silmek istediğine emin misin?')">Sil</a>

          <a class="action" href="trip_seats.php?trip_id=<?= (int)$t['id'] ?>">Koltukları Gör</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>

    <!-- Kupon yönetimi -->
    <h2>Kupon Yönetimi</h2>
    <form method="post">
      <label>Kupon Kodu:</label>
      <input type="text" name="code" required>

      <label>İndirim Oranı (%):</label>
      <input type="number" name="discount" min="1" max="100" required>

      <label>Kullanım Limiti:</label>
      <input type="number" name="usage_limit" min="1" required>

      <label>Son Kullanma Tarihi:</label>
      <input type="date" name="expire_date" required>

      <button type="submit" name="add_coupon">Ekle</button>
    </form>

    <h3>Mevcut Kuponlar</h3>
    <table>
      <tr><th>ID</th><th>Kod</th><th>İndirim</th><th>Limit</th><th>Son Tarih</th><th>İşlemler</th></tr>
      <?php foreach ($coupons as $cp): ?>
        <tr>
          <td><?= htmlspecialchars($cp['id']) ?></td>
          <td><?= htmlspecialchars($cp['code']) ?></td>
          <td><?= htmlspecialchars($cp['discount']) ?>%</td>
          <td><?= htmlspecialchars($cp['usage_limit']) ?></td>
          <td><?= htmlspecialchars($cp['expire_date']) ?></td>
          <td>
            <form method="post" style="display:inline-block;">
              <input type="hidden" name="coupon_id" value="<?= htmlspecialchars($cp['id']) ?>">
              <input type="number" name="new_rate" placeholder="Yeni %" min="1" max="100" required>
              <input type="number" name="new_limit" placeholder="Yeni limit" min="1" required>
              <button type="submit" name="edit_coupon">Güncelle</button>
            </form>

            <a class="action" href="company_panel.php?delete_coupon=<?= (int)$cp['id'] ?>" onclick="return confirm('Silmek istediğine emin misin?')">Sil</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>

    <h2>Biletler</h2>
    <table>
      <tr><th>ID</th><th>Yolcu</th><th>E-posta</th><th>Sefer</th><th>Koltuk</th><th>Fiyat</th><th>Durum</th><th>Tarih</th></tr>
      <?php foreach ($tickets as $t): ?>
        <tr>
          <td><?= htmlspecialchars($t['ticket_id']) ?></td>
          <td><?= htmlspecialchars($t['full_name']) ?></td>
          <td><?= htmlspecialchars($t['email']) ?></td>
          <td><?= htmlspecialchars($t['departure_city']) ?> → <?= htmlspecialchars($t['destination_city']) ?></td>
          <td><?= htmlspecialchars($t['seat_number'] ?? 'Belirtilmemiş') ?></td>
          <td><?= htmlspecialchars($t['total_price']) ?> ₺</td>
          <td><?= htmlspecialchars($t['status']) ?></td>
          <td><?= htmlspecialchars($t['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>

  </div>
</body>
</html>