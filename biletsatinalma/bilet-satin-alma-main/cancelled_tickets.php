<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$db = new PDO("sqlite:bilet_sistemi.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

$query = "SELECT tk.id AS ticket_id, tk.status, 
                 tr.departure_city, tr.destination_city, 
                 tr.departure_time, tr.arrival_time, tr.price,
                 b.name AS company_name,
                 u.full_name, u.email
          FROM Tickets tk
          JOIN Trips tr ON tk.trip_id = tr.id
          JOIN Bus_Company b ON tr.company_id = b.id
          JOIN User u ON tk.user_id = u.id
          WHERE tk.status = 'cancelled'";

$params = [];

if ($start_date && $end_date) {
    $query .= " AND date(tr.departure_time) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
}

$query .= " ORDER BY tr.departure_time DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$cancelled = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>İptal Edilen Biletler</title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f1fdfb; margin: 0; padding: 20px; }
    h1 { text-align: center; color: #2a4d4a; }
    form { text-align: center; margin-bottom: 20px; }
    input[type="date"] { padding: 5px; margin: 0 5px; }
    button { padding: 6px 12px; background: #2a9d8f; color: white; border: none; cursor: pointer; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    th { background: #2a9d8f; color: white; }
  </style>
</head>
<body>
  <h1>İptal Edilen Biletler</h1>

  <form method="get">
    <label>Başlangıç Tarihi: <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>"></label>
    <label>Bitiş Tarihi: <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>"></label>
    <button type="submit">Filtrele</button>
  </form>

  <table>
    <tr>
      <th>Bilet ID</th>
      <th>Yolcu</th>
      <th>Email</th>
      <th>Firma</th>
      <th>Kalkış</th>
      <th>Varış</th>
      <th>Kalkış Saati</th>
      <th>Fiyat</th>
    </tr>
    <?php foreach ($cancelled as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['ticket_id']) ?></td>
        <td><?= htmlspecialchars($c['full_name']) ?></td>
        <td><?= htmlspecialchars($c['email']) ?></td>
        <td><?= htmlspecialchars($c['company_name']) ?></td>
        <td><?= htmlspecialchars($c['departure_city']) ?></td>
        <td><?= htmlspecialchars($c['destination_city']) ?></td>
        <td><?= htmlspecialchars($c['departure_time']) ?></td>
        <td><?= htmlspecialchars($c['price']) ?> ₺</td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>
</html>