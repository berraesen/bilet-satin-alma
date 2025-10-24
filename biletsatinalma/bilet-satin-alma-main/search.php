<?php
// Veritabanına bağlan
$db = new PDO("sqlite:bilet_sistemi.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Formdan gelen değerleri al
$departure = $_POST['departure_city'] ?? '';
$destination = $_POST['destination_city'] ?? '';
$date = $_POST['date'] ?? '';

// Tarihi şimdilik sadece alıyoruz, istersen sorguya ekleyebilirsin
$stmt = $db->prepare("SELECT * FROM Trips 
                      WHERE departure_city = :dep COLLATE NOCASE 
                        AND destination_city = :dest COLLATE NOCASE");
$stmt->execute([':dep' => $departure, ':dest' => $destination]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Sefer Sonuçları</title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #a8dadc; padding: 20px; }
    h1 { text-align: center; color: #2a4d4a; }
    table { width: 80%; margin: 20px auto; border-collapse: collapse; background: #f1fdfb; }
    th, td { padding: 12px; border: 1px solid #ccc; text-align: center; }
    th { background: #2a9d8f; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
    .no-result { text-align: center; margin-top: 30px; font-size: 18px; color: #555; }
  </style>
</head>
<body>
  <h1>Sefer Sonuçları</h1>

  <?php if ($trips): ?>
    <table>
      <tr>
        <th>Kalkış</th>
        <th>Varış</th>
        <th>Kalkış Saati</th>
        <th>Varış Saati</th>
        <th>Fiyat</th>
        <th>Kapasite</th>
        <th>İşlem</th>
      </tr>
      <?php foreach ($trips as $trip): ?>
        <tr>
          <td><?= htmlspecialchars($trip['departure_city']) ?></td>
          <td><?= htmlspecialchars($trip['destination_city']) ?></td>
          <td><?= htmlspecialchars($trip['departure_time']) ?></td>
          <td><?= htmlspecialchars($trip['arrival_time']) ?></td>
          <td><?= htmlspecialchars($trip['price']) ?> ₺</td>
          <td><?= htmlspecialchars($trip['capacity']) ?></td>
          <td>
  <form action="buy_ticket.php" method="post">
    <input type="hidden" name="trip_id" value="<?= $trip['id'] ?>">
    <button type="submit">Bilet Al</button>
  </form>
</td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <div class="no-result">Sefer bulunamadı.</div>
  <?php endif; ?>
</body>
</html>