<?php
// buy_ticket.php
// Sadece sefer detayı ve kupon ile satın alma akışı.
// - Eğer trip_id verilmemişse kullanıcıyı search.php'ye yönlendirir.
// - Kredi yükleme UI'sı KALDIRILDI (arka planda bakiye kontrolü yapılır).
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = new PDO("sqlite:bilet_sistemi.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = "";
$user_id = $_SESSION['user_id'];

// Kullanıcı bakiyesi (arka planda kontrol için)
$stmt = $db->prepare("SELECT balance FROM User WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$balance = $user ? (float)$user['balance'] : 0.0;

// trip_id mutlaka olmalı — yoksa arama sayfasına yönlendir
if (!isset($_REQUEST['trip_id'])) {
    header("Location: search.php");
    exit;
}

$trip_id = (int) $_REQUEST['trip_id'];

// Seferi al
$stmt = $db->prepare("SELECT t.*, b.name AS company_name FROM Trips t JOIN Bus_Company b ON t.company_id = b.id WHERE t.id = ?");
try {
    $stmt->execute([$trip_id]);
} catch (Exception $e) {
    // Eğer sorguda hata olursa kullanıcı dostu mesaj göster
    error_log("BUY_TICKET_FETCH_ERROR: " . $e->getMessage());
    die("Sefer sorgulanırken hata oluştu.");
}
$trip = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$trip) {
    die("Sefer bulunamadı.");
}

// Satın alma işlemi (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['buy'])) {
    $coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ""));
    $discount = 0;

    // Tekrar seferi çek (güncel veri)
    $stmt = $db->prepare("SELECT t.*, b.name AS company_name FROM Trips t JOIN Bus_Company b ON t.company_id = b.id WHERE t.id = ?");
    $stmt->execute([$trip_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        $message = "<p class='error'>Sefer bulunamadı.</p>";
    } else {
        $price = (float) $trip['price'];

        // Kapasite kontrolü
        if ((int)$trip['capacity'] <= 0) {
            $message = "<p class='error'>Bu seferde yer kalmamış.</p>";
        } else {
            // Kupon kontrol
            $coupon = null;
            if ($coupon_code !== "") {
                $kstmt = $db->prepare("SELECT * FROM Coupons WHERE code = ? AND expire_date >= date('now') AND usage_limit > 0 AND (company_id = ? OR company_id IS NULL)");
                $kstmt->execute([$coupon_code, $trip['company_id']]);
                $coupon = $kstmt->fetch(PDO::FETCH_ASSOC);
                if (!$coupon) {
                    $message = "<p class='error'>Geçersiz veya süresi dolmuş kupon!</p>";
                } else {
                    $discount = (int)$coupon['discount'];
                    $price = round($price - ($price * $discount / 100), 2);
                }
            }
        }

        // Bakiye kontrol ve transaction
        if (!$message) {
            try {
                $db->beginTransaction();

                // Güncel bakiye
                $ustmt = $db->prepare("SELECT balance FROM User WHERE id = ?");
                $ustmt->execute([$user_id]);
                $creditVal = $ustmt->fetchColumn();
                $credit = $creditVal === false ? 0.0 : (float)$creditVal;

                if ($credit < $price) {
                    $db->rollBack();
                    $message = "<p class='error'>Bakiyeniz yetersiz!</p>";
                } else {
                    // Bilet ekle
                    $insert = $db->prepare("INSERT INTO Tickets (user_id, trip_id, total_price, status, created_at) VALUES (?, ?, ?, 'active', datetime('now'))");
                    $insert->execute([$user_id, $trip_id, $price]);
                    $ticket_id = $db->lastInsertId();

                    // Kullanıcı bakiyesini düş
                    $db->prepare("UPDATE User SET balance = balance - ? WHERE id = ?")->execute([$price, $user_id]);

                    // Trips kapasite azalt (safeguard: capacity>0 ise)
                    $db->prepare("UPDATE Trips SET capacity = capacity - 1 WHERE id = ? AND capacity > 0")->execute([$trip_id]);

                    // Kupon kullanıldıysa limit azalt ve ilişki kaydet
                    if (!empty($coupon)) {
                        $db->prepare("UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE id = ?")->execute([$coupon['id']]);
                        $db->prepare("INSERT INTO User_Coupons (coupon_id, user_id, created_at) VALUES (?, ?, datetime('now'))")->execute([$coupon['id'], $user_id]);
                    }

                    $db->commit();
                    $message = "<p class='success'>Bilet satın alındı 🎉 <a href='mytickets.php'>Biletlerim</a></p>";
                    // güncel bakiye - veritabanında azaldığı için aynı calculasyonla azaltıyoruz
                    $balance = $credit - $price;
                }
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("BUY_TICKET_ERROR: " . $e->getMessage());
                $message = "<p class='error'>Bilet satın alırken hata oluştu.</p>";
            }
        }
    }
}

// helper
function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Bilet Satın Al - Sefer Detayı</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .container{max-width:700px;margin:20px auto;padding:15px;background:#f9f9f9;border-radius:8px}
    .btn{display:inline-block;padding:8px 12px;background:#2a9d8f;color:#fff;border-radius:6px;text-decoration:none;border:none;cursor:pointer}
    .success{color:green;font-weight:bold}
    .error{color:red;font-weight:bold}
    .balance { font-size:1.1rem; margin-bottom:12px; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Sefer Detayı</h2>
    <?= $message ?>

    <!-- Mevcut bakiye gösterimi -->
    <div class="balance"><strong>Mevcut Bakiyeniz:</strong> <?= number_format($balance, 2, ',', '.') ?> ₺</div>

    <div>
      <p><strong>Firma:</strong> <?= h($trip['company_name']) ?></p>
      <p><strong>Kalkış:</strong> <?= h($trip['departure_city']) ?> - <?= date("Y-m-d H:i", strtotime($trip['departure_time'])) ?></p>
      <p><strong>Varış:</strong> <?= h($trip['destination_city']) ?> - <?= date("Y-m-d H:i", strtotime($trip['arrival_time'])) ?></p>
      <p><strong>Fiyat:</strong> <?= number_format($trip['price'], 2, ',', '.') ?> ₺</p>
      <p><strong>Boş Koltuk / Kapasite:</strong> <?= (int)$trip['capacity'] ?></p>
    </div>

    <form method="post" style="margin-top:12px;">
      <input type="hidden" name="trip_id" value="<?= h($trip['id']) ?>">
      <label>Kupon Kodu (opsiyonel):</label>
      <input type="text" name="coupon_code" placeholder="Kupon kodu girin">
      <div style="margin-top:10px;">
        <button type="submit" name="buy" class="btn">Alışverişi Tamamla</button>
        <a href="search.php" style="margin-left:10px;">← Geri</a>
      </div>
    </form>
  </div>
</body>
</html>