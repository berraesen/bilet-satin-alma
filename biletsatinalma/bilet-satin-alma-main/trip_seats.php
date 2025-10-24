<?php
session_start();
$db = new PDO("sqlite:bilet_sistemi.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$trip_id = $_GET['trip_id'] ?? null;
if (!$trip_id || !is_numeric($trip_id)) {
    die("Geçersiz sefer ID.");
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'company') {
    die("Yetkisiz erişim.");
}

$trip_id = $_GET['trip_id'] ?? null;
$company_id = $_SESSION['company_id'];

if (!$trip_id || !is_numeric($trip_id)) {
    die("Geçersiz sefer ID.");
}

// Seferin firmaya ait olduğunu kontrol et
$stmt = $db->prepare("SELECT id, capacity FROM Trips WHERE id = ? AND company_id = ?");
$stmt->execute([$trip_id, $company_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    die("Bu sefer size ait değil.");
}

// Dolu koltuk sayısını çek
$stmt = $db->prepare("SELECT COUNT(*) FROM Tickets WHERE trip_id = ? AND status = 'active'");
$stmt->execute([$trip_id]);
$occupied = $stmt->fetchColumn();

$available = $trip['capacity'] - $occupied;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Koltuk Durumu</title>
  <style>
    body { font-family: Arial; background: #f1fdfb; padding: 20px; }
    h2 { color: #2a4d4a; }
    .seats { background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  </style>
</head>
<body>
  <h2>Sefer ID: <?= $trip_id ?></h2>
  <div class="seats">
    <p><strong>Toplam kapasite:</strong> <?= $trip['capacity'] ?></p>
    <p><strong>Dolu koltuk sayısı:</strong> <?= $occupied ?></p>
    <p><strong>Boş koltuk sayısı:</strong> <?= $available ?></p>
    <a href="company_panel.php">← Geri Dön</a>
  </div>
</body>
</html>