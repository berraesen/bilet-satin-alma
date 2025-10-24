<?php
session_start();
$db = new PDO("sqlite:bilet_sistemi.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    die("Bilet satın almak için giriş yapmalısınız.");
}

$user_id = $_SESSION['user_id'];
$trip_id = $_POST['trip_id'] ?? null;
$coupon_code = trim($_POST['coupon_code'] ?? "");

if (!$trip_id) {
    die("Geçersiz istek.");
}

// Sefer bilgisi
$stmt = $db->prepare("SELECT * FROM Trips WHERE id = :id");
$stmt->execute([':id' => $trip_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    die("Sefer bulunamadı.");
}

// Kapasite kontrolü
if ($trip['capacity'] <= 0) {
    die("Bu seferde boş koltuk kalmamış.");
}

$original_price = $trip['price'];
$final_price = $original_price;
$discount_text = "";
$coupon_id = null;

// Kupon kontrolü
if ($coupon_code !== "") {
    $stmt = $db->prepare("SELECT * FROM Coupons WHERE code = ?");
    $stmt->execute([$coupon_code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($coupon) {
        $today = date("Y-m-d");
        if ($coupon['expiration_date'] >= $today) {
            $discount = ($original_price * $coupon['discount']) / 100;
            $final_price -= $discount;
            $discount_text = "Kupon uygulandı: -" . round($discount, 2) . " ₺ (" . $coupon['discount'] . "%)";
            $coupon_id = $coupon['id'];

            // Kullanımı kaydet
            $stmt = $db->prepare("INSERT INTO User_Coupons (user_id, coupon_id, created_at) VALUES (?, ?, datetime('now'))");
            $stmt->execute([$user_id, $coupon_id]);
        } else {
            $discount_text = "Kupon süresi dolmuş ❌";
        }
    } else {
        $discount_text = "Kupon bulunamadı ❌";
    }
}

// Bileti kaydet
$stmt = $db->prepare("INSERT INTO Tickets (user_id, trip_id, status, total_price, created_at) VALUES (?, ?, 'active', ?, datetime('now'))");
$stmt->execute([$user_id, $trip_id, $final_price]);

// Kapasiteyi azalt
$stmt = $db->prepare("UPDATE Trips SET capacity = capacity - 1 WHERE id = :id");
$stmt->execute([':id' => $trip_id]);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Bilet Onayı</title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f1fdfb; text-align: center; padding: 50px; }
    .card { background: white; padding: 30px; border-radius: 10px; display: inline-block; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    h2 { color: #2a4d4a; }
    a { display: inline-block; margin-top: 20px; padding: 10px 15px; background: #2a9d8f; color: white; border-radius: 6px; text-decoration: none; }
    a:hover { background: #1d6f65; }
    .discount { margin-top: 10px; font-weight: bold; color: #1d6f65; }
  </style>
</head>
<body>
  <div class="card">
    <h2>Biletiniz başarıyla alındı 🎉</h2>
    <p><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></p>
    <p>Kalkış: <?= htmlspecialchars($trip['departure_time']) ?></p>
    <p>Varış: <?= htmlspecialchars($trip['arrival_time']) ?></p>
    <p>Fiyat: <?= round($final_price, 2) ?> ₺</p>
    <?php if ($discount_text): ?>
      <p class="discount"><?= htmlspecialchars($discount_text) ?></p>
    <?php endif; ?>
    <a href="mytickets.php">Biletlerimi Gör</a>
  </div>
</body>
</html>