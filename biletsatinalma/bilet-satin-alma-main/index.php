<?php
session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Otobüs Bilet Sistemi</title>
  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" integrity="sha512-SzlrxWUlpfuzQ+pcUCosxcglQRNAq/DZjVsC0lE40xsADsfeQoEypE+enwcOiGjk/bSuGGKHEyjSoQ1zVisanQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
  <!-- Üst kısım -->
  <header class="header">
    <div class="container">
      <div class="brand">
       <i class="fas fa-bus fa-2x"></i>
        <h1>Otobüs Bilet Sistemi</h1>
      </div>
      <nav class="nav">
        <a href="index.php">Ana Sayfa</a>
        <?php if (!isset($_SESSION['user_id'])): ?>
          <a href="login.php">Giriş Yap</a>
          <a href="register.php">Kayıt Ol</a>
        <?php elseif ($_SESSION['role'] === 'admin'): ?>
          <a href="admin_panel.php">Admin Paneli</a>
          <a href="manage_companies.php">Firma Yönetimi</a>
          <a href="manage_users.php">Kullanıcı Yönetimi</a>
          <a href="logout.php">Çıkış Yap</a>
        <?php elseif ($_SESSION['role'] === 'company_admin'): ?>
          <a href="company_panel.php">Sefer Yönetimi</a>
          <a href="coupons.php">Kupon Yönetimi</a>
          <a href="logout.php">Çıkış Yap</a>
        <?php elseif ($_SESSION['role'] === 'user'): ?>
          <a href="mytickets.php">Biletlerim</a>
          <a href="logout.php">Çıkış Yap</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <!-- İçerik -->
  <main class="container">
    <section class="hero">
      <?php if (isset($_SESSION['full_name'])): ?>
        <h2>Hoş geldin, <?= htmlspecialchars($_SESSION['full_name']) ?> 👋</h2>
      <?php else: ?>
        <h2>Hoş Geldiniz</h2>
      <?php endif; ?>
      <p>Buradan şehirler arası otobüs biletinizi kolayca satın alabilirsiniz.</p>

      <div class="search-card">
        <form action="search.php" method="post">
          <div class="form-row">
            <div>
              <label>Kalkış Şehri</label>
              <input type="text" name="departure_city" required>
            </div>
            <div>
              <label>Varış Şehri</label>
              <input type="text" name="destination_city" required>
            </div>
            <div>
              <label>Tarih</label>
              <input type="date" name="date" required>
            </div>
            <div class="submit">
              <button type="submit">Sefer Ara</button>
            </div>
          </div>
        </form>
      </div>
    </section>
  </main>

  <footer class="footer">
    © <?= date('Y') ?> Otobüs Bilet Sistemi • Güvenli ve kolay yolculuk
  </footer>
</body>
</html>