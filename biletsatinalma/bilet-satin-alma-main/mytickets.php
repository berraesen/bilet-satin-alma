<?php
// mytickets.php - kullanıcının biletleri ve iptal işlemi (GET iptal mantığını korudum, eski iş mantığına uygun)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$db = new PDO("sqlite:bilet_sistemi.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user_id = $_SESSION['user_id'];
$message = "";

/* ------------------ BİLET İPTAL (GET) - eski mantığına uygun  ------------------ */
if (isset($_GET['cancel'])) {
    $ticket_id = (int) $_GET['cancel'];

    $stmt = $db->prepare("SELECT tk.id AS ticket_id, tr.id AS trip_id, tr.price, tr.departure_time
                          FROM Tickets tk
                          JOIN Trips tr ON tk.trip_id = tr.id
                          WHERE tk.id = ? AND tk.user_id = ? AND tk.status = 'active'");
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ticket) {
        $departure_time = strtotime($ticket['departure_time']);
        $now = time();

        if ($departure_time - $now > 3600) { // 1 saatten fazla varsa
            try {
                $db->beginTransaction();

                // Bilet iptal
                $stmt = $db->prepare("UPDATE Tickets SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$ticket_id]);

                // Ücreti iade et (balance sütunu)
                $stmt = $db->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$ticket['price'], $user_id]);

                // Trips kapasiteyi geri artır
                $stmt = $db->prepare("UPDATE Trips SET capacity = capacity + 1 WHERE id = ?");
                $stmt->execute([$ticket['trip_id']]);

                $db->commit();
                $message = "<p class='success'>Bilet iptal edildi, ücret iade edildi ✅</p>";
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("TICKET_CANCEL_ERROR: " . $e->getMessage());
                $message = "<p class='error'>Bilet iptal edilirken hata oluştu.</p>";
            }
        } else {
            $message = "<p class='error'>Kalkışa 1 saatten az kaldığı için iptal edilemez ❌</p>";
        }
    }
}

/* ------------------ BİLETLERİ GETİR ------------------ */
$stmt = $db->prepare("SELECT tk.id AS ticket_id, tk.status, tr.*, b.name AS company_name 
                      FROM Tickets tk
                      JOIN Trips tr ON tk.trip_id = tr.id
                      JOIN Bus_Company b ON tr.company_id = b.id
                      WHERE tk.user_id = ?
                      ORDER BY tr.departure_time ASC");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Biletlerim</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <header class="header">
    <div class="container">
      <div class="brand"><div class="logo">🎫</div><h1>Biletlerim</h1></div>
      <nav class="nav">
        <a href="index.php">Ana Sayfa</a>
        <a href="logout.php">Çıkış Yap</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <?= $message ?>

    <?php if ($tickets): ?>
      <div class="card">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Firma</th>
              <th>Kalkış</th>
              <th>Varış</th>
              <th>Kalkış Saati</th>
              <th>Durum</th>
              <th>İşlemler</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($tickets as $t): ?>
            <tr>
              <td><?= htmlspecialchars($t['ticket_id']) ?></td>
              <td><?= htmlspecialchars($t['company_name']) ?></td>
              <td><?= htmlspecialchars($t['departure_city']) ?></td>
              <td><?= htmlspecialchars($t['destination_city']) ?></td>
              <td><?= htmlspecialchars($t['departure_time']) ?></td>
              <td>
                <?php if ($t['status'] === 'active' || $t['status'] === 'aktif'): ?>
                  <span class="tag">Aktif</span>
                <?php else: ?>
                  <span class="tag" style="background:#ffe5e5;color:#b02a37;">İptal</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($t['status'] === 'active' || $t['status'] === 'aktif'): ?>
                  <a class="btn danger" href="mytickets.php?cancel=<?= (int)$t['ticket_id'] ?>" onclick="return confirm('Bileti iptal etmek istediğine emin misin?')">İptal Et</a>
                  <a class="btn secondary" href="ticket_pdf.php?id=<?= (int)$t['ticket_id'] ?>">PDF İndir</a>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="card">
        <p style="margin:0;color:var(--muted);">Henüz hiç bilet satın almamışsınız.</p>
      </div>
    <?php endif; ?>
  </main>

  <footer class="footer">
    © <?= date('Y') ?> Otobüs Bilet Sistemi
  </footer>
</body>
</html>